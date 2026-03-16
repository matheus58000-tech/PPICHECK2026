<?php
$host = 'localhost';
$user = 'root'; // Usuário padrão do XAMPP/WAMP
$pass = '';     // Senha padrão geralmente é vazia
$db   = 'sistema_check';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}
// Força o uso de UTF-8 para não bugar os acentos
$conn->set_charset("utf8");
?>