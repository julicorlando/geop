<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();
$user=current_user();
if(strtoupper((string)($user['perfil']??''))!=='TI'){
    http_response_code(403); die('Acesso negado.');
}

function v10_ident(string $value): string { return preg_replace('/[^A-Za-z0-9_]/', '', $value); }
function v10_col(PDO $pdo,string $table,string $col): bool {
    $table = v10_ident($table);
    if ($table === '') return false;
    $st = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    foreach (($st->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        if (strcasecmp((string)($row['Field'] ?? ''), $col) === 0) return true;
    }
    return false;
}
function v10_idx(PDO $pdo,string $table,string $idx): bool { $st=$pdo->query("SHOW INDEX FROM `$table`");foreach($st->fetchAll(PDO::FETCH_ASSOC)?:[] as $r){if(($r['Key_name']??'')===$idx)return true;}return false; }
$logs=[];$erro='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    try{
        if(!v10_col($pdo,'ordens_servico','tipo_ordem')){$pdo->exec("ALTER TABLE ordens_servico ADD COLUMN tipo_ordem VARCHAR(2) NOT NULL DEFAULT 'OS' AFTER numero_os");$logs[]='Campo tipo_ordem criado.';}else $logs[]='Campo tipo_ordem já existe.';
        if(!v10_idx($pdo,'ordens_servico','idx_ordens_tipo_status')){$pdo->exec("ALTER TABLE ordens_servico ADD INDEX idx_ordens_tipo_status (tipo_ordem,status)");$logs[]='Índice OS/OL criado.';}else $logs[]='Índice OS/OL já existe.';
        $pdo->exec("CREATE TABLE IF NOT EXISTS ol_numero_sequences (ano INT NOT NULL PRIMARY KEY,ultimo_numero INT NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $logs[]='Sequência de OL pronta.';
        $pdo->exec("CREATE TABLE IF NOT EXISTS ordem_checklist_itens (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,ordem_servico_id INT NOT NULL,tarefa_plano_id BIGINT UNSIGNED NULL,descricao VARCHAR(255) NOT NULL,obrigatorio TINYINT(1) NOT NULL DEFAULT 1,concluido TINYINT(1) NOT NULL DEFAULT 0,concluido_por INT NULL,concluido_em DATETIME NULL,observacao VARCHAR(255) NULL,ordem INT NOT NULL DEFAULT 0,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(id),KEY idx_ordem_checklist_os(ordem_servico_id,ordem),CONSTRAINT fk_ordem_checklist_os FOREIGN KEY(ordem_servico_id) REFERENCES ordens_servico(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $logs[]='Checklist unificado pronto.';
        $pdo->exec("CREATE TABLE IF NOT EXISTS ordem_limpeza_participantes (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,ordem_servico_id INT NOT NULL,membro_id BIGINT UNSIGNED NOT NULL,papel VARCHAR(20) NOT NULL DEFAULT 'ASG',principal TINYINT(1) NOT NULL DEFAULT 0,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(id),UNIQUE KEY uq_ordem_limpeza_participante(ordem_servico_id,membro_id),KEY idx_ordem_limpeza_papel(ordem_servico_id,papel),CONSTRAINT fk_ordem_limpeza_part_os FOREIGN KEY(ordem_servico_id) REFERENCES ordens_servico(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $logs[]='Equipe ASG e supervisor prontos nas OL unificadas.';
        $pdo->exec("CREATE TABLE IF NOT EXISTS ol_unificacao_migracoes (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,ordem_limpeza_id BIGINT UNSIGNED NOT NULL,ordem_servico_id INT NOT NULL,migrado_por INT NOT NULL,criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(id),UNIQUE KEY uq_ol_unificacao_origem(ordem_limpeza_id),UNIQUE KEY uq_ol_unificacao_destino(ordem_servico_id),KEY idx_ol_unificacao_usuario(migrado_por)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $logs[]='Controle de migração de OL legada pronto.';
        $pdo->exec("UPDATE ordens_servico os JOIN categorias c ON c.id=os.categoria_id SET os.tipo_ordem='OL' WHERE LOWER(TRIM(c.nome))='limpeza'");
        $logs[]='Ordens existentes da categoria Limpeza classificadas como OL (números antigos não foram renumerados).';
    }catch(Throwable $e){$erro=$e->getMessage();}
}
render_header('Atualização OS / OL V10');
?>
<h1 class="h3">Atualização OS / OL V10</h1>
<p class="text-muted">Unifica Ordens de Serviço e Ordens de Limpeza no mesmo ambiente. Não utiliza information_schema.</p>
<?php if($erro):?><div class="alert alert-danger"><?=e($erro)?></div><?php endif;?>
<?php foreach($logs as $l):?><div class="alert alert-success py-2"><?=e($l)?></div><?php endforeach;?>
<div class="card p-3" style="max-width:720px"><p>Faça backup antes de executar. A atualização não apaga as OL antigas; elas poderão ser migradas individualmente pelo TI.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="btn btn-primary">Ativar Ordens unificadas V10</button></form></div>
<?php render_footer(); ?>
