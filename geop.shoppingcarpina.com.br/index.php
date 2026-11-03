<?php
// Entrada alternativa para hospedagens sem suporte adequado ao .htaccess.
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$base = str_replace('\\', '/', dirname($script));
$base = trim($base, '/');
$prefix = $base !== '' && $base !== '.' ? '/' . $base : '';

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in() && (!empty($_SESSION['senha_troca_obrigatoria']) || password_change_required(current_user()))) {
    header('Location: ' . $prefix . '/public/mudar_senha.php?obrigatoria=1');
    exit;
}

header('Location: ' . $prefix . '/public/index.php');
exit;
