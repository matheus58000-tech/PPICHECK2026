<?php
require_once 'sessao.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ativo' => isset($_SESSION['usuario_id'])]);