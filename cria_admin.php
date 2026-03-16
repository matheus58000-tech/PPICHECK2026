<?php
require_once 'conexao.php'; // Puxa a sua conexão com o banco

$nome = 'Administrador Mestre';
$siape = '999999';
$email = 'mestre@check.com';
$senha_plana = 'admin123'; // Senha fácil para você testar
$tipo = 'admin';

// O próprio PHP vai criar a criptografia perfeita agora
$senha_hash = password_hash($senha_plana, PASSWORD_DEFAULT);

// Prepara o comando para inserir no banco
$stmt = $conn->prepare("INSERT INTO usuarios (nome, siape, email, senha, tipo_usuario) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nome, $siape, $email, $senha_hash, $tipo);

if ($stmt->execute()) {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: green;'>✅ Administrador criado com sucesso!</h1>";
    echo "<h3>Seus dados de acesso:</h3>";
    echo "<p><strong>Aba de Login:</strong> ADMIN</p>";
    echo "<p><strong>SIAPE:</strong> $siape</p>";
    echo "<p><strong>Senha:</strong> $senha_plana</p>";
    echo "<br><br><a href='index.html' style='padding: 10px 20px; background: #0d005f; color: white; text-decoration: none; border-radius: 5px;'>Ir para o Login</a>";
    echo "</div>";
} else {
    echo "<h2>Erro ao criar usuário: " . $stmt->error . "</h2>";
    echo "<p>Talvez o SIAPE ou E-mail já existam no banco.</p>";
}

$stmt->close();
$conn->close();
?>