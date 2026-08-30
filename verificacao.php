<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['codigo_verificacao'])) {
    header("Location: index.php");
    exit();
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_digitado = trim($_POST['digit1'] . $_POST['digit2'] . $_POST['digit3'] . $_POST['digit4']);

    if ($codigo_digitado === $_SESSION['codigo_verificacao']) {
        
        if ($_SESSION['acao_verificacao'] === 'cadastro') {
            $dados = $_SESSION['dados_temp_cadastro'];
            
            $stmt = $conn->prepare("INSERT INTO Usuarios (Nome, CPF, Matricula, Email, Data_nasc, Senha, Tipo_user) VALUES (?, ?, ?, ?, ?, ?, 'padrao')");
            $stmt->bind_param("ssssss", $dados['nome'], $dados['cpf'], $dados['matricula'], $dados['email'], $dados['data_nascimento'], $dados['senha']);
            
            if ($stmt->execute()) {
                $_SESSION['msg_sucesso'] = "Conta criada com sucesso! Faça login.";
            }
            
            unset($_SESSION['codigo_verificacao'], $_SESSION['acao_verificacao'], $_SESSION['dados_temp_cadastro']);
            
            header("Location: index.php");
            exit();

        } elseif ($_SESSION['acao_verificacao'] === 'recuperacao') {
            header("Location: nova_senha.php");
            exit();
        }

    } else {
        $erro = "Código incorreto. Verifique e tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação - Sistema Check</title>
    <link rel="stylesheet" href="FELOGINCHECKCSS.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        .code-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        .code-box {
            width: 60px;
            height: 70px;
            font-size: 28px;
            text-align: center;
            border: 2px solid #ccc;
            border-radius: 12px;
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            color: #333;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }
        .code-box:focus {
            border-color: #004aad;
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 8px rgba(0, 74, 173, 0.2);
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            
            <div class="left-panel">
                <div class="logo-container">
                    <img src="LOGOCHECKADAP.jpg" alt="Logo Check" class="logo-img">
                </div>
            </div>

            <div class="divider"></div>

            <div class="right-panel">
                
                <div class="view-section active">
                    <h2 class="form-title" style="margin-bottom: 5px;">VERIFICAÇÃO</h2>
                    <p style="color: #555; text-align: center; margin-bottom: 20px; font-size: 0.95rem;">
                        Enviamos um código de 4 dígitos para o seu e-mail. Digite-o abaixo:
                    </p>
                    
                    <?php if($erro): ?>
                        <div class="error-text" style="color: #dc3545; text-align: center; font-weight: bold; margin-bottom: 15px; background: #fff8f8; padding: 10px; border-radius: 6px; border: 1px solid #ffcaca;">
                            <i class="bi bi-exclamation-circle-fill"></i> <?php echo $erro; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="code-container">
                            <input type="text" class="code-box" name="digit1" maxlength="1" required autocomplete="off" autofocus>
                            <input type="text" class="code-box" name="digit2" maxlength="1" required autocomplete="off">
                            <input type="text" class="code-box" name="digit3" maxlength="1" required autocomplete="off">
                            <input type="text" class="code-box" name="digit4" maxlength="1" required autocomplete="off">
                        </div>
                        
                        <button type="submit" class="btn-primary" style="margin-top: 10px;">VALIDAR CÓDIGO</button>
                    </form>

                    <div class="links" style="margin-top: 25px;">
                        <a href="index.php" class="link-go-login">Voltar para o Início</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.code-box');
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                if (e.target.value !== '' && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>