<?php
session_start();
require_once 'conexao.php';
require_once 'envia_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificador = trim($_POST['usuario']);
    $cpf_limpo = preg_replace('/[^0-9]/', '', $identificador);
    $senha = $_POST['senha'];

    // Adicionamos a coluna "validado" na busca
    $stmt = $conn->prepare("SELECT id_user, Nome, Senha, Tipo_user, status, Email, validado FROM Usuarios WHERE CPF = ?");
    $stmt->bind_param("s", $cpf_limpo);
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
            
            // Verifica a nova coluna no banco
            if (isset($usuario['validado']) && $usuario['validado'] === 'nao') {
                $codigo = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                $_SESSION['login_temp'] = [
                    'id_user' => $usuario['id_user'],
                    'Nome' => $usuario['Nome'],
                    'Tipo_user' => $usuario['Tipo_user']
                ];
                $_SESSION['codigo_verificacao'] = $codigo;
                $_SESSION['acao_verificacao'] = 'primeiro_login';
                
                enviarCodigoEmail($usuario['Email'], $codigo);
                
                unset($_SESSION['erro_campo'], $_SESSION['erro_msg']);
                header("Location: verificacao.php");
                exit();
            } 
            // Se já for validado ('sim'), loga direto
            else {
                $_SESSION['usuario_id'] = $usuario['id_user'];
                $_SESSION['usuario_nome'] = $usuario['Nome'];
                $_SESSION['usuario_tipo'] = $usuario['Tipo_user'];
                
                $pagina_destino = ($usuario['Tipo_user'] === 'padrao') ? "FECHECKCOMUM.php" : "FECHECKADM.php";
                header("Location: " . $pagina_destino);
                exit();
            }

        } else {
            $_SESSION['erro_campo'] = "senha";
            $_SESSION['erro_msg'] = "Senha incorreta!";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['erro_campo'] = "usuario";
        $_SESSION['erro_msg'] = "CPF não encontrado no sistema.";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
