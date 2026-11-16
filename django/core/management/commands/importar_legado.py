"""Importa os dados do GEOP em PHP (MySQL/MariaDB) para o banco do Django.

Uso (após `migrate`):

    LEGACY_DATABASE_URL=mysql://usuario:senha@host:3306/banco \\
    python manage.py importar_legado --uploads /caminho/do/php/public

* O banco legado é aberto SOMENTE para leitura.
* Os IDs são preservados (links /ordem_detalhe?id=123 continuam válidos).
* Senhas: o hash bcrypt do PHP é mantido; no primeiro login o Django o
  regrava no formato próprio. Ninguém precisa redefinir a senha.
* Fotos: cada imagem de `public/uploads` é reduzida e regravada em WEBP
  (ou JPEG) — nenhuma foto é descartada. PDFs são copiados como estão.
* Pode ser executado mais de uma vez: registros já importados são ignorados.
"""
from __future__ import annotations

import os
import time
from datetime import date, datetime
from decimal import Decimal
from pathlib import Path

from django.core.files.base import ContentFile
from django.core.management.base import BaseCommand, CommandError
from django.core.management.color import no_style
from django.db import connection, connections, transaction
from django.utils import timezone

from accounts.catalogo import sincronizar
from accounts.models import Perfil, Permissao, Setor, Usuario
from ambientes.models import (Ambiente, AmbienteAcao, AmbienteAcaoVinculo, Registro, RegistroFoto, RegistroItem,
                              RegistroResponsavel, Validacao)
from core.imaging import TIPOS_IMAGEM, comprimir_imagem, detectar_mime, formatar_bytes
from core.models import AuditoriaEvento, Categoria, Local, Notificacao
from limpeza.models import OrganogramaNo, PlanoGrupo, PlanoTarefa
from ordens.models import (Anexo, ChecklistItem, Historico, Material, NumeroSequencia, Ordem, OrdemAgente,
                           OrdemPatrimonio, ParticipanteLimpeza, SolicitacaoExclusao)

from ._legado_modulos import EtapasModulos

STATUS_VALIDOS = {s for s, _ in Ordem._meta.get_field("status").choices}


def aware(valor):
    if valor in (None, "", "0000-00-00 00:00:00"):
        return None
    if isinstance(valor, str):
        try:
            valor = datetime.fromisoformat(valor)
        except ValueError:
            return None
    if isinstance(valor, date) and not isinstance(valor, datetime):
        valor = datetime(valor.year, valor.month, valor.day)
    return timezone.make_aware(valor) if timezone.is_naive(valor) else valor


def txt(valor, limite=None):
    s = "" if valor is None else str(valor).strip()
    return s[:limite] if limite else s


def boolv(valor):
    return bool(int(valor)) if str(valor).strip().lstrip("-").isdigit() else bool(valor)


class Command(EtapasModulos, BaseCommand):
    help = "Importa todo o GEOP em PHP: usuários, ordens, fotos, limpeza, ambientes, estoque, CFTV, chat, IoT…"

    def add_arguments(self, parser):
        parser.add_argument("--uploads", help="Pasta `public` do PHP (que contém `uploads/`).")
        parser.add_argument("--uploads-url", help="Ou o endereço do PHP no ar (ex.: https://geop.shoppingcarpina.com.br/): "
                                                  "as fotos são baixadas de <url>/uploads/…")
        parser.add_argument("--sem-arquivos", action="store_true", help="Importa só os dados, sem copiar fotos.")
        parser.add_argument("--usuario-padrao", type=int,
                            help="ID de usuário usado quando a referência original não existir (padrão: primeiro TI).")

    # ------------------------------------------------------------------ infraestrutura
    def handle(self, *args, **opts):
        if "legacy" not in connections.databases:
            raise CommandError("Defina LEGACY_DATABASE_URL (mysql://usuario:senha@host:porta/banco).")
        self.legado = connections["legacy"]
        self.base_uploads = Path(opts["uploads"]).resolve() if opts.get("uploads") else None
        self.uploads_url = (opts.get("uploads_url") or "").rstrip("/")
        if self.uploads_url and not self.uploads_url.startswith(("https://", "http://")):
            raise CommandError("--uploads-url deve começar com https://")
        self.copiar_arquivos = not opts["sem_arquivos"]
        if self.copiar_arquivos and not (self.base_uploads or self.uploads_url):
            raise CommandError("Informe --uploads /caminho/public ou --uploads-url https://… (ou use --sem-arquivos).")
        self.bytes_antes = self.bytes_depois = self.arquivos_ok = self.arquivos_faltando = 0
        sincronizar(Permissao, Perfil)

        etapas = [
            ("Setores", self.setores), ("Perfis", self.perfis), ("Usuários", self.usuarios),
            ("Locais e categorias", self.cadastros), ("Organograma e plano da limpeza", self.limpeza),
            ("Preventivas", self.preventivas), ("Ordens", self.ordens), ("Custos, qualidade e vínculos das ordens", self.ordens_extras),
            ("Equipe, histórico, materiais e checklist", self.detalhes_ordens),
            ("Anexos e fotos das ordens", self.anexos), ("Patrimônios vinculados", self.patrimonios),
            ("Exclusões", self.exclusoes), ("Ambientes QR", self.ambientes),
            ("Estoque, compras e custos", self.estoque), ("Checklists por categoria", self.checklists),
            ("Equipe, escalas e cronograma", self.operacao), ("Base de conhecimento", self.conhecimento),
            ("CFTV e controle de acesso", self.cftv), ("Chat", self.chat), ("IoT e rede", self.iot),
            ("Notificações e auditoria", self.logs),
        ]
        for nome, func in etapas:
            self.stdout.write(f"→ {nome}…")
            with transaction.atomic():
                total = func()
            self.stdout.write(self.style.SUCCESS(f"  {total} registro(s)"))
        self.ajustar_sequencias()
        if self.copiar_arquivos:
            self.stdout.write(self.style.SUCCESS(
                f"Arquivos: {self.arquivos_ok} importados, {self.arquivos_faltando} não encontrados. "
                f"Tamanho {formatar_bytes(self.bytes_antes)} → {formatar_bytes(self.bytes_depois)}."))

    def linhas(self, sql, params=None):
        try:
            with self.legado.cursor() as cur:
                cur.execute(sql, params or [])
                nomes = [c[0] for c in cur.description]
                return [dict(zip(nomes, r)) for r in cur.fetchall()]
        except Exception as exc:  # tabela/coluna opcional inexistente na instalação
            msg = str(exc)
            if any(t in msg for t in ("doesn't exist", "Unknown column", "1146", "no such table", "no such column")):
                return []
            raise

    def colunas(self, tabela):
        try:
            with self.legado.cursor() as cur:
                cur.execute(f"SHOW COLUMNS FROM `{tabela}`")
                return {r[0] for r in cur.fetchall()}
        except Exception:
            return set()

    def criar(self, modelo, objetos):
        objetos = [o for o in objetos if o is not None]
        existentes = set(modelo.objects.filter(pk__in=[o.pk for o in objetos if o.pk]).values_list("pk", flat=True))
        novos = [o for o in objetos if o.pk is None or o.pk not in existentes]
        modelo.objects.bulk_create(novos, batch_size=500)
        return len(novos)

    def uid(self, valor):
        try:
            v = int(valor or 0)
        except (TypeError, ValueError):
            return self.padrao
        return v if v in self.usuarios_ids else self.padrao

    def uid_ou_none(self, valor):
        try:
            v = int(valor or 0)
        except (TypeError, ValueError):
            return None
        return v if v in self.usuarios_ids else None

    # ------------------------------------------------------------------ etapas
    def setores(self):
        return self.criar(Setor, [Setor(pk=r["id"], nome=txt(r["nome"], 100), descricao=txt(r.get("descricao")),
                                        ativo=boolv(r.get("ativo", 1)), criado_em=aware(r.get("criado_em")) or timezone.now())
                                  for r in self.linhas("SELECT * FROM setores")])

    def perfis(self):
        n = 0
        for r in self.linhas("SELECT * FROM perfis"):
            codigo = txt(r["codigo"], 80).upper()
            _, criado = Perfil.objects.get_or_create(codigo=codigo, defaults={
                "nome": txt(r["nome"], 120), "descricao": txt(r.get("descricao"), 255),
                "ativo": boolv(r.get("ativo", 1)), "administrativo": boolv(r.get("administrativo", 0))})
            n += criado
        return n

    def usuarios(self):
        perfis = {p.codigo: p for p in Perfil.objects.all()}
        setores = set(Setor.objects.values_list("pk", flat=True))
        objs = []
        for r in self.linhas("SELECT * FROM usuarios"):
            codigo = txt(r.get("perfil")).upper()
            perfil = perfis.get(codigo)
            if perfil is None and codigo:
                perfil = Perfil.objects.create(codigo=codigo, nome=codigo.replace("_", " ").title())
                perfis[codigo] = perfil
            hash_php = txt(r.get("senha_hash"))
            objs.append(Usuario(
                pk=r["id"], nome=txt(r["nome"], 150), email=txt(r["email"], 150).lower(),
                telefone=txt(r.get("telefone"), 30), cargo=txt(r.get("cargo"), 100), perfil=perfil,
                setor_id=r.get("setor_id") if r.get("setor_id") in setores else None,
                is_active=txt(r.get("status") or "ATIVO").upper() == "ATIVO",
                is_superuser=codigo == "TI", is_staff=codigo == "TI",
                password=f"php_bcrypt${hash_php}" if hash_php.startswith("$2") else "!",
                forcar_troca_senha=boolv(r.get("forcar_troca_senha", 0)),
                senha_alterada_em=aware(r.get("senha_alterada_em")),
                criado_em=aware(r.get("criado_em")) or timezone.now(),
            ))
        n = self.criar(Usuario, objs)
        self.usuarios_ids = set(Usuario.objects.values_list("pk", flat=True))
        padrao = self.options_padrao()
        self.padrao = padrao
        return n

    def options_padrao(self):
        ti = Usuario.objects.filter(perfil__codigo="TI").order_by("pk").first() or Usuario.objects.order_by("pk").first()
        if not ti:
            raise CommandError("Nenhum usuário importado: confira o banco legado.")
        return ti.pk

    def cadastros(self):
        n = self.criar(Local, [Local(pk=r["id"], nome=txt(r["nome"], 150), descricao=txt(r.get("descricao")),
                                     ativo=boolv(r.get("ativo", 1)), criado_em=aware(r.get("criado_em")) or timezone.now())
                               for r in self.linhas("SELECT * FROM locais")])
        n += self.criar(Categoria, [Categoria(
            pk=r["id"], nome=txt(r["nome"], 100), descricao=txt(r.get("descricao")), ativo=boolv(r.get("ativo", 1)),
            exige_foto_antes=boolv(r.get("exige_foto_antes", 0)), exige_foto_durante=boolv(r.get("exige_foto_durante", 0)),
            exige_foto_depois=boolv(r.get("exige_foto_depois", 0)), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM categorias")])
        return n

    def limpeza(self):
        nos = self.linhas("SELECT * FROM limpeza_organograma_nos ORDER BY id")
        n = self.criar(OrganogramaNo, [OrganogramaNo(
            pk=r["id"], usuario_id=self.uid_ou_none(r.get("usuario_id")), nome_colaborador=txt(r["nome_colaborador"], 160),
            cargo=txt(r["cargo"], 120), setor=txt(r.get("setor"), 120), turno=txt(r.get("turno"), 80),
            contato=txt(r.get("contato"), 120), observacao=txt(r.get("observacao")), posicao=r.get("ordem") or 0,
            ativo=boolv(r.get("ativo", 1)), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in nos])
        ids = {r["id"] for r in nos}
        for r in nos:  # superiores depois de todos existirem
            if r.get("parent_id") in ids:
                OrganogramaNo.objects.filter(pk=r["id"]).update(parent_id=r["parent_id"])
        n += self.criar(PlanoGrupo, [PlanoGrupo(pk=r["id"], chave=txt(r["chave"], 80) or f"g{r['id']}",
                                                nome=txt(r["nome"], 220), descricao=txt(r.get("descricao")),
                                                posicao=r.get("ordem") or 0, ativo=boolv(r.get("ativo", 1)))
                                     for r in self.linhas("SELECT * FROM limpeza_plano_grupos")])
        grupos = set(PlanoGrupo.objects.values_list("pk", flat=True))
        n += self.criar(PlanoTarefa, [PlanoTarefa(
            pk=r["id"], grupo_id=r["grupo_id"], chave=txt(r["chave"], 80) or f"t{r['id']}", descricao=txt(r["descricao"], 500),
            observacao=txt(r.get("observacao")), periodicidade=txt(r.get("periodicidade"), 40) or "Diária",
            exige_foto=boolv(r.get("exige_foto", 0)), exige_observacao=boolv(r.get("exige_observacao", 0)),
            posicao=r.get("ordem") or 0, ativo=boolv(r.get("ativo", 1)),
        ) for r in self.linhas("SELECT * FROM limpeza_plano_tarefas") if r["grupo_id"] in grupos])
        return n

    def ordens(self):
        locais = set(Local.objects.values_list("pk", flat=True))
        categorias = set(Categoria.objects.values_list("pk", flat=True))
        objs = []
        for r in self.linhas("SELECT * FROM ordens_servico ORDER BY id"):
            if r["local_id"] not in locais or r["categoria_id"] not in categorias:
                self.stderr.write(f"  ordem {r['id']} ignorada: local/categoria inexistente")
                continue
            numero = txt(r["numero_os"], 30)
            tipo = txt(r.get("tipo_ordem")).upper() or ("OL" if numero.startswith("OL-") else "OS")
            status = txt(r.get("status")).upper() or "ABERTA"
            prioridade = txt(r.get("prioridade")).upper()
            objs.append(Ordem(
                pk=r["id"], numero=numero, tipo="OL" if tipo == "OL" else "OS", titulo=txt(r["titulo"], 150),
                descricao=txt(r["descricao"]), local_id=r["local_id"], categoria_id=r["categoria_id"],
                prioridade="CRITICA" if prioridade == "URGENTE" else (prioridade if prioridade in {"BAIXA", "MEDIA", "ALTA", "CRITICA"} else "MEDIA"),
                status=status if status in STATUS_VALIDOS else "ABERTA",
                solicitante_id=self.uid(r.get("solicitante_id")), gestor_id=self.uid(r.get("gestor_id") or r.get("solicitante_id")),
                agente_id=self.uid_ou_none(r.get("agente_id")), prazo=aware(r.get("sla_vencimento") or r.get("prazo")),
                sla_horas=r.get("sla_horas"), valor_previsto_orcamento=r.get("valor_previsto_orcamento"),
                semestre_previsto=r.get("semestre_previsto"), ano_previsto=r.get("ano_previsto"),
                data_inicio=aware(r.get("data_inicio")), data_conclusao=aware(r.get("data_conclusao")),
                data_encerramento=aware(r.get("data_encerramento")), aprovado_por_id=self.uid_ou_none(r.get("aprovado_por")),
                observacao_aprovacao=txt(r.get("observacao_aprovacao")), origem=txt(r.get("origem"), 30) or "MANUAL",
                custo_materiais=r.get("custo_materiais") or Decimal("0"),
                reabertura_contagem=r.get("reabertura_contagem") or 0,
                solicitacao_encerramento=boolv(r.get("solicitacao_encerramento") or 0),
                solicitacao_encerramento_em=aware(r.get("solicitacao_encerramento_data")),
                solicitacao_encerramento_obs=txt(r.get("solicitacao_encerramento_obs")),
                criado_em=aware(r.get("criado_em")) or timezone.now(),
                excluida_em=aware(r.get("excluida_em")), excluida_por_id=self.uid_ou_none(r.get("excluida_por")),
            ))
        n = self.criar(Ordem, objs)
        # atualizado_em é auto_now: restaura o valor original
        for r in self.linhas("SELECT id, atualizado_em FROM ordens_servico"):
            if r.get("atualizado_em"):
                Ordem.objects.filter(pk=r["id"]).update(atualizado_em=aware(r["atualizado_em"]))
        self.ordens_ids = set(Ordem.objects.values_list("pk", flat=True))
        return n

    def detalhes_ordens(self):
        ok = self.ordens_ids
        n = self.criar(OrdemAgente, [OrdemAgente(
            pk=r["id"], ordem_id=r["ordem_servico_id"], usuario_id=r["usuario_id"],
            papel="PRINCIPAL" if txt(r.get("papel")).upper() == "PRINCIPAL" else "AUXILIAR",
            criado_por_id=self.uid_ou_none(r.get("criado_por")), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM ordem_agentes")
            if r["ordem_servico_id"] in ok and r["usuario_id"] in self.usuarios_ids])
        # ordens com agente principal mas sem linha em ordem_agentes
        com_principal = set(OrdemAgente.objects.filter(papel="PRINCIPAL").values_list("ordem_id", flat=True))
        n += self.criar(OrdemAgente, [OrdemAgente(ordem_id=o, usuario_id=a, papel="PRINCIPAL") for o, a in
                                      Ordem.objects.filter(agente__isnull=False).exclude(pk__in=com_principal)
                                      .values_list("pk", "agente_id")
                                      if not OrdemAgente.objects.filter(ordem_id=o, usuario_id=a).exists()])
        n += self.criar(Historico, [Historico(
            pk=r["id"], ordem_id=r["ordem_servico_id"], usuario_id=self.uid_ou_none(r.get("usuario_id")),
            status_anterior=txt(r.get("status_anterior"), 50), status_novo=txt(r.get("status_novo"), 50),
            descricao=txt(r.get("descricao")), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM historico_os") if r["ordem_servico_id"] in ok])
        n += self.criar(Material, [Material(
            pk=r["id"], ordem_id=r["ordem_servico_id"], nome_material=txt(r["nome_material"], 150),
            quantidade=r.get("quantidade") or 0, unidade=txt(r.get("unidade"), 30), observacao=txt(r.get("observacao")),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM materiais_os") if r["ordem_servico_id"] in ok])
        tarefas = set(PlanoTarefa.objects.values_list("pk", flat=True))
        n += self.criar(ChecklistItem, [ChecklistItem(
            pk=r["id"], ordem_id=r["ordem_servico_id"],
            tarefa_plano_id=r.get("tarefa_plano_id") if r.get("tarefa_plano_id") in tarefas else None,
            descricao=txt(r["descricao"], 255), obrigatorio=boolv(r.get("obrigatorio", 1)), concluido=boolv(r.get("concluido", 0)),
            concluido_por_id=self.uid_ou_none(r.get("concluido_por")), concluido_em=aware(r.get("concluido_em")),
            observacao=txt(r.get("observacao"), 255), posicao=r.get("ordem") or 0,
        ) for r in self.linhas("SELECT * FROM ordem_checklist_itens") if r["ordem_servico_id"] in ok])
        membros = set(OrganogramaNo.objects.values_list("pk", flat=True))
        n += self.criar(ParticipanteLimpeza, [ParticipanteLimpeza(
            pk=r["id"], ordem_id=r["ordem_servico_id"], membro_id=r["membro_id"],
            papel="SUPERVISOR" if txt(r.get("papel")).upper() == "SUPERVISOR" else "ASG", principal=boolv(r.get("principal", 0)),
        ) for r in self.linhas("SELECT * FROM ordem_limpeza_participantes")
            if r["ordem_servico_id"] in ok and r["membro_id"] in membros])
        return n

    # ------------------------------------------------------------------ arquivos
    def ler_arquivo(self, url):
        """Lê o arquivo legado; devolve (bytes, nome) ou (None, None)."""
        if not self.copiar_arquivos or not url:
            return None, None
        rel = txt(url).replace("\\", "/").split("?", 1)[0]
        if "/uploads/" in rel:
            rel = rel[rel.index("/uploads/") + 1:]
        rel = rel.lstrip("/")
        if not self.base_uploads:
            return self.baixar_arquivo(rel)
        caminho = (self.base_uploads / rel).resolve()
        if not str(caminho).startswith(str(self.base_uploads)) or not caminho.is_file():
            self.arquivos_faltando += 1
            return None, None
        return caminho.read_bytes(), caminho.name

    def baixar_arquivo(self, rel):
        """Baixa um arquivo de <uploads_url>/<rel> (somente dentro de uploads/)."""
        import posixpath
        import urllib.error
        import urllib.parse
        import urllib.request

        rel = posixpath.normpath(rel)
        if not rel.startswith("uploads/") or ".." in rel.split("/"):
            self.arquivos_faltando += 1
            return None, None
        url = f"{self.uploads_url}/{urllib.parse.quote(rel)}"
        for tentativa in range(3):
            try:
                req = urllib.request.Request(url, headers={"User-Agent": "GEOP-importador/1.0"})
                with urllib.request.urlopen(req, timeout=60) as resp:  # noqa: S310 (URL informada pelo operador)
                    dados = resp.read(60 * 1024 * 1024)
                return dados, posixpath.basename(rel)
            except urllib.error.HTTPError as e:
                if e.code in (403, 404, 410):
                    break
            except (urllib.error.URLError, TimeoutError, ConnectionError):
                pass
            time.sleep(2 * (tentativa + 1))
        self.arquivos_faltando += 1
        return None, None

    def preencher_arquivo(self, obj, dados, nome, pasta):
        mime = detectar_mime(dados[:16])
        base = os.path.splitext(nome)[0]
        destino = f"{pasta}/legado/{base}"
        self.bytes_antes += len(dados)
        if mime in TIPOS_IMAGEM:
            try:
                r = comprimir_imagem(dados)
            except Exception:
                r = None
            if r:
                obj.arquivo.save(f"{destino}.{r['extensao']}", ContentFile(r["dados"]), save=False)
                obj.miniatura.save(f"{destino}_mini.{r['extensao']}", ContentFile(r["miniatura"]), save=False)
                obj.mime, obj.tamanho, obj.largura, obj.altura = r["mime"], len(r["dados"]), r["largura"], r["altura"]
                obj.tamanho_original = len(dados)
                self.bytes_depois += obj.tamanho
                self.arquivos_ok += 1
                return True
        ext = os.path.splitext(nome)[1].lower() or ".bin"
        obj.arquivo.save(f"{destino}{ext}", ContentFile(dados), save=False)
        obj.mime = mime or "application/octet-stream"
        obj.tamanho = obj.tamanho_original = len(dados)
        self.bytes_depois += len(dados)
        self.arquivos_ok += 1
        return True

    def anexos(self):
        n = 0
        existentes = set(Anexo.objects.values_list("pk", flat=True))
        tipos = set(Anexo.Tipo.values)
        for r in self.linhas("SELECT * FROM anexos_os ORDER BY id"):
            if r["id"] in existentes or r["ordem_servico_id"] not in self.ordens_ids:
                continue
            tipo = txt(r.get("tipo")).upper()
            anexo = Anexo(pk=r["id"], ordem_id=r["ordem_servico_id"], usuario_id=self.uid_ou_none(r.get("usuario_id")),
                          tipo=tipo if tipo in tipos else "OUTRO", descricao=txt(r.get("descricao")),
                          origem=Anexo.Origem.LEGADO, nome_original=os.path.basename(txt(r["arquivo_url"]))[:255],
                          criado_em=aware(r.get("criado_em")) or timezone.now())
            dados, nome = self.ler_arquivo(r["arquivo_url"])
            if dados is None:
                if self.copiar_arquivos:
                    self.stderr.write(f"  arquivo ausente: {r['arquivo_url']}")
                    continue
                anexo.arquivo.name = txt(r["arquivo_url"], 255)
            else:
                self.preencher_arquivo(anexo, dados, nome, "ordens")
            anexo.save()
            n += 1
        for r in self.linhas("SELECT * FROM os_fotos_publicas ORDER BY id"):
            if r["os_id"] not in self.ordens_ids:
                continue
            dados, nome = self.ler_arquivo(r["arquivo"])
            if dados is None:
                continue
            anexo = Anexo(ordem_id=r["os_id"], tipo="FOTO_DEPOIS", origem=Anexo.Origem.PUBLICO, nome_original=nome,
                          descricao=txt(r.get("observacao")), criado_em=aware(r.get("criado_em")) or timezone.now())
            if Anexo.objects.filter(ordem_id=r["os_id"], nome_original=nome).exists():
                continue
            self.preencher_arquivo(anexo, dados, nome, "ordens")
            anexo.save()
            n += 1
        return n

    def patrimonios(self):
        return self.criar(OrdemPatrimonio, [OrdemPatrimonio(
            pk=r["id"], ordem_id=r["ordem_id"], patrimonio_externo_id=txt(r.get("patrimonio_externo_id"), 190),
            codigo_barras=txt(r.get("codigo_barras"), 190), numero_patrimonio=txt(r.get("numero_patrimonio"), 190),
            descricao=txt(r.get("descricao_snapshot"), 255), marca=txt(r.get("marca_snapshot"), 120),
            modelo=txt(r.get("modelo_snapshot"), 120), serie=txt(r.get("serie_snapshot"), 190),
            setor=txt(r.get("setor_snapshot"), 190), localizacao=txt(r.get("localizacao_snapshot"), 255),
            origem=txt(r.get("origem"), 50) or "BANCO_EXTERNO", vinculado_por_id=self.uid(r.get("consultado_por")),
            vinculado_em=aware(r.get("consultado_em")) or timezone.now(),
            desvinculado_por_id=self.uid_ou_none(r.get("desvinculado_por")), desvinculado_em=aware(r.get("desvinculado_em")),
            ativo=boolv(r.get("ativo", 1)),
        ) for r in self.linhas("SELECT * FROM ordem_patrimonios") if r["ordem_id"] in self.ordens_ids])

    def exclusoes(self):
        estados = set(SolicitacaoExclusao.Estado.values)
        return self.criar(SolicitacaoExclusao, [SolicitacaoExclusao(
            pk=r["id"], ordem_id=r["ordem_id"], solicitado_por_id=self.uid(r.get("solicitado_por")), motivo=txt(r.get("motivo")),
            estado=txt(r.get("status")).upper() if txt(r.get("status")).upper() in estados else "PENDENTE",
            analisado_por_id=self.uid_ou_none(r.get("analisado_por")), analisado_em=aware(r.get("analisado_em")),
            observacao_analise=txt(r.get("observacao_analise")), executado_por_id=self.uid_ou_none(r.get("excluido_por")),
            executado_em=aware(r.get("excluido_em")), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM ordem_solicitacoes_exclusao WHERE ordem_tipo IN ('OS','OL')")
            if r["ordem_id"] in self.ordens_ids])

    def ambientes(self):
        locais = set(Local.objects.values_list("pk", flat=True))
        n = self.criar(AmbienteAcao, [AmbienteAcao(
            pk=r["id"], nome=txt(r["nome"], 160), descricao=txt(r.get("descricao"), 255), categoria=txt(r.get("categoria"), 80) or "LIMPEZA",
            exige_foto=boolv(r.get("exige_foto", 0)), exige_observacao=boolv(r.get("exige_observacao", 0)), ativo=boolv(r.get("ativo", 1)),
        ) for r in self.linhas("SELECT * FROM ambiente_acoes")])
        n += self.criar(Ambiente, [Ambiente(
            pk=r["id"], local_id=r["local_id"], nome_publico=txt(r["nome_publico"], 160), descricao_publica=txt(r.get("descricao_publica")),
            token_publico=txt(r["token_publico"], 64).lower(), ativo=boolv(r.get("ativo", 1)),
            exibir_historico=boolv(r.get("exibir_historico", 1)), exibir_fotos=boolv(r.get("exibir_fotos", 0)),
            historico_limite=r.get("historico_limite") or 10, frequencia_minutos=r.get("frequencia_minutos") or 60,
            exige_foto_asg=boolv(r.get("exige_foto_asg", 0)), exige_foto_supervisor=boolv(r.get("exige_foto_supervisor", 1)),
            gerado_por_id=self.uid_ou_none(r.get("gerado_por")), ultimo_acesso_em=aware(r.get("ultimo_acesso_em")),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM ambientes_qr") if r["local_id"] in locais])
        amb = set(Ambiente.objects.values_list("pk", flat=True))
        acoes = set(AmbienteAcao.objects.values_list("pk", flat=True))
        for r in self.linhas("SELECT * FROM ambiente_acao_locais"):
            if r["ambiente_id"] in amb and r["acao_id"] in acoes:
                AmbienteAcaoVinculo.objects.get_or_create(ambiente_id=r["ambiente_id"], acao_id=r["acao_id"], defaults={
                    "obrigatoria": boolv(r.get("obrigatoria", 0)), "posicao": r.get("ordem_exibicao") or 0,
                    "ativo": boolv(r.get("ativo", 1))})
        status = set(Registro.Status.values)
        regs = [r for r in self.linhas("SELECT * FROM ambiente_registros") if r["ambiente_id"] in amb]
        n += self.criar(Registro, [Registro(
            pk=r["id"], ambiente_id=r["ambiente_id"],
            ordem_id=r.get("ordem_servico_id") if r.get("ordem_servico_id") in self.ordens_ids else None,
            asg_id=self.uid(r.get("asg_id")),
            status=txt(r["status"]).upper() if txt(r["status"]).upper() in status else Registro.Status.AGUARDANDO_VALIDACAO,
            iniciado_em=aware(r.get("iniciado_em")), finalizado_em=aware(r.get("finalizado_em")) or timezone.now(),
            observacao_asg=txt(r.get("observacao_asg")), problema_encontrado=txt(r.get("problema_encontrado")),
            insumos_repostos=txt(r.get("insumos_repostos")), supervisor_id=self.uid_ou_none(r.get("supervisor_id")),
            validado_em=aware(r.get("validado_em")), observacao_supervisor=txt(r.get("observacao_supervisor")),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in regs])
        reg_ids = set(Registro.objects.values_list("pk", flat=True))
        nomes_acao = dict(AmbienteAcao.objects.values_list("pk", "nome"))
        n += self.criar(RegistroItem, [RegistroItem(
            pk=r["id"], registro_id=r["registro_id"], acao_id=r["acao_id"] if r["acao_id"] in acoes else None,
            descricao=txt(nomes_acao.get(r["acao_id"], "Ação"), 255), concluida=boolv(r.get("concluida", 1)),
            observacao=txt(r.get("observacao"), 255),
        ) for r in self.linhas("SELECT * FROM ambiente_registro_itens") if r["registro_id"] in reg_ids])
        membros = set(OrganogramaNo.objects.values_list("pk", flat=True))
        n += self.criar(RegistroResponsavel, [RegistroResponsavel(
            pk=r["id"], registro_id=r["registro_id"], membro_id=r.get("membro_id") if r.get("membro_id") in membros else None,
            usuario_id=self.uid_ou_none(r.get("usuario_id")), nome=txt(r["nome_responsavel"], 160),
            principal=boolv(r.get("responsavel_principal", 0)),
        ) for r in self.linhas("SELECT * FROM ambiente_registro_responsaveis") if r["registro_id"] in reg_ids])
        n += self.criar(Validacao, [Validacao(
            pk=r["id"], registro_id=r["registro_id"], supervisor_id=self.uid(r.get("supervisor_id")),
            resultado=txt(r["resultado"]).upper(), observacao=txt(r.get("observacao")),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM ambiente_validacoes") if r["registro_id"] in reg_ids])
        existentes = set(RegistroFoto.objects.values_list("pk", flat=True))
        for r in self.linhas("SELECT * FROM ambiente_registro_fotos ORDER BY id"):
            if r["id"] in existentes or r["registro_id"] not in reg_ids:
                continue
            dados, nome = self.ler_arquivo(r["arquivo_url"])
            if dados is None:
                continue
            origem, tipo = txt(r.get("origem")).upper(), txt(r.get("tipo")).upper()
            foto = RegistroFoto(pk=r["id"], registro_id=r["registro_id"], usuario_id=self.uid_ou_none(r.get("usuario_id")),
                                origem=origem if origem in RegistroFoto.Origem.values else "ASG",
                                tipo=tipo if tipo in RegistroFoto.Tipo.values else "DEPOIS",
                                descricao=txt(r.get("descricao"), 255), publica=boolv(r.get("publica", 0)), nome_original=nome,
                                criado_em=aware(r.get("criado_em")) or timezone.now())
            self.preencher_arquivo(foto, dados, nome, "ambientes")
            foto.save()
            n += 1
        return n

    def logs(self):
        n = self.criar(Notificacao, [Notificacao(
            usuario_id=r["usuario_id"], tipo=txt(r["tipo"], 60), titulo=txt(r["titulo"], 180), mensagem=txt(r.get("mensagem")),
            url="", lida_em=aware(r.get("lida_em")), criado_em=aware(r.get("created_at")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM notificacoes WHERE lida_em IS NULL")
            if r["usuario_id"] in self.usuarios_ids]) if not Notificacao.objects.exists() else 0
        if not AuditoriaEvento.objects.filter(acao__startswith="LEGADO_").exists():
            n += self.criar(AuditoriaEvento, [AuditoriaEvento(
                entidade=txt(r["entidade"], 80), entidade_id=r.get("entidade_id"), acao=("LEGADO_" + txt(r["acao"]))[:80],
                campo=txt(r.get("campo"), 120), valor_anterior=txt(r.get("valor_anterior")), valor_novo=txt(r.get("valor_novo")),
                usuario_id=self.uid_ou_none(r.get("usuario_id")), ip=None, user_agent=txt(r.get("user_agent"), 255),
                criado_em=aware(r.get("criado_em")) or timezone.now(),
            ) for r in self.linhas("SELECT * FROM auditoria_eventos")])
        return n

    def ajustar_sequencias(self):
        for tipo in ("OS", "OL"):
            maximos = {}
            for numero in Ordem.objects.filter(numero__startswith=f"{tipo}-").values_list("numero", flat=True):
                partes = numero.split("-")
                # Formato atual OS-AAAA-0000001 (o antigo OS-AAAAMMDD-0001 não interfere na sequência).
                if len(partes) == 3 and len(partes[1]) == 4 and partes[1].isdigit() and partes[2].isdigit():
                    ano = int(partes[1])
                    maximos[ano] = max(maximos.get(ano, 0), int(partes[2]))
            for ano, ultimo in maximos.items():
                seq, _ = NumeroSequencia.objects.get_or_create(tipo=tipo, ano=ano)
                if seq.ultimo < ultimo:
                    seq.ultimo = ultimo
                    seq.save()
        modelos = [Setor, Usuario, Local, Categoria, OrganogramaNo, PlanoGrupo, PlanoTarefa, Ordem, OrdemAgente,
                   Historico, Material, ChecklistItem, ParticipanteLimpeza, Anexo, OrdemPatrimonio, SolicitacaoExclusao,
                   AmbienteAcao, Ambiente, Registro, RegistroItem, RegistroResponsavel, Validacao, RegistroFoto,
                   *self.MODELOS_SEQUENCIA]
        sqls = connection.ops.sequence_reset_sql(no_style(), modelos)
        if sqls:
            with connection.cursor() as cur:
                for sql in sqls:
                    cur.execute(sql)
        self.stdout.write("Sequências de numeração ajustadas.")
