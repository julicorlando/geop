<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$checks=[
    ['public/ol_form.php','Preencha a OL no mesmo padrão de abertura da Ordem de Serviço.'],
    ['public/ol_form.php','Categoria'],
    ['public/ol_form.php','value="Limpeza" readonly'],
    ['public/ol_form.php','Soluções sugeridas'],
    ['public/ol_form.php','Dados da Ordem de Limpeza'],
    ['public/ol_form.php','membro_ids[]'],
    ['public/ol_form.php','principal_id'],
    ['public/ol_form.php','supervisor_membro_id'],
    ['public/ol_form.php','tarefa_ids[]'],
    ['public/ol_form.php','fotos_antes[]'],
    ['includes/auth.php','function gestor_report_register_generation'],
    ['includes/auth.php',"'limite' => 10"],
    ['includes/auth.php','gestor_relatorio_controle'],
    ['public/relatorios.php',"acaoRelatorio === 'registrar_geracao'"],
    ['public/relatorios.php','Limite de 10 relatórios atingido'],
    ['public/relatorios.php',"autorizarGeracaoRelatorio('PDF'"],
    ['public/relatorios.php',"autorizarGeracaoRelatorio('CRONOGRAMA'"],
    ['sql/migrations/012_gestor_relatorios_10_sem_token.sql','012_gestor_relatorios_10_sem_token'],
];
$fail=[];
foreach($checks as [$file,$needle]){
    $content=(string)@file_get_contents($root.'/'.$file);
    if(!str_contains($content,$needle))$fail[]=$file.' -> '.$needle;
}
if($fail){fwrite(STDERR,"FALHAS:\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "Ajustes OL + relatórios Gestor V12: OK\n";
