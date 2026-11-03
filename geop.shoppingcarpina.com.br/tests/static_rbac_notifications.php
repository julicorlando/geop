<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];
$checks=[
 'RBAC central'=>["includes/auth.php",'function has_permission'],
 'Middleware de permissao'=>["includes/auth.php",'function require_permission'],
 'Tela de perfis'=>["public/perfis.php","perfil_permissoes"],
 'Bloqueio concorrente'=>["public/ordem_detalhe.php",'FOR UPDATE'],
 'Resolucao atomica'=>["public/ordem_detalhe.php","status='RESOLVIDA'"],
 'Vinculo token/solicitacao'=>["public/ordem_detalhe.php",'solicitacao_id'],
 'SW somente estatico'=>["public/sw.js",'STATIC_ASSETS'],
];
foreach($checks as $name=>[$file,$needle])if(!str_contains((string)file_get_contents($root.'/'.$file),$needle))$fail[]=$name;
if($fail){fwrite(STDERR,'Falhas: '.implode(', ',$fail).PHP_EOL);exit(1);}echo count($checks)." verificacoes estaticas aprovadas.\n";
