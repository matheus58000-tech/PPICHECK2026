<?php
session_start();
require_once 'conexao.php';
require_once 'envia_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email_recuperacao']);

    $stmt = $conn->prepare("SELECT id_user FROM Usuarios WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $codigo = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        $_SESSION['email_recuperacao'] = $email;
        $_SESSION['codigo_verificacao'] = $codigo;
        $_SESSION['acao_verificacao'] = 'recuperacao';

        enviarCodigoEmail($email, $codigo);

        header("Location: verificacao.php");
        exit();
    } else {
        $_SESSION['erro_campo'] = "recuperacao";
        $_SESSION['erro_msg'] = "Este e-mail não consta no nosso sistema.";
        header("Location: index.php");
        exit();
    }
}
?>
