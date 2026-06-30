<?php
session_start();

$erro_campo = isset($_SESSION['erro_campo']) ? $_SESSION['erro_campo'] : '';
$erro_msg = isset($_SESSION['erro_msg']) ? $_SESSION['erro_msg'] : '';

$erros_cadastro = isset($_SESSION['erros_cadastro']) ? $_SESSION['erros_cadastro'] : [];

$msg_sucesso = isset($_SESSION['msg_sucesso']) ? $_SESSION['msg_sucesso'] : '';
$ultimo_tipo_login = isset($_SESSION['ultimo_tipo_login']) ? $_SESSION['ultimo_tipo_login'] : 'padrao';

unset($_SESSION['erro_campo'], $_SESSION['erro_msg'], $_SESSION['msg_sucesso'], $_SESSION['erros_cadastro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Check</title>
    <link rel="stylesheet" href="FELOGINCHECKCSS.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap" rel="stylesheet">
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
                
                <div id="view-login" class="view-section active">
                    <h2 class="form-title">LOGIN</h2>

                    <?php if(!empty($msg_sucesso)): ?>
                        <div class="success-box"><i class="bi bi-check-circle"></i> <?php echo $msg_sucesso; ?></div>
                    <?php endif; ?>

                    <div class="role-selector">
                        <button type="button" class="role-btn active" data-role="padrao">PADRÃO</button>
                        <button type="button" class="role-btn" data-role="resp">RESP.</button>
                        <button type="button" class="role-btn" data-role="admin">ADMIN</button>
                    </div>

                    <form id="login-form" action="login.php" method="POST">
                        <input type="hidden" name="tipo_login" id="tipo-login-hidden" value="padrao">

                        <div class="input-group">
                            <input type="text" id="login-identificador" name="usuario" placeholder="Matrícula" required class="<?php echo ($erro_campo === 'usuario') ? 'input-error' : ''; ?>">
                            <?php if($erro_campo === 'usuario'): ?>
                                <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erro_msg; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="input-group">
                            <input type="password" name="senha" placeholder="Senha" required class="<?php echo ($erro_campo === 'senha') ? 'input-error' : ''; ?>">
                            <i class="bi bi-eye toggle-password"></i>
                            <?php if($erro_campo === 'senha'): ?>
                                <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erro_msg; ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn-primary">ACESSAR</button>
                    </form>

                    <div class="links">
                        <a href="#" id="link-go-register">Não possui uma conta? Crie aqui.</a>
                        <a href="#" id="link-go-recover-login">Esqueceu sua senha?</a>
                    </div>
                </div>

                <div id="view-register" class="view-section hidden">
                    <h2 class="form-title" style="margin-bottom: 0;">CADASTRO</h2>
                    <h3 class="form-subtitle">USUÁRIO PADRÃO</h3>

                    <?php if(isset($erros_cadastro['geral'])): ?>
                        <div class="error-text-cadastro"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_cadastro['geral']; ?></div>
                    <?php endif; ?>

                    <form action="cadastrar.php" method="POST" novalidate>
                        
                        <div class="input-group">
                            <input type="text" name="nome" placeholder="Nome Completo" required class="<?php echo isset($erros_cadastro['cad_nome']) ? 'input-error' : ''; ?>">
                            <?php if(isset($erros_cadastro['cad_nome'])): ?>
                                <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_cadastro['cad_nome']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="input-row">
                            <div class="input-group">
                                <input type="text" id="cpf-input" name="cpf" placeholder="CPF" maxlength="14" required class="<?php echo isset($erros_cadastro['cad_cpf']) ? 'input-error' : ''; ?>">
                                <?php if(isset($erros_cadastro['cad_cpf'])): ?>
                                    <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_cadastro['cad_cpf']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="input-group">
                                <input type="text" name="matricula" placeholder="Matrícula" required class="<?php echo isset($erros_cadastro['cad_matricula']) ? 'input-error' : ''; ?>">
                                <?php if(isset($erros_cadastro['cad_matricula'])): ?>
                                    <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_cadastro['cad_matricula']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="input-group">
                            <input type="email" name="email" placeholder="E-mail" required class="<?php echo isset($erros_cadastro['cad_email']) ? 'input-error' : ''; ?>">
                            <?php if(isset($erros_cadastro['cad_email'])): ?>
                                <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_cadastro['cad_email']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="input-row">
                            <div class="input-group">
                                <input type="date" name="data_nascimento" title="Data de Nascimento" required class="<?php echo isset($erros_cadastro['cad_nascimento']) ? 'input-error' : ''; ?>">
                                <?php if(isset($erros_cadastro['cad_nascimento'])): ?>
                                    <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_cadastro['cad_nascimento']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="input-group">
                                <input type="password" name="senha" placeholder="Senha (Mín. 8 caracteres)" required class="<?php echo isset($erros_cadastro['cad_senha']) ? 'input-error' : ''; ?>">
                                <i class="bi bi-eye toggle-password"></i>
                                <?php if(isset($erros_cadastro['cad_senha'])): ?>
                                    <div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_cadastro['cad_senha']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">CRIAR</button>
                    </form>

                    <div class="links">
                        <a href="#" class="link-go-login">Já possui conta? Faça login aqui</a>
                    </div>
                </div>

                <div id="view-recover" class="view-section hidden">
                    <h2 class="form-title recover-title">RECUPERAÇÃO DE CONTA</h2>
                     <p class="recover-text">Essa função ainda não está disponível no sistema.</p>
<div class="links" style="margin-top: 25px;">
                        <a href="#" class="link-go-login">Voltar para o Login</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const viewLogin = document.getElementById('view-login');
        const viewRegister = document.getElementById('view-register');
        const viewRecover = document.getElementById('view-recover');

        const linkGoRegister = document.getElementById('link-go-register');
        const linkGoRecoverLogin = document.getElementById('link-go-recover-login');
        const linksGoLogin = document.querySelectorAll('.link-go-login');

        const roleButtons = document.querySelectorAll('.role-btn');
        const loginIdentificador = document.getElementById('login-identificador');
        const tipoLoginHidden = document.getElementById('tipo-login-hidden'); 

        function switchView(viewToShow) {
            viewLogin.classList.add('hidden');
            viewRegister.classList.add('hidden');
            viewRecover.classList.add('hidden');
            viewToShow.classList.remove('hidden');
        }

        linkGoRegister.addEventListener('click', (e) => { e.preventDefault(); switchView(viewRegister); });
        linkGoRecoverLogin.addEventListener('click', (e) => { e.preventDefault(); switchView(viewRecover); });
        
        linksGoLogin.forEach(link => {
            link.addEventListener('click', (e) => { e.preventDefault(); switchView(viewLogin); });
        });

        function setRole(role) {
            roleButtons.forEach(btn => btn.classList.remove('active'));
            const button = document.querySelector(`.role-btn[data-role="${role}"]`);
            if (button) button.classList.add('active');
            
            tipoLoginHidden.value = role;
            
            if(role === 'padrao') {
                linkGoRegister.style.display = 'block';
                loginIdentificador.placeholder = "Matrícula";
                loginIdentificador.removeAttribute('maxlength');
            } else if(role === 'resp') {
                linkGoRegister.style.display = 'none';
                loginIdentificador.placeholder = "CPF";
                loginIdentificador.setAttribute('maxlength', '14');
            } else if(role === 'admin') {
                linkGoRegister.style.display = 'none';
                loginIdentificador.placeholder = "SIAPE";
                loginIdentificador.removeAttribute('maxlength');
            }
        }

        roleButtons.forEach(button => {
            button.addEventListener('click', () => {
                setRole(button.getAttribute('data-role'));
                loginIdentificador.value = ""; 
            });
        });

        const ultimoTipo = "<?php echo $ultimo_tipo_login; ?>";
        setRole(ultimoTipo);

        <?php if(!empty($erros_cadastro)): ?>
            switchView(viewRegister);
        <?php elseif($erro_campo === 'recuperacao'): ?>
            switchView(viewRecover);
        <?php endif; ?>

        const cpfInput = document.getElementById('cpf-input');
        if(cpfInput) {
            cpfInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); 
                if (value.length > 11) value = value.slice(0, 11); 
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            });
        }

        loginIdentificador.addEventListener('input', function(e) {
            if (tipoLoginHidden.value === 'resp') {
                let value = e.target.value.replace(/\D/g, ''); 
                if (value.length > 11) value = value.slice(0, 11); 
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });

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
    </script>
</body>
</html>
