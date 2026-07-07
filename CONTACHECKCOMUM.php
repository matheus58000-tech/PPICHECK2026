<?php
$stmt = $conn->prepare("SELECT Nome, CPF, Matricula, Email, status FROM Usuarios WHERE id_user = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$dados_usuario = $resultado->fetch_assoc();

$nome_exibicao = htmlspecialchars($dados_usuario['Nome'] ?? '');
$cpf_exibicao = htmlspecialchars($dados_usuario['CPF'] ?? '');
$matricula_exibicao = htmlspecialchars($dados_usuario['Matricula'] ?? '');
$email_exibicao = htmlspecialchars($dados_usuario['Email'] ?? '');
$status_exibicao = $dados_usuario['status'] ?? 'ativo';

// Resgata mensagens da Sessão que vieram do FECHECKCOMUM.php
$toast_script = "";
if (isset($_SESSION['msg_erro'])) {
    $msg = addslashes($_SESSION['msg_erro']);
    $toast_script = "showToast('{$msg}', 'error');";
    unset($_SESSION['msg_erro']);
} elseif (isset($_SESSION['msg_sucesso'])) {
    $msg = addslashes($_SESSION['msg_sucesso']);
    $toast_script = "showToast('{$msg}', 'success');";
    unset($_SESSION['msg_sucesso']);
} elseif (isset($_SESSION['msg_warning'])) {
    $msg = addslashes($_SESSION['msg_warning']);
    $toast_script = "showToast('{$msg}', 'warning');";
    unset($_SESSION['msg_warning']);
}
?>

<style>
    .account-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    .account-column { display: flex; flex-direction: column; gap: 5px; }
    .readonly-notice { background-color: #f8f9fa; border-left: 4px solid #0f006d; padding: 12px; border-radius: 8px; font-size: 0.85rem; color: #555; margin-bottom: 15px; }
    
    .account-actions { display: flex; gap: 15px; margin-top: 15px; width: 100%; }
    
    .btn-acc-cancel { 
        flex: 1; 
        justify-content: center; 
        background-color: #e0e0e0; 
        color: #333; 
        padding: 12px; 
        border: none; 
        border-radius: 8px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.2s; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        font-size: 1rem; 
    }
    .btn-acc-cancel:hover { background-color: #ccc; }
    
    .btn-acc-save { 
        flex: 1; 
        justify-content: center; 
        background-color: #0f006d; 
        color: white; 
        padding: 12px; 
        border: none; 
        border-radius: 8px; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.2s; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        font-size: 1rem; 
    }
    .btn-acc-save:hover { background-color: #0a004a; }
    
    @media (max-width: 768px) { 
        .account-grid { grid-template-columns: 1fr; gap: 20px; } 
    }
</style>

<div id="tab-conta" class="spa-tab" style="display: none;">
    <div class="form-card" style="max-width: 900px; margin: 0 auto; box-shadow: none; border: 1px solid #e0e0e0;">
        <form id="account-form" method="POST" action="FECHECKCOMUM.php">
            <input type="hidden" name="atualizar_conta" value="1">
            
            <?php if ($status_exibicao === 'bloqueado'): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; text-align: center; border: 1px solid #f5c6cb;">
                    <i class="bi bi-slash-circle"></i> ATENÇÃO: Sua conta está atualmente bloqueada pelo Administrador. Você não poderá realizar novos pedidos.
                </div>
            <?php endif; ?>

            <div class="account-grid">
                <!-- COLUNA ESQUERDA (Inalterável) -->
                <div class="account-column">
                    <div class="readonly-notice">
                        <strong><i class="bi bi-info-circle"></i> Informações Fixas</strong><br>
                        Os dados abaixo não podem ser alterados por aqui. Caso precise modificá-los, dirija-se à administração do Laboratório.
                    </div>

                    <div class="input-group">
                        <label>Nome Completo</label>
                        <input type="text" value="<?php echo $nome_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
                    </div>
                    
                    <div class="input-group">
                        <label>CPF</label>
                        <input type="text" value="<?php echo $cpf_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
                    </div>
                    
                    <div class="input-group">
                        <label>Matrícula</label>
                        <input type="text" value="<?php echo $matricula_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
                    </div>
                </div>

                <!-- COLUNA DIREITA (Editável) -->
                <div class="account-column">
                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo $email_exibicao; ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Senha Atual <span style="font-size:0.8rem; color:#888;">(Apenas se for alterar)</span></label>
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

                    <!-- BOTÕES ALINHADOS -->
                    <div class="account-actions">
                        <button type="reset" class="btn-acc-cancel"><i class="bi bi-x-circle"></i> Cancelar</button>
                        <button type="submit" class="btn-acc-save"><i class="bi bi-save"></i> Salvar Alterações</button>
                    </div>
                </div>
                
            </div>
        </form>
    </div> 
</div>

<?php if (!empty($toast_script)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            <?php echo $toast_script; ?>
        }, 300);
    });
</script>
<?php endif; ?>
