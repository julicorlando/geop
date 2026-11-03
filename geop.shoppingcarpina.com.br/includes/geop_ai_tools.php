<?php
declare(strict_types=1);

require_once __DIR__ . '/geop_ai.php';
require_once __DIR__ . '/exclusao_ordens.php';

function geop_ai_tool_source(string $name, string $label, ?string $detail = null): array
{
    return array_filter([
        'nome'=>$name,
        'rotulo'=>$label,
        'detalhe'=>$detail,
    ], fn($v)=>$v!==null && $v!=='');
}

function geop_ai_tool_result(string $label, array $data, array $sources, int $rows = 0, array $meta = []): array
{
    return [
        'ok'=>true,
        'resumo'=>$label,
        'linhas'=>$rows,
        'fontes'=>$sources,
        'dados'=>$data,
        'meta'=>$meta,
    ];
}

function geop_ai_limit(PDO $pdo, $value): int
{
    $max = geop_ai_int_config($pdo,'ai_chat_max_rows',50,5,100);
    $value = (int)$value;
    if ($value <= 0) $value = 20;
    return max(1,min($max,$value));
}

function geop_ai_date(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') return null;
    $d = DateTimeImmutable::createFromFormat('Y-m-d',$value);
    return $d && $d->format('Y-m-d') === $value ? $value : null;
}

function geop_ai_os_scope(array $user, string $alias, array &$where, array &$params): void
{
    if ((string)($user['perfil'] ?? '') !== 'AGENTE') return;
    $uid = (int)$user['id'];

    if (geop_v8_table_exists($GLOBALS['pdo'],'ordem_agentes')) {
        $where[] = "({$alias}.agente_id=? OR EXISTS(
            SELECT 1 FROM ordem_agentes ai_oa
            WHERE ai_oa.ordem_servico_id={$alias}.id AND ai_oa.usuario_id=?
        ))";
        $params[]=$uid;
        $params[]=$uid;
    } else {
        $where[] = "{$alias}.agente_id=?";
        $params[]=$uid;
    }
}

function geop_ai_tools_definition(array $user): array
{
    $tools = [
        [
            'type'=>'function',
            'name'=>'buscar_ordens_servico',
            'description'=>'Busca ordens de serviço do GEOP por texto, status, período, local, categoria ou agente. Use para listas e perguntas sobre OS.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'texto'=>['type'=>['string','null']],
                    'status'=>['type'=>['string','null']],
                    'data_inicio'=>['type'=>['string','null'],'description'=>'YYYY-MM-DD ou null'],
                    'data_fim'=>['type'=>['string','null'],'description'=>'YYYY-MM-DD ou null'],
                    'local_id'=>['type'=>['integer','null']],
                    'categoria_id'=>['type'=>['integer','null']],
                    'agente_id'=>['type'=>['integer','null']],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['texto','status','data_inicio','data_fim','local_id','categoria_id','agente_id','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'resumir_ordem_servico',
            'description'=>'Obtém uma OS específica com histórico recente, materiais, custos, checklist, anexos e patrimônio vinculado.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'id'=>['type'=>['integer','null']],
                    'numero_os'=>['type'=>['string','null']],
                ],
                'required'=>['id','numero_os'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'consultar_ordens_limpeza',
            'description'=>'Consulta ordens de limpeza (OL) legadas e unificadas do GEOP.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'texto'=>['type'=>['string','null']],
                    'status'=>['type'=>['string','null']],
                    'data_inicio'=>['type'=>['string','null']],
                    'data_fim'=>['type'=>['string','null']],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['texto','status','data_inicio','data_fim','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'consultar_estoque',
            'description'=>'Consulta materiais, saldos, estoque mínimo e custo unitário.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'texto'=>['type'=>['string','null']],
                    'somente_criticos'=>['type'=>'boolean'],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['texto','somente_criticos','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'consultar_patrimonio',
            'description'=>'Busca ativos/patrimônios e retorna dados cadastrais e quantidade/custo das OS vinculadas.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'texto'=>['type'=>['string','null']],
                    'somente_ativos'=>['type'=>'boolean'],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['texto','somente_ativos','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'consultar_preventivas',
            'description'=>'Consulta manutenções preventivas ativas, atrasadas ou próximas.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'situacao'=>['type'=>'string','enum'=>['ATRASADAS','PROXIMAS','ATIVAS','TODAS']],
                    'dias'=>['type'=>'integer','minimum'=>1,'maximum'=>365],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['situacao','dias','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'consultar_reincidencias',
            'description'=>'Identifica patrimônios com maior número de OS e custo acumulado em um período.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'ativo_id'=>['type'=>['integer','null']],
                    'dias'=>['type'=>'integer','minimum'=>1,'maximum'=>1825],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['ativo_id','dias','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'consultar_conhecimento',
            'description'=>'Pesquisa artigos publicados da base de conhecimento do GEOP.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'texto'=>['type'=>'string'],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>20],
                ],
                'required'=>['texto','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
        [
            'type'=>'function',
            'name'=>'consultar_indicadores_operacionais',
            'description'=>'Retorna indicadores agregados de OS no período: abertas, encerradas, SLA, custos e principais categorias/locais.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'data_inicio'=>['type'=>['string','null']],
                    'data_fim'=>['type'=>['string','null']],
                ],
                'required'=>['data_inicio','data_fim'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ],
    ];

    if (geop_ai_finance_allowed($user)) {
        $tools[] = [
            'type'=>'function',
            'name'=>'consultar_custos',
            'description'=>'Consulta e agrega custos de OS por categoria, local, mês ou lista de ordens.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'data_inicio'=>['type'=>['string','null']],
                    'data_fim'=>['type'=>['string','null']],
                    'agrupar_por'=>['type'=>'string','enum'=>['categoria','local','mes','nenhum']],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['data_inicio','data_fim','agrupar_por','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ];

        $tools[] = [
            'type'=>'function',
            'name'=>'consultar_aprovacoes_pendentes',
            'description'=>'Consulta a central de aprovações pendentes: exclusão, reabertura, estoque e compras.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>new stdClass(),
                'required'=>[],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ];

        $tools[] = [
            'type'=>'function',
            'name'=>'consultar_liberacoes',
            'description'=>'Consulta liberações de acesso por período, status ou nome/destino. Não retorna CPF completo.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'texto'=>['type'=>['string','null']],
                    'status'=>['type'=>['string','null']],
                    'data_inicio'=>['type'=>['string','null']],
                    'data_fim'=>['type'=>['string','null']],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['texto','status','data_inicio','data_fim','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ];
    }

    if (geop_ai_admin_allowed($user)) {
        $tools[] = [
            'type'=>'function',
            'name'=>'consultar_auditoria',
            'description'=>'Consulta eventos recentes de auditoria do GEOP. Uso exclusivo de TI.',
            'parameters'=>[
                'type'=>'object',
                'properties'=>[
                    'entidade'=>['type'=>['string','null']],
                    'acao'=>['type'=>['string','null']],
                    'usuario_id'=>['type'=>['integer','null']],
                    'limite'=>['type'=>'integer','minimum'=>1,'maximum'=>50],
                ],
                'required'=>['entidade','acao','usuario_id','limite'],
                'additionalProperties'=>false,
            ],
            'strict'=>true,
        ];
    }

    // Perfis operacionais não recebem ferramentas que ampliariam sua visão
    // para módulos que a interface normal do GEOP não lhes concede.
    if (!geop_ai_finance_allowed($user)) {
        $blocked = ['consultar_ordens_limpeza','consultar_preventivas','consultar_reincidencias'];
        $tools = array_values(array_filter(
            $tools,
            static fn(array $tool): bool => !in_array((string)($tool['name'] ?? ''), $blocked, true)
        ));
    }

    if (!geop_ai_patrimony_allowed($user)) {
        $tools = array_values(array_filter(
            $tools,
            static fn(array $tool): bool => (string)($tool['name'] ?? '') !== 'consultar_patrimonio'
        ));
    }

    return $tools;
}

function geop_ai_tool_execute(PDO $pdo, array $user, string $name, array $args): array
{
    return match ($name) {
        'buscar_ordens_servico' => geop_ai_tool_os($pdo,$user,$args),
        'resumir_ordem_servico' => geop_ai_tool_os_detail($pdo,$user,$args),
        'consultar_ordens_limpeza' => geop_ai_finance_allowed($user)
            ? geop_ai_tool_ol($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Seu perfil não pode consultar o conjunto geral de OL pelo GEOP IA.'],
        'consultar_estoque' => geop_ai_tool_stock($pdo,$user,$args),
        'consultar_patrimonio' => geop_ai_patrimony_allowed($user)
            ? geop_ai_tool_assets($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Seu perfil não possui permissão para consultar patrimônio.'],
        'consultar_preventivas' => geop_ai_finance_allowed($user)
            ? geop_ai_tool_preventive($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Seu perfil não pode consultar preventivas pelo GEOP IA.'],
        'consultar_reincidencias' => geop_ai_finance_allowed($user)
            ? geop_ai_tool_recurrence($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Seu perfil não pode consultar reincidências globais.'],
        'consultar_conhecimento' => geop_ai_tool_knowledge($pdo,$user,$args),
        'consultar_indicadores_operacionais' => geop_ai_tool_indicators($pdo,$user,$args),
        'consultar_custos' => geop_ai_finance_allowed($user)
            ? geop_ai_tool_costs($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Seu perfil não pode consultar consolidados financeiros.'],
        'consultar_aprovacoes_pendentes' => geop_ai_finance_allowed($user)
            ? geop_ai_tool_approvals($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Seu perfil não pode consultar a central de aprovações.'],
        'consultar_liberacoes' => geop_ai_finance_allowed($user)
            ? geop_ai_tool_access_releases($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Seu perfil não pode consultar liberações pelo GEOP IA.'],
        'consultar_auditoria' => geop_ai_admin_allowed($user)
            ? geop_ai_tool_audit($pdo,$user,$args)
            : ['ok'=>false,'erro'=>'Auditoria é restrita ao TI.'],
        default => ['ok'=>false,'erro'=>'Ferramenta não autorizada ou inexistente.'],
    };
}

function geop_ai_tool_os(PDO $pdo, array $user, array $args): array
{
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $where=[exclusao_sql_nao_excluida($pdo,'ordens_servico','os')];
    $params=[];
    geop_ai_os_scope($user,'os',$where,$params);

    $text=trim((string)($args['texto']??''));
    if($text!==''){
        $where[]='(os.numero_os LIKE ? OR os.titulo LIKE ? OR os.descricao LIKE ?)';
        array_push($params,"%{$text}%","%{$text}%","%{$text}%");
    }
    $status=trim((string)($args['status']??''));
    if($status!==''){$where[]='os.status=?';$params[]=$status;}

    $di=geop_ai_date($args['data_inicio']??null);
    $df=geop_ai_date($args['data_fim']??null);
    if($di){$where[]='DATE(os.criado_em)>=?';$params[]=$di;}
    if($df){$where[]='DATE(os.criado_em)<=?';$params[]=$df;}

    foreach(['local_id','categoria_id','agente_id'] as $field){
        $v=(int)($args[$field]??0);
        if($v>0){$where[]="os.{$field}=?";$params[]=$v;}
    }

    $sql="SELECT os.id,os.numero_os,os.tipo_ordem,os.titulo,os.prioridade,os.status,
                 os.criado_em,os.atualizado_em,os.prazo,os.sla_vencimento,
                 l.nome local_nome,c.nome categoria_nome,
                 g.nome gestor_nome,a.nome agente_nome
          FROM ordens_servico os
          JOIN locais l ON l.id=os.local_id
          JOIN categorias c ON c.id=os.categoria_id
          JOIN usuarios g ON g.id=os.gestor_id
          LEFT JOIN usuarios a ON a.id=os.agente_id
          WHERE ".implode(' AND ',$where)."
          ORDER BY os.atualizado_em DESC
          LIMIT {$limit}";
    $st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        count($rows).' OS encontrada(s).',
        $rows,
        [geop_ai_tool_source('ordens_servico','Ordens de Serviço')],
        count($rows)
    );
}

function geop_ai_tool_os_detail(PDO $pdo, array $user, array $args): array
{
    $where=[exclusao_sql_nao_excluida($pdo,'ordens_servico','os')];
    $params=[];
    geop_ai_os_scope($user,'os',$where,$params);

    $id=(int)($args['id']??0);
    $numero=trim((string)($args['numero_os']??''));
    if($id>0){$where[]='os.id=?';$params[]=$id;}
    elseif($numero!==''){$where[]='os.numero_os=?';$params[]=$numero;}
    else return ['ok'=>false,'erro'=>'Informe id ou numero_os.'];

    $st=$pdo->prepare("
        SELECT os.*,l.nome local_nome,c.nome categoria_nome,
               g.nome gestor_nome,a.nome agente_nome,
               atv.codigo_patrimonial patrimonio_codigo,
               CONCAT_WS(' ',atv.marca,atv.modelo) patrimonio_descricao
        FROM ordens_servico os
        JOIN locais l ON l.id=os.local_id
        JOIN categorias c ON c.id=os.categoria_id
        JOIN usuarios g ON g.id=os.gestor_id
        LEFT JOIN usuarios a ON a.id=os.agente_id
        LEFT JOIN ativos atv ON atv.id=os.ativo_id
        WHERE ".implode(' AND ',$where)."
        LIMIT 1
    ");
    $st->execute($params);$os=$st->fetch(PDO::FETCH_ASSOC);
    if(!$os) return ['ok'=>false,'erro'=>'OS não encontrada ou sem permissão para consulta.'];

    $osId=(int)$os['id'];

    $mat=$pdo->prepare("SELECT nome_material,quantidade,unidade,observacao,criado_em FROM materiais_os WHERE ordem_servico_id=? ORDER BY criado_em");
    $mat->execute([$osId]);$materials=$mat->fetchAll(PDO::FETCH_ASSOC);

    $hist=$pdo->prepare("
        SELECT h.status_anterior,h.status_novo,h.observacao,h.criado_em,u.nome usuario_nome
        FROM historico_os h
        LEFT JOIN usuarios u ON u.id=h.usuario_id
        WHERE h.ordem_servico_id=?
        ORDER BY h.criado_em DESC LIMIT 20
    ");
    $hist->execute([$osId]);$history=$hist->fetchAll(PDO::FETCH_ASSOC);

    $attachments=[];
    try{
        $an=$pdo->prepare("SELECT tipo,descricao,arquivo_url,criado_em FROM anexos_os WHERE ordem_servico_id=? ORDER BY criado_em DESC LIMIT 30");
        $an->execute([$osId]);$attachments=$an->fetchAll(PDO::FETCH_ASSOC);
        foreach($attachments as &$a){
            $a['arquivo']=basename((string)($a['arquivo_url']??''));
            unset($a['arquivo_url']);
        }unset($a);
    }catch(Throwable $e){}

    $check=[];
    if(geop_v8_table_exists($pdo,'os_checklist_itens')){
        $cl=$pdo->prepare("SELECT descricao,obrigatorio,exige_foto,concluido,observacao,concluido_em FROM os_checklist_itens WHERE ordem_servico_id=? ORDER BY ordem,id");
        $cl->execute([$osId]);$check=$cl->fetchAll(PDO::FETCH_ASSOC);
    }

    $safeOs=$os;
    foreach(['token_publico_hash'] as $secretField) unset($safeOs[$secretField]);

    return geop_ai_tool_result(
        'Resumo completo da '.$os['numero_os'].'.',
        [
            'ordem'=>$safeOs,
            'materiais'=>$materials,
            'historico'=>$history,
            'anexos'=>$attachments,
            'checklist'=>$check,
        ],
        [
            geop_ai_tool_source('ordens_servico','Ordem de Serviço',$os['numero_os']),
            geop_ai_tool_source('historico_os','Histórico da OS'),
            geop_ai_tool_source('materiais_os','Materiais da OS'),
            geop_ai_tool_source('anexos_os','Anexos da OS'),
        ],
        1
    );
}

function geop_ai_tool_ol(PDO $pdo, array $user, array $args): array
{
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $text=trim((string)($args['texto']??''));
    $status=trim((string)($args['status']??''));
    $di=geop_ai_date($args['data_inicio']??null);
    $df=geop_ai_date($args['data_fim']??null);
    $data=['legadas'=>[],'unificadas'=>[]];
    $sources=[];

    if(geop_v8_table_exists($pdo,'limpeza_ordens')){
        $where=['1=1'];$params=[];
        if($text!==''){$where[]='(numero_ol LIKE ? OR titulo LIKE ? OR descricao LIKE ?)';array_push($params,"%{$text}%","%{$text}%","%{$text}%");}
        if($status!==''){$where[]='status=?';$params[]=$status;}
        if($di){$where[]='DATE(criado_em)>=?';$params[]=$di;}
        if($df){$where[]='DATE(criado_em)<=?';$params[]=$df;}
        $st=$pdo->prepare("SELECT id,numero_ol,titulo,tipo,prioridade,status,local_id,data_programada,prazo_em,criado_em,atualizado_em FROM limpeza_ordens WHERE ".implode(' AND ',$where)." ORDER BY atualizado_em DESC LIMIT {$limit}");
        $st->execute($params);$data['legadas']=$st->fetchAll(PDO::FETCH_ASSOC);
        $sources[]=geop_ai_tool_source('limpeza_ordens','Ordens de Limpeza');
    }

    if(geop_v8_column_exists($pdo,'ordens_servico','tipo_ordem')){
        $where=["os.tipo_ordem='OL'",exclusao_sql_nao_excluida($pdo,'ordens_servico','os')];$params=[];
        geop_ai_os_scope($user,'os',$where,$params);
        if($text!==''){$where[]='(os.numero_os LIKE ? OR os.titulo LIKE ? OR os.descricao LIKE ?)';array_push($params,"%{$text}%","%{$text}%","%{$text}%");}
        if($status!==''){$where[]='os.status=?';$params[]=$status;}
        if($di){$where[]='DATE(os.criado_em)>=?';$params[]=$di;}
        if($df){$where[]='DATE(os.criado_em)<=?';$params[]=$df;}
        $st=$pdo->prepare("SELECT os.id,os.numero_os,os.titulo,os.prioridade,os.status,os.local_id,os.prazo,os.criado_em,os.atualizado_em FROM ordens_servico os WHERE ".implode(' AND ',$where)." ORDER BY os.atualizado_em DESC LIMIT {$limit}");
        $st->execute($params);$data['unificadas']=$st->fetchAll(PDO::FETCH_ASSOC);
        $sources[]=geop_ai_tool_source('ordens_servico','Ordens Unificadas','tipo_ordem=OL');
    }

    $count=count($data['legadas'])+count($data['unificadas']);
    return geop_ai_tool_result($count.' OL encontrada(s).',$data,$sources,$count);
}

function geop_ai_tool_stock(PDO $pdo, array $user, array $args): array
{
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $where=['ativo=1'];$params=[];
    $text=trim((string)($args['texto']??''));
    if($text!==''){$where[]='nome LIKE ?';$params[]="%{$text}%";}
    if(!empty($args['somente_criticos']))$where[]='quantidade_atual<=estoque_minimo';

    $st=$pdo->prepare("SELECT id,nome,unidade,quantidade_atual,estoque_minimo,custo_unitario,atualizado_em FROM estoque_materiais WHERE ".implode(' AND ',$where)." ORDER BY (quantidade_atual<=estoque_minimo) DESC,nome LIMIT {$limit}");
    $st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        count($rows).' material(is) encontrado(s).',
        $rows,
        [geop_ai_tool_source('estoque_materiais','Estoque de Materiais')],
        count($rows)
    );
}

function geop_ai_tool_assets(PDO $pdo, array $user, array $args): array
{
    if(!geop_v8_table_exists($pdo,'ativos')) return ['ok'=>false,'erro'=>'Módulo de patrimônio não disponível.'];
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $where=['1=1'];$params=[];
    if(!empty($args['somente_ativos']))$where[]='atv.ativo=1';
    $text=trim((string)($args['texto']??''));
    if($text!==''){
        $where[]="(atv.codigo_patrimonial LIKE ? OR atv.marca LIKE ? OR atv.modelo LIKE ? OR atv.numero_serie LIKE ?)";
        array_push($params,"%{$text}%","%{$text}%","%{$text}%","%{$text}%");
    }

    $costExpr = geop_v8_column_exists($pdo,'ordens_servico','custo_total_final')
        ? 'COALESCE(os.custo_total_final,COALESCE(os.custo_materiais,0))'
        : 'COALESCE(os.custo_materiais,0)';

    $sql="SELECT atv.id,atv.codigo_patrimonial,atv.marca,atv.modelo,atv.numero_serie,
                 atv.estado_conservacao,atv.garantia_ate,atv.adquirido_em,atv.ativo,
                 l.nome local_nome,
                 COUNT(os.id) total_os,
                 COALESCE(SUM({$costExpr}),0) custo_os
          FROM ativos atv
          LEFT JOIN locais l ON l.id=atv.local_id
          LEFT JOIN ordens_servico os ON os.ativo_id=atv.id
          WHERE ".implode(' AND ',$where)."
          GROUP BY atv.id
          ORDER BY total_os DESC,atv.codigo_patrimonial
          LIMIT {$limit}";
    $st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        count($rows).' patrimônio(s) encontrado(s).',
        $rows,
        [
            geop_ai_tool_source('ativos','Patrimônio'),
            geop_ai_tool_source('ordens_servico','OS vinculadas ao patrimônio'),
        ],
        count($rows)
    );
}

function geop_ai_tool_preventive(PDO $pdo, array $user, array $args): array
{
    if(!geop_v8_table_exists($pdo,'manutencoes_preventivas')) return ['ok'=>false,'erro'=>'Módulo de preventivas não disponível.'];
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $situation=(string)($args['situacao']??'ATIVAS');
    $days=max(1,min(365,(int)($args['dias']??30)));
    $where=[];$params=[];
    if($situation!=='TODAS')$where[]='p.ativo=1';
    if($situation==='ATRASADAS')$where[]='p.proxima_execucao<CURDATE()';
    elseif($situation==='PROXIMAS'){$where[]='p.proxima_execucao BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL ? DAY)';$params[]=$days;}

    $sql="SELECT p.id,p.titulo,p.descricao,p.prioridade,p.intervalo_dias,p.proxima_execucao,
                 p.ultimo_gerado_em,p.ativo,l.nome local_nome,c.nome categoria_nome,
                 u.nome agente_nome,atv.codigo_patrimonial
          FROM manutencoes_preventivas p
          JOIN locais l ON l.id=p.local_id
          JOIN categorias c ON c.id=p.categoria_id
          LEFT JOIN usuarios u ON u.id=p.agente_id
          LEFT JOIN ativos atv ON atv.id=p.ativo_id
          ".($where?'WHERE '.implode(' AND ',$where):'')."
          ORDER BY p.proxima_execucao ASC
          LIMIT {$limit}";
    $st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        count($rows).' preventiva(s) encontrada(s).',
        $rows,
        [geop_ai_tool_source('manutencoes_preventivas','Manutenções Preventivas')],
        count($rows)
    );
}

function geop_ai_tool_recurrence(PDO $pdo, array $user, array $args): array
{
    $days=max(1,min(1825,(int)($args['dias']??365)));
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $where=['os.ativo_id IS NOT NULL',"os.criado_em>=DATE_SUB(NOW(),INTERVAL {$days} DAY)"];
    $params=[];
    geop_ai_os_scope($user,'os',$where,$params);
    $ativoId=(int)($args['ativo_id']??0);
    if($ativoId>0){$where[]='os.ativo_id=?';$params[]=$ativoId;}

    $costExpr=geop_v8_column_exists($pdo,'ordens_servico','custo_total_final')
        ? 'COALESCE(os.custo_total_final,COALESCE(os.custo_materiais,0))'
        : 'COALESCE(os.custo_materiais,0)';

    $st=$pdo->prepare("
        SELECT atv.id ativo_id,atv.codigo_patrimonial,atv.marca,atv.modelo,
               COUNT(os.id) total_os,
               COALESCE(SUM({$costExpr}),0) custo_acumulado,
               MAX(os.criado_em) ultima_os
        FROM ordens_servico os
        JOIN ativos atv ON atv.id=os.ativo_id
        WHERE ".implode(' AND ',$where)."
        GROUP BY atv.id
        ORDER BY total_os DESC,custo_acumulado DESC
        LIMIT {$limit}
    ");
    $st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        'Reincidências dos últimos '.$days.' dia(s).',
        $rows,
        [
            geop_ai_tool_source('ordens_servico','Ordens de Serviço'),
            geop_ai_tool_source('ativos','Patrimônio'),
        ],
        count($rows),
        ['periodo_dias'=>$days]
    );
}

function geop_ai_tool_knowledge(PDO $pdo, array $user, array $args): array
{
    if(!geop_v8_table_exists($pdo,'conhecimento_artigos')) return ['ok'=>false,'erro'=>'Base de conhecimento não disponível.'];
    $limit=max(1,min(20,(int)($args['limite']??10)));
    $text=trim((string)($args['texto']??''));
    if($text==='') return ['ok'=>false,'erro'=>'Informe um texto para pesquisa.'];

    $st=$pdo->prepare("
        SELECT id,titulo,LEFT(conteudo,3000) conteudo,versao,revisar_em,updated_at
        FROM conhecimento_artigos
        WHERE status='PUBLICADO' AND (titulo LIKE ? OR conteudo LIKE ?)
        ORDER BY updated_at DESC
        LIMIT {$limit}
    ");
    $st->execute(["%{$text}%","%{$text}%"]);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        count($rows).' artigo(s) encontrado(s).',
        $rows,
        [geop_ai_tool_source('conhecimento_artigos','Base de Conhecimento')],
        count($rows)
    );
}

function geop_ai_tool_indicators(PDO $pdo, array $user, array $args): array
{
    $di=geop_ai_date($args['data_inicio']??null) ?: date('Y-m-01');
    $df=geop_ai_date($args['data_fim']??null) ?: date('Y-m-d');
    $where=['DATE(os.criado_em) BETWEEN ? AND ?'];
    $params=[$di,$df];
    geop_ai_os_scope($user,'os',$where,$params);
    if(geop_v8_column_exists($pdo,'ordens_servico','excluida_em'))$where[]='os.excluida_em IS NULL';
    $w=implode(' AND ',$where);

    $costSelect = geop_ai_finance_allowed($user)
        ? "COALESCE(SUM(COALESCE(os.custo_total_final,COALESCE(os.custo_materiais,0))),0) custo_total"
        : "NULL custo_total";

    $st=$pdo->prepare("
        SELECT COUNT(*) total,
               SUM(os.status='ENCERRADA') encerradas,
               SUM(os.status NOT IN ('ENCERRADA','REPROVADA')) abertas,
               SUM(os.sla_vencimento IS NOT NULL AND os.sla_vencimento<NOW() AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')) sla_vencidas,
               {$costSelect}
        FROM ordens_servico os
        WHERE {$w}
    ");
    $st->execute($params);$summary=$st->fetch(PDO::FETCH_ASSOC)?:[];

    $topCat=$pdo->prepare("
        SELECT c.nome categoria,COUNT(*) total
        FROM ordens_servico os JOIN categorias c ON c.id=os.categoria_id
        WHERE {$w}
        GROUP BY c.id ORDER BY total DESC LIMIT 5
    ");
    $topCat->execute($params);$cats=$topCat->fetchAll(PDO::FETCH_ASSOC);

    $topLoc=$pdo->prepare("
        SELECT l.nome local,COUNT(*) total
        FROM ordens_servico os JOIN locais l ON l.id=os.local_id
        WHERE {$w}
        GROUP BY l.id ORDER BY total DESC LIMIT 5
    ");
    $topLoc->execute($params);$locs=$topLoc->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        "Indicadores de {$di} a {$df}.",
        ['resumo'=>$summary,'top_categorias'=>$cats,'top_locais'=>$locs],
        [
            geop_ai_tool_source('ordens_servico','Ordens de Serviço'),
            geop_ai_tool_source('categorias','Categorias'),
            geop_ai_tool_source('locais','Locais'),
        ],
        (int)($summary['total']??0),
        ['data_inicio'=>$di,'data_fim'=>$df]
    );
}

function geop_ai_tool_costs(PDO $pdo, array $user, array $args): array
{
    $di=geop_ai_date($args['data_inicio']??null) ?: date('Y-m-01');
    $df=geop_ai_date($args['data_fim']??null) ?: date('Y-m-d');
    $group=(string)($args['agrupar_por']??'nenhum');
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $dateExpr='DATE(COALESCE(os.data_encerramento,os.data_conclusao,os.criado_em))';

    $where=["{$dateExpr} BETWEEN ? AND ?"];
    $params=[$di,$df];
    if(geop_v8_column_exists($pdo,'ordens_servico','excluida_em'))$where[]='os.excluida_em IS NULL';
    $w=implode(' AND ',$where);
    $cost='COALESCE(os.custo_total_final,COALESCE(os.custo_materiais,0)+COALESCE(os.custo_mao_obra,0)+COALESCE(os.custo_terceiros,0))';

    if($group==='categoria'){
        $sql="SELECT c.nome grupo,COUNT(*) total_os,SUM({$cost}) custo_total,AVG({$cost}) custo_medio
              FROM ordens_servico os JOIN categorias c ON c.id=os.categoria_id
              WHERE {$w} GROUP BY c.id ORDER BY custo_total DESC LIMIT {$limit}";
    }elseif($group==='local'){
        $sql="SELECT l.nome grupo,COUNT(*) total_os,SUM({$cost}) custo_total,AVG({$cost}) custo_medio
              FROM ordens_servico os JOIN locais l ON l.id=os.local_id
              WHERE {$w} GROUP BY l.id ORDER BY custo_total DESC LIMIT {$limit}";
    }elseif($group==='mes'){
        $sql="SELECT DATE_FORMAT(COALESCE(os.data_encerramento,os.data_conclusao,os.criado_em),'%Y-%m') grupo,
                     COUNT(*) total_os,SUM({$cost}) custo_total,AVG({$cost}) custo_medio
              FROM ordens_servico os
              WHERE {$w} GROUP BY grupo ORDER BY grupo DESC LIMIT {$limit}";
    }else{
        $sql="SELECT os.id,os.numero_os,os.titulo,{$dateExpr} data_referencia,{$cost} custo_total
              FROM ordens_servico os WHERE {$w} ORDER BY custo_total DESC LIMIT {$limit}";
    }

    $st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        "Custos de {$di} a {$df}, agrupamento {$group}.",
        $rows,
        [geop_ai_tool_source('ordens_servico','Custos das Ordens de Serviço')],
        count($rows),
        ['data_inicio'=>$di,'data_fim'=>$df,'agrupar_por'=>$group]
    );
}

function geop_ai_tool_approvals(PDO $pdo, array $user, array $args): array
{
    $rows=geop_v8_pending_approvals($pdo);
    return geop_ai_tool_result(
        count($rows).' aprovação(ões) pendente(s).',
        $rows,
        [
            geop_ai_tool_source('aprovacoes','Central de Aprovações'),
            geop_ai_tool_source('ordem_solicitacoes_exclusao','Solicitações de Exclusão'),
            geop_ai_tool_source('os_reabertura_solicitacoes','Solicitações de Reabertura'),
        ],
        count($rows)
    );
}

function geop_ai_tool_access_releases(PDO $pdo, array $user, array $args): array
{
    if(!geop_v8_table_exists($pdo,'cftv_liberacoes')) return ['ok'=>false,'erro'=>'Módulo de liberações não disponível.'];
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $where=['1=1'];$params=[];
    $text=trim((string)($args['texto']??''));
    if($text!==''){
        $where[]='(nome_completo LIKE ? OR destino_nome LIKE ? OR motivo LIKE ?)';
        array_push($params,"%{$text}%","%{$text}%","%{$text}%");
    }
    $status=trim((string)($args['status']??''));
    if($status!==''){$where[]='status=?';$params[]=$status;}
    $di=geop_ai_date($args['data_inicio']??null);$df=geop_ai_date($args['data_fim']??null);
    if($di){$where[]='data_acesso>=?';$params[]=$di;}
    if($df){$where[]='COALESCE(data_fim,data_acesso)<=?';$params[]=$df;}

    $st=$pdo->prepare("
        SELECT id,data_acesso,data_fim,hora_inicio,hora_fim,nome_completo,
               CASE
                   WHEN cpf IS NULL OR cpf='' THEN NULL
                   ELSE CONCAT('***.***.***-',RIGHT(REGEXP_REPLACE(cpf,'[^0-9]',''),2))
               END cpf_mascarado,
               destino_tipo,destino_nome,motivo,status,criado_em
        FROM cftv_liberacoes
        WHERE ".implode(' AND ',$where)."
        ORDER BY data_acesso DESC,criado_em DESC
        LIMIT {$limit}
    ");
    try{
        $st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);
    }catch(Throwable $e){
        // MariaDB sem REGEXP_REPLACE em configuração específica: não retorna CPF.
        $st=$pdo->prepare("
            SELECT id,data_acesso,data_fim,hora_inicio,hora_fim,nome_completo,
                   destino_tipo,destino_nome,motivo,status,criado_em
            FROM cftv_liberacoes
            WHERE ".implode(' AND ',$where)."
            ORDER BY data_acesso DESC,criado_em DESC
            LIMIT {$limit}
        ");
        $st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);
    }

    return geop_ai_tool_result(
        count($rows).' liberação(ões) encontrada(s).',
        $rows,
        [geop_ai_tool_source('cftv_liberacoes','Liberações de Acesso')],
        count($rows)
    );
}

function geop_ai_tool_audit(PDO $pdo, array $user, array $args): array
{
    if(!geop_v8_table_exists($pdo,'auditoria_eventos')) return ['ok'=>false,'erro'=>'Auditoria não disponível.'];
    $limit=geop_ai_limit($pdo,$args['limite']??20);
    $where=['1=1'];$params=[];
    foreach(['entidade','acao'] as $field){
        $v=trim((string)($args[$field]??''));
        if($v!==''){$where[]="ae.{$field}=?";$params[]=$v;}
    }
    $uid=(int)($args['usuario_id']??0);
    if($uid>0){$where[]='ae.usuario_id=?';$params[]=$uid;}

    $st=$pdo->prepare("
        SELECT ae.id,ae.entidade,ae.entidade_id,ae.acao,ae.campo,
               ae.valor_anterior,ae.valor_novo,ae.criado_em,u.nome usuario_nome
        FROM auditoria_eventos ae
        LEFT JOIN usuarios u ON u.id=ae.usuario_id
        WHERE ".implode(' AND ',$where)."
        ORDER BY ae.criado_em DESC
        LIMIT {$limit}
    ");
    $st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);

    return geop_ai_tool_result(
        count($rows).' evento(s) de auditoria.',
        $rows,
        [geop_ai_tool_source('auditoria_eventos','Auditoria')],
        count($rows)
    );
}
