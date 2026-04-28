<?php
$mensagem_conta = "";

// PROCESSAMENTO: ATUALIZAÇÃO DA PRÓPRIA CONTA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_conta'])) {
    global $aba_ativa;
    $aba_ativa = "view-conta"; 
    $novo_email = $_POST['email'];
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    $stmt_check = $conn->prepare("SELECT Senha FROM Usuarios WHERE id_user = ?");
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    $user_db = $resultado_check->fetch_assoc();

    try {
        if (!empty($nova_senha)) {
            if ($nova_senha !== $confirma_senha) {
                $mensagem_conta = "<script>window.onload = function() { showToast('As novas senhas não coincidem.', 'error'); }</script>";
            } else if (!password_verify($senha_atual, $user_db['Senha'])) {
                $mensagem_conta = "<script>window.onload = function() { showToast('A senha atual está incorreta.', 'error'); }</script>";
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE Usuarios SET Email = ?, Senha = ? WHERE id_user = ?");
                $stmt_upd->bind_param("ssi", $novo_email, $senha_hash, $id_usuario);
                $stmt_upd->execute();
                $mensagem_conta = "<script>window.onload = function() { showToast('Dados e senha atualizados com sucesso!', 'success'); }</script>";
            }
        } else {
            $stmt_upd = $conn->prepare("UPDATE Usuarios SET Email = ? WHERE id_user = ?");
            $stmt_upd->bind_param("si", $novo_email, $id_usuario);
            $stmt_upd->execute();
            $mensagem_conta = "<script>window.onload = function() { showToast('Email atualizado com sucesso!', 'success'); }</script>";
        }
    } catch (mysqli_sql_exception $e) {
        $mensagem_conta = "<script>window.onload = function() { showToast('Erro: O E-mail digitado já está em uso.', 'error'); }</script>";
    }
}

// Busca os dados da PRÓPRIA CONTA para preencher a aba
$stmt = $conn->prepare("SELECT Nome, CPF, SIAPE, Email FROM Usuarios WHERE id_user = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$dados_usuario = $stmt->get_result()->fetch_assoc();

$nome_exibicao = htmlspecialchars($dados_usuario['Nome'] ?? '');
$cpf_exibicao = htmlspecialchars($dados_usuario['CPF'] ?? '');
$siape_exibicao = htmlspecialchars($dados_usuario['SIAPE'] ?? '');
$email_exibicao = htmlspecialchars($dados_usuario['Email'] ?? '');

// Renderiza a mensagem caso tenha tido alguma alteração
echo $mensagem_conta;
?>

<main id="view-conta" class="main-view conta-main-container" style="display:none;">
    <h2>Minha Conta</h2>
    <div class="form-card" style="max-width: 700px; margin: 0 auto;">
        <form method="POST" action="FECHECKADM.php">
            <input type="hidden" name="atualizar_conta" value="1">
            
            <div class="input-group">
                <label>Nome Completo</label>
                <input type="text" name="nome" value="<?php echo $nome_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
            </div>
            
            <div class="input-row" style="display: flex; gap: 15px;">
                <div class="input-group" style="flex: 1;">
                    <label>CPF</label>
                    <input type="text" value="<?php echo !empty($cpf_exibicao) ? $cpf_exibicao : 'Não cadastrado'; ?>" readonly style="background-color: #e9ecef;">
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>SIAPE</label>
                    <input type="text" value="<?php echo !empty($siape_exibicao) ? $siape_exibicao : 'Não cadastrado'; ?>" readonly style="background-color: #e9ecef;">
                </div>
            </div>
            
            <hr class="divider">
            
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $email_exibicao; ?>" required>
            </div>
            <div class="input-group">
                <label>Senha Atual (Preencha apenas se for trocar a senha)</label>
                <input type="password" name="senha_atual" placeholder="Digite sua senha atual">
            </div>
            <div class="input-group">
                <label>Nova Senha</label>
                <input type="password" name="nova_senha" placeholder="Crie uma nova senha">
            </div>
            <div class="input-group">
                <label>Confirmar Nova Senha</label>
                <input type="password" name="confirma_senha" placeholder="Repita a nova senha">
            </div>
            
            <button type="submit" class="btn-save" style="cursor: pointer;"><i class="bi bi-save"></i> Salvar Alterações</button>
        </form>
    </div> 
</main>
