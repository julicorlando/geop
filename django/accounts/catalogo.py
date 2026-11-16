"""Catálogo de permissões e perfis padrão do GEOP.

As regras reproduzem o comportamento do PHP (que misturava `usuario_pode()` com
listas fixas de perfis), mas agora tudo é configurável pela tela de Perfis.
"""

PERMISSOES = [
    # (codigo, nome, modulo)
    ("dashboard.visualizar", "Visualizar painel", "Painel"),
    ("os.criar", "Abrir ordens", "Ordens"),
    ("os.visualizar_todas", "Visualizar todas as ordens", "Ordens"),
    ("os.gerenciar", "Editar, atribuir, aprovar e encerrar ordens", "Ordens"),
    ("os.executar", "Executar ordens atribuídas", "Ordens"),
    ("os.reabrir", "Solicitar/usar token de reabertura", "Ordens"),
    ("os.excluir_solicitar", "Solicitar exclusão de ordens", "Ordens"),
    ("os.excluir_analisar", "Analisar solicitações de exclusão", "Ordens"),
    ("relatorios.visualizar", "Visualizar relatórios", "Relatórios"),
    ("usuarios.gerenciar", "Gerenciar usuários", "Administração"),
    ("perfis.gerenciar", "Gerenciar perfis e permissões", "Administração"),
    ("cadastros.gerenciar", "Gerenciar locais, categorias e setores", "Administração"),
    ("auditoria.visualizar", "Visualizar auditoria", "Administração"),
    ("patrimonio.consultar", "Consultar patrimônio", "Patrimônio"),
    ("patrimonio.vincular", "Vincular/desvincular patrimônio na OS", "Patrimônio"),
    ("limpeza.acessar", "Acessar módulo Limpeza", "Limpeza"),
    ("limpeza.organograma", "Gerenciar organograma da limpeza", "Limpeza"),
    ("limpeza.plano", "Gerenciar plano de tarefas da limpeza", "Limpeza"),
    ("ambientes.visualizar", "Visualizar ambientes QR", "Ambientes"),
    ("ambientes.gerenciar", "Gerenciar ambientes e ações", "Ambientes"),
    ("ambientes.gerar_qr", "Gerar e imprimir QR Codes", "Ambientes"),
    ("ambientes.registrar", "Registrar limpeza pelo QR", "Ambientes"),
    ("ambientes.validar", "Validar registros de limpeza", "Ambientes"),
    ("ambientes.publicar_fotos", "Publicar fotos no QR público", "Ambientes"),
]

_GESTAO = [
    "dashboard.visualizar", "os.criar", "os.visualizar_todas", "os.gerenciar", "os.reabrir",
    "os.excluir_solicitar", "os.excluir_analisar", "relatorios.visualizar", "patrimonio.consultar",
    "patrimonio.vincular", "limpeza.acessar", "limpeza.organograma", "limpeza.plano",
    "ambientes.visualizar", "ambientes.gerenciar", "ambientes.gerar_qr", "ambientes.validar",
    "ambientes.relatorios", "ambientes.publicar_fotos",
]

PERFIS_PADRAO = {
    # codigo: (nome, descricao, administrativo, permissões)
    "TI": ("TI", "Administração integral", True, []),
    "GESTOR": ("Gestor", "Gestão operacional", False, _GESTAO),
    "GESTOR_SINDICO": ("Gestor / Síndico", "Gestão operacional", False, [p for p in _GESTAO if p != "os.criar"]),
    "AGENTE": ("Agente", "Execução de ordens vinculadas", False,
               ["dashboard.visualizar", "os.executar", "os.reabrir", "relatorios.visualizar",
                "patrimonio.consultar", "patrimonio.vincular"]),
    "COORDENADOR_OPERACOES_OBRAS": ("Coordenador de operações e obras", "Coordenação operacional", False,
                                    ["dashboard.visualizar", "os.criar", "os.visualizar_todas", "os.executar",
                                     "os.reabrir", "relatorios.visualizar", "patrimonio.consultar",
                                     "patrimonio.vincular"]),
    "DIRETOR_EXECUTIVO": ("Diretor executivo", "Visão executiva", False,
                          ["dashboard.visualizar", "os.visualizar_todas", "relatorios.visualizar",
                           "ambientes.visualizar"]),
    "CFTV": ("CFTV", "Operação CFTV", False, ["dashboard.visualizar"]),
    "CONTROLE_ACESSO": ("Controle de acesso", "Controle de acesso", False, ["dashboard.visualizar"]),
    "SUPERVISOR_ASG": ("Supervisor de Serviços Gerais", "Supervisão da limpeza", False,
                       ["dashboard.visualizar", "limpeza.acessar", "ambientes.visualizar", "ambientes.registrar",
                        "ambientes.validar", "ambientes.publicar_fotos"]),
    "ASG": ("Auxiliar de Serviços Gerais", "Execução da limpeza", False,
            ["dashboard.visualizar", "ambientes.registrar"]),
}

# "ambientes.relatorios" aparece na lista de gestão; garante que exista no catálogo.
PERMISSOES.append(("ambientes.relatorios", "Relatórios de ambientes", "Ambientes"))


def sincronizar(Permissao, Perfil, sobrescrever=False):
    """Cria permissões e perfis padrão. Não remove nada já configurado."""
    por_codigo = {}
    for codigo, nome, modulo in PERMISSOES:
        obj, _ = Permissao.objects.update_or_create(codigo=codigo, defaults={"nome": nome, "modulo": modulo})
        por_codigo[codigo] = obj
    for codigo, (nome, descricao, admin, perms) in PERFIS_PADRAO.items():
        perfil, criado = Perfil.objects.get_or_create(
            codigo=codigo,
            defaults={"nome": nome, "descricao": descricao, "administrativo": admin, "sistema": True},
        )
        if criado or sobrescrever:
            perfil.permissoes.set([por_codigo[p] for p in perms if p in por_codigo])
    return por_codigo
