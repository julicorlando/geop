<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';
require_once __DIR__ . '/../includes/operacional.php';
require_role(['TI','GESTOR','GESTOR_SINDICO','COORDENADOR_OPERACOES_OBRAS']);
ensure_operational_schema($pdo);
gestao_ensure_schema($pdo);
$user=current_user();
$mesEscala=(string)($_GET['mes']??$_POST['mes']??date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/',$mesEscala)) $mesEscala=date('Y-m');
$funcoesEscala=['Agente de Manutenção','Brigadista','Limpeza','ASG','CFTV','Fiscal de Mall'];
$funcaoFiltro=(string)($_GET['visualizar_funcao']??$_POST['visualizar_funcao']??'');
if(!in_array($funcaoFiltro,$funcoesEscala,true)) $funcaoFiltro='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $action=(string)($_POST['action']??'');
    if ($action==='estado_equipe') {
        $uid=(int)($_POST['usuario_id']??0);
        $estado=in_array($_POST['estado']??'', ['DISPONIVEL','OCUPADO','EM_ATENDIMENTO','AUSENTE','FERIAS','AFASTADO'],true)?$_POST['estado']:'DISPONIVEL';
        $cap=max(1,min(50,(int)($_POST['capacidade_maxima']??5)));
        $pdo->prepare("INSERT INTO equipe_estado(usuario_id,estado,capacidade_maxima,observacao)
            VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE estado=VALUES(estado),capacidade_maxima=VALUES(capacidade_maxima),observacao=VALUES(observacao)")
            ->execute([$uid,$estado,$cap,trim((string)($_POST['observacao']??''))]);
    }
    if ($action==='especialidade') {
        $uid=(int)($_POST['usuario_id']??0);$esp=trim((string)($_POST['especialidade']??''));
        if ($uid&&$esp!=='') $pdo->prepare("INSERT INTO usuario_especialidades(usuario_id,especialidade,nivel)
            VALUES(?,?,?) ON DUPLICATE KEY UPDATE nivel=VALUES(nivel)")->execute([$uid,$esp,max(1,min(5,(int)($_POST['nivel']??1)))]);
    }
    if ($action==='feriado') {
        $data=(string)($_POST['data']??'');$nome=trim((string)($_POST['nome']??''));
        if ($data&&$nome) $pdo->prepare("INSERT INTO feriados_operacionais(data,nome,local_id)
            VALUES(?,?,?) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ativo=1")
            ->execute([$data,$nome,(int)($_POST['local_id']??0)?:null]);
    }
    if ($action==='medidor') {
        $ativo=(int)($_POST['ativo_id']??0);$tipo=trim((string)($_POST['tipo']??'HORAS'));
        if ($ativo) $pdo->prepare("INSERT INTO ativos_medidores(ativo_id,tipo,valor_atual,limite_proxima_os,incremento_ciclo)
            VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE valor_atual=VALUES(valor_atual),
            limite_proxima_os=VALUES(limite_proxima_os),incremento_ciclo=VALUES(incremento_ciclo)")
            ->execute([$ativo,$tipo,(float)$_POST['valor_atual'],(float)$_POST['limite_proxima_os'],(float)$_POST['incremento_ciclo']]);
    }
    if ($action==='escala_criar') {
        $agenteId=(int)($_POST['agente_id']??0);
        $dataServico=(string)($_POST['data_servico']??'');
        $horaInicio=(string)($_POST['hora_inicio']??'');
        $horaFim=(string)($_POST['hora_fim']??'');
        $titulo=trim((string)($_POST['titulo']??'Escala de serviço'));
        $descricao=trim((string)($_POST['descricao']??''));
        $localId=(int)($_POST['local_id']??0)?:null;
        $timestamp=strtotime($dataServico);
        if ($agenteId<=0||!$timestamp||$titulo===''||$horaInicio===''||$horaFim===''||$horaFim<=$horaInicio) {
            $_SESSION['mensagem_erro']='Preencha a escala corretamente. O horário final deve ser posterior ao inicial.';
        } else {
            $diaSemana=(int)date('N',$timestamp);
            $semanaInicio=date('Y-m-d',strtotime('-'.($diaSemana-1).' days',$timestamp));
            $semanaFim=date('Y-m-d',strtotime($semanaInicio.' +6 days'));
            $conflito=$pdo->prepare("SELECT COUNT(*) FROM cronograma_agentes
                WHERE agente_id=? AND semana_inicio=? AND dia_semana=? AND status<>'CANCELADO'
                  AND hora_inicio<? AND hora_fim>?");
            $conflito->execute([$agenteId,$semanaInicio,$diaSemana,$horaFim,$horaInicio]);
            if ((int)$conflito->fetchColumn()>0) {
                $_SESSION['mensagem_erro']='O técnico já possui uma atividade que coincide com esse horário.';
            } else {
                $pdo->prepare("INSERT INTO cronograma_agentes
                    (agente_id,gestor_id,titulo,descricao,local_id,dia_semana,hora_inicio,hora_fim,semana_inicio,semana_fim,status)
                    VALUES(?,?,?,?,?,?,?,?,?,?,'PLANEJADO')")
                    ->execute([$agenteId,(int)$user['id'],$titulo,$descricao,$localId,$diaSemana,$horaInicio,$horaFim,$semanaInicio,$semanaFim]);
                $escalaId=(int)$pdo->lastInsertId();
                gestao_notificar($pdo,$agenteId,'ESCALA_SERVICO','Nova escala de serviço',
                    $titulo.' em '.date('d/m/Y',$timestamp).' das '.$horaInicio.' às '.$horaFim,
                    app_path('cronograma_agentes',['semana'=>$semanaInicio]),'escala-'.$escalaId);
                $_SESSION['mensagem_sucesso']='Escala adicionada e o técnico foi notificado.';
            }
        }
    }
    if ($action==='escala_status') {
        $escalaId=(int)($_POST['escala_id']??0);
        $status=in_array($_POST['status']??'', ['PLANEJADO','EM_ANDAMENTO','CONCLUIDO','CANCELADO'],true)
            ? (string)$_POST['status'] : 'PLANEJADO';
        $pdo->prepare("UPDATE cronograma_agentes SET status=? WHERE id=?")->execute([$status,$escalaId]);
        $_SESSION['mensagem_sucesso']='Situação da escala atualizada.';
    }
    if ($action==='escala_12x36') {
        $agenteId=(int)($_POST['agente_id']??0);
        $funcao=in_array($_POST['funcao']??'', $funcoesEscala,true)?(string)$_POST['funcao']:'Agente de Manutenção';
        $posto=trim((string)($_POST['posto']??$funcao));
        $primeiroPlantao=(string)($_POST['primeiro_plantao']??'');
        $horaInicio=(string)($_POST['hora_inicio']??'07:00');
        $horaFim=(string)($_POST['hora_fim']??'19:00');
        $intervaloInicio=(string)($_POST['intervalo_inicio']??'12:00');
        $intervaloFim=(string)($_POST['intervalo_fim']??'13:00');
        $inicioMes=$mesEscala.'-01';
        $fimMes=date('Y-m-t',strtotime($inicioMes));
        if ($agenteId<=0||!strtotime($primeiroPlantao)||substr($primeiroPlantao,0,7)!==$mesEscala||$posto==='') {
            $_SESSION['mensagem_erro']='Informe o colaborador, a função e o primeiro plantão dentro do mês selecionado.';
        } else {
            $pdo->beginTransaction();
            try {
                $salvar=$pdo->prepare("INSERT INTO escalas_servico
                    (usuario_id,funcao,posto,data_servico,tipo,jornada_horas,hora_inicio,hora_fim,intervalo_inicio,intervalo_fim,criado_por)
                    VALUES(?,?,?,?,?,12,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE funcao=VALUES(funcao),posto=VALUES(posto),tipo=VALUES(tipo),jornada_horas=12,
                    hora_inicio=VALUES(hora_inicio),hora_fim=VALUES(hora_fim),
                    intervalo_inicio=VALUES(intervalo_inicio),intervalo_fim=VALUES(intervalo_fim),criado_por=VALUES(criado_por)");
                $referencia=strtotime($primeiroPlantao);
                for($dia=strtotime($inicioMes);$dia<=strtotime($fimMes);$dia=strtotime('+1 day',$dia)) {
                    $trabalha=abs((int)(($dia-$referencia)/86400))%2===0;
                    $salvar->execute([$agenteId,$funcao,$posto,date('Y-m-d',$dia),$trabalha?'TRABALHO':'FOLGA',
                        $trabalha?$horaInicio:null,$trabalha?$horaFim:null,
                        $trabalha?$intervaloInicio:null,$trabalha?$intervaloFim:null,(int)$user['id']]);
                }
                $pdo->commit();
                gestao_notificar($pdo,$agenteId,'ESCALA_SERVICO','Escala mensal publicada',
                    'Sua escala 12x36 de '.$mesEscala.' foi cadastrada.',
                    app_path('gestao',['mes'=>$mesEscala]).'#escala-servico','escala-mensal-'.$agenteId.'-'.$mesEscala);
                $_SESSION['mensagem_sucesso']='Escala 12x36 gerada para o mês inteiro.';
            } catch(Throwable $e) {
                if($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        }
    }
    if ($action==='escala_ajustar_dia') {
        $agenteId=(int)($_POST['agente_id']??0);
        $dataServico=(string)($_POST['data_servico']??'');
        $tipo=($_POST['tipo']??'TRABALHO')==='FOLGA'?'FOLGA':'TRABALHO';
        $funcao=in_array($_POST['funcao']??'', $funcoesEscala,true)?(string)$_POST['funcao']:'Agente de Manutenção';
        $horaInicio=$tipo==='TRABALHO'?(string)($_POST['hora_inicio']??'07:00'):null;
        $horaFim=$tipo==='TRABALHO'?(string)($_POST['hora_fim']??'19:00'):null;
        $intervaloInicio=$tipo==='TRABALHO'?(string)($_POST['intervalo_inicio']??'12:00'):null;
        $intervaloFim=$tipo==='TRABALHO'?(string)($_POST['intervalo_fim']??'13:00'):null;
        if($agenteId<=0||substr($dataServico,0,7)!==$mesEscala||($tipo==='TRABALHO'&&$horaFim===$horaInicio)) {
            $_SESSION['mensagem_erro']='Preencha corretamente o ajuste do dia.';
        } else {
            $pdo->prepare("INSERT INTO escalas_servico
                (usuario_id,funcao,posto,data_servico,tipo,jornada_horas,hora_inicio,hora_fim,intervalo_inicio,intervalo_fim,criado_por)
                VALUES(?,?,?,?,?,?, ?,?,?,?,?)
                ON DUPLICATE KEY UPDATE funcao=VALUES(funcao),posto=VALUES(posto),
                tipo=VALUES(tipo),jornada_horas=VALUES(jornada_horas),hora_inicio=VALUES(hora_inicio),
                hora_fim=VALUES(hora_fim),intervalo_inicio=VALUES(intervalo_inicio),intervalo_fim=VALUES(intervalo_fim)")
                ->execute([$agenteId,$funcao,$funcao,$dataServico,$tipo,
                    $tipo==='TRABALHO'?max(1,(int)round((strtotime($horaFim)-strtotime($horaInicio)+86400)%86400/3600)):0,
                    $horaInicio,$horaFim,$intervaloInicio,$intervaloFim,(int)$user['id']]);
            gestao_notificar($pdo,$agenteId,'ESCALA_AJUSTADA','Escala ajustada',
                'Sua escala de '.date('d/m/Y',strtotime($dataServico)).' foi atualizada.',
                app_path('gestao',['mes'=>$mesEscala]).'#escala-servico','escala-ajuste-'.$agenteId.'-'.$dataServico.'-'.time());
            $_SESSION['mensagem_sucesso']='Horário do dia atualizado.';
        }
    }
    if ($action==='escala_remover_agente') {
        $agenteId=(int)($_POST['agente_id']??0);
        $pdo->prepare("DELETE FROM escalas_servico WHERE usuario_id=? AND data_servico BETWEEN ? AND ?")
            ->execute([$agenteId,$mesEscala.'-01',date('Y-m-t',strtotime($mesEscala.'-01'))]);
        $_SESSION['mensagem_sucesso']='Escala mensal do técnico removida.';
    }
    if (in_array($action,['aprovar_estoque','reprovar_estoque'],true)) {
        $reservaId=(int)($_POST['reserva_id']??0);
        $pdo->beginTransaction();
        $st=$pdo->prepare("SELECT er.*,em.nome,em.quantidade_atual FROM estoque_reservas er
            JOIN estoque_materiais em ON em.id=er.material_id WHERE er.id=? AND er.status='APROVACAO_NEGATIVO' FOR UPDATE");
        $st->execute([$reservaId]);$reserva=$st->fetch(PDO::FETCH_ASSOC);
        if ($reserva) {
            if ($action==='aprovar_estoque') {
                $pdo->prepare("UPDATE estoque_reservas SET status='CONSUMIDA',aprovado_por=?,aprovado_em=NOW(),atualizado_em=NOW() WHERE id=?")
                    ->execute([$user['id'],$reservaId]);
                $pdo->prepare("UPDATE estoque_materiais SET quantidade_atual=quantidade_atual-?,atualizado_em=NOW() WHERE id=?")
                    ->execute([$reserva['quantidade'],$reserva['material_id']]);
                $pdo->prepare("INSERT INTO movimentacoes_estoque(material_id,ordem_servico_id,usuario_id,tipo,quantidade,custo_unitario,observacao)
                    VALUES(?,?,?,'SAIDA_OS_NEGATIVA',?,?,?)")->execute([$reserva['material_id'],$reserva['ordem_servico_id'],$user['id'],$reserva['quantidade'],$reserva['custo_unitario'],'Estoque negativo aprovado']);
                $custo=(float)$reserva['quantidade']*(float)$reserva['custo_unitario'];
                if (gestao_column_exists($pdo,'ordens_servico','custo_materiais')) $pdo->prepare("UPDATE ordens_servico SET custo_materiais=COALESCE(custo_materiais,0)+? WHERE id=?")->execute([$custo,$reserva['ordem_servico_id']]);
                $status='APROVADA';
            } else {
                $pdo->prepare("UPDATE estoque_reservas SET status='INDISPONIVEL',aprovado_por=?,aprovado_em=NOW(),atualizado_em=NOW() WHERE id=?")
                    ->execute([$user['id'],$reservaId]);$status='REPROVADA';
            }
            $pdo->prepare("UPDATE aprovacoes SET status=?,aprovador_id=?,decidido_em=NOW() WHERE entidade='estoque_reserva' AND entidade_id=? AND status='PENDENTE'")
                ->execute([$status,$user['id'],$reservaId]);
            gestao_notificar($pdo,(int)$reserva['usuario_id'],'MATERIAL_'.$status,'Solicitação de material '.$status,
                $reserva['nome'].' para a OS #'.$reserva['ordem_servico_id'],app_path('ordem_detalhe',['id'=>$reserva['ordem_servico_id']]),
                'material-decisao-'.$reservaId);
        }
        $pdo->commit();
    }
    redirect('gestao?mes='.urlencode($mesEscala).($funcaoFiltro!==''?'&visualizar_funcao='.urlencode($funcaoFiltro):'').'#escala-servico');
}

$ordemAtivaSemAlias = exclusao_sql_nao_excluida($pdo, 'ordens_servico');
$ordemAtivaOs = exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os');

$kpi=$pdo->query("SELECT
 COUNT(*) total,
 SUM(status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','CANCELADA')) abertas,
 SUM(status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','CANCELADA') AND COALESCE(sla_vencimento,prazo)<NOW()) atrasadas,
 SUM(prioridade='CRITICA' AND status NOT IN ('ENCERRADA','CANCELADA')) criticas,
 ROUND(AVG(CASE WHEN data_inicio IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,criado_em,data_inicio) END)/60,1) tma_horas,
 ROUND(AVG(CASE WHEN data_encerramento IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,criado_em,data_encerramento) END)/60,1) tms_horas,
 ROUND(100*AVG(CASE WHEN data_encerramento IS NOT NULL THEN data_encerramento<=COALESCE(sla_vencimento,prazo) END),1) sla_percentual,
 ROUND(100*AVG(CASE WHEN COALESCE(reabertura_contagem,0)>0 THEN 1 ELSE 0 END),1) reabertura_percentual
 FROM ordens_servico WHERE $ordemAtivaSemAlias")->fetch(PDO::FETCH_ASSOC) ?: [];
$equipe=$pdo->query("SELECT u.id,u.nome,COALESCE(ee.estado,'DISPONIVEL') estado,COALESCE(ee.capacidade_maxima,5) capacidade,
 (SELECT COUNT(*) FROM ordens_servico os WHERE os.agente_id=u.id AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','CANCELADA') AND $ordemAtivaOs) carga,
 (SELECT GROUP_CONCAT(especialidade SEPARATOR ', ') FROM usuario_especialidades ue WHERE ue.usuario_id=u.id) especialidades
 FROM usuarios u LEFT JOIN equipe_estado ee ON ee.usuario_id=u.id
 WHERE u.status='ATIVO' AND u.perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS') ORDER BY u.nome")->fetchAll(PDO::FETCH_ASSOC);
$colaboradoresEscala=$pdo->query("SELECT id,nome,perfil FROM usuarios WHERE status='ATIVO' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$reservas=$pdo->query("SELECT er.*,em.nome material_nome,os.numero_os,u.nome solicitante_nome FROM estoque_reservas er
 JOIN estoque_materiais em ON em.id=er.material_id JOIN ordens_servico os ON os.id=er.ordem_servico_id AND $ordemAtivaOs
 LEFT JOIN usuarios u ON u.id=er.usuario_id WHERE er.status='APROVACAO_NEGATIVO' ORDER BY er.criado_em")->fetchAll(PDO::FETCH_ASSOC);
$custosSetor=$pdo->query("SELECT COALESCE(s.nome,'Sem setor') nome,SUM(me.quantidade*me.custo_unitario) custo
 FROM movimentacoes_estoque me JOIN ordens_servico os ON os.id=me.ordem_servico_id
 LEFT JOIN usuarios sol ON sol.id=os.solicitante_id LEFT JOIN setores s ON s.id=sol.setor_id
 WHERE me.tipo LIKE 'SAIDA_OS%' AND $ordemAtivaOs GROUP BY s.nome ORDER BY custo DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$custosCategoria=$pdo->query("SELECT c.nome,SUM(me.quantidade*me.custo_unitario) custo
 FROM movimentacoes_estoque me JOIN ordens_servico os ON os.id=me.ordem_servico_id
 JOIN categorias c ON c.id=os.categoria_id WHERE me.tipo LIKE 'SAIDA_OS%' AND $ordemAtivaOs
 GROUP BY c.nome ORDER BY custo DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$produtividade=$pdo->query("SELECT u.nome,COUNT(os.id) concluidas,
 ROUND(AVG(TIMESTAMPDIFF(MINUTE,os.data_inicio,os.data_encerramento))/60,1) tempo_medio_h,
 ROUND(100*AVG(os.data_encerramento<=COALESCE(os.sla_vencimento,os.prazo)),1) sla,
 ROUND(AVG(COALESCE(os.reabertura_contagem,0)),2) reaberturas
 FROM usuarios u LEFT JOIN ordens_servico os ON os.agente_id=u.id AND os.status='ENCERRADA' AND $ordemAtivaOs
 WHERE u.perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS') GROUP BY u.id,u.nome ORDER BY sla DESC,tempo_medio_h ASC")->fetchAll(PDO::FETCH_ASSOC);
$locais=$pdo->query("SELECT id,nome FROM locais ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$ativos=app_table_exists($pdo,'ativos')?$pdo->query("SELECT id,codigo_patrimonial FROM ativos WHERE ativo=1 ORDER BY codigo_patrimonial")->fetchAll(PDO::FETCH_ASSOC):[];
$inicioEscala=date('Y-m-d',strtotime('monday this week'));
$fimEscala=date('Y-m-d',strtotime($inicioEscala.' +34 days'));
$stEscalas=$pdo->prepare("SELECT ca.*,u.nome agente_nome,l.nome local_nome
 FROM cronograma_agentes ca JOIN usuarios u ON u.id=ca.agente_id
 LEFT JOIN locais l ON l.id=ca.local_id WHERE ca.semana_inicio BETWEEN ? AND ?
 ORDER BY ca.semana_inicio,ca.dia_semana,ca.hora_inicio,u.nome");
$stEscalas->execute([$inicioEscala,$fimEscala]);
$escalas=$stEscalas->fetchAll(PDO::FETCH_ASSOC);
$inicioMesEscala=$mesEscala.'-01';
$fimMesEscala=date('Y-m-t',strtotime($inicioMesEscala));
$whereFuncaoEscala=$funcaoFiltro!==''?' AND es.funcao=?':'';
$stMensal=$pdo->prepare("SELECT es.*,u.nome usuario_nome FROM escalas_servico es
 JOIN usuarios u ON u.id=es.usuario_id
 WHERE es.data_servico BETWEEN ? AND ? $whereFuncaoEscala
 ORDER BY es.funcao,u.nome,es.data_servico");
$paramsEscalaMensal=[$inicioMesEscala,$fimMesEscala];
if($funcaoFiltro!=='') $paramsEscalaMensal[]=$funcaoFiltro;
$stMensal->execute($paramsEscalaMensal);
$escalaMensal=$stMensal->fetchAll(PDO::FETCH_ASSOC);
$agentesEscala=[];
foreach($escalaMensal as $itemEscala) {
    $uid=(int)$itemEscala['usuario_id'];
    if(!isset($agentesEscala[$uid])) $agentesEscala[$uid]=[
        'id'=>$uid,'nome'=>$itemEscala['usuario_nome'],'posto'=>$itemEscala['posto'],
        'funcao'=>$itemEscala['funcao']?:'Agente de Manutenção',
        'intervalo_inicio'=>$itemEscala['intervalo_inicio'],'intervalo_fim'=>$itemEscala['intervalo_fim'],'dias'=>[]
    ];
    $agentesEscala[$uid]['dias'][$itemEscala['data_servico']]=$itemEscala;
}
$falhasRepetidas=$pdo->query("SELECT af.ativo_id,a.codigo_patrimonial,COUNT(*) ocorrencias,MAX(af.registrada_em) ultima
 FROM ativo_falhas af LEFT JOIN ativos a ON a.id=af.ativo_id
 WHERE af.registrada_em>=DATE_SUB(NOW(),INTERVAL 90 DAY)
 GROUP BY af.ativo_id,a.codigo_patrimonial HAVING COUNT(*)>=2 ORDER BY ocorrencias DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$temposStatus=$pdo->query("SELECT status,ROUND(AVG(COALESCE(segundos,TIMESTAMPDIFF(SECOND,iniciado_em,NOW())))/3600,1) horas
 FROM os_status_tempos GROUP BY status ORDER BY horas DESC")->fetchAll(PDO::FETCH_ASSOC);
render_header('Painel de gestão integrada');
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-0">Painel de gestão integrada</h1><small class="text-muted">Operação, SLA, equipe, materiais, preventivas e conhecimento.</small></div><a class="btn btn-outline-primary" href="<?=e(app_path('notificacoes'))?>">Notificações</a></div>
<div class="row g-3 mb-4">
 <?php foreach([['Abertas',$kpi['abertas']??0,'primary'],['Atrasadas',$kpi['atrasadas']??0,'danger'],['Críticas',$kpi['criticas']??0,'warning'],['TMA (h)',$kpi['tma_horas']??0,'info'],['Solução (h)',$kpi['tms_horas']??0,'secondary'],['SLA %',$kpi['sla_percentual']??0,'success'],['Reabertura %',$kpi['reabertura_percentual']??0,'dark']] as $card): ?>
 <div class="col-6 col-lg"><div class="card p-3 h-100 border-start border-<?=$card[2]?> border-4"><strong class="h4 mb-0"><?=e($card[1])?></strong><small><?=e($card[0])?></small></div></div><?php endforeach ?>
</div>
<?php if($reservas): ?><div class="card p-3 mb-3 border-danger"><h2 class="h5">Aprovações de estoque negativo</h2><?php foreach($reservas as $r): ?><div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center"><span><strong><?=e($r['material_nome'])?></strong> — <?=e($r['quantidade'])?> para <?=e($r['numero_os'])?><br><small><?=e($r['justificativa'])?></small></span><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="reserva_id" value="<?=e($r['id'])?>"><button class="btn btn-sm btn-success" name="action" value="aprovar_estoque">Aprovar</button> <button class="btn btn-sm btn-danger" name="action" value="reprovar_estoque">Reprovar</button></form></div><?php endforeach ?></div><?php endif ?>
<div class="row g-3">
 <div class="col-xl-7"><div class="card p-3"><h2 class="h5">Disponibilidade e capacidade da equipe</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Técnico</th><th>Estado</th><th>Carga</th><th>Especialidades</th><th></th></tr></thead><tbody><?php foreach($equipe as $e): ?><tr><td><?=e($e['nome'])?></td><td><span class="badge text-bg-<?=$e['estado']==='DISPONIVEL'?'success':'secondary'?>"><?=e($e['estado'])?></span></td><td><?=e($e['carga'])?> / <?=e($e['capacidade'])?></td><td><?=e($e['especialidades']?:'-')?></td><td><form method="post" class="d-flex gap-1"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="estado_equipe"><input type="hidden" name="usuario_id" value="<?=e($e['id'])?>"><select class="form-select form-select-sm" name="estado"><?php foreach(['DISPONIVEL','OCUPADO','EM_ATENDIMENTO','AUSENTE','FERIAS','AFASTADO'] as $s): ?><option <?=$s===$e['estado']?'selected':''?>><?=$s?></option><?php endforeach ?></select><input class="form-control form-control-sm" type="number" name="capacidade_maxima" value="<?=e($e['capacidade'])?>" min="1" style="width:70px"><button class="btn btn-sm btn-primary">Salvar</button></form></td></tr><?php endforeach ?></tbody></table></div></div></div>
 <div class="col-xl-5"><div class="card p-3 mb-3"><h2 class="h5">Adicionar especialidade</h2><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="especialidade"><div class="col-5"><select class="form-select" name="usuario_id"><?php foreach($equipe as $e): ?><option value="<?=e($e['id'])?>"><?=e($e['nome'])?></option><?php endforeach ?></select></div><div class="col-5"><input class="form-control" name="especialidade" placeholder="Especialidade" required></div><div class="col-2"><button class="btn btn-primary">+</button></div></form></div>
 <div class="card p-3"><h2 class="h5">Feriado/indisponibilidade operacional</h2><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="feriado"><div class="col-4"><input class="form-control" type="date" name="data" required></div><div class="col-5"><input class="form-control" name="nome" placeholder="Motivo" required></div><div class="col-3"><button class="btn btn-primary">Cadastrar</button></div></form></div></div>
</div>
<div class="row g-3 mt-1">
 <div class="col-lg-6"><div class="card p-3 h-100"><h2 class="h5">Produtividade equilibrada</h2><small class="text-muted">Combina volume, tempo, SLA e reaberturas.</small><table class="table table-sm"><thead><tr><th>Técnico</th><th>OS</th><th>Tempo h</th><th>SLA %</th><th>Reab.</th></tr></thead><tbody><?php foreach($produtividade as $p): ?><tr><td><?=e($p['nome'])?></td><td><?=e($p['concluidas'])?></td><td><?=e($p['tempo_medio_h']?:'-')?></td><td><?=e($p['sla']?:'-')?></td><td><?=e($p['reaberturas'])?></td></tr><?php endforeach ?></tbody></table></div></div>
 <div class="col-lg-3"><div class="card p-3 h-100"><h2 class="h5">Custo por setor</h2><?php foreach($custosSetor as $c): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?=e($c['nome'])?></span><strong>R$ <?=e(number_format((float)$c['custo'],2,',','.'))?></strong></div><?php endforeach ?></div></div>
 <div class="col-lg-3"><div class="card p-3 h-100"><h2 class="h5">Custo por categoria</h2><?php foreach($custosCategoria as $c): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?=e($c['nome'])?></span><strong>R$ <?=e(number_format((float)$c['custo'],2,',','.'))?></strong></div><?php endforeach ?></div></div>
</div>
<div class="card p-3 mt-3"><div class="d-flex justify-content-between align-items-center"><div><h2 class="h5 mb-1">Manutenção recorrente</h2><span class="text-muted">Calendário, medidores, feriados e geração automática de OS.</span></div><a class="btn btn-primary" href="<?=e(app_path('preventivas'))?>">Configurar preventivas</a></div></div>
<div class="row g-3 mt-1">
 <div class="col-lg-6"><div class="card p-3 h-100"><h2 class="h5">Tempo médio parado por status</h2><?php foreach($temposStatus as $t): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?=e($t['status'])?></span><strong><?=e($t['horas'])?> h</strong></div><?php endforeach ?></div></div>
 <div class="col-lg-6"><div class="card p-3 h-100"><h2 class="h5">Falhas repetidas (90 dias)</h2><?php if(!$falhasRepetidas): ?><span class="text-muted">Nenhuma repetição detectada.</span><?php endif; foreach($falhasRepetidas as $f): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?=e($f['codigo_patrimonial']?:('Ativo #'.$f['ativo_id']))?></span><strong><?=e($f['ocorrencias'])?> ocorrências</strong></div><?php endforeach ?></div></div>
</div>
<div class="card p-3 mt-3"><h2 class="h5">Leitura de medidor</h2><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="medidor"><div class="col-md-4"><select class="form-select" name="ativo_id" required><option value="">Equipamento</option><?php foreach($ativos as $a): ?><option value="<?=e($a['id'])?>"><?=e($a['codigo_patrimonial'])?></option><?php endforeach ?></select></div><div class="col-md-2"><input class="form-control" name="tipo" value="HORAS" required></div><div class="col-md-2"><input class="form-control" type="number" step=".01" name="valor_atual" placeholder="Leitura atual" required></div><div class="col-md-2"><input class="form-control" type="number" step=".01" name="limite_proxima_os" placeholder="Próximo limite" required></div><div class="col-md-2"><input type="hidden" name="incremento_ciclo" value="1"><button class="btn btn-primary w-100">Registrar</button></div></form></div>
<div class="card p-3 mt-3" id="escala-servico">
 <style>
  .escala-modelo table{font-size:.78rem;min-width:1050px}.escala-modelo th{background:#d9dcdf;text-align:center}
  .escala-modelo td,.escala-modelo th{border:1px solid #555!important;vertical-align:middle}
  .escala-modelo .plantao{text-align:center;line-height:1.15}.escala-modelo .folga{text-align:center;font-weight:700}
  .escala-modelo .domingo{color:#dc3545}.escala-modelo .fora-mes{background:#f3f3f3}
  @media print{body>*:not(main){display:none!important}.escala-config,.nao-imprimir{display:none!important}.escala-modelo table{font-size:9px;min-width:0}.card{border:0}}
 </style>
 <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-0">Escala de serviço por função</h2><small class="text-muted">Modelo mensal 12x36 com ajustes individuais por dia.</small></div><button class="btn btn-sm btn-outline-secondary nao-imprimir" onclick="window.print()">Imprimir escala</button></div>
 <form method="get" class="row g-2 mb-3 escala-config"><div class="col-md-3"><label class="form-label">Mês da escala</label><input class="form-control" type="month" name="mes" value="<?=e($mesEscala)?>"></div><div class="col-md-4"><label class="form-label">Visualizar setor/função</label><select class="form-select" name="visualizar_funcao"><option value="">Todos os setores/funções</option><?php foreach($funcoesEscala as $funcao): ?><option value="<?=e($funcao)?>" <?=$funcaoFiltro===$funcao?'selected':''?>><?=e($funcao)?></option><?php endforeach ?></select></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Visualizar</button></div></form>
 <form method="post" class="row g-2 mb-4 escala-config">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="escala_12x36"><input type="hidden" name="mes" value="<?=e($mesEscala)?>"><input type="hidden" name="visualizar_funcao" value="<?=e($funcaoFiltro)?>">
  <div class="col-md-3"><label class="form-label">Efetivo</label><select class="form-select" name="agente_id" required><option value="">Selecione</option><?php foreach($colaboradoresEscala as $e): ?><option value="<?=e($e['id'])?>"><?=e($e['nome'])?></option><?php endforeach ?></select></div>
  <div class="col-md-2"><label class="form-label">Função</label><select class="form-select" name="funcao" required><?php foreach($funcoesEscala as $funcao): ?><option><?=e($funcao)?></option><?php endforeach ?></select></div>
  <div class="col-md-2"><label class="form-label">Primeiro plantão</label><input class="form-control" type="date" name="primeiro_plantao" min="<?=e($inicioMesEscala)?>" max="<?=e($fimMesEscala)?>" required></div>
  <div class="col-md-1"><label class="form-label">Entrada</label><input class="form-control" type="time" name="hora_inicio" value="07:00" required></div>
  <div class="col-md-1"><label class="form-label">Saída</label><input class="form-control" type="time" name="hora_fim" value="19:00" required></div>
  <div class="col-md-1"><label class="form-label">Int. início</label><input class="form-control" type="time" name="intervalo_inicio" value="12:00" required></div>
  <div class="col-md-1"><label class="form-label">Int. fim</label><input class="form-control" type="time" name="intervalo_fim" value="13:00" required></div>
  <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100">Gerar</button></div>
 </form>
 <details class="border rounded p-3 mb-4 escala-config"><summary class="fw-semibold">Ajustar horário ou folga de um dia específico</summary>
  <form method="post" class="row g-2 mt-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="escala_ajustar_dia"><input type="hidden" name="mes" value="<?=e($mesEscala)?>"><input type="hidden" name="visualizar_funcao" value="<?=e($funcaoFiltro)?>">
   <div class="col-md-3"><label class="form-label">Efetivo</label><select class="form-select" name="agente_id" required><option value="">Selecione</option><?php foreach($colaboradoresEscala as $e): ?><option value="<?=e($e['id'])?>"><?=e($e['nome'])?></option><?php endforeach ?></select></div>
   <div class="col-md-2"><label class="form-label">Função</label><select class="form-select" name="funcao"><?php foreach($funcoesEscala as $funcao): ?><option><?=e($funcao)?></option><?php endforeach ?></select></div>
   <div class="col-md-2"><label class="form-label">Dia</label><input class="form-control" type="date" name="data_servico" min="<?=e($inicioMesEscala)?>" max="<?=e($fimMesEscala)?>" required></div>
   <div class="col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo" id="escala_tipo_dia"><option value="TRABALHO">Trabalho</option><option value="FOLGA">Folga</option></select></div>
   <div class="col-md-1"><label class="form-label">Entrada</label><input class="form-control horario-dia" type="time" name="hora_inicio" value="07:00"></div>
   <div class="col-md-2"><label class="form-label">Saída</label><input class="form-control horario-dia" type="time" name="hora_fim" value="19:00"></div>
   <div class="col-md-2"><label class="form-label">Intervalo início</label><input class="form-control horario-dia" type="time" name="intervalo_inicio" value="12:00"></div>
   <div class="col-md-2"><label class="form-label">Intervalo fim</label><input class="form-control horario-dia" type="time" name="intervalo_fim" value="13:00"></div>
   <div class="col-md-2 d-flex align-items-end"><button class="btn btn-warning w-100">Salvar ajuste</button></div>
  </form>
 </details>
 <div class="escala-modelo">
 <?php
 $diasCabecalho=['seg','ter','qua','qui','sex','sáb','dom'];
 $inicioCalendario=strtotime('monday this week',strtotime($inicioMesEscala));
 if((int)date('N',strtotime($inicioMesEscala))===1) $inicioCalendario=strtotime($inicioMesEscala);
 $fimCalendario=strtotime('sunday this week',strtotime($fimMesEscala));
 if((int)date('N',strtotime($fimMesEscala))===7) $fimCalendario=strtotime($fimMesEscala);
 for($semana=$inicioCalendario;$semana<=$fimCalendario;$semana=strtotime('+7 days',$semana)):
 ?>
  <div class="table-responsive mb-3"><table class="table table-sm mb-0"><thead><tr><th>Função</th><th>Efetivo</th><th>Intervalo</th><?php for($d=0;$d<7;$d++): $dt=strtotime("+$d days",$semana); ?><th class="<?=$d===6?'domingo':''?>"><?=e($diasCabecalho[$d])?><br><?=e(date('d/m',$dt))?></th><?php endfor ?></tr></thead><tbody>
  <?php foreach($agentesEscala as $indice=>$agente): ?><tr><td><?=e($agente['funcao'])?></td><td><strong><?=e($agente['nome'])?></strong></td><td class="text-center"><?=e($agente['intervalo_inicio']?substr($agente['intervalo_inicio'],0,5).' às '.substr($agente['intervalo_fim'],0,5):'-')?></td>
   <?php for($d=0;$d<7;$d++): $dataCelula=date('Y-m-d',strtotime("+$d days",$semana)); $item=$agente['dias'][$dataCelula]??null; $fora=substr($dataCelula,0,7)!==$mesEscala; ?>
    <td class="<?=($d===6?'domingo ':'').($fora?'fora-mes':'')?>"><?php if($item): ?><?php if($item['tipo']==='FOLGA'): ?><div class="folga">Folga</div><?php else: ?><div class="plantao"><strong><?=e($item['jornada_horas'])?></strong><br><?=e(substr($item['hora_inicio'],0,5))?> às <?=e(substr($item['hora_fim'],0,5))?></div><?php endif ?><?php endif ?></td>
   <?php endfor ?></tr><?php endforeach ?>
  <?php if(!$agentesEscala): ?><tr><td colspan="10" class="text-center text-muted py-3">Cadastre os colaboradores acima para gerar a escala mensal por função.</td></tr><?php endif ?>
  </tbody></table></div>
 <?php endfor ?>
  <?php if($agentesEscala): ?><div class="row g-4 mt-4 mb-2"><?php foreach($agentesEscala as $agente): ?><div class="col-6 col-lg text-center"><div class="border-top border-dark pt-1"><?=e($agente['nome'])?></div></div><?php endforeach ?></div><?php endif ?>
 </div>
 <?php if($agentesEscala): ?><div class="d-flex flex-wrap gap-2 escala-config"><?php foreach($agentesEscala as $agente): ?><form method="post" onsubmit="return confirm('Remover a escala mensal de <?=e($agente['nome'])?>?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="escala_remover_agente"><input type="hidden" name="mes" value="<?=e($mesEscala)?>"><input type="hidden" name="visualizar_funcao" value="<?=e($funcaoFiltro)?>"><input type="hidden" name="agente_id" value="<?=e($agente['id'])?>"><button class="btn btn-sm btn-outline-danger">Remover <?=e($agente['nome'])?></button></form><?php endforeach ?></div><?php endif ?>
</div>
<script>
(() => {
 const tipo=document.getElementById('escala_tipo_dia');
 const sincronizar=()=>document.querySelectorAll('.horario-dia').forEach(campo=>campo.disabled=tipo?.value==='FOLGA');
 tipo?.addEventListener('change',sincronizar); sincronizar();
})();
</script>
<?php render_footer(); ?>
