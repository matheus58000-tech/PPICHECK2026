<?php
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $matricula = $_POST['matricula'];
    $email = $_POST['email'];
    $data_nascimento = $_POST['data_nascimento'];
    
    // Criptografa a senha (NUNCA salve senhas em texto puro no banco)
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Prepara a query SQL (usando ? evita ataques de SQL Injection)
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, cpf, matricula, email, data_nascimento, senha, tipo_usuario) VALUES (?, ?, ?, ?, ?, ?, 'padrao')");
    $stmt->bind_param("ssssss", $nome, $cpf, $matricula, $email, $data_nascimento, $senha_hash);

    if ($stmt->execute()) {
        echo "<script>
                alert('Conta criada com sucesso! Faça login para continuar.');
                window.location.href='index.html';
              </script>";
    } else {
        echo "<script>
                alert('Erro ao cadastrar! Talvez o CPF, Matrícula ou E-mail já existam.');
                window.history.back();
              </script>";
    }

    $stmt->close();
    $conn->close();
}
?>