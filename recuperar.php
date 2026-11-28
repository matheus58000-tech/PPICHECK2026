<?php
session_start();
require_once __DIR__ . '/conexao.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$email = mb_strtolower(trim($_POST['email_recuperacao'] ?? ''), 'UTF-8');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erro_campo'] = "recuperacao";
    $_SESSION['erro_msg']   = "Digite um e-mail válido.";
    session_write_close();
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT id_user, Email FROM Usuarios WHERE LOWER(Email) = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $stmt->close();

    unset($_SESSION['etapa_criacao_senha'], $_SESSION['recuperacao_verificada'], $_SESSION['login_temp']);

    $codigo = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

    $_SESSION['email_recuperacao']         = $row['Email'];
    $_SESSION['recuperacao_verificada']    = false;
    $_SESSION['codigo_verificacao']        = $codigo;
    $_SESSION['codigo_expira']             = time() + 600;
    $_SESSION['tentativas_verificacao']    = 0;
    $_SESSION['acao_verificacao']          = 'recuperacao';
    $_SESSION['email_destino_verificacao'] = $row['Email'];
    $_SESSION['email_pendente_envio']      = true;

    session_write_close();
    header("Location: verificacao.php");
    exit();
} else {
    $stmt->close();
    $_SESSION['erro_campo'] = "recuperacao";
    $_SESSION['erro_msg']   = "Este e-mail não consta no nosso sistema.";
    session_write_close();
    header("Location: index.php");
    exit();
}
