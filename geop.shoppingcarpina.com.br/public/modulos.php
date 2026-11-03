<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();
$modules = [
 ['Patrimônio e QR Code','ativos','Cadastro, documentos, histórico e abertura de OS por ativo.'],
 ['Checklists','checklist_modelos','Modelos configuráveis e validação de itens obrigatórios.'],
 ['Fotos antes/depois','categorias','Regras por categoria e metadados dos anexos.'],
 ['Preventivas automáticas','manutencoes_preventivas','Geração por cron, ciclo único e simulação.'],
 ['Solicitação por QR Code','tokens_seguros','Formulário público e acompanhamento por token.'],
 ['Central de aprovações','aprovacoes','Fila única com histórico de decisão.'],
 ['Rota diária','ordens_servico','Priorização por criticidade, SLA e disponibilidade.'],
 ['Base de conhecimento','conhecimento_artigos','Artigos versionados vinculáveis às categorias.'],
 ['Disponibilidade','equipe_disponibilidade','Escalas, férias, ausências e especialidades.'],
 ['Passagem de turno','passagens_turno','Pendências e confirmação de leitura.'],
 ['Terceiros','terceiros','Contratos, documentos, custos e vencimentos.'],
 ['Assinatura de conclusão','conclusao_assinaturas','Aceite simples, sem alegação de certificado digital.'],
 ['Dashboard executivo','ordens_servico','Indicadores filtráveis a partir do conjunto completo.'],
 ['Recorrências','recorrencias_os','Relações por ativo, local, categoria e termos.'],
 ['Mapa operacional','ativos','Coordenadas opcionais e suporte a planta interna.'],
 ['Relatório de reunião','ordens_servico','Resumo executivo exportável.'],
 ['Dispositivos IoT','dispositivos_iot','API autenticada e histórico de leituras.'],
 ['CFTV e rede','ativos_rede','Monitoramento com limiar de falhas consecutivas.'],
];
render_header('Módulos operacionais');
?>
<div class="row g-3">
<?php foreach($modules as [$title,$table,$description]): ?>
  <div class="col-md-6 col-xl-4"><section class="card h-100 p-3">
    <h2 class="h5"><?= e($title) ?></h2><p><?= e($description) ?></p>
    <small class="text-muted">Estrutura: <?= e($table) ?></small>
  </section></div>
<?php endforeach ?>
</div>
<?php render_footer(); ?>
