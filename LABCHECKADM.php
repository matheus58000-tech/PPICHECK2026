<?php
// Função para gerar um código aleatório (Só declara se ainda não existir)
if (!function_exists('gerarCodigoUnico')) {
    function gerarCodigoUnico($conn) {
        $codigo = '';
        $existe = true;
        while ($existe) {
            $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $aleatorio = substr(str_shuffle($caracteres), 0, 5);
            $codigo = 'CHK-' . $aleatorio;
            
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE codigo_usuario = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 0) {
                $existe = false;
            }
            $stmt->close();
        }
        return $codigo;
    }
}

$mensagem_lab = "";

// PROCESSAMENTO: GERENCIAMENTO DE USUÁRIOS E LABORATÓRIO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_usuario'])) {
    global $aba_ativa, $sub_aba_ativa;
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-usuarios";
    $acao = $_POST['acao_usuario'];
    
    $id_alvo = isset($_POST['id_alvo']) ? intval($_POST['id_alvo']) : 0;

    if ($id_alvo === $id_usuario && ($acao === 'excluir' || $acao === 'bloquear')) {
        $mensagem_lab = "<script>window.onload = function() { showToast('Você não pode bloquear ou excluir a própria conta!', 'warning'); }</script>";
    } else {
        try {
            if ($acao === 'bloquear') {
                $conn->query("UPDATE usuarios SET status = IF(status='ativo', 'bloqueado', 'ativo') WHERE id = $id_alvo");
                $mensagem_lab = "<script>window.onload = function() { showToast('Status do usuário alterado com sucesso!', 'success'); }</script>";
            
            } elseif ($acao === 'excluir') {
                $conn->query("DELETE FROM usuarios WHERE id = $id_alvo");
                $mensagem_lab = "<script>window.onload = function() { showToast('Usuário excluído permanentemente!', 'success'); }</script>";
            
            } elseif ($acao === 'editar') {
                $nome = $_POST['edit_nome'];
                $email = $_POST['edit_email'];
                $cpf = $_POST['edit_cpf'];
                $matricula = $_POST['edit_matricula'];
                $siape = $_POST['edit_siape'];
                $tipo = $_POST['edit_tipo'];
                $nova_senha = $_POST['edit_senha'];

                if (!empty($nova_senha)) {
                    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt_edit = $conn->prepare("UPDATE usuarios SET nome=?, email=?, cpf=?, matricula=?, siape=?, tipo_usuario=?, senha=? WHERE id=?");
                    $stmt_edit->bind_param("sssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $hash, $id_alvo);
                } else {
                    $stmt_edit = $conn->prepare("UPDATE usuarios SET nome=?, email=?, cpf=?, matricula=?, siape=?, tipo_usuario=? WHERE id=?");
                    $stmt_edit->bind_param("ssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $id_alvo);
                }
                
                $stmt_edit->execute();
                $mensagem_lab = "<script>window.onload = function() { showToast('Dados do usuário atualizados com sucesso!', 'success'); }</script>";
            
            } elseif ($acao === 'adicionar') {
                $tipo = $_POST['add_tipo'];
                $cpf = $_POST['add_cpf'];
                $nome = $_POST['add_nome'];
                $email = $_POST['add_email'];
                $nascimento = $_POST['add_nascimento'];
                $senha = $_POST['add_senha'];
                $confirma = $_POST['add_confirma'];
                
                $dinamico = isset($_POST['add_dinamico']) ? $_POST['add_dinamico'] : null;
                $matricula = ($tipo === 'padrao') ? $dinamico : null;
                $siape = ($tipo === 'admin') ? $dinamico : null;

                if ($senha !== $confirma) {
                    $mensagem_lab = "<script>window.onload = function() { showToast('As senhas não coincidem!', 'error'); }</script>";
                    $sub_aba_ativa = "tab-novo-usuario"; 
                } else {
                    $codigo_usuario = gerarCodigoUnico($conn); 
                    
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt_add = $conn->prepare("INSERT INTO usuarios (nome, cpf, matricula, siape, email, data_nascimento, senha, tipo_usuario, codigo_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_add->bind_param("sssssssss", $nome, $cpf, $matricula, $siape, $email, $nascimento, $hash, $tipo, $codigo_usuario);
                    
                    $stmt_add->execute();
                    $mensagem_lab = "<script>window.onload = function() { showToast('Usuário cadastrado com sucesso!', 'success'); }</script>";
                }
            }
        } catch (mysqli_sql_exception $e) {
            $mensagem_lab = "<script>window.onload = function() { showToast('ERRO: CPF, Matrícula, SIAPE ou E-mail já estão em uso.', 'error'); }</script>";
            if ($acao === 'adicionar') {
                $sub_aba_ativa = "tab-novo-usuario";
            }
        }
    }
}

echo $mensagem_lab;
?>

<main id="view-lab" class="main-view laboratory-main" style="display:none;">
    <div class="page-title-container">
        <h1 id="page-main-title">LEPEP de Hardware</h1>
    </div>

    <div id="tab-inicio" class="spa-tab">
        <div class="content-area">
            <p>Bem-vindo à área de Gerenciamento do Laboratório. Use o menu <strong>Laboratório</strong> na barra superior para navegar entre as funções.</p>
        </div>
        <div class="background-logo-container">
            <img src="LOGOCHECKADAP.jpg" alt="Logo LEPEP" class="background-logo">
        </div>
    </div>

    <div id="tab-pedidos-lab" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="filter-buttons">
                <button class="btn-filter active" onclick="switchLabFilter('aprovacao-andamento', this)"><i class="bi bi-list-check"></i> Aprovação & Preparação</button>
                <button class="btn-filter" onclick="switchLabFilter('historico-lab', this)"><i class="bi bi-clock-history"></i> Histórico de Pedidos</button>
            </div>

            <div class="orders-container" id="aprovacao-andamento">
                <h3 class="order-section-title">Aguardando Aprovação</h3>
                <div class="order-card aprovacao" id="card-2025-001">
                    <div class="order-header">
                        <span class="order-code">#2025-001</span>
                        <span class="order-user">Usuário: Lucas Ribolli (Comum)</span>
                        <div class="action-buttons">
                            <button class="btn-action approve" onclick="aprovarPedido('2025-001')"><i class="bi bi-check-lg"></i> Aprovar</button>
                            <button class="btn-action delete" onclick="abrirModalRecusa('2025-001')"><i class="bi bi-x-lg"></i> Recusar</button>
                        </div>
                    </div>
                    <div class="order-details">
                        <p><strong>Itens:</strong> LED Amarelo (x10), Resistor 1k (x5)</p>
                        <p>Data do Pedido: 26/11/2025</p>
                    </div>
                </div>
                
                <h3 class="order-section-title">Em Produção (Preparando)</h3>
                <div class="order-card producao">
                    <div class="order-header">
                        <span class="order-code">#2025-002</span>
                        <span class="order-user">Usuário: Stella Lyana Montenegro</span>
                        <div class="status-selector">
                            <label for="status-2025-002">Status:</label>
                            <select id="status-2025-002" onchange="updateStatus('2025-002', this.value)">
                                <option value="producao" selected>Em Produção</option>
                                <option value="retirada">Pronto para Retirada</option>
                                <option value="retirado">Pedido Retirado</option>
                                <option value="devolvido">Pedido Devolvido</option>
                            </select>
                            <button class="btn-devolver" onclick="devolverPedido('2025-002')"><i class="bi bi-arrow-counterclockwise"></i> Devolver</button>
                        </div>
                    </div>
                    <div class="order-details">
                        <p><strong>Itens:</strong> Arduino Uno R3 (x1)</p>
                        <p>Data do Pedido: 25/11/2025</p>
                    </div>
                </div>
            </div>

            <div class="orders-container" id="historico-lab" style="display: none;">
                <h3 class="order-section-title">Pedidos Finalizados</h3>
                <div class="order-card devolvido">
                    <div class="order-header">
                        <span class="order-code">#2025-005</span>
                        <span class="order-user">Usuário: Stella Lyana Montenegro</span>
                        <div class="status-selector"><span class="status-badge devolvido-badge">Pedido Devolvido</span></div>
                    </div>
                    <div class="order-details">
                        <p><strong>Itens:</strong> Resistor 10k (x100)</p>
                        <p>Data da Devolução: 18/11/2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-estoque" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="stock-list-header">
                <h3>Itens Cadastrados</h3>
                <button onclick="switchLabTab('tab-novo-item', 'Adicionar Novo Item')" class="btn-add-item"><i class="bi bi-plus-circle"></i> Adicionar Novo Item</button>
            </div>
            <div class="table-responsive-wrapper">
                <table class="stock-table">
                    <thead><tr><th>Foto</th><th>Nome do Item</th><th>Quantidade</th><th>Ações</th></tr></thead>
                    <tbody>
                        <tr data-item-id="1">
                            <td><div class="item-thumb-container"><img src="LEDAMARELO.jpg" alt="Led Amarelo" class="item-thumb"></div></td>
                            <td>Led Amarelo</td><td>10</td>
                            <td class="action-buttons"><button class="btn-action edit"><i class="bi bi-pencil-square"></i> Editar</button><button class="btn-action delete"><i class="bi bi-trash"></i> Excluir</button></td>
                        </tr>
                        <tr data-item-id="4">
                            <td><div class="item-thumb-container"><img src="ARDUINO.webp" alt="Arduino" class="item-thumb"></div></td>
                            <td>Arduino</td><td>0 <span class="badge out-of-stock-badge">Esgotado</span></td>
                            <td class="action-buttons"><button class="btn-action edit"><i class="bi bi-pencil-square"></i> Editar</button><button class="btn-action delete"><i class="bi bi-trash"></i> Excluir</button></td>
                        </tr>
                        <tr data-item-id="6">
                            <td><div class="item-thumb-container"><img src="PARAFUSO1.jpg" alt="Parafuso" class="item-thumb"></div></td>
                            <td>Parafuso Pequeno</td><td>12</td>
                            <td class="action-buttons"><button class="btn-action edit"><i class="bi bi-pencil-square"></i> Editar</button><button class="btn-action delete"><i class="bi bi-trash"></i> Excluir</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-novo-item" class="spa-tab" style="display: none;">
        <div class="content-area form-container">
            <form id="add-item-form" onsubmit="submitFormLab(event, 'tab-estoque', 'Item cadastrado com sucesso!')">
                <h3 class="form-section-title"><i class="bi bi-box-seam"></i> Dados do Produto</h3>
                <div class="input-group full-width"><label>Nome Do Item</label><input type="text" required></div>
                <div class="input-group full-width">
                    <label>Categoria</label><select required><option value="">Selecione...</option><option>Hardware</option><option>Periféricos</option></select>
                </div>
                <div class="form-row"> 
                    <div class="input-group"><label>Descrição</label><textarea rows="4" style="width:100%; border:1px solid #ccc; border-radius:8px; padding:10px;" required></textarea></div>
                    <div class="input-group"><label>Quantidade</label><input type="number" required min="1"></div>
                </div>
                <div class="input-group full-width"><label>Foto do Item</label><input type="file" accept="image/*" required></div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Cadastrar Item</button>
                    <button type="button" class="btn-secondary-action" onclick="switchLabTab('tab-estoque', 'Gerenciamento de Estoque')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="tab-usuarios" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="stock-list-header">
                <h3>Lista de Usuários Cadastrados</h3>
                <button onclick="switchLabTab('tab-novo-usuario', 'Adicionar Novo Usuário')" class="btn-add-item"><i class="bi bi-person-plus"></i> Adicionar Usuário</button>
            </div>
            
            <div class="table-responsive-wrapper">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th>Nome Completo</th>
                            <th>E-mail</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res_usuarios = $conn->query("SELECT * FROM usuarios ORDER BY nome ASC");
                        while ($u = $res_usuarios->fetch_assoc()):
                            $id_u = $u['id'];
                            $nome_u = htmlspecialchars($u['nome']);
                            $email_u = htmlspecialchars($u['email']);
                            $tipo_u = ucfirst($u['tipo_usuario']);
                            $status_u = $u['status'];
                            
                            $cor_status = ($status_u === 'ativo') ? 'green' : 'red';
                            $texto_status = ($status_u === 'ativo') ? 'Ativo' : 'Bloqueado';
                        ?>
                            <tr data-user-id="<?php echo $id_u; ?>">
                                <td><?php echo $nome_u; ?></td>
                                <td><?php echo $email_u; ?></td>
                                <td><?php echo $tipo_u; ?></td>
                                <td style="color: <?php echo $cor_status; ?>; font-weight: bold;"><?php echo $texto_status; ?></td>
                                <td>
                                    <button class="btn-view-pedidos" onclick="togglePedidos(<?php echo $id_u; ?>)">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php 
            $res_usuarios->data_seek(0); 
            while ($u = $res_usuarios->fetch_assoc()): 
                $id_u = $u['id'];
                $btn_bloqueio_txt = ($u['status'] === 'ativo') ? 'Bloquear' : 'Desbloquear';
                $btn_bloqueio_icon = ($u['status'] === 'ativo') ? 'bi-slash-circle' : 'bi-check-circle';
                
                $dados_json = htmlspecialchars(json_encode([
                    'id' => $u['id'], 
                    'nome' => $u['nome'], 
                    'email' => $u['email'], 
                    'cpf' => $u['cpf'], 
                    'matricula' => $u['matricula'], 
                    'siape' => $u['siape'], 
                    'tipo' => $u['tipo_usuario']
                ]));
            ?>
                <div class="pedidos-detail-container" id="pedidos-detail-<?php echo $id_u; ?>" style="display: none;">
                    <h4>Detalhes - <span class="user-name-placeholder"><?php echo htmlspecialchars($u['nome']); ?></span></h4>
                    <p style="margin-bottom: 10px;">
                       <strong>Código:</strong> <?php echo !empty($u['codigo_usuario']) ? htmlspecialchars($u['codigo_usuario']) : '-'; ?> | 
                       <strong>CPF:</strong> <?php echo !empty($u['cpf']) ? htmlspecialchars($u['cpf']) : '-'; ?> | 
                       <strong>Matrícula:</strong> <?php echo !empty($u['matricula']) ? htmlspecialchars($u['matricula']) : '-'; ?> | 
                       <strong>SIAPE:</strong> <?php echo !empty($u['siape']) ? htmlspecialchars($u['siape']) : '-'; ?>
                    </p>
                    
                    <div class="user-actions-footer">
                        <button class="btn-user-opt btn-edit" onclick="abrirModalEditUser('<?php echo $dados_json; ?>')">
                            <i class="bi bi-pencil-square"></i> Editar
                        </button>
                        <button class="btn-user-opt btn-block" onclick="acaoUsuario('bloquear', <?php echo $id_u; ?>)">
                            <i class="bi <?php echo $btn_bloqueio_icon; ?>"></i> <?php echo $btn_bloqueio_txt; ?>
                        </button>
                        <button class="btn-user-opt btn-delete" onclick="acaoUsuario('excluir', <?php echo $id_u; ?>)">
                            <i class="bi bi-trash-fill"></i> Excluir
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="tab-novo-usuario" class="spa-tab" style="display: none;">
        <div class="content-area form-container">
            <form id="add-user-form" method="POST" action="FECHECKADM.php">
                <input type="hidden" name="acao_usuario" value="adicionar">
                
                <h3 class="form-section-title"><i class="bi bi-person-circle"></i> Informações Básicas</h3>
                <div class="form-row">
                    <div class="input-group">
                        <label>Tipo de Usuário</label>
                        <select name="add_tipo" id="add_tipo" required onchange="mudarCampoDinamicoAddUser(this.value)">
                            <option value="">Selecione...</option>
                            <option value="padrao">Comum (Aluno)</option>
                            <option value="resp">Responsável LEPEP</option>
                            <option value="admin">Admin LEPEP</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>CPF</label>
                        <input type="text" name="add_cpf" id="add_cpf" placeholder="000.000.000-00" maxlength="14" required>
                    </div>
                </div>
                
                <div class="input-group full-width" id="grupo-dinamico-add-user" style="display: none;">
                    <label id="label-dinamico-add-user">Identificação</label>
                    <input type="text" name="add_dinamico" id="input-dinamico-add-user">
                </div>
                
                <div class="input-group full-width">
                    <label>Nome Completo</label>
                    <input type="text" name="add_nome" required>
                </div>
                
                <div class="form-row">
                    <div class="input-group">
                        <label>Data de Nascimento</label>
                        <input type="date" name="add_nascimento" required>
                    </div>
                    <div class="input-group">
                        <label>E-mail</label>
                        <input type="email" name="add_email" required>
                    </div>
                </div>
                
                <h3 class="form-section-title" style="margin-top: 1rem;"><i class="bi bi-lock"></i> Dados de Acesso</h3>
                <div class="form-row">
                    <div class="input-group">
                        <label>Senha</label>
                        <input type="password" name="add_senha" required>
                    </div>
                    <div class="input-group">
                        <label>Confirmação</label>
                        <input type="password" name="add_confirma" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary-action"><i class="bi bi-person-plus"></i> Cadastrar Usuário</button>
                    <button type="button" class="btn-secondary-action" onclick="switchLabTab('tab-usuarios', 'Gerenciamento de Usuários')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Modais Exclusivos do Laboratório -->
<div id="modal-recusa" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-heading-red"><i class="bi bi-exclamation-triangle"></i> Recusar Pedido</h3>
        <p id="modal-msg-titulo" style="margin-bottom:15px; color:#555;">Recusando pedido.</p>
        <div class="modal-input-group">
            <label for="justificativa" style="text-align:left; display:block; margin-bottom:5px; font-weight:bold;">Justificativa da recusa (Opcional):</label>
            <textarea id="justificativa" rows="4" style="width:100%; border-radius:8px; border:1px solid #ccc; padding:10px; resize:vertical;"></textarea>
        </div>
        <div class="modal-footer" style="margin-top:20px;">
            <button class="btn-cancel" onclick="fecharModalRecusa()">Cancelar</button>
            <button class="btn-modal-confirm-delete" onclick="confirmarRecusa()">Confirmar Recusa</button>
        </div>
    </div>
</div>

<form id="form-acao-usuario" method="POST" action="FECHECKADM.php" style="display:none;">
    <input type="hidden" name="acao_usuario" id="form-acao-val">
    <input type="hidden" name="id_alvo" id="form-id-alvo">
</form>

<div id="modalEditUser" class="modal-overlay">
    <div class="modal-content form-container" style="max-width: 600px;">
        <h3 class="form-section-title"><i class="bi bi-pencil-square"></i> Editar Usuário</h3>
        <form method="POST" action="FECHECKADM.php" style="margin-top: 15px;">
            <input type="hidden" name="acao_usuario" value="editar">
            <input type="hidden" name="id_alvo" id="edit_id_alvo">
            
            <div class="input-row" style="display:flex; gap:10px;">
                <div class="input-group" style="flex:1;">
                    <label>Tipo de Usuário</label>
                    <select name="edit_tipo" id="edit_tipo" required style="width:100%; height:45px; border-radius:5px;">
                        <option value="padrao">Padrão</option>
                        <option value="resp">Responsável LEPEP</option>
                        <option value="admin">Admin LEPEP</option>
                    </select>
                </div>
                <div class="input-group" style="flex:1;">
                    <label>CPF</label>
                    <input type="text" name="edit_cpf" id="edit_cpf" style="width:100%; height:45px;">
                </div>
            </div>
            
            <div class="input-row" style="display:flex; gap:10px; margin-top:10px;">
                <div class="input-group" style="flex:1;"><label>Matrícula</label><input type="text" name="edit_matricula" id="edit_matricula" style="width:100%; height:45px;"></div>
                <div class="input-group" style="flex:1;"><label>SIAPE</label><input type="text" name="edit_siape" id="edit_siape" style="width:100%; height:45px;"></div>
            </div>
            
            <div class="input-group" style="margin-top:10px;"><label>Nome Completo</label><input type="text" name="edit_nome" id="edit_nome" required style="width:100%; height:45px;"></div>
            <div class="input-group" style="margin-top:10px;"><label>E-mail</label><input type="email" name="edit_email" id="edit_email" required style="width:100%; height:45px;"></div>
            
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">
            <div class="input-group">
                <label>Forçar Nova Senha (deixe em branco para não alterar)</label>
                <input type="password" name="edit_senha" placeholder="Digite apenas se quiser redefinir" style="width:100%; height:45px;">
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn-primary-action" style="padding: 10px 20px; background-color:#0d005f; color:white; border:none; border-radius:5px;"><i class="bi bi-save"></i> Salvar</button>
                <button type="button" class="btn-secondary-action" onclick="fecharModalEdit()" style="padding: 10px 20px; background-color:#ccc; border:none; border-radius:5px; margin-left:10px;">Cancelar</button>
            </div>
        </form>
    </div>
</div>