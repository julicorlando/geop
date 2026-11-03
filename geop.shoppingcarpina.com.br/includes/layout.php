<?php require_once __DIR__ . '/helpers.php'; ?>
<?php require_once __DIR__ . '/exclusao_ordens.php'; ?>
<?php require_once __DIR__ . '/gestao_integrada.php'; ?>
<?php require_once __DIR__ . '/geop_v8.php'; ?>
<?php require_once __DIR__ . '/geop_ai.php'; ?>
<?php require_once __DIR__ . '/limpeza.php'; ?>
<?php require_once __DIR__ . '/ordens_limpeza.php'; ?>
<?php function render_header($title = 'Sistema OS') {
    $user = current_user();
    $centralNotificacoes = [];
    $totalNotificacoes = 0;
    $notificationCursor = 0;
    if ($user && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        geop_v8_boot($GLOBALS['pdo']);
        gestao_processar_automacoes($GLOBALS['pdo']);
        geop_v8_notify_operations($GLOBALS['pdo']);
        $centralNotificacoes = gestao_notificacoes_usuario($GLOBALS['pdo'], (int)$user['id'], 8);
        foreach ($centralNotificacoes as $notificacao) {
            if (empty($notificacao['lida_em'])) $totalNotificacoes++;
            $notificationCursor = max($notificationCursor, (int)($notificacao['id'] ?? 0));
        }
        try {
            $notificationSummary = gestao_notificacoes_resumo_usuario($GLOBALS['pdo'], (int)$user['id']);
            $notificationCursor = (int)($notificationSummary['max_id'] ?? $notificationCursor);
            $totalNotificacoes = (int)($notificationSummary['unread'] ?? $totalNotificacoes);
        } catch (Throwable $e) {}
    }

    $currentPage = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));
    $currentQuery = $_GET;
    $perfil = strtoupper((string)($user['perfil'] ?? ''));

    $itemActive = static function (array $item) use ($currentPage, $currentQuery): bool {
        if (!empty($item['active_pages']) && in_array($currentPage, (array)$item['active_pages'], true)) {
            return true;
        }
        if (empty($item['path'])) return false;

        $clean = app_clean_internal_path((string)$item['path']);
        $parts = parse_url('/' . ltrim($clean, '/'));
        $itemPage = basename((string)($parts['path'] ?? ''));
        if ($itemPage !== $currentPage) return false;

        $expected = [];
        if (!empty($parts['query'])) parse_str((string)$parts['query'], $expected);
        if (array_key_exists('match_query', $item)) $expected = (array)$item['match_query'];

        foreach ($expected as $key => $value) {
            $actual = $currentQuery[$key] ?? null;
            if ($value === null || $value === '') {
                if ($actual !== null && $actual !== '') return false;
            } elseif ((string)$actual !== (string)$value) {
                return false;
            }
        }
        return true;
    };

    $navLink = static function (string $label, string $path, array $activePages = []) use ($currentPage): string {
        $targetPage = basename((string)parse_url('/' . app_clean_internal_path($path), PHP_URL_PATH));
        $active = in_array($currentPage, $activePages ?: [$targetPage], true);
        return '<li class="nav-item"><a class="nav-link' . ($active ? ' active' : '') . '"' . ($active ? ' aria-current="page"' : '') . ' href="' . e(app_path($path)) . '">' . e($label) . '</a></li>';
    };

    $menuDropdown = static function (string $label, array $sections, array $activePages = []) use ($currentPage, $itemActive): string {
        $visibleSections = [];
        $hasActiveItem = false;
        foreach ($sections as $section) {
            $items = [];
            foreach ((array)($section['items'] ?? []) as $item) {
                if (array_key_exists('visible', $item) && !$item['visible']) continue;
                $items[] = $item;
                if ($itemActive($item)) $hasActiveItem = true;
            }
            if ($items) {
                $section['items'] = $items;
                $visibleSections[] = $section;
            }
        }
        if (!$visibleSections) return '';

        $active = $hasActiveItem || in_array($currentPage, $activePages, true);
        $id = 'menu-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($label));
        $html = '<li class="nav-item dropdown">';
        $html .= '<a id="' . e($id) . '" class="nav-link dropdown-toggle' . ($active ? ' active' : '') . '" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">' . e($label) . '</a>';
        $html .= '<ul class="dropdown-menu geop-module-menu" aria-labelledby="' . e($id) . '">';
        foreach ($visibleSections as $sectionIndex => $section) {
            if ($sectionIndex > 0) $html .= '<li><hr class="dropdown-divider"></li>';
            if (!empty($section['title'])) $html .= '<li><h6 class="dropdown-header">' . e($section['title']) . '</h6></li>';
            foreach ($section['items'] as $item) {
                $activeItem = $itemActive($item);
                $description = trim((string)($item['description'] ?? ''));
                $html .= '<li><a class="dropdown-item' . ($activeItem ? ' active' : '') . '"' . ($activeItem ? ' aria-current="page"' : '') . ' href="' . e(app_path((string)$item['path'])) . '">';
                $html .= '<span class="geop-menu-label">' . e((string)$item['label']) . '</span>';
                if ($description !== '') $html .= '<small class="geop-menu-description">' . e($description) . '</small>';
                $html .= '</a></li>';
            }
        }
        $html .= '</ul></li>';
        return $html;
    };

    $canViewOrders = in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','AGENTE','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true);
    $canCftvLiberacoes = in_array($perfil, ['TI','CFTV','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true);
    $canCftvReports = in_array($perfil, ['TI','CFTV'], true);
    $canCftvAccess = in_array($perfil, ['TI','CFTV','CONTROLE_ACESSO'], true);
    $canCftvApproved = in_array($perfil, ['TI','CFTV','CONTROLE_ACESSO','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true);
    $canCftvHistory = in_array($perfil, ['TI','CFTV','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true);
    $canGeneralReport = in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','AGENTE','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true);

    $ordensSections = [
        [
            'title' => 'Consulta',
            'items' => [
                ['label' => 'Visualizar ordens', 'path' => 'ordens', 'description' => 'Listagem, filtros e acompanhamento', 'visible' => $canViewOrders, 'active_pages' => ['ordens.php','ordem_detalhe.php']],
                ['label' => 'Solicitações de exclusão', 'path' => 'exclusoes_ordens', 'description' => 'Aprovar, reprovar e executar exclusões', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO'], true), 'active_pages' => ['exclusoes_ordens.php']],
                ['label' => 'Lixeira de ordens', 'path' => 'lixeira_ordens', 'description' => 'Restaurar ordens excluídas', 'visible' => $perfil === 'TI', 'active_pages' => ['lixeira_ordens.php']],
            ],
        ],
        [
            'title' => 'Criar',
            'items' => [
                ['label' => 'Nova OS / OL', 'path' => 'ordem_form', 'description' => 'A categoria define automaticamente OS ou OL', 'visible' => can_create_os()],
                ['label' => 'Serviço essencial', 'path' => 'nova_os_essencial', 'description' => 'Registro rápido de serviço essencial', 'visible' => can_create_os()],
            ],
        ],
    ];

    $limpezaSections = [
        [
            'title' => 'Visão geral',
            'items' => [
                ['label' => 'Painel da limpeza', 'path' => 'limpeza', 'description' => 'Indicadores, pendências e execução', 'visible' => function_exists('limpeza_access_allowed') && limpeza_access_allowed()],
            ],
        ],
        [
            'title' => 'Ordens de limpeza legadas',
            'items' => [
                ['label' => 'Migrar OL antigas', 'path' => 'ol', 'description' => 'Somente TI: migrar OL antigas para o ambiente unificado de Ordens', 'visible' => $perfil === 'TI', 'active_pages' => ['ol.php','ol_detalhe.php']],
            ],
        ],
        [
            'title' => 'Planejamento',
            'items' => [
                ['label' => 'Cronograma de limpeza', 'path' => 'limpeza_cronograma', 'description' => 'Atividades, datas e equipes', 'visible' => function_exists('limpeza_access_allowed') && limpeza_access_allowed() && usuario_pode('limpeza.cronograma')],
                ['label' => 'Organograma da limpeza', 'path' => 'limpeza_organograma', 'description' => 'Colaboradores, cargos e hierarquia', 'visible' => function_exists('limpeza_access_allowed') && limpeza_access_allowed() && usuario_pode('limpeza.organograma')],
            ],
        ],
        [
            'title' => 'Execução e controle',
            'items' => [
                ['label' => 'Registrar limpeza', 'path' => 'ambiente_registro', 'description' => 'Lançar serviço e equipe executora', 'visible' => function_exists('limpeza_access_allowed') && limpeza_access_allowed() && usuario_pode('ambientes.registrar')],
                ['label' => usuario_pode('ambientes.gerenciar') ? 'Ambientes e QR Codes' : 'Ambientes', 'path' => 'ambientes', 'description' => 'Cadastro, histórico e QR Code', 'visible' => function_exists('limpeza_access_allowed') && limpeza_access_allowed() && usuario_pode('ambientes.visualizar')],
                ['label' => 'Validar limpezas', 'path' => 'ambiente_validar', 'description' => 'Aprovar, reprovar e anexar evidências', 'visible' => function_exists('limpeza_access_allowed') && limpeza_access_allowed() && usuario_pode('ambientes.validar')],
                ['label' => 'Relatórios de limpeza', 'path' => 'ambiente_relatorios', 'description' => 'Histórico por ambiente e responsável', 'visible' => function_exists('limpeza_access_allowed') && limpeza_access_allowed() && usuario_pode('ambientes.relatorios')],
            ],
        ],
    ];

    $cftvSections = [
        [
            'title' => 'Liberações',
            'items' => [
                ['label' => 'Solicitações de liberação', 'path' => 'cftv?view=liberacoes', 'description' => 'Pendentes, aprovações e links públicos', 'visible' => $canCftvLiberacoes, 'match_query' => ['view' => 'liberacoes']],
                ['label' => 'Liberações ativas', 'path' => 'liberacoes_aprovadas', 'description' => 'Autorizações aprovadas e válidas para acesso', 'visible' => $canCftvApproved],
                ['label' => 'Controle de acesso', 'path' => 'controle_acesso', 'description' => 'Leitura de QR Code, entrada e saída', 'visible' => $canCftvAccess],
                ['label' => 'Histórico de acessos', 'path' => 'cftv_historico_acessos', 'description' => 'Base histórica e auditoria', 'visible' => $canCftvHistory],
            ],
        ],
        [
            'title' => 'Monitoramento CFTV',
            'items' => [
                ['label' => 'Relatórios', 'path' => 'cftv', 'description' => 'Plantões, rondas e registros operacionais', 'visible' => $canCftvReports, 'match_query' => ['view' => null, 'filtro' => null]],
                ['label' => 'Câmeras OFF', 'path' => 'cftv?filtro=cameras_off', 'description' => 'Relatórios com câmeras offline', 'visible' => $canCftvReports, 'match_query' => ['filtro' => 'cameras_off']],
                ['label' => 'Ocorrências', 'path' => 'cftv?filtro=ocorrencias', 'description' => 'Relatórios que possuem ocorrências', 'visible' => $canCftvReports, 'match_query' => ['filtro' => 'ocorrencias']],
                ['label' => 'Relatório geral CFTV', 'path' => 'cftv?view=geral', 'description' => 'Consolidado do período', 'visible' => $perfil === 'TI', 'match_query' => ['view' => 'geral']],
            ],
        ],
        [
            'title' => 'Configuração CFTV',
            'items' => [
                ['label' => 'Lojas e destinos', 'path' => 'cftv_destinos', 'description' => 'Destinos usados nas liberações', 'visible' => $perfil === 'TI'],
            ],
        ],
    ];

    $operacoesSections = [
        [
            'title' => 'Rotina operacional',
            'items' => [
                ['label' => 'Minha rota', 'path' => 'agente', 'description' => 'Ordens atribuídas e execução em campo', 'visible' => in_array($perfil, ['AGENTE','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Cronograma de agentes', 'path' => 'cronograma_agentes', 'description' => 'Escala e distribuição dos agentes', 'visible' => in_array($perfil, ['TI','GESTOR'], true)],
                ['label' => 'Manutenções preventivas', 'path' => 'preventivas', 'description' => 'Planos e recorrências preventivas', 'visible' => in_array($perfil, ['TI','GESTOR'], true)],
                ['label' => 'Estoque', 'path' => 'estoque', 'description' => 'Materiais, movimentações e saldos', 'visible' => in_array($perfil, ['TI','GESTOR'], true)],
                ['label' => 'Gestão integrada', 'path' => 'gestao', 'description' => 'Indicadores, automações e acompanhamento', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Central de aprovações', 'path' => 'central_aprovacoes', 'description' => 'Exclusões, reaberturas, estoque e compras', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Dashboard financeiro', 'path' => 'dashboard_financeiro', 'description' => 'Custos, orçamento e concentração', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Custos por OS', 'path' => 'custos_os', 'description' => 'Fornecedor, NF e custo final', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Compras', 'path' => 'compras', 'description' => 'Solicitação, aprovação, compra e recebimento', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','AGENTE','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Reincidências', 'path' => 'reincidencias', 'description' => 'Falhas repetidas e custo acumulado', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Relatório mensal', 'path' => 'relatorio_mensal', 'description' => 'Resumo operacional e financeiro', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'IA assistiva', 'path' => 'ia_assistente', 'description' => 'Sugestões, materiais e duplicidades', 'visible' => !in_array($perfil, ['CFTV','CONTROLE_ACESSO','ASG'], true)],
                ['label' => 'GEOP IA Chat', 'path' => 'ia_chat', 'description' => 'Pergunte sobre OS, custos, estoque e patrimônio', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','AGENTE','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Alertas operacionais', 'path' => 'operacional_alertas', 'description' => 'Pendências e alertas da operação', 'visible' => !in_array($perfil, ['CFTV','CONTROLE_ACESSO','ASG','SUPERVISOR_ASG'], true)],
            ],
        ],
        [
            'title' => 'Consulta e apoio',
            'items' => [
                ['label' => 'Base de conhecimento', 'path' => 'conhecimento', 'description' => 'Procedimentos e soluções documentadas', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO','AGENTE','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true)],
                ['label' => 'Consultar patrimônio', 'path' => 'patrimonios', 'description' => 'Pesquisa por código, número ou descrição', 'visible' => usuario_pode('patrimonio.consultar')],
                ['label' => 'Módulos operacionais', 'path' => 'modulos', 'description' => 'Visão das estruturas implantadas', 'visible' => $perfil === 'TI'],
            ],
        ],
    ];

    $adminSections = [
        [
            'title' => 'Usuários e acesso',
            'items' => [
                ['label' => 'Usuários', 'path' => 'usuarios', 'description' => 'Cadastro, status e perfis', 'visible' => can_manage_users()],
                ['label' => 'Perfis e permissões', 'path' => 'perfis', 'description' => 'Controle de acesso por função', 'visible' => $perfil === 'TI'],
                ['label' => 'Convidar usuário', 'path' => 'usuario_convite', 'description' => 'Gerar e acompanhar convites', 'visible' => can_manage_users()],
            ],
        ],
        [
            'title' => 'Configurações do sistema',
            'items' => [
                ['label' => 'Cadastros gerais', 'path' => 'cadastros', 'description' => 'Categorias, locais e catálogos', 'visible' => can_manage_catalogs()],
                ['label' => 'Token do gestor', 'path' => 'gestor_token', 'description' => 'Autorizações temporárias e segurança', 'visible' => $perfil === 'TI'],
                ['label' => 'Configurar chat', 'path' => 'chat_config', 'description' => 'Parâmetros da comunicação interna', 'visible' => $perfil === 'TI'],
                ['label' => 'Auditoria', 'path' => 'auditoria', 'description' => 'Histórico de alterações e ações', 'visible' => $perfil === 'TI'],
                ['label' => 'Checklists por categoria', 'path' => 'checklists', 'description' => 'Modelos obrigatórios para novas OS', 'visible' => in_array($perfil, ['TI','GESTOR','GESTOR_SINDICO'], true)],
                ['label' => 'Segurança', 'path' => 'seguranca', 'description' => '2FA, sessão e tentativas de login', 'visible' => is_logged_in()],
                ['label' => 'Saúde do GEOP', 'path' => 'saude_sistema', 'description' => 'Banco, disco, uploads e automações', 'visible' => $perfil === 'TI'],
                ['label' => 'Integridade de arquivos', 'path' => 'integridade_arquivos', 'description' => 'Referências quebradas e órfãos', 'visible' => $perfil === 'TI'],
                ['label' => 'Backups', 'path' => 'backups', 'description' => 'Backup SQL privado com SHA-256', 'visible' => $perfil === 'TI'],
                ['label' => 'GEOP IA — Configuração', 'path' => 'ia_config', 'description' => 'Modelo, limites e auditoria da IA', 'visible' => $perfil === 'TI'],
                ['label' => 'Atualizações', 'path' => 'atualizacoes', 'description' => 'Migrations e preflight', 'visible' => $perfil === 'TI'],
            ],
        ],
    ];
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> - Shopping Carpina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="manifest" href="<?= e(app_path('manifest.json')) ?>">
  <meta name="theme-color" content="#0b2341">
  <link rel="icon" href="<?= e(app_path('assets/icon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(app_path('assets/style.css')) ?>">
  <style>
    .geop-module-menu{min-width:21rem;max-width:24rem;max-height:calc(100vh - 88px);overflow-y:auto;padding:.5rem}
    .geop-module-menu .dropdown-header{font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;color:#6c757d;padding:.55rem .75rem .3rem}
    .geop-module-menu .dropdown-item{white-space:normal;border-radius:.55rem;padding:.55rem .75rem}
    .geop-module-menu .dropdown-item:hover,.geop-module-menu .dropdown-item:focus{background:#eef4fb}
    .geop-module-menu .dropdown-item.active{background:#0b5ed7;color:#fff}
    .geop-menu-label{display:block;font-weight:600;line-height:1.2}
    .geop-menu-description{display:block;margin-top:.18rem;line-height:1.25;color:#6c757d}
    .geop-module-menu .dropdown-item.active .geop-menu-description{color:rgba(255,255,255,.82)}
    .navbar .nav-link.active{font-weight:700}
    @media (max-width:991.98px){
      #nav{max-height:calc(100vh - 64px);overflow-y:auto;padding-bottom:1rem}
      .geop-module-menu{position:static!important;transform:none!important;min-width:0;max-width:none;max-height:none;overflow:visible;margin:.25rem 0 .6rem;border:0;border-radius:.65rem}
      .geop-module-menu .dropdown-item{padding:.65rem .8rem}
      .navbar-text.nav-user-link{display:flex!important;margin:.75rem 0;align-items:flex-start}
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= e(app_path('index')) ?>">GEOP Shopping Carpina</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Abrir menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <?php if ($user): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?= $menuDropdown('Ordens', $ordensSections, ['ordens.php','ordem_detalhe.php','ordem_form.php','nova_os_essencial.php','exclusoes_ordens.php','lixeira_ordens.php']) ?>
        <?= $menuDropdown('Limpeza', $limpezaSections, ['limpeza.php','ol.php','ol_detalhe.php','limpeza_cronograma.php','limpeza_organograma.php','ambiente_registro.php','ambientes.php','ambiente_validar.php','ambiente_relatorios.php','ambiente_qr_imprimir.php']) ?>
        <?= $menuDropdown('CFTV', $cftvSections, ['cftv.php','controle_acesso.php','liberacoes_aprovadas.php','cftv_historico_acessos.php','cftv_destinos.php','app_controle_acesso.php']) ?>
        <?= $menuDropdown('Operações', $operacoesSections, ['agente.php','cronograma_agentes.php','preventivas.php','estoque.php','gestao.php','central_aprovacoes.php','dashboard_financeiro.php','custos_os.php','compras.php','reincidencias.php','relatorio_mensal.php','ia_assistente.php','operacional_alertas.php','operacional_agente_auxiliar.php','conhecimento.php','patrimonios.php','modulos.php']) ?>
        <?= $menuDropdown('Admin', $adminSections, ['usuarios.php','perfis.php','usuario_convite.php','cadastros.php','gestor_token.php','chat_config.php','auditoria.php','checklists.php','seguranca.php','saude_sistema.php','integridade_arquivos.php','backups.php','atualizacoes.php']) ?>
        <?php if ($canGeneralReport): ?>
          <?= $navLink('Relatório geral', 'relatorios', ['relatorios.php']) ?>
        <?php endif; ?>
      </ul>
      <div class="dropdown me-2">
        <button class="btn btn-outline-light btn-sm position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificações">
          🔔
          <span id="geop-notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $totalNotificacoes ? '' : 'd-none' ?>"><?= e(min(99,$totalNotificacoes)) ?></span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-2" style="width:min(380px,92vw);max-height:420px;overflow:auto">
          <div class="d-flex justify-content-between align-items-center px-2 py-1">
            <strong>Notificações</strong><a class="small" href="<?= e(app_path('notificacoes')) ?>">Ver todas</a>
          </div>
          <div class="px-2 pb-2">
            <button type="button" id="geop-enable-alerts" class="btn btn-sm btn-primary w-100">Ativar som, vibração e notificações</button>
            <small class="text-muted d-block mt-1">No celular, permita as notificações do navegador quando solicitado.</small>
          </div>
          <?php foreach ($centralNotificacoes as $notificacao): ?>
            <a class="dropdown-item text-wrap rounded <?= empty($notificacao['lida_em']) ? 'bg-light' : '' ?>" href="<?= e(app_path('notificacoes', ['abrir'=>$notificacao['id']])) ?>">
              <strong class="d-block"><?= e($notificacao['titulo']) ?></strong>
              <small><?= e($notificacao['mensagem']) ?></small>
            </a>
          <?php endforeach; ?>
          <?php if (!$centralNotificacoes): ?><div class="text-muted small p-3 text-center">Nenhuma notificação.</div><?php endif; ?>
          <?php if ($centralNotificacoes): ?>
            <div class="border-top mt-2 pt-2">
              <form method="post" action="<?= e(app_path('notificacoes')) ?>" onsubmit="return confirm('Limpar somente as suas notificações? Esta ação não afeta outros usuários.')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="limpar">
                <button type="submit" class="btn btn-sm btn-outline-danger w-100">Limpar minhas notificações</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <a class="navbar-text nav-user-link me-lg-2 text-decoration-none text-light" href="<?= e(app_path('mudar_senha')) ?>" title="Clique para alterar sua senha">
        <span class="nav-user-name"><?= e($user['nome']) ?></span>
        <span class="nav-user-role"><?= e(profile_label($user['perfil'] ?? '')) ?></span>
      </a>
      <a class="btn btn-outline-light btn-sm" href="<?= e(app_path('logout')) ?>">Sair</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php if ($user): ?>
<script>
window.GEOP_NOTIFICATION_CONFIG = <?= json_encode([
    'api' => app_path('notificacoes_api'),
    'notificationsUrl' => app_path('notificacoes'),
    'icon' => app_path('assets/icon.svg'),
    'badge' => app_path('assets/icon.svg'),
    'userId' => (int)$user['id'],
    'cursor' => $notificationCursor,
    'interval' => 7000,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php endif; ?>
<main class="container-fluid py-4 px-3 px-md-4">
<?php
    if ($user && function_exists('render_system_side_alerts')) {
        render_system_side_alerts();
    }
?>
<?php } ?>

<?php function render_system_side_alerts(): void {
    $user = current_user();
    if (!$user) return;

    global $pdo;
    $perfil = strtoupper((string)($user['perfil'] ?? ''));
    $atribuicoesPendentes = [];
    $tokensPendentes = [];

    if (in_array($perfil, ['GESTOR','COORDENADOR_OPERACOES_OBRAS'], true) && function_exists('os_alertas_atribuicao_gestor')) {
        $atribuicoesPendentes = os_alertas_atribuicao_gestor($pdo, (int)$user['id']);
    }

    if ($perfil === 'TI' && function_exists('os_alertas_token_ti')) {
        $tokensPendentes = os_alertas_token_ti($pdo);
    }

    if (!$atribuicoesPendentes && !$tokensPendentes) return;
    ?>
    <style>
        .geop-side-alerts{position:fixed!important;right:18px;top:86px;z-index:99999!important;width:min(400px,calc(100vw - 28px));display:flex!important;flex-direction:column;gap:12px;pointer-events:auto;touch-action:none}.geop-side-alerts.is-dragging{opacity:.92;cursor:grabbing}.geop-side-alert{background:#fff;border:0;border-left:6px solid #f59e0b;border-radius:18px;box-shadow:0 18px 50px rgba(15,23,42,.22);overflow:hidden}.geop-side-alert.token{border-left-color:#dc2626}.geop-side-alert .alert-head{background:linear-gradient(135deg,#0b2341,#164b7a);color:#fff;padding:12px 14px;cursor:move;user-select:none}.geop-side-alert .alert-title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.geop-alert-controls{display:flex;gap:6px;align-items:center}.geop-alert-control{border:1px solid rgba(255,255,255,.42);background:rgba(255,255,255,.14);color:#fff;border-radius:999px;min-width:30px;height:30px;line-height:1;font-weight:800;display:inline-flex;align-items:center;justify-content:center}.geop-alert-control:hover{background:rgba(255,255,255,.25)}.geop-alert-move{font-size:16px;cursor:grab}.geop-side-alert .alert-body{padding:14px 16px;max-height:360px;overflow:auto}.geop-alert-item{border:1px solid #e5e7eb;border-radius:14px;padding:10px 12px;margin-bottom:10px;background:#f8fafc}.geop-alert-item:last-child{margin-bottom:0}.geop-pulse-dot{display:inline-block;width:10px;height:10px;border-radius:50%;background:#ef4444;margin-right:6px;box-shadow:0 0 0 0 rgba(239,68,68,.7);animation:geopPulse 1.4s infinite}@keyframes geopPulse{70%{box-shadow:0 0 0 10px rgba(239,68,68,0)}100%{box-shadow:0 0 0 0 rgba(239,68,68,0)}}@media(max-width:768px){.geop-side-alerts{right:10px;left:10px;top:74px;width:auto}}
    </style>
    <div class="geop-side-alerts no-print" id="geopSideAlerts" aria-live="polite">
        <?php if ($atribuicoesPendentes): ?>
            <div class="geop-side-alert" data-alert-box="atribuicao">
                <div class="alert-head" data-drag-handle="1">
                    <div class="alert-title-row">
                        <div>
                            <div class="fw-bold"><span class="geop-pulse-dot"></span>OS aguardando atribuição</div>
                            <div class="small opacity-75">Defina o agente principal para remover este alerta.</div>
                        </div>
                        <div class="geop-alert-controls">
                            <button type="button" class="geop-alert-control geop-alert-move" title="Clique e arraste para mover" data-drag-handle="1">↕</button>
                            <button type="button" class="geop-alert-control" title="Fechar alerta nesta tela" data-geop-alert-close="atribuicao">×</button>
                        </div>
                    </div>
                </div>
                <div class="alert-body">
                    <?php foreach ($atribuicoesPendentes as $osAlerta): ?>
                        <div class="geop-alert-item">
                            <div class="fw-semibold"><?= e($osAlerta['numero_os'] ?? ('OS #' . $osAlerta['id'])) ?> — <?= e($osAlerta['titulo'] ?? '-') ?></div>
                            <div class="small text-muted">Local: <?= e($osAlerta['local_nome'] ?? '-') ?> • Prioridade: <?= e($osAlerta['prioridade'] ?? '-') ?></div>
                            <a class="btn btn-sm btn-warning mt-2 w-100" href="<?= e(app_path('ordem_detalhe', ['id' => $osAlerta['id']])) ?>#atribuir-agente">Definir agente agora</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($tokensPendentes): ?>
            <div class="geop-side-alert token" data-alert-box="token">
                <div class="alert-head" data-drag-handle="1">
                    <div class="alert-title-row">
                        <div>
                            <div class="fw-bold"><span class="geop-pulse-dot"></span>Solicitação de token</div>
                            <div class="small opacity-75">Há pedido de token aguardando o TI.</div>
                        </div>
                        <div class="geop-alert-controls">
                            <button type="button" class="geop-alert-control geop-alert-move" title="Clique e arraste para mover" data-drag-handle="1">↕</button>
                            <button type="button" class="geop-alert-control" title="Fechar alerta nesta tela" data-geop-alert-close="token">×</button>
                        </div>
                    </div>
                </div>
                <div class="alert-body">
                    <?php foreach ($tokensPendentes as $tk): ?>
                        <div class="geop-alert-item">
                            <div class="fw-semibold"><?= e($tk['numero_os'] ?? ('OS #' . $tk['ordem_servico_id'])) ?> — <?= e($tk['titulo'] ?? '-') ?></div>
                            <div class="small text-muted">Solicitado por <?= e($tk['solicitado_por_nome'] ?: 'usuário') ?> em <?= e($tk['criado_em'] ?? '-') ?></div>
                            <?php if (!empty($tk['motivo'])): ?><div class="small mt-1"><?= e($tk['motivo']) ?></div><?php endif; ?>
                            <a class="btn btn-sm btn-danger mt-2 w-100" href="<?= e(app_path('ordem_detalhe', ['id' => $tk['ordem_servico_id']])) ?>#token-reabertura">Abrir OS e gerar token</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script>
    (function(){
        const box = document.getElementById('geopSideAlerts');
        if (!box) return;
        try {
            const saved = JSON.parse(localStorage.getItem('geopSideAlertsPosition') || 'null');
            if (saved && Number.isFinite(saved.left) && Number.isFinite(saved.top)) {
                box.style.left = Math.max(8, Math.min(saved.left, window.innerWidth - 80)) + 'px';
                box.style.top = Math.max(8, Math.min(saved.top, window.innerHeight - 80)) + 'px';
                box.style.right = 'auto';
            }
        } catch(e) {}

        box.querySelectorAll('[data-geop-alert-close]').forEach(function(btn){
            btn.addEventListener('click', function(ev){
                ev.preventDefault();
                ev.stopPropagation();
                const card = btn.closest('.geop-side-alert');
                if (card) card.style.display = 'none';
            });
        });

        let dragging = false, startX = 0, startY = 0, startLeft = 0, startTop = 0;
        function startDrag(ev){
            const target = ev.target;
            if (!target || !target.closest('[data-drag-handle]')) return;
            if (target.closest('[data-geop-alert-close]')) return;
            dragging = true;
            const point = ev.touches ? ev.touches[0] : ev;
            const rect = box.getBoundingClientRect();
            startX = point.clientX; startY = point.clientY;
            startLeft = rect.left; startTop = rect.top;
            box.classList.add('is-dragging');
            box.style.right = 'auto';
            document.addEventListener('mousemove', moveDrag);
            document.addEventListener('mouseup', stopDrag);
            document.addEventListener('touchmove', moveDrag, {passive:false});
            document.addEventListener('touchend', stopDrag);
        }
        function moveDrag(ev){
            if (!dragging) return;
            if (ev.cancelable) ev.preventDefault();
            const point = ev.touches ? ev.touches[0] : ev;
            const rect = box.getBoundingClientRect();
            let left = startLeft + (point.clientX - startX);
            let top = startTop + (point.clientY - startY);
            left = Math.max(8, Math.min(left, window.innerWidth - rect.width - 8));
            top = Math.max(8, Math.min(top, window.innerHeight - 80));
            box.style.left = left + 'px';
            box.style.top = top + 'px';
        }
        function stopDrag(){
            if (!dragging) return;
            dragging = false;
            box.classList.remove('is-dragging');
            const rect = box.getBoundingClientRect();
            try { localStorage.setItem('geopSideAlertsPosition', JSON.stringify({left: rect.left, top: rect.top})); } catch(e) {}
            document.removeEventListener('mousemove', moveDrag);
            document.removeEventListener('mouseup', stopDrag);
            document.removeEventListener('touchmove', moveDrag);
            document.removeEventListener('touchend', stopDrag);
        }
        box.addEventListener('mousedown', startDrag);
        box.addEventListener('touchstart', startDrag, {passive:true});
    })();
    </script>
    <?php
} ?>

<?php function render_footer() { ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('<?= e(app_path('sw.js')) ?>').catch(function () {});
  });
}
</script>
<?php if (function_exists('current_user') && current_user()): ?>
<script src="<?= e(app_path('assets/notificacoes-realtime.js', ['v' => '20260805-7'])) ?>" defer></script>
<?php endif; ?>
<?php
    $aiFloatingUser = function_exists('current_user') ? current_user() : null;
    if ($aiFloatingUser && function_exists('geop_ai_access_allowed')) {
        $showAiFloating = geop_ai_access_allowed($aiFloatingUser);
    } else {
        $showAiFloating = $aiFloatingUser && in_array((string)($aiFloatingUser['perfil'] ?? ''), ['TI','GESTOR','GESTOR_SINDICO','AGENTE','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'], true);
    }
?>
<?php if ($showAiFloating): ?>
<button type="button"
   id="geopAiLauncher"
   data-geop-draggable="ai"
   data-geop-open-url="<?= e(app_path('ia_chat')) ?>"
   class="btn btn-primary shadow no-print geop-floating-draggable"
   aria-label="Abrir GEOP IA"
   title="GEOP IA · clique para abrir ou arraste para mover"
   style="position:fixed;left:18px;bottom:18px;z-index:99997;border-radius:999px;padding:.72rem 1rem;touch-action:none;cursor:grab;user-select:none">
   🤖 GEOP IA
</button>
<?php endif; ?>
<?php
    $chatWidget = __DIR__ . '/../public/chat.php';
    $chatUser = function_exists('current_user') ? current_user() : null;
    $chatEnabled = true;
    if ($chatUser) {
        require_once __DIR__ . '/chat_core.php';
        $chatEnabled = chat_is_enabled($GLOBALS['pdo']);
    }

    if ($chatUser && $chatEnabled && file_exists($chatWidget)) {
        include $chatWidget;
    }
?>
<script>
(function(){
  const uid = <?= json_encode((int)(($aiFloatingUser['id'] ?? $chatUser['id'] ?? 0))) ?>;
  const items = document.querySelectorAll('[data-geop-draggable]');

  function storageKey(el){
    return 'geop-floating-pos-v1-' + uid + '-' + (el.dataset.geopDraggable || el.id || 'item');
  }

  function clamp(el, x, y){
    const rect = el.getBoundingClientRect();
    const margin = 8;
    const maxX = Math.max(margin, window.innerWidth - rect.width - margin);
    const maxY = Math.max(margin, window.innerHeight - rect.height - margin);
    return {
      x: Math.min(maxX, Math.max(margin, x)),
      y: Math.min(maxY, Math.max(margin, y))
    };
  }

  function apply(el, x, y, save){
    const pos = clamp(el, x, y);
    el.style.left = pos.x + 'px';
    el.style.top = pos.y + 'px';
    el.style.right = 'auto';
    el.style.bottom = 'auto';
    if (save) {
      try { localStorage.setItem(storageKey(el), JSON.stringify(pos)); } catch(e) {}
    }
  }

  function restore(el){
    try {
      const raw = localStorage.getItem(storageKey(el));
      if (!raw) return;
      const pos = JSON.parse(raw);
      if (Number.isFinite(pos.x) && Number.isFinite(pos.y)) apply(el, pos.x, pos.y, false);
    } catch(e) {}
  }

  items.forEach(function(el){
    restore(el);
    let drag = null;
    let suppressClick = false;

    el.addEventListener('pointerdown', function(ev){
      if (ev.button !== undefined && ev.button !== 0) return;
      const rect = el.getBoundingClientRect();
      drag = {
        pointerId: ev.pointerId,
        startX: ev.clientX,
        startY: ev.clientY,
        left: rect.left,
        top: rect.top,
        moved: false
      };
      try { el.setPointerCapture(ev.pointerId); } catch(e) {}
      el.style.cursor = 'grabbing';
    });

    el.addEventListener('pointermove', function(ev){
      if (!drag || ev.pointerId !== drag.pointerId) return;
      const dx = ev.clientX - drag.startX;
      const dy = ev.clientY - drag.startY;
      if (!drag.moved && Math.hypot(dx,dy) < 6) return;
      drag.moved = true;
      ev.preventDefault();
      apply(el, drag.left + dx, drag.top + dy, false);
    });

    function finish(ev){
      if (!drag || (ev.pointerId !== undefined && ev.pointerId !== drag.pointerId)) return;
      const moved = drag.moved;
      drag = null;
      el.style.cursor = 'grab';
      if (moved) {
        const rect = el.getBoundingClientRect();
        apply(el, rect.left, rect.top, true);
        suppressClick = true;
        setTimeout(function(){ suppressClick = false; }, 80);
      }
    }

    el.addEventListener('pointerup', finish);
    el.addEventListener('pointercancel', finish);
    el.addEventListener('click', function(ev){
      if (suppressClick) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
        return;
      }

      const openUrl = el.dataset.geopOpenUrl || '';
      if (openUrl) {
        ev.preventDefault();
        window.location.href = openUrl;
      }
    }, true);
  });

  window.addEventListener('resize', function(){
    items.forEach(function(el){
      const rect = el.getBoundingClientRect();
      apply(el, rect.left, rect.top, true);
    });
  });
})();
</script>
</body>
</html>
<?php } ?>
