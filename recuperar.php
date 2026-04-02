<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email_recuperacao']);

    // Verifica se o e-mail existe no banco
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // E-mail encontrado! 
        // No mundo real, aqui você enviaria a API de e-mail (PHPMailer).
        $_SESSION['msg_sucesso'] = "Instruções enviadas para o seu e-mail!";
        header("Location: index.php");
        exit();
    } else {
        // E-mail não encontrado
        $_SESSION['erro_campo'] = "recuperacao";
        $_SESSION['erro_msg'] = "Este e-mail não consta no nosso sistema.";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>