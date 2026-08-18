<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email_recuperacao']);

    $stmt = $conn->prepare("SELECT id_user FROM Usuarios WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['msg_sucesso'] = "Instruções enviadas para o seu e-mail!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['erro_campo'] = "recuperacao";
        $_SESSION['erro_msg'] = "Este e-mail não consta no nosso sistema.";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
