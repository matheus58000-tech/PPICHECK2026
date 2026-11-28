<?php
session_start();
require_once __DIR__ . '/envia_email.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit();
}

if (empty($_SESSION['codigo_verificacao']) || empty($_SESSION['email_destino_verificacao'])) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma verificação ativa.']);
    exit();
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$modo = $in['modo'] ?? 'inicial';

$email  = $_SESSION['email_destino_verificacao'];
$codigo = $_SESSION['codigo_verificacao'];

if ($modo === 'inicial') {
    if (empty($_SESSION['email_pendente_envio'])) {
        echo json_encode(['ok' => true, 'ja_enviado' => true]);
        exit();
    }
    $_SESSION['email_pendente_envio'] = false;
    $_SESSION['ultimo_envio_email']   = time();
} elseif ($modo === 'reenviar') {
    $ultimo = $_SESSION['ultimo_envio_email'] ?? 0;
    $restante = 30 - (time() - $ultimo);
    if ($restante > 0) {
        echo json_encode(['ok' => false, 'msg' => "Aguarde {$restante}s para reenviar o código."]);
        exit();
    }
    $codigo = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $_SESSION['codigo_verificacao']     = $codigo;
    $_SESSION['codigo_expira']          = time() + 600;
    $_SESSION['tentativas_verificacao'] = 0;
    $_SESSION['ultimo_envio_email']     = time();
} else {
    echo json_encode(['ok' => false, 'msg' => 'Modo inválido.']);
    exit();
}

// Libera a sessão ANTES da conexão SMTP para não travar a submissão do código em verificacao.php
session_write_close();

$enviado = enviarCodigoEmail($email, $codigo);

if ($enviado) {
    echo json_encode(['ok' => true, 'msg' => 'Código enviado com sucesso!']);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Não foi possível enviar o e-mail agora. Tente reenviar.']);
}