<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/patrimonio.php';
$tests=0;
function ok(bool $condition,string $message):void{global $tests;$tests++;if(!$condition){fwrite(STDERR,"FALHA: $message\n");exit(1);}}
ok(patrimonio_normalize_code('0000123')==='0000123','zeros a esquerda');
ok(patrimonio_normalize_code(" ABC\n")==='ABC','normalizacao sem conversao numerica');
ok(patrimonio_normalize_code("123' OR 1=1 --")==="123' OR 1=1 --",'entrada permanece valor para prepared statement');
try{patrimonio_identifier('table;DROP TABLE x');ok(false,'identificador malicioso');}catch(RuntimeException $e){ok(true,'identificador rejeitado');}
putenv('PATRIMONIO_DB_ENABLED=false');try{patrimonio_pdo();ok(false,'integracao desabilitada');}catch(RuntimeException $e){ok(str_contains($e->getMessage(),'habilitada'),'falha amigavel quando desabilitado');}
$sw=file_get_contents(dirname(__DIR__).'/public/sw.js');ok(!str_contains($sw,'patrimonio_api')&&!str_contains($sw,'patrimonios.php'),'service worker nao armazena consultas');
$scanner=file_get_contents(dirname(__DIR__).'/public/assets/patrimonio-scanner.js');ok(str_contains($scanner,'getUserMedia')&&str_contains($scanner,'BarcodeDetector')&&str_contains($scanner,'pagehide'),'camera e encerramento de stream');
echo "$tests testes unitarios/estaticos de patrimonio aprovados.\n";
