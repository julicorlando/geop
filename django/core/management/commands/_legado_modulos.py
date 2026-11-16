"""Etapas do importador para os módulos além do núcleo (estoque, operação, CFTV, chat, IoT…).

Cada etapa tolera tabelas/colunas inexistentes — instalações antigas do PHP não
têm todas as tabelas. Os IDs são preservados sempre que o legado os possui.
"""
from __future__ import annotations

import json
from decimal import Decimal

from django.conf import settings
from django.utils import timezone

from cftv.models import (ConfirmacaoLiberacao, Destino, EventoAcesso, Liberacao, Observacao, Ocorrencia,
                         PessoaAdicional, RelatorioPlantao, SolicitacaoAcesso, normalizar_cpf)
from chat.models import Conversa, Mensagem, Participante
from conhecimento.models import Artigo, Avaliacao
from core.models import Local
from estoque.models import Compra, CompraItem, CustoDocumento, MaterialEstoque, Movimentacao, Reserva
from iot.models import AtivoRede, Dispositivo, Leitura
from operacao.models import CronogramaAgente, EquipeEstado, Escala, Especialidade, Feriado, PassagemTurno, Preventiva
from ordens.models import ChecklistItem, ChecklistModelo, ChecklistModeloItem, Material, Ordem


def _escolha(valor, validos, padrao):
    v = str(valor or "").strip().upper()
    return v if v in validos else padrao


def _dec(valor, padrao="0"):
    try:
        return Decimal(str(valor)) if valor not in (None, "") else Decimal(padrao)
    except Exception:
        return Decimal(padrao)


class EtapasModulos:
    """Mixin usado pelo comando `importar_legado` (depende de linhas/criar/uid/aware/txt…)."""

    # ------------------------------------------------------------------ preventivas (antes das ordens)
    def preventivas(self):
        from .importar_legado import aware, boolv, txt

        locais = set(Local.objects.values_list("pk", flat=True))
        from core.models import Categoria
        categorias = set(Categoria.objects.values_list("pk", flat=True))
        return self.criar(Preventiva, [Preventiva(
            pk=r["id"], titulo=txt(r["titulo"], 150), descricao=txt(r.get("descricao")), local_id=r["local_id"],
            categoria_id=r["categoria_id"], agente_id=self.uid_ou_none(r.get("agente_id")),
            prioridade=_escolha(r.get("prioridade"), {c for c, _ in Preventiva._meta.get_field("prioridade").choices}, "MEDIA"),
            intervalo_dias=r.get("intervalo_dias") or 30, proxima_execucao=r.get("proxima_execucao") or timezone.localdate(),
            ultimo_gerado_em=aware(r.get("ultimo_gerado_em")), ativo=boolv(r.get("ativo", 1)),
            criado_por_id=self.uid_ou_none(r.get("criado_por")), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM manutencoes_preventivas")
            if r.get("local_id") in locais and r.get("categoria_id") in categorias])

    # ------------------------------------------------------------------ campos novos das ordens
    def ordens_extras(self):
        prev = set(Preventiva.objects.values_list("pk", flat=True))
        n = 0
        for r in self.linhas("SELECT * FROM ordens_servico"):
            if r["id"] not in self.ordens_ids:
                continue
            campos = {}
            for c in ("custo_mao_obra", "custo_terceiros", "custo_total_final"):
                if r.get(c) is not None:
                    campos[c] = _dec(r[c])
            if r.get("qualidade_percentual") is not None:
                campos["qualidade_percentual"] = r["qualidade_percentual"]
            if r.get("preventiva_id") in prev:
                campos["preventiva_id"] = r["preventiva_id"]
            if r.get("duplicidade_referencia_id") in self.ordens_ids and r["duplicidade_referencia_id"] != r["id"]:
                campos["duplicidade_referencia_id"] = r["duplicidade_referencia_id"]
            if campos:
                n += Ordem.objects.filter(pk=r["id"]).update(**campos)
        return n

    # ------------------------------------------------------------------ estoque e compras
    def estoque(self):
        from .importar_legado import aware, boolv, txt

        n = self.criar(MaterialEstoque, [MaterialEstoque(
            pk=r["id"], nome=txt(r["nome"], 160), unidade=txt(r.get("unidade"), 30) or "UND",
            quantidade_atual=_dec(r.get("quantidade_atual")), estoque_minimo=_dec(r.get("estoque_minimo")),
            custo_unitario=_dec(r.get("custo_unitario")), ativo=boolv(r.get("ativo", 1)),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM estoque_materiais")])
        mats = set(MaterialEstoque.objects.values_list("pk", flat=True))
        tipos = set(Movimentacao.Tipo.values)
        movs = []
        for r in self.linhas("SELECT * FROM movimentacoes_estoque"):
            if r.get("material_id") not in mats:
                continue
            tipo = _escolha(r.get("tipo"), tipos, "AJUSTE")
            qtd = _dec(r.get("quantidade"))
            if tipo in ("SAIDA", "SAIDA_OS") and qtd > 0:  # no Django saída é negativa
                qtd = -qtd
            movs.append(Movimentacao(
                pk=r["id"], material_id=r["material_id"], tipo=tipo, quantidade=qtd,
                ordem_id=r.get("ordem_servico_id") if r.get("ordem_servico_id") in self.ordens_ids else None,
                usuario_id=self.uid_ou_none(r.get("usuario_id")), custo_unitario=_dec(r.get("custo_unitario")),
                observacao=txt(r.get("observacao")), criado_em=aware(r.get("criado_em")) or timezone.now()))
        n += self.criar(Movimentacao, movs)
        status_res = {c for c, _ in Reserva._meta.get_field("status").choices}
        n += self.criar(Reserva, [Reserva(
            pk=r["id"], ordem_id=r["ordem_servico_id"], material_id=r["material_id"], quantidade=_dec(r.get("quantidade")),
            custo_unitario=_dec(r.get("custo_unitario")), status=_escolha(r.get("status"), status_res, "RESERVADA"),
            usuario_id=self.uid_ou_none(r.get("usuario_id") or r.get("criado_por")),
            justificativa=txt(r.get("justificativa")), aprovado_por_id=self.uid_ou_none(r.get("aprovado_por")),
            aprovado_em=aware(r.get("aprovado_em")), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM estoque_reservas")
            if r.get("ordem_servico_id") in self.ordens_ids and r.get("material_id") in mats])
        status_c = {c for c, _ in Compra._meta.get_field("status").choices}
        compras = []
        for r in self.linhas("SELECT * FROM compras_solicitacoes"):
            st = txt(r.get("status")).upper()
            st = "SOLICITADA" if st in ("RASCUNHO", "PENDENTE", "") else _escolha(st, status_c, "SOLICITADA")
            compras.append(Compra(
                pk=r["id"], ordem_id=r.get("ordem_servico_id") if r.get("ordem_servico_id") in self.ordens_ids else None,
                solicitante_id=self.uid(r.get("solicitante_id")), aprovador_id=self.uid_ou_none(r.get("aprovador_id")),
                status=st, justificativa=txt(r.get("justificativa")), fornecedor=txt(r.get("fornecedor"), 180),
                numero_documento=txt(r.get("numero_documento"), 100), valor_previsto=_dec(r.get("valor_previsto")),
                valor_final=_dec(r.get("valor_final")), decidido_em=aware(r.get("decidido_em")),
                comprado_em=aware(r.get("comprado_em")), recebido_em=aware(r.get("recebido_em")),
                criado_em=aware(r.get("solicitado_em") or r.get("criado_em")) or timezone.now()))
        n += self.criar(Compra, compras)
        cids = set(Compra.objects.values_list("pk", flat=True))
        n += self.criar(CompraItem, [CompraItem(
            pk=r["id"], compra_id=r["compra_id"], material_id=r.get("material_id") if r.get("material_id") in mats else None,
            descricao=txt(r["descricao"], 190), quantidade=_dec(r.get("quantidade"), "1"), unidade=txt(r.get("unidade"), 30),
            custo_unitario_previsto=_dec(r.get("custo_unitario_previsto")),
            custo_unitario_final=_dec(r["custo_unitario_final"]) if r.get("custo_unitario_final") is not None else None,
        ) for r in self.linhas("SELECT * FROM compras_itens") if r.get("compra_id") in cids])
        # documentos de custo (com arquivo)
        tipos_c = {c for c, _ in CustoDocumento._meta.get_field("tipo").choices}
        existentes = set(CustoDocumento.objects.values_list("pk", flat=True))
        for r in self.linhas("SELECT * FROM os_custos_documentos ORDER BY id"):
            if r["id"] in existentes or r.get("ordem_servico_id") not in self.ordens_ids:
                continue
            doc = CustoDocumento(
                pk=r["id"], ordem_id=r["ordem_servico_id"], tipo=_escolha(r.get("tipo"), tipos_c, "OUTRO"),
                fornecedor=txt(r.get("fornecedor"), 180), documento=txt(r.get("documento"), 100),
                descricao=txt(r.get("descricao"), 255), valor=_dec(r.get("valor")),
                registrado_por_id=self.uid_ou_none(r.get("registrado_por")), criado_em=aware(r.get("criado_em")) or timezone.now())
            dados, nome = self.ler_arquivo(r.get("arquivo_url"))
            if dados is not None:
                doc.nome_original = nome
                self.preencher_arquivo(doc, dados, nome, "custos")
            doc.save()
            n += 1
        n += self.ajustar_custos_materiais()
        return n

    def ajustar_custos_materiais(self):
        """Materiais do PHP não têm custo unitário; preserva o custo gravado na OS com uma linha de ajuste."""
        n = 0
        nome = "Ajuste de custo (importado do PHP)"
        for o in Ordem.objects.filter(custo_materiais__gt=0).exclude(materiais__nome_material=nome):
            soma = sum((m.custo_total for m in o.materiais.filter(
                situacao__in=["CONSUMIDO", "AVULSO"])), Decimal("0"))
            diferenca = o.custo_materiais - soma
            if diferenca > 0:
                Material.objects.create(ordem=o, nome_material=nome, quantidade=1, unidade="UND",
                                        situacao="AVULSO", custo_unitario=diferenca)
                n += 1
        return n

    # ------------------------------------------------------------------ checklists por categoria
    def checklists(self):
        from core.models import Categoria

        from .importar_legado import aware, boolv, txt

        categorias = set(Categoria.objects.values_list("pk", flat=True))
        n = self.criar(ChecklistModelo, [ChecklistModelo(
            pk=r["id"], categoria_id=r["categoria_id"], nome=txt(r["nome"], 160), ativo=boolv(r.get("ativo", 1)),
            criado_por_id=self.uid_ou_none(r.get("criado_por")),
            criado_em=aware(r.get("criado_em") or r.get("created_at")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM checklist_modelos") if r.get("categoria_id") in categorias])
        modelos = set(ChecklistModelo.objects.values_list("pk", flat=True))
        n += self.criar(ChecklistModeloItem, [ChecklistModeloItem(
            pk=r["id"], modelo_id=r["modelo_id"], descricao=txt(r.get("descricao") or r.get("rotulo"), 255),
            obrigatorio=boolv(r.get("obrigatorio", 0)), exige_foto=boolv(r.get("exige_foto", 0)), posicao=r.get("ordem") or 0,
        ) for r in self.linhas("SELECT * FROM checklist_modelo_itens") if r.get("modelo_id") in modelos])
        itens_modelo = set(ChecklistModeloItem.objects.values_list("pk", flat=True))
        existentes = set(ChecklistItem.objects.values_list("ordem_id", "descricao"))
        novos = []
        for r in self.linhas("SELECT * FROM os_checklist_itens ORDER BY id"):
            chave = (r.get("ordem_servico_id"), txt(r.get("descricao"), 255))
            if chave[0] not in self.ordens_ids or chave in existentes:
                continue
            existentes.add(chave)
            novos.append(ChecklistItem(
                ordem_id=chave[0], descricao=chave[1], obrigatorio=boolv(r.get("obrigatorio", 0)),
                modelo_item_id=r.get("origem_item_id") if r.get("origem_item_id") in itens_modelo else None,
                concluido=boolv(r.get("concluido", 0)), concluido_por_id=self.uid_ou_none(r.get("concluido_por")),
                concluido_em=aware(r.get("concluido_em")), observacao=txt(r.get("observacao"), 255),
                posicao=r.get("ordem") or 0))
        n += self.criar(ChecklistItem, novos)
        return n

    # ------------------------------------------------------------------ equipe e operação
    def operacao(self):
        from .importar_legado import aware, boolv, txt

        u = self.usuarios_ids
        estados = set(EquipeEstado.Estado.values)
        n = 0
        for r in self.linhas("SELECT * FROM equipe_estado"):
            if r.get("usuario_id") in u and not EquipeEstado.objects.filter(usuario_id=r["usuario_id"]).exists():
                EquipeEstado.objects.create(usuario_id=r["usuario_id"], estado=_escolha(r.get("estado"), estados, "DISPONIVEL"),
                                            capacidade_maxima=r.get("capacidade_maxima") or 5,
                                            observacao=txt(r.get("observacao"), 255))
                n += 1
        for r in self.linhas("SELECT * FROM usuario_especialidades"):
            if r.get("usuario_id") in u and txt(r.get("especialidade")):
                _, criado = Especialidade.objects.get_or_create(
                    usuario_id=r["usuario_id"], especialidade=txt(r["especialidade"], 100),
                    defaults={"nivel": min(max(int(r.get("nivel") or 1), 1), 3)})
                n += criado
        tipos_e = {c for c, _ in Escala._meta.get_field("tipo").choices}
        for r in self.linhas("SELECT * FROM escalas_servico"):
            if r.get("usuario_id") in u and r.get("data_servico"):
                _, criado = Escala.objects.get_or_create(usuario_id=r["usuario_id"], data_servico=r["data_servico"], defaults={
                    "funcao": txt(r.get("funcao"), 100), "posto": txt(r.get("posto"), 100),
                    "tipo": _escolha(r.get("tipo"), tipos_e, "TRABALHO"), "jornada_horas": r.get("jornada_horas") or 8,
                    "hora_inicio": r.get("hora_inicio"), "hora_fim": r.get("hora_fim"),
                    "criado_por_id": self.uid_ou_none(r.get("criado_por"))})
                n += criado
        locais = set(Local.objects.values_list("pk", flat=True))
        for r in self.linhas("SELECT * FROM feriados_operacionais"):
            if r.get("data"):
                _, criado = Feriado.objects.get_or_create(
                    data=r["data"], local_id=r.get("local_id") if r.get("local_id") in locais else None,
                    defaults={"nome": txt(r.get("nome"), 120) or "Feriado", "ativo": boolv(r.get("ativo", 1))})
                n += criado
        n += self.criar(PassagemTurno, [PassagemTurno(
            pk=r["id"], entrega_id=r["entrega_id"], recebe_id=r["recebe_id"], pendencias=txt(r.get("pendencias")),
            ocorrencias_criticas=txt(r.get("ocorrencias_criticas")),
            equipamentos_indisponiveis=txt(r.get("equipamentos_indisponiveis")),
            servicos_andamento=txt(r.get("servicos_andamento")),
            observacoes="\n".join(x for x in (txt(r.get("observacoes")), txt(r.get("itens_entregues"))) if x),
            lida_em=aware(r.get("lida_em")), criado_em=aware(r.get("created_at") or r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM passagens_turno") if r.get("entrega_id") in u and r.get("recebe_id") in u])
        status_cr = {c for c, _ in CronogramaAgente._meta.get_field("status").choices}
        n += self.criar(CronogramaAgente, [CronogramaAgente(
            pk=r["id"], agente_id=r["agente_id"], gestor_id=self.uid(r.get("gestor_id")), titulo=txt(r["titulo"], 160),
            descricao=txt(r.get("descricao")), local_id=r.get("local_id") if r.get("local_id") in locais else None,
            dia_semana=r.get("dia_semana") or 1, hora_inicio=r["hora_inicio"], hora_fim=r["hora_fim"],
            semana_inicio=r["semana_inicio"], status=_escolha(r.get("status"), status_cr, "PLANEJADO"),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM cronograma_agentes") if r.get("agente_id") in u and r.get("semana_inicio")])
        return n

    # ------------------------------------------------------------------ base de conhecimento
    def conhecimento(self):
        from core.models import Categoria

        from .importar_legado import aware, txt

        categorias = set(Categoria.objects.values_list("pk", flat=True))
        status = {c for c, _ in Artigo._meta.get_field("status").choices}
        artigos = []
        for r in self.linhas("SELECT * FROM conhecimento_artigos"):
            links = r.get("links")
            if isinstance(links, str):
                try:
                    links = json.loads(links)
                except ValueError:
                    links = [links]
            links = "\n".join(str(x) for x in links) if isinstance(links, list) else txt(links)
            artigos.append(Artigo(
                pk=r["id"], titulo=txt(r["titulo"], 200), conteudo=txt(r.get("conteudo")) or "-",
                categoria_id=r.get("categoria_id") if r.get("categoria_id") in categorias else None,
                problema=txt(r.get("problema")), palavras_chave=txt(r.get("palavras_chave"), 255), links=links,
                origem_ordem_id=r.get("origem_os_id") if r.get("origem_os_id") in self.ordens_ids else None,
                versao=txt(r.get("versao"), 30) or "1.0", responsavel_id=self.uid_ou_none(r.get("responsavel_id")),
                revisar_em=r.get("revisar_em"), status=_escolha(r.get("status"), status, "RASCUNHO"),
                criado_em=aware(r.get("created_at") or r.get("criado_em")) or timezone.now()))
        n = self.criar(Artigo, artigos)
        ids = set(Artigo.objects.values_list("pk", flat=True))
        for r in self.linhas("SELECT * FROM conhecimento_avaliacoes"):
            if r.get("artigo_id") in ids and r.get("usuario_id") in self.usuarios_ids:
                from .importar_legado import boolv
                _, criado = Avaliacao.objects.get_or_create(artigo_id=r["artigo_id"], usuario_id=r["usuario_id"], defaults={
                    "util": boolv(r.get("util", 1)), "comentario": txt(r.get("comentario"))})
                n += criado
        return n

    # ------------------------------------------------------------------ CFTV e controle de acesso
    def cftv(self):
        from .importar_legado import aware, boolv, txt

        def campos_plantao(r):
            nomes = [f.attname for f in RelatorioPlantao._meta.concrete_fields
                     if f.attname not in ("id", "operador_id", "criado_em", "atualizado_em", "finalizado_em")]
            d = {}
            for c in nomes:
                if c in r and r[c] is not None:
                    f = RelatorioPlantao._meta.get_field(c)
                    d[c] = boolv(r[c]) if f.get_internal_type() == "BooleanField" else r[c]
            return d

        n = self.criar(RelatorioPlantao, [RelatorioPlantao(
            pk=r["id"], operador_id=self.uid(r.get("operador_id")), finalizado_em=aware(r.get("finalizado_em")),
            criado_em=aware(r.get("criado_em")) or timezone.now(), **campos_plantao(r),
        ) for r in self.linhas("SELECT * FROM cftv_relatorios")])
        rel = set(RelatorioPlantao.objects.values_list("pk", flat=True))
        niveis = {c for c, _ in Ocorrencia._meta.get_field("nivel").choices}
        n += self.criar(Ocorrencia, [Ocorrencia(
            pk=r["id"], relatorio_id=r["relatorio_id"], horario=aware(r.get("horario")),
            nivel=_escolha(r.get("nivel"), niveis, "NIVEL_1"), tipo=txt(r.get("tipo"), 160), local=txt(r.get("local"), 160),
            descricao=txt(r.get("descricao")), envolvidos=txt(r.get("envolvidos")), imagens=txt(r.get("imagens")),
            tempo_resposta_min=r.get("tempo_resposta_min"), equipes_acionadas=txt(r.get("equipes_acionadas")),
            encerramento=txt(r.get("encerramento")), preservar_imagens=boolv(r.get("preservar_imagens", 0)),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM cftv_ocorrencias") if r.get("relatorio_id") in rel])
        classes = {c for c, _ in Observacao._meta.get_field("classificacao").choices}
        n += self.criar(Observacao, [Observacao(
            pk=r["id"], relatorio_id=r["relatorio_id"], equipe=txt(r.get("equipe"), 80) or "-", horario=aware(r.get("horario")),
            local=txt(r.get("local"), 160), item=txt(r.get("item"), 160),
            classificacao=_escolha(r.get("classificacao"), classes, "INFORMATIVA"), descricao=txt(r.get("descricao")),
            tempo_resposta_min=r.get("tempo_resposta_min"), providencia=txt(r.get("providencia")),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM cftv_observacoes") if r.get("relatorio_id") in rel])
        tipos_d = {c for c, _ in Destino._meta.get_field("tipo").choices}
        n += self.criar(Destino, [Destino(
            pk=r["id"], tipo=_escolha(r.get("tipo"), tipos_d, "LOJA_MALL"), nome=txt(r["nome"], 160),
            ativo=boolv(r.get("ativo", 1)), criado_por_id=self.uid_ou_none(r.get("criado_por")),
            criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM cftv_destinos_acesso")])
        n += self.criar(Liberacao, [Liberacao(
            pk=r["id"], data_acesso=r["data_acesso"], data_fim=r.get("data_fim"), hora_inicio=r.get("hora_inicio"),
            hora_fim=r.get("hora_fim"), nome_completo=txt(r["nome_completo"], 180), cpf=normalizar_cpf(r.get("cpf")),
            email=txt(r.get("email"), 190), destino_tipo=_escolha(r.get("destino_tipo"), tipos_d, "LOJA_MALL"),
            destino_nome=txt(r.get("destino_nome"), 160) or "-", motivo=txt(r.get("motivo")),
            observacoes=txt(r.get("observacoes")), status="CANCELADA" if txt(r.get("status")).upper() == "CANCELADA" else "ATIVA",
            criado_por_id=self.uid(r.get("criado_por")), criado_em=aware(r.get("criado_em")) or timezone.now(),
            cancelado_por_id=self.uid_ou_none(r.get("cancelado_por")), cancelado_em=aware(r.get("cancelado_em")),
        ) for r in self.linhas("SELECT * FROM cftv_liberacoes") if r.get("data_acesso")])
        libs = set(Liberacao.objects.values_list("pk", flat=True))
        conf = {c for c, _ in ConfirmacaoLiberacao._meta.get_field("status_confirmacao").choices}
        n += self.criar(ConfirmacaoLiberacao, [ConfirmacaoLiberacao(
            pk=r["id"], relatorio_id=r["relatorio_id"], liberacao_id=r["liberacao_id"],
            status_confirmacao=_escolha(r.get("status_confirmacao"), conf, "CONFIRMADA"),
            horario_confirmacao=aware(r.get("horario_confirmacao")), alteracao_observada=txt(r.get("alteracao_observada")),
            confirmado_por_id=self.uid(r.get("confirmado_por")), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM cftv_liberacao_confirmacoes")
            if r.get("relatorio_id") in rel and r.get("liberacao_id") in libs])
        status_s = {c for c, _ in SolicitacaoAcesso._meta.get_field("status").choices}
        # IDs preservados: os QR codes impressos (id.hmac) continuam válidos com o mesmo GEOP_QR_SECRET.
        n += self.criar(SolicitacaoAcesso, [SolicitacaoAcesso(
            pk=r["id"], protocolo=txt(r["protocolo"], 30), nome_completo=txt(r["nome_completo"], 180),
            cpf=normalizar_cpf(r.get("cpf")), telefone=txt(r.get("telefone"), 30), email=txt(r.get("email"), 190),
            empresa=txt(r.get("empresa"), 160), data_acesso=r["data_acesso"], data_fim=r.get("data_fim"),
            hora_inicio=r.get("hora_inicio"), hora_fim=r.get("hora_fim"),
            destino_tipo=_escolha(r.get("destino_tipo"), tipos_d, "LOJA_MALL"), destino_nome=txt(r.get("destino_nome"), 160) or "-",
            responsavel_interno=txt(r.get("responsavel_interno"), 160), motivo=txt(r.get("motivo")),
            veiculo_placa=txt(r.get("veiculo_placa"), 20), observacoes=txt(r.get("observacoes")),
            status=_escolha(r.get("status"), status_s, "PENDENTE"), decisao_justificativa=txt(r.get("decisao_justificativa")),
            decidido_por_id=self.uid_ou_none(r.get("decidido_por")), decidido_em=aware(r.get("decidido_em")),
            liberacao_id=r.get("liberacao_id") if r.get("liberacao_id") in libs else None,
            ip_hash=txt(r.get("ip_hash"), 64), criado_em=aware(r.get("criado_em")) or timezone.now(),
        ) for r in self.linhas("SELECT * FROM cftv_solicitacoes_acesso") if r.get("data_acesso") and r.get("protocolo")])
        sols = set(SolicitacaoAcesso.objects.values_list("pk", flat=True))
        n += self.criar(PessoaAdicional, [PessoaAdicional(
            pk=r["id"], solicitacao_id=r["solicitacao_id"], nome_completo=txt(r["nome_completo"], 180),
            cpf=normalizar_cpf(r.get("cpf")),
        ) for r in self.linhas("SELECT * FROM cftv_solicitacao_pessoas") if r.get("solicitacao_id") in sols])
        n += self.criar(EventoAcesso, [EventoAcesso(
            pk=r["id"], solicitacao_id=r["solicitacao_id"], tipo="SAIDA" if txt(r.get("tipo")).upper() == "SAIDA" else "ENTRADA",
            registrado_por_id=self.uid(r.get("registrado_por")), registrado_em=aware(r.get("registrado_em")) or timezone.now(),
            observacao=txt(r.get("observacao"), 255),
        ) for r in self.linhas("SELECT * FROM cftv_acesso_eventos") if r.get("solicitacao_id") in sols])
        for r in self.linhas("SELECT * FROM cftv_acesso_config WHERE chave = 'qr_secret'"):
            if txt(r.get("valor")) and txt(r.get("valor")) != settings.GEOP_QR_SECRET:
                self.stderr.write(self.style.WARNING(
                    "  Atenção: o segredo dos QR codes do PHP é diferente de GEOP_QR_SECRET. "
                    "Copie o valor de cftv_acesso_config.qr_secret para o .env para manter as credenciais impressas válidas."))
        return n

    # ------------------------------------------------------------------ chat
    def chat(self):
        from .importar_legado import aware, txt

        u = self.usuarios_ids
        n = 0
        mapa = {}
        for r in self.linhas("SELECT * FROM chat_conversas ORDER BY id"):
            a, b = r.get("usuario_1_id"), r.get("usuario_2_id")
            tipo = txt(r.get("tipo")).upper()
            if tipo == "GRUPO":
                conv, criada = Conversa.objects.get_or_create(pk=r["id"], defaults={
                    "tipo": Conversa.Tipo.GRUPO, "nome": txt(r.get("nome"), 120) or "Grupo", "criador_id": self.uid_ou_none(a)})
            else:
                if a not in u or b not in u or a == b:
                    continue
                chave = "-".join(str(x) for x in sorted([a, b]))
                conv, criada = Conversa.objects.get_or_create(chave_direta=chave, defaults={
                    "tipo": Conversa.Tipo.DIRETA, "criador_id": a})
                if criada:
                    Participante.objects.bulk_create([Participante(conversa=conv, usuario_id=a),
                                                      Participante(conversa=conv, usuario_id=b)])
            if criada:
                Conversa.objects.filter(pk=conv.pk).update(criado_em=aware(r.get("criado_em")) or timezone.now())
                n += 1
            mapa[r["id"]] = conv.pk
        for r in self.linhas("SELECT * FROM chat_grupo_participantes"):
            cid = mapa.get(r.get("conversa_id"))
            if cid and r.get("usuario_id") in u:
                n += Participante.objects.get_or_create(conversa_id=cid, usuario_id=r["usuario_id"])[1]
        if Mensagem.objects.exists():
            return n
        msgs = []
        for r in self.linhas("SELECT * FROM chat_mensagens ORDER BY id"):
            cid = mapa.get(r.get("conversa_id"))
            if not cid or r.get("remetente_id") not in u:
                continue
            msgs.append(Mensagem(conversa_id=cid, remetente_id=r["remetente_id"], texto=txt(r.get("mensagem")),
                                 editada_em=aware(r.get("editada_em")), excluida_em=aware(r.get("excluida_em")),
                                 criado_em=aware(r.get("criado_em")) or timezone.now()))
        n += self.criar(Mensagem, msgs)
        # marca como lidas as conversas cujas mensagens já tinham lida_em no PHP
        for p in Participante.objects.filter(ultima_leitura__isnull=True, conversa_id__in=set(mapa.values())):
            ultima = Mensagem.objects.filter(conversa_id=p.conversa_id).order_by("-criado_em").values_list("criado_em", flat=True).first()
            if ultima:
                Participante.objects.filter(pk=p.pk).update(ultima_leitura=ultima)
        return n

    # ------------------------------------------------------------------ IoT e rede
    def iot(self):
        from .importar_legado import aware, boolv, txt

        locais = set(Local.objects.values_list("pk", flat=True))
        disp = []
        for r in self.linhas("SELECT * FROM dispositivos_iot"):
            lim = r.get("limite_alerta")
            if isinstance(lim, str):
                try:
                    lim = json.loads(lim)
                except ValueError:
                    lim = {}
            lim = lim if isinstance(lim, dict) else {}
            disp.append(Dispositivo(
                pk=r["id"], identificador=txt(r["identificador"], 100), tipo=txt(r.get("tipo"), 80) or "sensor",
                local_id=r.get("local_id") if r.get("local_id") in locais else None,
                limite_min=lim.get("min"), limite_max=lim.get("max"), api_key_hash=txt(r["api_key_hash"], 64),
                status=txt(r.get("status"), 30) or "NOVO", ultima_comunicacao=aware(r.get("ultima_comunicacao")),
                bateria=r.get("bateria"), ativo=boolv(r.get("ativo", 1)),
                criado_em=aware(r.get("created_at") or r.get("criado_em")) or timezone.now()))
        n = self.criar(Dispositivo, disp)
        ids = set(Dispositivo.objects.values_list("pk", flat=True))
        leituras = []
        for r in self.linhas("SELECT * FROM iot_leituras"):
            if r.get("dispositivo_id") not in ids:
                continue
            payload = r.get("payload")
            if isinstance(payload, str):
                try:
                    payload = json.loads(payload)
                except ValueError:
                    payload = {"bruto": payload}
            leituras.append(Leitura(pk=r["id"], dispositivo_id=r["dispositivo_id"], tipo=txt(r.get("tipo"), 80) or "valor",
                                    valor_numero=r.get("valor_numero"), valor_texto=txt(r.get("valor_texto"), 255),
                                    payload=payload or {}, recebida_em=aware(r.get("recebida_em")) or timezone.now()))
        n += self.criar(Leitura, leituras)
        tipos = {c for c, _ in AtivoRede._meta.get_field("tipo").choices}
        crit = {c for c, _ in AtivoRede._meta.get_field("criticidade").choices}
        n += self.criar(AtivoRede, [AtivoRede(
            pk=r["id"], tipo=_escolha(r.get("tipo"), tipos, "CAMERA"), nome=txt(r["nome"], 160),
            endereco_ip=txt(r.get("endereco_ip"), 255), porta=r.get("porta") or 80,
            local_id=r.get("local_id") if r.get("local_id") in locais else None,
            criticidade=_escolha(r.get("criticidade"), crit, "MEDIA"), falhas_para_alerta=r.get("falhas_para_alerta") or 3,
            falhas_consecutivas=r.get("falhas_consecutivas") or 0, status=txt(r.get("status"), 30) or "DESCONHECIDO",
            ultima_verificacao=aware(r.get("ultima_verificacao")), ativo=boolv(r.get("ativo", 1)),
        ) for r in self.linhas("SELECT * FROM ativos_rede") if txt(r.get("endereco_ip"))])
        return n

    MODELOS_SEQUENCIA = [Preventiva, MaterialEstoque, Movimentacao, Reserva, Compra, CompraItem, CustoDocumento,
                         ChecklistModelo, ChecklistModeloItem, PassagemTurno, CronogramaAgente, Artigo,
                         RelatorioPlantao, Ocorrencia, Observacao, Destino, Liberacao, ConfirmacaoLiberacao,
                         SolicitacaoAcesso, PessoaAdicional, EventoAcesso, Conversa, Dispositivo, Leitura, AtivoRede]

