<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificador = trim($_POST['usuario']); // Pode ser matricula, cpf ou siape
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo_login']; // Vem do campo hidden no HTML

    // Define em qual coluna do banco o sistema vai procurar o usuário e pra onde ele vai após logar
    $coluna_busca = "";
    $pagina_destino = "";

    if ($tipo === 'padrao') {
        $coluna_busca = "matricula";
        $pagina_destino = "FECHECKCOMUM.php";
    } elseif ($tipo === 'resp') {
        $coluna_busca = "cpf";
        $pagina_destino = "FECHECKADM.php";
    } elseif ($tipo === 'admin') {
        $coluna_busca = "siape";
        $pagina_destino = "FECHECKADM.php";
    } else {
        die("Tipo de usuário inválido.");
    }

    // Busca o usuário no banco
    $stmt = $conn->prepare("SELECT id, nome, senha, tipo_usuario FROM usuarios WHERE $coluna_busca = ? AND tipo_usuario = ?");
    $stmt->bind_param("ss", $identificador, $tipo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        
        // Verifica se a senha digitada bate com a senha criptografada do banco
        if (password_verify($senha, $usuario['senha'])) {
            
            // Login feito com sucesso! Salva os dados na Sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
            
            // Redireciona para o painel correto
            header("Location: " . $pagina_destino);
            exit();
        } else {
            echo "<script>alert('Senha incorreta!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Usuário não encontrado neste nível de acesso!'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>