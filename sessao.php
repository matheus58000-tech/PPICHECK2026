<?php
if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    $duracao = 28800; 
    ini_set('session.gc_maxlifetime', (string)$duracao);
    ini_set('session.cookie_lifetime', (string)$duracao);

    session_set_cookie_params([
        'lifetime' => $duracao,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

$tempo_limite = 28800;
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso'] > $tempo_limite)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['ultimo_acesso'] = time();