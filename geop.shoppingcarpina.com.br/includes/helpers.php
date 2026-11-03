<?php
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function app_base_path(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = trim(str_replace('\\', '/', dirname($scriptName)), '/');

    // O sistema fica dentro da pasta public.
    // Subdomínio: /public/login.php
    // Teste em pasta: /geoptst/public/login.php
    if ($dir === '.' || $dir === '') {
        return '/public';
    }

    if ($dir === 'public' || substr($dir, -7) === '/public') {
        return '/' . trim($dir, '/');
    }

    return '/' . trim($dir . '/public', '/');
}

function app_clean_internal_path(string $path): string {
    $path = str_replace('\\', '/', trim($path));
    $path = ltrim($path, '/');

    $query = '';
    $hash = '';

    if (($hashPos = strpos($path, '#')) !== false) {
        $hash = substr($path, $hashPos);
        $path = substr($path, 0, $hashPos);
    }

    if (($queryPos = strpos($path, '?')) !== false) {
        $query = substr($path, $queryPos);
        $path = substr($path, 0, $queryPos);
    }

    // Evita duplicar public quando alguém chamar app_path('public/login.php')
    if (strpos($path, 'public/') === 0) {
        $path = substr($path, 7);
    }

    $path = trim($path, '/');

    if ($path === '' || $path === 'index') {
        $path = 'index.php';
    } else {
        $baseName = basename($path);
        // Se não for arquivo com extensão, assume página PHP.
        if (strpos($baseName, '.') === false) {
            $path .= '.php';
        }
    }

    return $path . $query . $hash;
}

function app_path(string $path = '', $query = null): string {
    if ($path !== '' && (preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) || strpos($path, '//') === 0 || strpos($path, '#') === 0)) {
        return $path;
    }

    $path = app_clean_internal_path($path);

    if (is_array($query) && $query) {
        $path .= (strpos($path, '?') === false ? '?' : '&') . http_build_query($query);
    } elseif (is_string($query) && $query !== '') {
        $path .= (strpos($path, '?') === false ? '?' : '&') . ltrim($query, '?&');
    }

    $base = rtrim(app_base_path(), '/');
    return $base . '/' . ltrim($path, '/');
}

function app_url(string $path = '', $query = null): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return app_path($path, $query);
    }

    $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $scheme = $https ? 'https' : 'http';

    return $scheme . '://' . $host . app_path($path, $query);
}

function badge_status($status) {
    $map = [
        'ABERTA'=>'info','ATRIBUIDA'=>'primary','EM_ANDAMENTO'=>'warning','PAUSADA'=>'secondary',
        'AGUARDANDO_MATERIAL'=>'secondary','CONCLUIDA_PELO_AGENTE'=>'success','REPROVADA'=>'danger','ENCERRADA'=>'dark'
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-'.$class.'">'.e(str_replace('_',' ', $status)).'</span>';
}

function badge_prioridade($p) {
    $map = ['BAIXA'=>'success','MEDIA'=>'info','ALTA'=>'warning','CRITICA'=>'danger'];
    $class = $map[$p] ?? 'secondary';
    return '<span class="badge text-bg-'.$class.'">'.e($p).'</span>';
}

function redirect($url) { header('Location: ' . app_path((string)$url)); exit; }

function gerar_numero_os(PDO $pdo) {
    $year = (int) date('Y');

    // A geração da sequência não depende mais de beginTransaction()/commit().
    // O LAST_INSERT_ID(expr) é atômico por conexão no MySQL/MariaDB e evita
    // "There is no active transaction" mesmo quando o servidor/hospedagem
    // realiza commits implícitos em outras rotinas do sistema.
    try {
        $stTabela = $pdo->query("SHOW TABLES LIKE 'os_numero_sequences'");
        if (!$stTabela->fetchColumn()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS os_numero_sequences (
                ano INT NOT NULL PRIMARY KEY,
                ultimo_numero INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        $st = $pdo->prepare("INSERT INTO os_numero_sequences (ano, ultimo_numero)
            VALUES (?, LAST_INSERT_ID(1))
            ON DUPLICATE KEY UPDATE ultimo_numero = LAST_INSERT_ID(ultimo_numero + 1)");
        $st->execute([$year]);

        $next = (int)$pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        if ($next <= 0) {
            $q = $pdo->prepare("SELECT ultimo_numero FROM os_numero_sequences WHERE ano=? LIMIT 1");
            $q->execute([$year]);
            $next = (int)$q->fetchColumn();
        }
        if ($next <= 0) {
            throw new RuntimeException('Nao foi possivel gerar o numero sequencial da OS.');
        }

        return sprintf('OS-%04d-%07d', $year, $next);
    } catch (Throwable $e) {
        throw $e;
    }
}

function prioridades_os(): array { return ['BAIXA', 'MEDIA', 'ALTA', 'CRITICA']; }

function validar_prioridade(string $prioridade): string {
    $prioridade = strtoupper(trim($prioridade));
    if ($prioridade === 'URGENTE') $prioridade = 'CRITICA';
    if (!in_array($prioridade, prioridades_os(), true)) {
        throw new InvalidArgumentException('Prioridade inválida.');
    }
    return $prioridade;
}


/**
 * Remove um arquivo local de uploads apenas quando nenhum outro registro do GEOP
 * ainda referencia o mesmo caminho. Evita quebrar fotos compartilhadas por OS,
 * OL migradas e histórico público de ambientes.
 */
function remover_upload_local_se_sem_referencia(PDO $pdo, string $arquivoUrl): bool {
    $arquivoUrl = trim(str_replace('\\', '/', $arquivoUrl));
    if ($arquivoUrl === '' || preg_match('/^https?:\/\//i', $arquivoUrl)) return false;

    if (strpos($arquivoUrl, '/uploads/') !== false) {
        $arquivoUrl = substr($arquivoUrl, strpos($arquivoUrl, '/uploads/') + 1);
    }
    $arquivoUrl = ltrim(preg_replace('/\?.*$/', '', $arquivoUrl), '/');
    if (!str_starts_with($arquivoUrl, 'uploads/') || str_contains($arquivoUrl, '..')) return false;

    $referencias = 0;
    foreach ([
        ['anexos_os', 'arquivo_url'],
        ['limpeza_ordem_fotos', 'arquivo_url'],
        ['ambiente_registro_fotos', 'arquivo_url'],
    ] as [$tabela, $coluna]) {
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM `{$tabela}` WHERE `{$coluna}` = ?");
            $st->execute([$arquivoUrl]);
            $referencias += (int)$st->fetchColumn();
        } catch (Throwable $e) {
            // Tabela opcional/legada inexistente: ignora sem exigir information_schema.
        }
    }
    if ($referencias > 0) return false;

    $publicDir = realpath(dirname(__DIR__) . '/public');
    $uploadsDir = realpath(dirname(__DIR__) . '/public/uploads');
    if ($publicDir === false || $uploadsDir === false) return false;

    $candidato = dirname(__DIR__) . '/public/' . $arquivoUrl;
    $pastaReal = realpath(dirname($candidato));
    if ($pastaReal === false) return false;

    $uploadsPrefix = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if ($pastaReal !== $uploadsDir && !str_starts_with($pastaReal . DIRECTORY_SEPARATOR, $uploadsPrefix)) return false;

    if (!is_file($candidato)) return true;
    return @unlink($candidato);
}

function registrar_historico(PDO $pdo, $osId, $usuarioId, $anterior, $novo, $descricao) {
    $stmt = $pdo->prepare("INSERT INTO historico_os (ordem_servico_id, usuario_id, status_anterior, status_novo, descricao) VALUES (?,?,?,?,?)");
    $stmt->execute([$osId, $usuarioId, $anterior, $novo, $descricao]);
}
