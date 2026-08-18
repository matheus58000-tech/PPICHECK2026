<?php
require_once 'conexao.php'; 

$nome = 'Administrador Mestre';
$siape = '999999';
$email = 'mestre@check.com';
$senha_plana = 'admin123';
$tipo = 'admin';

$senha_hash = password_hash($senha_plana, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO Usuarios (Nome, SIAPE, Email, Senha, Tipo_user) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nome, $siape, $email, $senha_hash, $tipo);

if ($stmt->execute()) {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: #10ac84;'>✅ Administrador criado com sucesso!</h1>";
    echo "<h3>Seus dados de acesso:</h3>";
    echo "<p><strong>Aba de Login:</strong> ADMIN</p>";
    echo "<p><strong>SIAPE:</strong> $siape</p>";
    echo "<p><strong>Senha:</strong> $senha_plana</p>";
    echo "<br><br><a href='index.php' style='padding: 10px 20px; background: #0d005f; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Ir para o Login</a>";
    echo "</div>";
} else {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #dc3545;'>Erro ao criar usuário: " . $stmt->error . "</h2>";
    echo "<p>Talvez o SIAPE ou E-mail já existam no banco de dados.</p>";
    echo "</div>";
}

$stmt->close();
$conn->close();
?>
