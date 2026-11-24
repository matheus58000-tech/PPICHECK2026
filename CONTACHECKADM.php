<?php
$mensagem_conta = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_conta'])) {
    global $aba_ativa;
    $aba_ativa = "view-conta"; 
    $novo_email = trim($_POST['email']);
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    $foto_ok = false;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $_FILES['foto']['size'] > 0) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $tamanho_mb = round($_FILES['foto']['size'] / 1024 / 1024, 1);
        if ($_FILES['foto']['size'] > 10 * 1024 * 1024) {
            $mensagem_conta = "showToast('A imagem é muito pesada ({$tamanho_mb}MB). O limite é 10MB.', 'error');";
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'bmp', 'avif'], true)) {
            $mensagem_conta = "showToast('Formato inválido" . (!empty($ext) ? " (.{$ext})" : "") . ". Use JPG, PNG, GIF ou WebP.', 'error');";
        } else {
            $antiga = $conn->query("SELECT Foto FROM Usuarios WHERE id_user = " . (int)$id_usuario)->fetch_assoc()['Foto'] ?? '';
            $foto_nome = 'user_' . $id_usuario . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_nome)) {
                $stmt_f = $conn->prepare("UPDATE Usuarios SET Foto = ? WHERE id_user = ?");
                $stmt_f->bind_param("si", $foto_nome, $id_usuario);
                $stmt_f->execute();
                if (!empty($antiga) && file_exists('uploads/' . $antiga)) @unlink('uploads/' . $antiga);
                $foto_ok = true;
            } else {
                $mensagem_conta = "showToast('Não foi possível salvar a imagem. Tente novamente.', 'error');";
            }
        }
    }

    $stmt_check = $conn->prepare("SELECT Email, Senha FROM Usuarios WHERE id_user = ?");
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    $user_db = $resultado_check->fetch_assoc();

    try {
        if (!empty($nova_senha)) {
            if ($nova_senha !== $confirma_senha) {
                $mensagem_conta = "showToast('As novas senhas não coincidem.', 'error');";
            } else if (!password_verify($senha_atual, $user_db['Senha'])) {
                $mensagem_conta = "showToast('A senha atual está incorreta.', 'error');";
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE Usuarios SET Email = ?, Senha = ? WHERE id_user = ?");
                $stmt_upd->bind_param("ssi", $novo_email, $senha_hash, $id_usuario);
                $stmt_upd->execute();
                $mensagem_conta = "showToast('Dados e senha atualizados com sucesso!', 'success');";
            }
        } else {
           
            if ($novo_email !== $user_db['Email']) {
                $stmt_upd = $conn->prepare("UPDATE Usuarios SET Email = ? WHERE id_user = ?");
                $stmt_upd->bind_param("si", $novo_email, $id_usuario);
                $stmt_upd->execute();
                $mensagem_conta = "showToast('Email atualizado com sucesso!', 'success');";
            } else {
               
                $mensagem_conta = "showToast('Nenhuma alteração foi feita.', 'warning');";
            }
        }
    } catch (mysqli_sql_exception $e) {
        $mensagem_conta = "showToast('Erro: O E-mail digitado já está em uso.', 'error');";
    }

    if ($foto_ok) {
        if (strpos($mensagem_conta, 'Nenhuma alteração') !== false) {
            $mensagem_conta = "showToast('Foto de perfil atualizada com sucesso!', 'success');";
        } else {
            $mensagem_conta .= "showToast('Foto de perfil atualizada com sucesso!', 'success');";
        }
    }
}

$stmt = $conn->prepare("SELECT Nome, CPF, SIAPE, Email, Foto FROM Usuarios WHERE id_user = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$dados_usuario = $stmt->get_result()->fetch_assoc();

$nome_exibicao = htmlspecialchars($dados_usuario['Nome'] ?? '');
$cpf_exibicao = htmlspecialchars($dados_usuario['CPF'] ?? '');
$siape_exibicao = htmlspecialchars($dados_usuario['SIAPE'] ?? '');
$email_exibicao = htmlspecialchars($dados_usuario['Email'] ?? '');
$foto_exibicao = $dados_usuario['Foto'] ?? '';
$foto_exibicao_url = (!empty($foto_exibicao) && file_exists('uploads/' . $foto_exibicao)) ? 'uploads/' . $foto_exibicao : '';
?>
<style>
    .account-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    .account-column { display: flex; flex-direction: column; gap: 5px; }
    .readonly-notice { background-color: #f8f9fa; border-left: 4px solid #0f006d; padding: 12px; border-radius: 8px; font-size: 0.85rem; color: #555; margin-bottom: 15px; }
    
    .account-actions { display: flex; gap: 15px; margin-top: 15px; width: 100%; }
    
    .btn-acc-cancel { flex: 1; justify-content: center; background-color: #e0e0e0; color: #333; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; font-size: 1rem; }
    .btn-acc-cancel:hover { background-color: #ccc; }
    
    .btn-acc-save { flex: 1; justify-content: center; background-color: #0f006d; color: white; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; font-size: 1rem; }
    .btn-acc-save:hover { background-color: #0a004a; }
    
    @media (max-width: 768px) { .account-grid { grid-template-columns: 1fr; gap: 20px; } }
</style>

<main id="view-conta" class="main-view conta-main-container" style="display:none;">
    <div class="page-header-row">
        <h2>Minha Conta</h2>
    </div>
    
    <div class="form-card" style="max-width: 900px; margin: 0 auto; box-shadow: none; border: 1px solid #e0e0e0;">
        <form method="POST" action="FECHECKADM.php" enctype="multipart/form-data">
            <input type="hidden" name="atualizar_conta" value="1">

            <div style="display: flex; align-items: center; gap: 20px; background: #f8f9ff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                <div id="preview-foto" style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; background: #eef0f8; border: 3px solid #0f006d; box-shadow: 0 0 0 4px rgba(15,0,109,0.12); display: flex; align-items: center; justify-content: center; color: #0f006d; font-size: 2.2rem; flex-shrink: 0;">
                    <?php if ($foto_exibicao_url): ?>
                        <img src="<?php echo htmlspecialchars($foto_exibicao_url); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 8px;">Foto de Perfil</label>
                    <input type="file" name="foto" id="input-foto" accept="image/*" style="font-size: 0.9rem;">
                    <p style="font-size: 0.78rem; color: #888; margin-top: 6px;">JPG, PNG, GIF ou WebP. Tamanho máximo: 10MB.</p>
                </div>
            </div>

            <div class="account-grid">
                <div class="account-column">
                    <div class="readonly-notice">
                        <strong><i class="bi bi-info-circle"></i> Informações Fixas</strong><br>
                        Os dados abaixo não podem ser alterados por aqui. Caso precise modificá-los, solicite pelo gerenciamento do Laboratório.
                    </div>
                    
                    <div class="input-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" value="<?php echo $nome_exibicao; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
                    </div>
                    
                    <div class="input-group">
                        <label>CPF</label>
                        <input type="text" value="<?php echo !empty($cpf_exibicao) ? $cpf_exibicao : 'Não cadastrado'; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
                    </div>
                    
                    <div class="input-group">
                        <label>SIAPE</label>
                        <input type="text" value="<?php echo !empty($siape_exibicao) ? $siape_exibicao : 'Não cadastrado'; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; opacity: 0.8;">
                    </div>
                </div>

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
                    
                    <div class="account-actions">
                        <button type="reset" class="btn-acc-cancel"><i class="bi bi-x-circle"></i> Cancelar</button>
                        <button type="submit" class="btn-acc-save"><i class="bi bi-save"></i> Salvar Alterações</button>
                    </div>
                </div>
            </div>
        </form>
    </div> 
</main>

<script>
    document.getElementById('input-foto').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            if (typeof showToast === 'function') showToast('A imagem é muito pesada (' + (file.size / 1048576).toFixed(1) + 'MB). O limite é 10MB.', 'error');
            else alert('A imagem é muito pesada. O limite é 10MB.');
            e.target.value = '';
            return;
        }
        const preview = document.getElementById('preview-foto');
        preview.innerHTML = `<img src="${URL.createObjectURL(file)}" style="width:100%; height:100%; object-fit:cover;">`;
    });
</script>

<?php if (!empty($mensagem_conta)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            <?php echo $mensagem_conta; ?>
        }, 300);
    });
</script>
<?php endif; ?>
