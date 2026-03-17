<?php
session_start();
require_once 'conexao.php';

// Função para gerar um código aleatório e verificar se já existe no banco
function gerarCodigoUnico($conn) {
    $codigo = '';
    $existe = true;
    
    while ($existe) {
        // Gera um código tipo CHK-X8B9Q
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $aleatorio = substr(str_shuffle($caracteres), 0, 5);
        $codigo = 'CHK-' . $aleatorio;
        
        // Verifica no banco se por acaso alguém já tem esse código
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE codigo_usuario = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            $existe = false; // Achou um código livre!
        }
        $stmt->close();
    }
    return $codigo;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $matricula = trim($_POST['matricula']);
    $email = trim($_POST['email']);
    $data_nascimento = $_POST['data_nascimento'];
    
    // Gera o código único para este novo aluno
    $codigo_usuario = gerarCodigoUnico($conn);
    
    // Criptografa a senha (NUNCA salve senhas em texto puro no banco)
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    try {
        // Prepara a query SQL adicionando o codigo_usuario
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, cpf, matricula, email, data_nascimento, senha, tipo_usuario, codigo_usuario) VALUES (?, ?, ?, ?, ?, ?, 'padrao', ?)");
        $stmt->bind_param("sssssss", $nome, $cpf, $matricula, $email, $data_nascimento, $senha_hash, $codigo_usuario);

        $stmt->execute();
        
        // Sucesso! Prepara o Toast Verde e manda pro index.php
        $_SESSION['msg_sucesso'] = "Conta criada com sucesso! Faça login.";
        header("Location: index.php");
        exit();

    } catch (\Exception $e) {
        // Erro! Prepara o Toast Vermelho, diz que foi na tela de cadastro e manda pro index.php
        $_SESSION['erro_campo'] = "cadastro";
        $_SESSION['erro_msg'] = "Erro ao cadastrar! CPF, Matrícula ou E-mail já estão em uso.";
        header("Location: index.php");
        exit();
    }

    $conn->close();
}
?>
