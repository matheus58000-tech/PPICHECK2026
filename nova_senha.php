<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['email_recuperacao'])) {
    header("Location: index.php");
    exit();
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $senha = $_POST['nova_senha'];
    $senha_conf = $_POST['confirma_senha'];

    if (strlen($senha) < 8 || !preg_match('/[A-Z]/', $senha) || !preg_match('/[0-9]/', $senha) || !preg_match('/[^A-Za-z0-9]/', $senha)) {
        $erro = "A senha não atende aos requisitos mínimos de segurança.";
    } elseif ($senha !== $senha_conf) {
        $erro = "As senhas não coincidem.";
    } else {
        $email = $_SESSION['email_recuperacao'];
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE Usuarios SET Senha = ? WHERE Email = ?");
        $stmt->bind_param("ss", $senha_hash, $email);
        
        if ($stmt->execute()) {
            $_SESSION['msg_sucesso'] = "Senha alterada com sucesso! Faça login.";
            unset($_SESSION['email_recuperacao']);
            header("Location: index.php");
            exit();
        } else {
            $erro = "Erro ao atualizar a senha. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - Sistema Check</title>
    <link rel="stylesheet" href="FELOGINCHECKCSS.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        .strength-meter-container {
            margin-top: -10px;
            margin-bottom: 15px;
            width: 100%;
        }
        .strength-meter {
            height: 6px;
            background-color: #eee;
            border-radius: 3px;
            overflow: hidden;
            width: 100%;
        }
        .strength-meter-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .strength-text {
            font-size: 0.75rem;
            color: #777;
            margin-top: 5px;
            display: block;
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
                    <h2 class="form-title" style="margin-bottom: 5px;">NOVA SENHA</h2>
                    <p style="color: #555; text-align: center; margin-bottom: 20px; font-size: 0.95rem;">
                        Digite sua nova senha de acesso.
                    </p>
                    
                    <?php if($erro): ?>
                        <div class="error-text" style="color: #dc3545; text-align: center; font-weight: bold; margin-bottom: 15px; background: #fff8f8; padding: 10px; border-radius: 6px; border: 1px solid #ffcaca;">
                            <i class="bi bi-exclamation-circle-fill"></i> <?php echo $erro; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="input-group" style="margin-bottom: 25px;">
                            <input type="password" name="nova_senha" id="rec-senha" placeholder="Nova Senha" required>
                            <i class="bi bi-eye toggle-password"></i>
                        </div>
                        
                        <div class="strength-meter-container">
                            <div class="strength-meter">
                                <div class="strength-meter-fill" id="rec-meter-fill"></div>
                            </div>
                            <span class="strength-text" id="rec-meter-text">Mín. 8 caracteres, 1 maiúscula, 1 número e 1 caractere especial.</span>
                        </div>

                        <div class="input-group">
                            <input type="password" name="confirma_senha" placeholder="Confirme a Nova Senha" required>
                            <i class="bi bi-eye toggle-password"></i>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="margin-top: 20px;">SALVAR SENHA</button>
                    </form>

                    <div class="links" style="margin-top: 25px;">
                        <a href="index.php" class="link-go-login">Cancelar e Voltar</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('bi-eye');
                    this.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.remove('bi-eye-slash');
                    this.classList.add('bi-eye');
                }
            });
        });

        const recSenha = document.getElementById('rec-senha');
        const recMeterFill = document.getElementById('rec-meter-fill');
        const recMeterText = document.getElementById('rec-meter-text');

        if(recSenha) {
            recSenha.addEventListener('input', function() {
                const val = this.value;
                let strength = 0;
                
                if (val.length >= 8) strength++;
                if (/[A-Z]/.test(val)) strength++;
                if (/[0-9]/.test(val)) strength++;
                if (/[^A-Za-z0-9]/.test(val)) strength++;

                let color = 'transparent';
                let width = '0%';
                let msg = 'Mín. 8 caracteres, 1 maiúscula, 1 número e 1 caractere especial.';

                if (val.length > 0) {
                    if (strength <= 2) {
                        color = '#dc3545';
                        width = (strength === 1 ? '25%' : '50%');
                        msg = 'Proteção: Fraca';
                    } else if (strength === 3) {
                        color = '#ffc107';
                        width = '75%';
                        msg = 'Proteção: Média';
                    } else if (strength === 4) {
                        color = '#004aad';
                        width = '100%';
                        msg = 'Proteção: Forte';
                    }
                }

                recMeterFill.style.width = width;
                recMeterFill.style.backgroundColor = color;
                recMeterText.innerText = msg;
                recMeterText.style.color = (val.length > 0) ? color : '#777';
            });
        }
    </script>
</body>
</html>