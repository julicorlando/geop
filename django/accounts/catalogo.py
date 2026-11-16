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
    ("estoque.gerenciar", "Gerenciar estoque e aprovar saldo negativo", "Estoque e compras"),
    ("compras.solicitar", "Solicitar compras", "Estoque e compras"),
    ("compras.gerenciar", "Aprovar, comprar e receber compras", "Estoque e compras"),
    ("custos.gerenciar", "Lançar custos e orçamento das OS", "Estoque e compras"),
    ("checklists.gerenciar", "Gerenciar checklists por categoria", "Ordens"),
    ("preventivas.gerenciar", "Gerenciar manutenções preventivas", "Operação"),
    ("equipe.gerenciar", "Gerenciar equipe, escalas e especialidades", "Operação"),
    ("cronograma.gerenciar", "Gerenciar cronograma dos agentes", "Operação"),
    ("aprovacoes.central", "Acessar a central de aprovações", "Operação"),
    ("conhecimento.visualizar", "Consultar base de conhecimento", "Conhecimento"),
    ("conhecimento.gerenciar", "Editar base de conhecimento", "Conhecimento"),
    ("relatorios.exportar", "Exportar relatórios (CSV/impressão)", "Relatórios"),
    ("relatorios.financeiro", "Relatórios financeiro e mensal", "Relatórios"),
    ("cftv.operar", "Operar CFTV (relatórios de plantão)", "CFTV e acesso"),
    ("cftv.liberacoes", "Gerenciar liberações e aprovar solicitações de acesso", "CFTV e acesso"),
    ("cftv.historico", "Consultar histórico de acessos", "CFTV e acesso"),
    ("controle_acesso.registrar", "Registrar entrada/saída (leitor de QR)", "CFTV e acesso"),
    ("chat.acessar", "Usar o chat interno", "Comunicação"),
    ("ia.usar", "Usar o GEOP IA", "GEOP IA"),
    ("ia.financeiro", "GEOP IA pode consultar custos", "GEOP IA"),
    ("iot.gerenciar", "Gerenciar dispositivos IoT e rede", "IoT"),
]

_GESTAO = [
    "dashboard.visualizar", "os.criar", "os.visualizar_todas", "os.gerenciar", "os.reabrir",
    "os.excluir_solicitar", "os.excluir_analisar", "relatorios.visualizar", "patrimonio.consultar",
    "patrimonio.vincular", "limpeza.acessar", "limpeza.organograma", "limpeza.plano",
    "ambientes.visualizar", "ambientes.gerenciar", "ambientes.gerar_qr", "ambientes.validar",
    "ambientes.relatorios", "ambientes.publicar_fotos", "estoque.gerenciar", "compras.solicitar",
    "compras.gerenciar", "custos.gerenciar", "checklists.gerenciar", "preventivas.gerenciar", "equipe.gerenciar",
    "cronograma.gerenciar", "aprovacoes.central", "conhecimento.visualizar", "conhecimento.gerenciar",
    "relatorios.exportar", "relatorios.financeiro", "cftv.liberacoes", "cftv.historico", "chat.acessar", "ia.usar",
    "ia.financeiro", "iot.gerenciar",
]

PERFIS_PADRAO = {
    # codigo: (nome, descricao, administrativo, permissões)
    "TI": ("TI", "Administração integral", True, []),
    "GESTOR": ("Gestor", "Gestão operacional", False, _GESTAO),
    "GESTOR_SINDICO": ("Gestor / Síndico", "Gestão operacional", False, [p for p in _GESTAO if p != "os.criar"]),
    "AGENTE": ("Agente", "Execução de ordens vinculadas", False,
               ["dashboard.visualizar", "os.executar", "os.reabrir", "relatorios.visualizar",
                "patrimonio.consultar", "patrimonio.vincular", "compras.solicitar", "conhecimento.visualizar",
                "chat.acessar", "ia.usar"]),
    "COORDENADOR_OPERACOES_OBRAS": ("Coordenador de operações e obras", "Coordenação operacional", False,
                                    ["dashboard.visualizar", "os.criar", "os.visualizar_todas", "os.executar",
                                     "os.reabrir", "relatorios.visualizar", "patrimonio.consultar",
                                     "patrimonio.vincular", "compras.solicitar", "compras.gerenciar",
                                     "custos.gerenciar", "aprovacoes.central", "equipe.gerenciar",
                                     "conhecimento.visualizar", "relatorios.exportar", "relatorios.financeiro",
                                     "cftv.liberacoes", "chat.acessar", "ia.usar"]),
    "DIRETOR_EXECUTIVO": ("Diretor executivo", "Visão executiva", False,
                          ["dashboard.visualizar", "os.visualizar_todas", "relatorios.visualizar",
                           "ambientes.visualizar", "relatorios.exportar", "relatorios.financeiro",
                           "conhecimento.visualizar", "cftv.liberacoes", "cftv.historico", "chat.acessar", "ia.usar",
                           "ia.financeiro"]),
    "CFTV": ("CFTV", "Operação CFTV", False, ["dashboard.visualizar", "cftv.operar", "cftv.historico",
                                              "controle_acesso.registrar", "chat.acessar"]),
    "CONTROLE_ACESSO": ("Controle de acesso", "Controle de acesso", False,
                        ["dashboard.visualizar", "controle_acesso.registrar", "chat.acessar"]),
    "SUPERVISOR_ASG": ("Supervisor de Serviços Gerais", "Supervisão da limpeza", False,
                       ["dashboard.visualizar", "limpeza.acessar", "ambientes.visualizar", "ambientes.registrar",
                        "ambientes.validar", "ambientes.publicar_fotos", "chat.acessar",
                        "conhecimento.visualizar"]),
    "ASG": ("Auxiliar de Serviços Gerais", "Execução da limpeza", False,
            ["dashboard.visualizar", "ambientes.registrar", "chat.acessar"]),
}

# "ambientes.relatorios" aparece na lista de gestão; garante que exista no catálogo.
PERMISSOES.append(("ambientes.relatorios", "Relatórios de ambientes", "Ambientes"))


def sincronizar(Permissao, Perfil, sobrescrever=False):
    """Cria permissões e perfis padrão. Não remove nada já configurado.

    Permissões novas (que ainda não existiam no banco) são acrescentadas aos perfis padrão
    que as preveem, para que uma atualização do GEOP já libere os módulos novos.
    """
    por_codigo, novas = {}, set()
    for codigo, nome, modulo in PERMISSOES:
        obj, criada = Permissao.objects.update_or_create(codigo=codigo, defaults={"nome": nome, "modulo": modulo})
        por_codigo[codigo] = obj
        if criada:
            novas.add(codigo)
    for codigo, (nome, descricao, admin, perms) in PERFIS_PADRAO.items():
        perfil, criado = Perfil.objects.get_or_create(
            codigo=codigo,
            defaults={"nome": nome, "descricao": descricao, "administrativo": admin, "sistema": True},
        )
        if criado or sobrescrever:
            perfil.permissoes.set([por_codigo[p] for p in perms if p in por_codigo])
        else:
            perfil.permissoes.add(*[por_codigo[p] for p in perms if p in novas])
    return por_codigo
