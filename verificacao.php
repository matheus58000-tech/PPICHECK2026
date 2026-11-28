<?php
session_start();
require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['codigo_verificacao'])) {
    header("Location: index.php");
    exit();
}

// Expiração do código (10 minutos)
if (isset($_SESSION['codigo_expira']) && time() > $_SESSION['codigo_expira']) {
    unset(
        $_SESSION['codigo_verificacao'],
        $_SESSION['codigo_expira'],
        $_SESSION['acao_verificacao'],
        $_SESSION['login_temp'],
        $_SESSION['etapa_criacao_senha'],
        $_SESSION['tentativas_verificacao'],
        $_SESSION['email_destino_verificacao'],
        $_SESSION['email_pendente_envio']
    );
    $_SESSION['erro_geral'] = "O código de verificação expirou. Solicite um novo acesso.";
    session_write_close();
    header("Location: index.php");
    exit();
}

$erro = "";
$etapa = isset($_SESSION['etapa_criacao_senha']) ? 2 : 1;
$email_pendente = !empty($_SESSION['email_pendente_envio']);
$email_destino  = $_SESSION['email_destino_verificacao'] ?? '';

function mascararEmail($email) {
    if (!str_contains($email, '@')) return $email;
    [$user, $dom] = explode('@', $email, 2);
    $vis = mb_substr($user, 0, min(2, mb_strlen($user)));
    return $vis . '***@' . $dom;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($etapa === 1 && isset($_POST['digit1'])) {
        $codigo_digitado = trim(
            ($_POST['digit1'] ?? '') .
            ($_POST['digit2'] ?? '') .
            ($_POST['digit3'] ?? '') .
            ($_POST['digit4'] ?? '')
        );

        if (hash_equals((string)$_SESSION['codigo_verificacao'], $codigo_digitado)) {
            $acao = $_SESSION['acao_verificacao'] ?? '';

            if ($acao === 'primeiro_login_criacao_senha') {
                $_SESSION['etapa_criacao_senha'] = true;
                unset($_SESSION['email_pendente_envio']);
                $etapa = 2;
                $erro = "";
            } elseif ($acao === 'primeiro_login') {
                $dados = $_SESSION['login_temp'];
                $stmt_update = $conn->prepare("UPDATE Usuarios SET validado = 'sim' WHERE id_user = ?");
                $stmt_update->bind_param("i", $dados['id_user']);
                $stmt_update->execute();
                $stmt_update->close();

                session_regenerate_id(true);
                $_SESSION['usuario_id']   = $dados['id_user'];
                $_SESSION['usuario_nome'] = $dados['Nome'];
                $_SESSION['usuario_tipo'] = $dados['Tipo_user'];

                unset(
                    $_SESSION['codigo_verificacao'],
                    $_SESSION['codigo_expira'],
                    $_SESSION['acao_verificacao'],
                    $_SESSION['login_temp'],
                    $_SESSION['tentativas_verificacao'],
                    $_SESSION['email_destino_verificacao'],
                    $_SESSION['email_pendente_envio']
                );
                $pagina_destino = ($dados['Tipo_user'] === 'padrao') ? "FECHECKCOMUM.php" : "FECHECKADM.php";
                session_write_close();
                header("Location: " . $pagina_destino);
                exit();
            } elseif ($acao === 'recuperacao') {
                $_SESSION['recuperacao_verificada'] = true;
                unset(
                    $_SESSION['codigo_verificacao'],
                    $_SESSION['codigo_expira'],
                    $_SESSION['acao_verificacao'],
                    $_SESSION['tentativas_verificacao'],
                    $_SESSION['email_destino_verificacao'],
                    $_SESSION['email_pendente_envio']
                );
                session_write_close();
                header("Location: nova_senha.php");
                exit();
            }
        } else {
            $_SESSION['tentativas_verificacao'] = ($_SESSION['tentativas_verificacao'] ?? 0) + 1;
            $restantes = 5 - $_SESSION['tentativas_verificacao'];
            if ($restantes <= 0) {
                unset(
                    $_SESSION['codigo_verificacao'],
                    $_SESSION['codigo_expira'],
                    $_SESSION['acao_verificacao'],
                    $_SESSION['login_temp'],
                    $_SESSION['etapa_criacao_senha'],
                    $_SESSION['tentativas_verificacao'],
                    $_SESSION['email_destino_verificacao'],
                    $_SESSION['email_pendente_envio'],
                    $_SESSION['email_recuperacao'],
                    $_SESSION['recuperacao_verificada']
                );
                $_SESSION['erro_geral'] = "Número máximo de tentativas excedido. Solicite um novo código.";
                session_write_close();
                header("Location: index.php");
                exit();
            }
            $erro = "Código incorreto. Tentativas restantes: $restantes";
        }
    }

    elseif ($etapa === 2 && isset($_POST['nova_senha'])) {
        $nova_senha     = $_POST['nova_senha'];
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        if (strlen($nova_senha) < 8 || !preg_match('/[A-Z]/', $nova_senha) || !preg_match('/[0-9]/', $nova_senha) || !preg_match('/[^A-Za-z0-9]/', $nova_senha)) {
            $erro = "A senha não atende aos requisitos mínimos de segurança.";
        } elseif ($nova_senha !== $confirma_senha) {
            $erro = "As senhas não coincidem.";
        } else {
            $dados = $_SESSION['login_temp'];
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            $stmt_update = $conn->prepare("UPDATE Usuarios SET Senha = ?, validado = 'sim' WHERE id_user = ?");
            $stmt_update->bind_param("si", $senha_hash, $dados['id_user']);
            $stmt_update->execute();
            $stmt_update->close();

            session_regenerate_id(true);
            $_SESSION['usuario_id']   = $dados['id_user'];
            $_SESSION['usuario_nome'] = $dados['Nome'];
            $_SESSION['usuario_tipo'] = $dados['Tipo_user'];

            unset(
                $_SESSION['codigo_verificacao'],
                $_SESSION['codigo_expira'],
                $_SESSION['acao_verificacao'],
                $_SESSION['login_temp'],
                $_SESSION['etapa_criacao_senha'],
                $_SESSION['tentativas_verificacao'],
                $_SESSION['email_destino_verificacao'],
                $_SESSION['email_pendente_envio']
            );

            $pagina_destino = ($dados['Tipo_user'] === 'padrao') ? "FECHECKCOMUM.php" : "FECHECKADM.php";
            session_write_close();
            header("Location: " . $pagina_destino);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação e Senha - Sistema Check</title>
    <link rel="stylesheet" href="FELOGINCHECKCSS.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        .code-container { display: flex; gap: 15px; justify-content: center; margin: 25px 0; }
        .code-box { width: 60px; height: 70px; font-size: 28px; text-align: center; border: 2px solid #ccc; border-radius: 12px; font-weight: 700; font-family: 'Montserrat', sans-serif; color: #333; background-color: #f9f9f9; transition: all 0.3s ease; }
        .code-box:focus { border-color: #004aad; background-color: #fff; outline: none; box-shadow: 0 0 8px rgba(0, 74, 173, 0.2); }
        .strength-meter-container { margin-top: -10px; margin-bottom: 15px; width: 100%; }
        .strength-meter { height: 6px; background-color: #eee; border-radius: 3px; overflow: hidden; width: 100%; }
        .strength-meter-fill { height: 100%; width: 0%; transition: width 0.3s ease, background-color 0.3s ease; }
        .strength-text { font-size: 0.75rem; color: #777; margin-top: 5px; display: block; }
        .email-status-box { font-size: 0.85rem; text-align: center; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; background: #f0f6ff; color: #004aad; border: 1px solid #cce0ff; }
        .email-status-box.erro { background: #fff8f8; color: #dc3545; border-color: #ffcaca; }
        .email-status-box.ok { background: #f0fff8; color: #10ac84; border-color: #b8f2d8; }
        .btn-reenviar { background: none; border: none; color: #004aad; font-weight: 600; font-size: 0.85rem; cursor: pointer; text-decoration: underline; margin-top: 12px; display: inline-block; }
        .btn-reenviar:disabled { color: #999; cursor: not-allowed; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="left-panel"><div class="logo-container"><img src="LOGOCHECKADAP.jpg" alt="Logo Check" class="logo-img"></div></div>
            <div class="divider"></div>
            <div class="right-panel">
                <div class="view-section active">

                    <?php if ($etapa === 1): ?>
                        <h2 class="form-title" style="margin-bottom: 5px;">VERIFICAÇÃO</h2>
                        <p style="color: #555; text-align: center; margin-bottom: 12px; font-size: 0.95rem;">
                            Digite o código de 4 dígitos enviado para <strong><?php echo htmlspecialchars(mascararEmail($email_destino)); ?></strong>:
                        </p>

                        <div id="email-status-banner" class="email-status-box <?php echo $email_pendente ? '' : 'ok'; ?>">
                            <?php if ($email_pendente): ?>
                                <i class="bi bi-hourglass-split"></i> Enviando código para seu e-mail...
                            <?php else: ?>
                                <i class="bi bi-envelope-check"></i> Código enviado! Verifique sua caixa de entrada e spam.
                            <?php endif; ?>
                        </div>

                        <?php if($erro): ?>
                            <div class="error-text" style="color: #dc3545; text-align: center; font-weight: bold; margin-bottom: 15px; background: #fff8f8; padding: 10px; border-radius: 6px; border: 1px solid #ffcaca;"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erro; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="code-container">
                                <input type="text" class="code-box" name="digit1" maxlength="1" inputmode="numeric" required autocomplete="off" autofocus>
                                <input type="text" class="code-box" name="digit2" maxlength="1" inputmode="numeric" required autocomplete="off">
                                <input type="text" class="code-box" name="digit3" maxlength="1" inputmode="numeric" required autocomplete="off">
                                <input type="text" class="code-box" name="digit4" maxlength="1" inputmode="numeric" required autocomplete="off">
                            </div>
                            <button type="submit" class="btn-primary" style="margin-top: 10px;">VALIDAR ACESSO</button>
                        </form>

                        <div style="text-align: center;">
                            <button type="button" id="btn-reenviar-codigo" class="btn-reenviar" <?php echo $email_pendente ? 'disabled' : ''; ?>>
                                Reenviar código
                            </button>
                        </div>

                    <?php else: ?>
                        <h2 class="form-title" style="margin-bottom: 5px;">DEFINIR SENHA</h2>
                        <p style="color: #555; text-align: center; margin-bottom: 20px; font-size: 0.95rem;">Crie sua senha definitiva de acesso ao sistema:</p>

                        <?php if($erro): ?>
                            <div class="error-text" style="color: #dc3545; text-align: center; font-weight: bold; margin-bottom: 15px; background: #fff8f8; padding: 10px; border-radius: 6px; border: 1px solid #ffcaca;"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erro; ?></div>
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

                            <button type="submit" class="btn-primary" style="margin-top: 20px;">SALVAR E ENTRAR</button>
                        </form>
                    <?php endif; ?>

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
                if (e.target.value !== '' && index < inputs.length - 1) inputs[index + 1].focus();
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) inputs[index - 1].focus();
            });
            
            input.addEventListener('paste', (e) => {
                const texto = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
                if (texto.length === 4) {
                    e.preventDefault();
                    inputs.forEach((box, idx) => { box.value = texto[idx] || ''; });
                    inputs[3].focus();
                }
            });
        });

        const banner = document.getElementById('email-status-banner');
        const btnReenviar = document.getElementById('btn-reenviar-codigo');

        function iniciarCooldownReenvio(segundos) {
            if (!btnReenviar) return;
            let rest = segundos;
            btnReenviar.disabled = true;
            btnReenviar.innerText = `Reenviar código (${rest}s)`;
            const timer = setInterval(() => {
                rest--;
                if (rest <= 0) {
                    clearInterval(timer);
                    btnReenviar.disabled = false;
                    btnReenviar.innerText = 'Reenviar código';
                } else {
                    btnReenviar.innerText = `Reenviar código (${rest}s)`;
                }
            }, 1000);
        }

        async function dispararEnvioEmail(modo) {
            if (!banner) return;
            banner.className = 'email-status-box';
            banner.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando código para seu e-mail...';
            if (btnReenviar) btnReenviar.disabled = true;

            try {
                const r = await fetch('disparar_email.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ modo })
                });
                const data = await r.json();
                if (data.ok) {
                    banner.className = 'email-status-box ok';
                    banner.innerHTML = '<i class="bi bi-envelope-check"></i> Código enviado! Verifique sua caixa de entrada e spam.';
                    iniciarCooldownReenvio(30);
                } else {
                    banner.className = 'email-status-box erro';
                    banner.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (data.msg || 'Erro ao enviar e-mail.');
                    if (btnReenviar) btnReenviar.disabled = false;
                }
            } catch (e) {
                banner.className = 'email-status-box erro';
                banner.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Falha de conexão ao disparar e-mail.';
                if (btnReenviar) btnReenviar.disabled = false;
            }
        }

        <?php if ($etapa === 1 && $email_pendente): ?>
        document.addEventListener('DOMContentLoaded', () => {
            dispararEnvioEmail('inicial');
        });
        <?php endif; ?>

        if (btnReenviar) {
            btnReenviar.addEventListener('click', () => dispararEnvioEmail('reenviar'));
        }

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
