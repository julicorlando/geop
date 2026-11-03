<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$required=[
 'includes/limpeza.php','public/limpeza.php','public/limpeza_cronograma.php','public/limpeza_organograma.php',
 'public/ambiente_registro.php','public/ambiente_validar.php','public/ambiente_relatorios.php',
 'sql/ATUALIZACAO_MODULO_LIMPEZA_V2.sql','sql/ATUALIZACAO_MODULO_LIMPEZA_V3.sql',
 'sql/INSTALACAO_COMPLETA_MODULO_LIMPEZA_V3.sql','sql/migrations/006_limpeza_multiplos_responsaveis.sql',
 'sql/plano_limpeza_agosto_2026.json'
];
foreach($required as $file){if(!is_file($root.'/'.$file)){fwrite(STDERR,"Arquivo ausente: $file\n");exit(1);}}
$data=json_decode((string)file_get_contents($root.'/sql/plano_limpeza_agosto_2026.json'),true,512,JSON_THROW_ON_ERROR);
$groups=[];$marks=0;
foreach($data as $row){$groups[(string)$row['group']]=true;$marks+=count($row['days']??[]);}
if(count($groups)!==8||count($data)!==34||$marks!==532){fwrite(STDERR,"Dados da planilha divergentes: grupos=".count($groups).", tarefas=".count($data).", marcas=$marks\n");exit(1);}
$sql=(string)file_get_contents($root.'/sql/ATUALIZACAO_MODULO_LIMPEZA_V3.sql');
foreach(['limpeza_cronograma_responsaveis','ambiente_registro_responsaveis','responsavel_principal','nome_responsavel'] as $needle){if(strpos($sql,$needle)===false){fwrite(STDERR,"SQL V3 sem $needle\n");exit(1);}}
$cronograma=(string)file_get_contents($root.'/public/limpeza_cronograma.php');
foreach(['responsavel_membro_ids[]','responsavel_principal_id','limpeza_save_schedule_team','Copiar marcações e equipes'] as $needle){if(strpos($cronograma,$needle)===false){fwrite(STDERR,"Cronograma sem múltiplos responsáveis: $needle\n");exit(1);}}
$register=(string)file_get_contents($root.'/public/ambiente_registro.php');
foreach(['executor_membro_ids[]','executor_principal_id','ambiente_registro_responsaveis','Equipe que realizou a limpeza'] as $needle){if(strpos($register,$needle)===false){fwrite(STDERR,"Lançamento sem equipe múltipla: $needle\n");exit(1);}}
$reports=(string)file_get_contents($root.'/public/ambiente_relatorios.php');
if(strpos($reports,'Equipe responsável')===false||strpos($reports,'ambiente_registro_responsaveis')===false){fwrite(STDERR,"Relatórios sem suporte a equipes.\n");exit(1);}
echo "Limpeza V3 static test: OK (8 áreas, 34 atividades, 532 marcações, múltiplos responsáveis)\n";
