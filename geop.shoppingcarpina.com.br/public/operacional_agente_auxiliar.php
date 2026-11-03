<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Location: ' . app_path('agente'), true, 302);
exit;
