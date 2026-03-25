<?php
// BUSCA OS DADOS DO USUÁRIO PARA EXIBIR NA TELA
$stmt = $conn->prepare("SELECT nome, cpf, matricula, email, status FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$dados_usuario = $resultado->fetch_assoc();

$nome_exibicao = htmlspecialchars($dados_usuario['nome'] ?? '');
$cpf_exibicao = htmlspecialchars($dados_usuario['cpf'] ?? '');
$matricula_exibicao = htmlspecialchars($dados_usuario['matricula'] ?? '');
$email_exibicao = htmlspecialchars($dados_usuario['email'] ?? '');
$status_exibicao = $dados_usuario['status'] ?? 'ativo';
?>

<div id="tab-conta" class="spa-tab" style="display: none;">
    <div class="form-card" style="max-width: 700px; margin: 0 auto;">
        
        <form id="account-form" method="POST" action="FECHECKCOMUM.php">
            <input type="hidden" name="atualizar_conta" value="1">
            
            <?php if ($status_exibicao === 'bloqueado'): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid #f5c6cb;">
                    <i class="bi bi-slash-circle"></i> ATENÇÃO: Sua conta está atualmente bloqueada pelo Administrador. Você não poderá realizar novos pedidos.
                </div>
            <?php endif; ?>

            <div class="input-group">
                <label>Nome Completo</label>
                <input type="text" value="<?php echo $nome_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
            </div>
            
            <div class="input-row" style="display: flex; gap: 15px;">
                <div class="input-group" style="flex: 1;">
                    <label>CPF</label>
                    <input type="text" value="<?php echo $cpf_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>Matrícula</label>
                    <input type="text" value="<?php echo $matricula_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
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
</div>