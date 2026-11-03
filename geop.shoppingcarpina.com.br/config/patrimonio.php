<?php
declare(strict_types=1);
require_once __DIR__ . '/env.php';

function patrimonio_config(): array {
    // Mapeamento confirmado no dump shoppingcarpinac_tickets.
    // Campos inexistentes no banco externo ficam deliberadamente ausentes.
    $defaults = [
        'id' => 'id',
        'codigo_barras' => 'tomb_number',
        'numero_patrimonio' => 'tomb_number',
        'nome' => 'model',
        'descricao' => 'notes',
        'categoria' => 'item_id',
        'marca' => 'brand',
        'modelo' => 'model',
        'serie' => 'serial_number',
        'setor' => 'department_id',
        'responsavel' => 'employee_id',
        'situacao' => 'asset_status',
        'aquisicao' => 'nfe_date',
        'observacoes' => 'notes',
    ];

    $fields = [];
    foreach ($defaults as $key => $defaultColumn) {
        $configured = getenv('PATRIMONIO_FIELD_' . strtoupper($key));
        $value = trim((string)($configured !== false ? $configured : $defaultColumn));
        if ($value !== '') {
            $fields[$key] = $value;
        }
    }

    $enabledSetting = getenv('PATRIMONIO_DB_ENABLED');
    $hasConnectionSettings = getenv('PATRIMONIO_DB_HOST') !== false
        && getenv('PATRIMONIO_DB_NAME') !== false
        && getenv('PATRIMONIO_DB_USER') !== false
        && getenv('PATRIMONIO_DB_PASSWORD') !== false;

    return [
        // Uma definição explícita false sempre desabilita. Se a flag estiver
        // ausente, credenciais externas completas habilitam a integração.
        'enabled' => $enabledSetting !== false
            ? filter_var($enabledSetting, FILTER_VALIDATE_BOOL)
            : $hasConnectionSettings,
        'driver' => getenv('PATRIMONIO_DB_DRIVER') ?: 'mysql',
        'host' => getenv('PATRIMONIO_DB_HOST') ?: '',
        'port' => (int)(getenv('PATRIMONIO_DB_PORT') ?: 3306),
        'name' => getenv('PATRIMONIO_DB_NAME') ?: '',
        'user' => getenv('PATRIMONIO_DB_USER') ?: '',
        'password' => getenv('PATRIMONIO_DB_PASSWORD'),
        'charset' => getenv('PATRIMONIO_DB_CHARSET') ?: 'utf8mb4',
        'table' => getenv('PATRIMONIO_DB_TABLE') ?: 'assets',
        'fields' => $fields,
                'responsavel_relation' => [
            'table' => getenv('PATRIMONIO_USERS_TABLE') ?: 'users',
            'id' => getenv('PATRIMONIO_USERS_FIELD_ID') ?: 'id',
            'name' => getenv('PATRIMONIO_USERS_FIELD_NAME') ?: 'nome',
        ],
    ];
}

function patrimonio_identifier(string $name): string {
    if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$name))throw new RuntimeException('Mapeamento externo inválido.');
    return '`'.$name.'`';
}

function patrimonio_pdo(): PDO {
    static $pdo=null;if($pdo instanceof PDO)return $pdo;$c=patrimonio_config();
    if(!$c['enabled'])throw new RuntimeException('A consulta de patrimônio não está habilitada.');
    if($c['driver']!=='mysql'||$c['host']===''||$c['name']===''||$c['user']===''||$c['password']===false||$c['table']==='')throw new RuntimeException('A integração de patrimônio ainda não foi configurada.');
    try{$pdo=new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}",$c['user'],(string)$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3]);return $pdo;}
    catch(Throwable $e){error_log('Banco externo de patrimonio indisponivel ['.$e->getCode().'].');throw new RuntimeException('O sistema de patrimônio está indisponível. Tente novamente.');}
}
