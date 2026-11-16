import hashlib

from django.conf import settings
from django.core.exceptions import PermissionDenied, ValidationError
from django.db import transaction
from django.urls import reverse
from django.utils import timezone

from core.imaging import processar_upload
from core.services import auditar, notificar, notificar_permissao
from ordens.models import Anexo, Ordem, Status
from ordens.services import RegraNegocio, historico

from .models import AcessoQR, Registro, RegistroFoto, RegistroItem, RegistroResponsavel, Validacao

STATUS_OS_ABERTA = [Status.ABERTA, Status.ATRIBUIDA, Status.EM_ANDAMENTO, Status.PAUSADA,
                    Status.AGUARDANDO_MATERIAL, Status.REPROVADA]


def ordens_abertas_do_local(local):
    return Ordem.objects.ativas().filter(local=local, status__in=STATUS_OS_ABERTA).order_by("-criado_em")


def registrar_acesso(ambiente, request):
    def h(valor):
        return hashlib.sha256(f"{settings.SECRET_KEY}|{valor}".encode()).hexdigest()

    AcessoQR.objects.create(
        ambiente=ambiente, usuario=request.user if request.user.is_authenticated else None,
        ip_hash=h(request.META.get("REMOTE_ADDR", "")), user_agent_hash=h(request.META.get("HTTP_USER_AGENT", "")),
    )
    type(ambiente).objects.filter(pk=ambiente.pk).update(ultimo_acesso_em=timezone.now())


def salvar_fotos(registro, usuario, arquivos, origem, tipo, publica=False):
    arquivos = [a for a in arquivos if a]
    if len(arquivos) > settings.GEOP_FOTOS_POR_ENVIO:
        raise RegraNegocio(f"Envie no máximo {settings.GEOP_FOTOS_POR_ENVIO} fotos por vez.")
    processados = []
    for a in arquivos:
        try:
            processados.append((a.name, processar_upload(a, permitir_pdf=False, prefixo=f"amb_{registro.ambiente_id}_")))
        except ValidationError as exc:
            raise RegraNegocio(f"{a.name}: {exc.messages[0]}") from exc
    fotos = []
    for nome, proc in processados:
        foto = RegistroFoto(registro=registro, usuario=usuario, origem=origem, tipo=tipo, publica=publica)
        foto.aplicar(proc, nome)
        foto.save()
        fotos.append(foto)
    return fotos


def origem_do_usuario(user):
    if user.pode("ambientes.gerenciar"):
        return RegistroFoto.Origem.GESTOR
    if user.pode("ambientes.validar"):
        return RegistroFoto.Origem.SUPERVISOR
    return RegistroFoto.Origem.ASG


def criar_registro(ambiente, user, dados, arquivos, request=None):
    if not user.pode("ambientes.registrar"):
        raise PermissionDenied
    if not ambiente.ativo:
        raise RegraNegocio("Este ambiente está inativo.")
    vinculos = list(ambiente.acoes_ativas())
    acoes_sel = set(dados.get("acoes") or [])
    faltando = [v.acao.nome for v in vinculos if v.obrigatoria and v.acao_id not in {a.pk for a in acoes_sel}]
    if faltando:
        raise RegraNegocio("Marque as ações obrigatórias: " + ", ".join(faltando) + ".")
    tarefas = list(dados.get("tarefas") or [])
    if not acoes_sel and not tarefas:
        raise RegraNegocio("Marque ao menos uma ação ou tarefa realizada.")
    exige_foto = ambiente.exige_foto_asg or any(a.exige_foto for a in acoes_sel) or any(t.exige_foto for t in tarefas)
    if exige_foto and not [a for a in arquivos if a]:
        raise RegraNegocio("Este registro exige ao menos uma foto.")
    ordem = dados.get("ordem")
    if ordem and ordem not in ordens_abertas_do_local(ambiente.local):
        raise RegraNegocio("A ordem escolhida não está aberta neste local.")
    with transaction.atomic():
        reg = Registro.objects.create(
            ambiente=ambiente, ordem=ordem, asg=user,
            iniciado_em=dados.get("iniciado_em"), finalizado_em=dados.get("finalizado_em") or timezone.now(),
            observacao_asg=dados.get("observacao_asg", ""), problema_encontrado=dados.get("problema_encontrado", ""),
            insumos_repostos=dados.get("insumos_repostos", ""),
        )
        for acao in acoes_sel:
            RegistroItem.objects.create(registro=reg, acao=acao, descricao=acao.nome[:255])
        for tarefa in tarefas:
            RegistroItem.objects.create(registro=reg, tarefa=tarefa, descricao=tarefa.descricao[:255])
        membros = list(dados.get("equipe") or [])
        principal = dados.get("principal")
        for m in membros:
            RegistroResponsavel.objects.create(registro=reg, membro=m, usuario=m.usuario, nome=m.nome_colaborador,
                                               principal=(m == principal))
        if dados.get("executor_nome"):
            RegistroResponsavel.objects.create(registro=reg, nome=dados["executor_nome"][:160], principal=not membros)
        if not membros and not dados.get("executor_nome"):
            RegistroResponsavel.objects.create(registro=reg, usuario=user, nome=user.nome, principal=True)
        fotos = salvar_fotos(reg, user, arquivos, origem_do_usuario(user), RegistroFoto.Tipo.DEPOIS)
        auditar(request, "ambiente_registro", reg.pk, "CRIAR", "", None, f"{len(fotos)} foto(s)")
        notificar_permissao("ambientes.validar", "LIMPEZA_VALIDAR", f"Limpeza aguardando validação — {ambiente}",
                            reg.equipe_nomes(), reverse("ambientes:validar", args=[reg.pk]),
                            f"amb-validar-{reg.pk}", excluir=user)
    return reg


def validar(registro, user, resultado, observacao, arquivos, publicar_fotos=False, request=None):
    if not user.pode("ambientes.validar"):
        raise PermissionDenied
    if registro.status != Registro.Status.AGUARDANDO_VALIDACAO:
        raise RegraNegocio("Este registro já foi analisado.")
    if registro.asg_id == user.pk and not user.is_ti:
        raise RegraNegocio("Quem lançou o registro não pode validá-lo.")
    if resultado not in ("APROVADA", "REPROVADA", "CORRECAO_SOLICITADA"):
        raise RegraNegocio("Resultado inválido.")
    observacao = (observacao or "").strip()
    if resultado != "APROVADA" and not observacao:
        raise RegraNegocio("Explique o motivo da reprovação ou da correção.")
    arquivos = [a for a in arquivos if a]
    if resultado == "APROVADA" and registro.ambiente.exige_foto_supervisor and not arquivos \
            and not registro.fotos.filter(origem__in=["SUPERVISOR", "GESTOR"]).exists():
        raise RegraNegocio("Este ambiente exige foto de inspeção do supervisor para aprovar.")
    pode_publicar = publicar_fotos and registro.ambiente.exibir_fotos and user.pode("ambientes.publicar_fotos")
    with transaction.atomic():
        salvar_fotos(registro, user, arquivos, origem_do_usuario(user), RegistroFoto.Tipo.INSPECAO)
        Validacao.objects.create(registro=registro, supervisor=user, resultado=resultado, observacao=observacao)
        registro.status = {"APROVADA": Registro.Status.VALIDADA, "REPROVADA": Registro.Status.REPROVADA,
                           "CORRECAO_SOLICITADA": Registro.Status.CORRECAO_SOLICITADA}[resultado]
        registro.supervisor, registro.validado_em, registro.observacao_supervisor = user, timezone.now(), observacao
        registro.save()
        if pode_publicar and resultado == "APROVADA":
            registro.fotos.update(publica=True)
        if resultado == "APROVADA" and registro.ordem_id:
            _encaminhar_ordem(registro, user)
        auditar(request, "ambiente_registro", registro.pk, "VALIDAR", "status", "AGUARDANDO_VALIDACAO", registro.status)
        notificar(registro.asg, "LIMPEZA_VALIDADA", f"Registro {registro.get_status_display().lower()} — {registro.ambiente}",
                  observacao, reverse("ambientes:registro", args=[registro.ambiente.token_publico]),
                  f"amb-res-{registro.pk}")


def _encaminhar_ordem(registro, user):
    """Copia as fotos para a OS/OL vinculada e pede o encerramento ao gestor (sem duplicar arquivos)."""
    ordem = registro.ordem
    for foto in registro.fotos.all():
        Anexo.objects.create(
            ordem=ordem, usuario=foto.usuario, tipo=Anexo.Tipo.FOTO_DEPOIS, origem=Anexo.Origem.AMBIENTE,
            descricao=f"Registro de limpeza #{registro.pk} ({foto.get_tipo_display()})",
            arquivo=foto.arquivo.name, miniatura=foto.miniatura.name, nome_original=foto.nome_original, mime=foto.mime,
            tamanho=foto.tamanho, tamanho_original=foto.tamanho_original, largura=foto.largura, altura=foto.altura,
            sha256=foto.sha256,
        )
    ordem.solicitacao_encerramento = True
    ordem.solicitacao_encerramento_em = timezone.now()
    ordem.solicitacao_encerramento_obs = f"Limpeza validada pelo QR ({registro.ambiente}). {registro.observacao_supervisor}".strip()
    ordem.save(update_fields=["solicitacao_encerramento", "solicitacao_encerramento_em",
                              "solicitacao_encerramento_obs", "atualizado_em"])
    historico(ordem, user, f"Registro de limpeza #{registro.pk} validado; fotos copiadas e encerramento solicitado.")
    notificar(ordem.gestor, "ENCERRAMENTO_SOLICITADO", f"Encerramento solicitado — {ordem.numero}",
              ordem.solicitacao_encerramento_obs, reverse("ordens:detalhe", args=[ordem.pk]), f"amb-os-{registro.pk}")
