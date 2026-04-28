<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $matricula = trim($_POST['matricula']);
    $email = trim($_POST['email']);
    $data_nascimento = $_POST['data_nascimento'];
    $senha = $_POST['senha'];
    
    $erros = [];
    
    // --- 1. VALIDAÇÕES DE SEGURANÇA MULTIPLAS ---
    
    if (empty($nome) || strlen($nome) < 3) {
        $erros['cad_nome'] = "O nome deve ter pelo menos 3 letras.";
    }

    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf); 
    if (empty($cpf_limpo) || strlen($cpf_limpo) !== 11) {
        $erros['cad_cpf'] = "O CPF deve conter 11 dígitos.";
    }

    if (empty($matricula) || strlen($matricula) !== 10 || !is_numeric($matricula)) {
        $erros['cad_matricula'] = "Digite 10 números.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['cad_email'] = "Digite um e-mail válido.";
    }

    $data_atual = date("Y-m-d");
    if (empty($data_nascimento) || $data_nascimento > $data_atual) {
        $erros['cad_nascimento'] = "Data inválida.";
    }

    if (empty($senha) || strlen($senha) < 8) {
        $erros['cad_senha'] = "Mínimo 8 caracteres.";
    }

    // Se encontrou erros, devolve tudo pra tela
    if (count($erros) > 0) {
        $_SESSION['erros_cadastro'] = $erros;
        header("Location: index.php"); 
        exit();
    }

    // --- 2. SALVAMENTO NO BANCO ---
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO Usuarios (Nome, CPF, Matricula, Email, Data_nasc, Senha, Tipo_user) VALUES (?, ?, ?, ?, ?, ?, 'padrao')");
        $stmt->bind_param("ssssss", $nome, $cpf, $matricula, $email, $data_nascimento, $senha_hash);

        $stmt->execute();
        
        $_SESSION['msg_sucesso'] = "Conta criada com sucesso! Faça login.";
        header("Location: index.php");
        exit();

    } catch (\Exception $e) {
        $_SESSION['erros_cadastro']['geral'] = "Erro: CPF, Matrícula ou E-mail já estão em uso no sistema.";
        header("Location: index.php");
        exit();
    }

    $conn->close();
}
?>
