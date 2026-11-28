<?php
session_start();
require_once __DIR__ . '/conexao.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$identificador = trim($_POST['usuario'] ?? '');
$cpf_limpo     = preg_replace('/[^0-9]/', '', $identificador);
$senha         = $_POST['senha'] ?? '';

if (strlen($cpf_limpo) !== 11) {
    $_SESSION['erro_campo'] = "usuario";
    $_SESSION['erro_msg']   = "Informe um CPF válido com 11 dígitos.";
    $_SESSION['login_cpf_anterior'] = $identificador;
    session_write_close();
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT id_user, Nome, Senha, Tipo_user, status, Email, validado FROM Usuarios WHERE CPF = ?");
$stmt->bind_param("s", $cpf_limpo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    $stmt->close();

    if (isset($usuario['status']) && $usuario['status'] === 'bloqueado') {
        $_SESSION['erro_campo'] = "usuario";
        $_SESSION['erro_msg']   = "Sua conta foi bloqueada pelo administrador.";
        $_SESSION['login_cpf_anterior'] = $identificador;
        session_write_close();
        header("Location: index.php");
        exit();
    }

    // 1º acesso de conta criada internamente no laboratório (Senha nula ou vazia): entra somente com CPF
    if (empty($usuario['Senha'])) {
        unset($_SESSION['etapa_criacao_senha'], $_SESSION['recuperacao_verificada'], $_SESSION['email_recuperacao'], $_SESSION['login_cpf_anterior']);

        $codigo = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $_SESSION['login_temp'] = [
            'id_user'   => $usuario['id_user'],
            'Nome'      => $usuario['Nome'],
            'Tipo_user' => $usuario['Tipo_user']
        ];
        $_SESSION['codigo_verificacao']        = $codigo;
        $_SESSION['codigo_expira']             = time() + 600;
        $_SESSION['tentativas_verificacao']    = 0;
        $_SESSION['acao_verificacao']          = 'primeiro_login_criacao_senha';
        $_SESSION['email_destino_verificacao'] = $usuario['Email'];
        $_SESSION['email_pendente_envio']      = true;

        session_write_close();
        header("Location: verificacao.php");
        exit();
    }

    // Conta já possui senha definida: exige senha válida
    if ($senha === '') {
        $_SESSION['erro_campo'] = "senha";
        $_SESSION['erro_msg']   = "Esta conta já possui senha. Digite sua senha para entrar.";
        $_SESSION['login_cpf_anterior'] = $identificador;
        session_write_close();
        header("Location: index.php");
        exit();
    }

    if (password_verify($senha, $usuario['Senha'])) {
        unset($_SESSION['login_cpf_anterior'], $_SESSION['etapa_criacao_senha'], $_SESSION['recuperacao_verificada'], $_SESSION['email_recuperacao']);

        if (isset($usuario['validado']) && $usuario['validado'] === 'nao') {
            $codigo = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $_SESSION['login_temp'] = [
                'id_user'   => $usuario['id_user'],
                'Nome'      => $usuario['Nome'],
                'Tipo_user' => $usuario['Tipo_user']
            ];
            $_SESSION['codigo_verificacao']        = $codigo;
            $_SESSION['codigo_expira']             = time() + 600;
            $_SESSION['tentativas_verificacao']    = 0;
            $_SESSION['acao_verificacao']          = 'primeiro_login';
            $_SESSION['email_destino_verificacao'] = $usuario['Email'];
            $_SESSION['email_pendente_envio']      = true;

            session_write_close();
            header("Location: verificacao.php");
            exit();
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario_id']   = $usuario['id_user'];
            $_SESSION['usuario_nome'] = $usuario['Nome'];
            $_SESSION['usuario_tipo'] = $usuario['Tipo_user'];

            $pagina_destino = ($usuario['Tipo_user'] === 'padrao') ? "FECHECKCOMUM.php" : "FECHECKADM.php";
            session_write_close();
            header("Location: " . $pagina_destino);
            exit();
        }
    } else {
        $_SESSION['erro_campo'] = "senha";
        $_SESSION['erro_msg']   = "Senha incorreta!";
        $_SESSION['login_cpf_anterior'] = $identificador;
        session_write_close();
        header("Location: index.php");
        exit();
    }
} else {
    $stmt->close();
    $_SESSION['erro_campo'] = "usuario";
    $_SESSION['erro_msg']   = "CPF não encontrado no sistema.";
    $_SESSION['login_cpf_anterior'] = $identificador;
    session_write_close();
    header("Location: index.php");
    exit();
}
