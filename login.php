<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificador = trim($_POST['usuario']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo_login']; 


    $_SESSION['ultimo_tipo_login'] = $tipo;

    $coluna_busca = "";
    $pagina_destino = "";

    if ($tipo === 'padrao') {
        $coluna_busca = "Matricula";
        $pagina_destino = "FECHECKCOMUM.php";
    } elseif ($tipo === 'resp') {
        $coluna_busca = "CPF";
        $pagina_destino = "FECHECKADM.php";
    } elseif ($tipo === 'admin') {
        $coluna_busca = "SIAPE";
        $pagina_destino = "FECHECKADM.php";
    } else {
        die("Tipo de usuário inválido.");
    }

    $stmt = $conn->prepare("SELECT id_user, Nome, Senha, Tipo_user, status FROM Usuarios WHERE $coluna_busca = ? AND Tipo_user = ?");
    $stmt->bind_param("ss", $identificador, $tipo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        
     
        if (isset($usuario['status']) && $usuario['status'] === 'bloqueado') {
            $_SESSION['erro_campo'] = "usuario";
            $_SESSION['erro_msg'] = "Sua conta foi bloqueada pelo administrador.";
            header("Location: index.php");
            exit();
        }
        
        if (password_verify($senha, $usuario['Senha'])) {
            $_SESSION['usuario_id'] = $usuario['id_user'];
            $_SESSION['usuario_nome'] = $usuario['Nome'];
            $_SESSION['usuario_tipo'] = $usuario['Tipo_user'];
            
    
            unset($_SESSION['erro_campo'], $_SESSION['erro_msg'], $_SESSION['ultimo_tipo_login']);
            
            header("Location: " . $pagina_destino);
            exit();
        } else {
          
            $_SESSION['erro_campo'] = "senha";
            $_SESSION['erro_msg'] = "Senha incorreta!";
            header("Location: index.php");
            exit();
        }
    } else {
       
        $_SESSION['erro_campo'] = "usuario";
        $_SESSION['erro_msg'] = "Usuário não encontrado neste nível de acesso.";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
