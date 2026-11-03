<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/geop_v8.php';
require_login();geop_v8_boot($pdo);header('Content-Type: application/json; charset=utf-8');
try{$title=trim((string)($_GET['titulo']??''));$desc=trim((string)($_GET['descricao']??''));$local=(int)($_GET['local_id']??0);$cat=(int)($_GET['categoria_id']??0);if(mb_strlen($title.' '.$desc,'UTF-8')<8){echo json_encode(['ok'=>true,'resultado'=>null],JSON_UNESCAPED_UNICODE);exit;}$r=geop_v8_ai_assist($pdo,$title,$desc,$local);if($cat>0)$r['duplicidades']=geop_v8_find_duplicates($pdo,$title,$desc,$local,$cat,5);echo json_encode(['ok'=>true,'resultado'=>$r],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'erro'=>'Não foi possível analisar a OS.'],JSON_UNESCAPED_UNICODE);}
