<?php
$mensagem_lab = "";
$erros_lab = [];

// =========================================================================
// 1. PROCESSAMENTO DE USUÁRIOS
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_usuario'])) {
    global $aba_ativa, $sub_aba_ativa;
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-usuarios";
    $acao = $_POST['acao_usuario'];
    
    $id_alvo = isset($_POST['id_alvo']) ? intval($_POST['id_alvo']) : 0;

    if ($id_alvo === $id_usuario && ($acao === 'excluir' || $acao === 'bloquear')) {
        $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Você não pode bloquear ou excluir a própria conta!', 'warning'));</script>";
    } else {
        try {
            if ($acao === 'bloquear') {
                $conn->query("UPDATE Usuarios SET status = IF(status='ativo', 'bloqueado', 'ativo') WHERE id_user = $id_alvo");
                $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Status do usuário alterado com sucesso!', 'success'));</script>";
            } elseif ($acao === 'excluir') {
                $conn->query("DELETE FROM Usuarios WHERE id_user = $id_alvo");
                $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Usuário excluído permanentemente!', 'success'));</script>";
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
                    $stmt_edit = $conn->prepare("UPDATE Usuarios SET Nome=?, Email=?, CPF=?, Matricula=?, SIAPE=?, Tipo_user=?, Senha=? WHERE id_user=?");
                    $stmt_edit->bind_param("sssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $hash, $id_alvo);
                } else {
                    $stmt_edit = $conn->prepare("UPDATE Usuarios SET Nome=?, Email=?, CPF=?, Matricula=?, SIAPE=?, Tipo_user=? WHERE id_user=?");
                    $stmt_edit->bind_param("ssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $id_alvo);
                }
                $stmt_edit->execute();
                $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Dados do usuário atualizados com sucesso!', 'success'));</script>";
            } elseif ($acao === 'adicionar') {
                $tipo = trim($_POST['add_tipo']);
                $cpf = trim($_POST['add_cpf']);
                $nome = trim($_POST['add_nome']);
                $email = trim($_POST['add_email']);
                $nascimento = $_POST['add_nascimento'];
                $senha = $_POST['add_senha'];
                $confirma = $_POST['add_confirma'];
                
                $dinamico = isset($_POST['add_dinamico']) ? trim($_POST['add_dinamico']) : null;
                $matricula = ($tipo === 'padrao') ? $dinamico : null;
                $siape = ($tipo === 'admin' || $tipo === 'resp') ? $dinamico : null;

                if (empty($nome) || strlen($nome) < 3) $erros_lab['add_nome'] = "Mín. 3 letras.";
                $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf); 
                if (empty($cpf_limpo) || strlen($cpf_limpo) !== 11) $erros_lab['add_cpf'] = "Exatos 11 dígitos.";
                if (empty($tipo)) $erros_lab['add_tipo'] = "Selecione o nível.";
                elseif ($tipo === 'padrao' && (empty($matricula) || strlen($matricula) !== 10 || !is_numeric($matricula))) $erros_lab['add_dinamico'] = "Exatos 10 números.";
                elseif (($tipo === 'admin' || $tipo === 'resp') && (empty($siape) || strlen($siape) !== 7 || !is_numeric($siape))) $erros_lab['add_dinamico'] = "Exatos 7 números.";
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros_lab['add_email'] = "E-mail inválido.";
                $data_atual = date("Y-m-d");
                if (empty($nascimento) || $nascimento > $data_atual) $erros_lab['add_nascimento'] = "Data inválida.";
                if (empty($senha) || strlen($senha) < 8) $erros_lab['add_senha'] = "Mín. 8 caracteres.";
                if ($senha !== $confirma) $erros_lab['add_confirma'] = "Senhas não coincidem.";

                if (count($erros_lab) > 0) {
                    $erros_lab['geral'] = "Preencha todos os campos corretamente e tente novamente.";
                    $sub_aba_ativa = "tab-novo-usuario"; 
                } else {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt_add = $conn->prepare("INSERT INTO Usuarios (Nome, CPF, Matricula, SIAPE, Email, Data_nasc, Senha, Tipo_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_add->bind_param("ssssssss", $nome, $cpf, $matricula, $siape, $email, $nascimento, $hash, $tipo);
                    $stmt_add->execute();
                    $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Usuário cadastrado com sucesso!', 'success'));</script>";
                    unset($_POST); // Limpa o form após sucesso
                }
            }
        } catch (\Exception $e) {
            $erros_lab['geral'] = "ERRO: O CPF, Matrícula, SIAPE ou E-mail digitado já estão em uso no sistema.";
            if ($acao === 'adicionar') { $sub_aba_ativa = "tab-novo-usuario"; }
        }
    }
}

// =========================================================================
// 2. PROCESSAMENTO DO ESTOQUE (ITENS)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_item'])) {
    global $aba_ativa, $sub_aba_ativa;
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-estoque";
    $acao_item = $_POST['acao_item'];

    try {
        if ($acao_item === 'adicionar') {
            $nome = trim($_POST['add_item_nome']);
            $categoria = isset($_POST['add_item_categoria']) ? intval($_POST['add_item_categoria']) : 0;
            $descricao = trim($_POST['add_item_descricao']);
            $qntd = isset($_POST['add_item_qntd']) ? intval($_POST['add_item_qntd']) : -1;
            
            // --- VALIDAÇÕES DE ERRO DO ITEM ---
            if (empty($nome) || strlen($nome) < 3) $erros_lab['item_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if ($categoria <= 0) $erros_lab['item_categoria'] = "Selecione uma categoria válida.";
            if ($qntd < 0) $erros_lab['item_qntd'] = "A quantidade não pode ser negativa.";
            if (empty($descricao) || strlen($descricao) < 10) $erros_lab['item_descricao'] = "Forneça uma descrição técnica (mín. 10 caracteres).";
            
            if (!isset($_FILES['add_item_foto']) || $_FILES['add_item_foto']['error'] != 0) {
                $erros_lab['item_foto'] = "É obrigatório enviar uma foto do produto.";
            }

            if (count($erros_lab) > 0) {
                $erros_lab['geral'] = "Por favor, verifique os campos destacados e tente novamente.";
                $sub_aba_ativa = "tab-novo-item"; 
            } else {
                $ext = pathinfo($_FILES['add_item_foto']['name'], PATHINFO_EXTENSION);
                $imagem = uniqid() . "." . $ext;
                move_uploaded_file($_FILES['add_item_foto']['tmp_name'], "uploads/" . $imagem);

                $stmt_item = $conn->prepare("INSERT INTO Item (Nome, Descricao_Item, Qntd, id_cat, Imagem) VALUES (?, ?, ?, ?, ?)");
                $stmt_item->bind_param("ssiis", $nome, $descricao, $qntd, $categoria, $imagem);
                $stmt_item->execute();
                $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Item adicionado ao estoque!', 'success'));</script>";
                unset($_POST); // Limpa o form
            }
            
        } elseif ($acao_item === 'excluir') {
            $id_alvo_item = intval($_POST['id_alvo_item']);
            $conn->query("DELETE FROM Item WHERE id_item = $id_alvo_item");
            $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Item excluído com sucesso!', 'success'));</script>";
            
        } elseif ($acao_item === 'editar') {
            $id_alvo_item = intval($_POST['id_alvo_item']);
            $nome = trim($_POST['edit_item_nome']);
            $categoria = intval($_POST['edit_item_categoria']);
            $descricao = trim($_POST['edit_item_descricao']);
            $qntd = intval($_POST['edit_item_qntd']);

            if (isset($_FILES['edit_item_foto']) && $_FILES['edit_item_foto']['error'] == 0) {
                $ext = pathinfo($_FILES['edit_item_foto']['name'], PATHINFO_EXTENSION);
                $imagem = uniqid() . "." . $ext;
                move_uploaded_file($_FILES['edit_item_foto']['tmp_name'], "uploads/" . $imagem);
                
                $stmt_edit_item = $conn->prepare("UPDATE Item SET Nome=?, Descricao_Item=?, Qntd=?, id_cat=?, Imagem=? WHERE id_item=?");
                $stmt_edit_item->bind_param("ssiisi", $nome, $descricao, $qntd, $categoria, $imagem, $id_alvo_item);
            } else {
                $stmt_edit_item = $conn->prepare("UPDATE Item SET Nome=?, Descricao_Item=?, Qntd=?, id_cat=? WHERE id_item=?");
                $stmt_edit_item->bind_param("ssiii", $nome, $descricao, $qntd, $categoria, $id_alvo_item);
            }
            $stmt_edit_item->execute();
            $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Item atualizado com sucesso!', 'success'));</script>";
        }
    } catch (\Exception $e) {
        $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('ERRO: Este item faz parte do histórico de um pedido e não pode ser apagado.', 'error'));</script>";
    }
}

// =========================================================================
// 3. PROCESSAMENTO DE CATEGORIAS (NOVO MÓDULO CRUD)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_categoria'])) {
    global $aba_ativa, $sub_aba_ativa;
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-categorias";
    $acao_cat = $_POST['acao_categoria'];

    try {
        if ($acao_cat === 'adicionar') {
            $nome_cat = trim($_POST['cat_nome']);
            $desc_cat = trim($_POST['cat_desc']);

            // --- VALIDAÇÕES DE ERRO DA CATEGORIA ---
            if (empty($nome_cat) || strlen($nome_cat) < 3) $erros_lab['cat_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if (empty($desc_cat) || strlen($desc_cat) < 10) $erros_lab['cat_desc'] = "A descrição deve ter no mínimo 10 caracteres.";

            if (count($erros_lab) > 0) {
                $erros_lab['geral'] = "Por favor, preencha as informações corretamente.";
                $sub_aba_ativa = "tab-nova-categoria";
            } else {
                $stmt_cat = $conn->prepare("INSERT INTO Categoria (Nome, Descricao_cat) VALUES (?, ?)");
                $stmt_cat->bind_param("ss", $nome_cat, $desc_cat);
                $stmt_cat->execute();
                $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Categoria criada com sucesso!', 'success'));</script>";
                unset($_POST); // Limpa o form
            }
            
        } elseif ($acao_cat === 'editar') {
            $id_cat = intval($_POST['id_alvo_cat']);
            $nome_cat = trim($_POST['edit_cat_nome']);
            $desc_cat = trim($_POST['edit_cat_desc']);

            $stmt_edit_cat = $conn->prepare("UPDATE Categoria SET Nome=?, Descricao_cat=? WHERE id_cat=?");
            $stmt_edit_cat->bind_param("ssi", $nome_cat, $desc_cat, $id_cat);
            $stmt_edit_cat->execute();
            $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Categoria atualizada!', 'success'));</script>";

        } elseif ($acao_cat === 'excluir') {
            $id_cat = intval($_POST['id_alvo_cat']);
            $conn->query("DELETE FROM Categoria WHERE id_cat = $id_cat");
            $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('Categoria excluída com sucesso!', 'success'));</script>";
        }
    } catch (\Exception $e) {
        $mensagem_lab = "<script>window.addEventListener('DOMContentLoaded', () => showToast('ERRO: Não é possível excluir uma categoria que possui itens cadastrados nela.', 'error'));</script>";
    }
}

echo $mensagem_lab;

// Busca categorias para os Dropdowns
$res_categorias = $conn->query("SELECT * FROM Categoria ORDER BY Nome ASC");
$categorias_array = [];
while ($cat = $res_categorias->fetch_assoc()) {
    $categorias_array[] = $cat;
}
?>

<main id="view-lab" class="main-view laboratory-main" style="display:none;">
    <div class="page-title-container">
        <h1 id="page-main-title">LEPEP de Hardware</h1>
    </div>

    <!-- ABA INÍCIO -->
    <div id="tab-inicio" class="spa-tab">
        <div class="content-area">
            <p>Bem-vindo à área de Gerenciamento do Laboratório. Use o menu <strong>Laboratório</strong> na barra superior para navegar entre as funções.</p>
            <div style="margin-top: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                <button class="btn-primary-action" onclick="switchLabTab('tab-pedidos-lab', 'Gerenciamento de Pedidos')"><i class="bi bi-archive"></i> Ver Pedidos</button>
                <button class="btn-primary-action" onclick="switchLabTab('tab-estoque', 'Gerenciamento de Estoque')"><i class="bi bi-box-seam"></i> Acessar Estoque</button>
                <button class="btn-primary-action" onclick="switchLabTab('tab-categorias', 'Gerenciamento de Categorias')"><i class="bi bi-tags"></i> Gerenciar Categorias</button>
                <button class="btn-primary-action" onclick="switchLabTab('tab-usuarios', 'Gerenciamento de Usuários')"><i class="bi bi-people"></i> Gerenciar Usuários</button>
            </div>
        </div>
        <div class="background-logo-container">
            <img src="LOGOCHECKADAP.jpg" alt="Logo LEPEP" class="background-logo">
        </div>
    </div>

    <!-- ABA PEDIDOS DO LAB -->
    <div id="tab-pedidos-lab" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="filter-buttons">
                <button class="btn-filter active" onclick="switchLabFilter('aprovacao-andamento', this)"><i class="bi bi-list-check"></i> Aprovação & Preparação</button>
                <button class="btn-filter" onclick="switchLabFilter('historico-lab', this)"><i class="bi bi-clock-history"></i> Histórico de Pedidos</button>
            </div>
            <!-- Exemplo Estático (Será dinâmico na próxima etapa) -->
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
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- ABA DE ESTOQUE E ITENS -->
    <!-- ========================================================= -->
    <div id="tab-estoque" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="stock-list-header">
                <h3>Itens Cadastrados no Sistema</h3>
                <button onclick="switchLabTab('tab-novo-item', 'Adicionar Novo Item')" class="btn-add-item"><i class="bi bi-plus-circle"></i> Adicionar Novo Item</button>
            </div>
            <div class="table-responsive-wrapper">
                <table class="stock-table">
                    <thead><tr><th>Foto</th><th>Nome do Item</th><th>Categoria</th><th>Quantidade</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php
                        $res_itens = $conn->query("SELECT i.*, c.Nome as CategoriaNome FROM Item i LEFT JOIN Categoria c ON i.id_cat = c.id_cat ORDER BY i.Nome ASC");
                        if ($res_itens->num_rows > 0):
                            while ($item = $res_itens->fetch_assoc()):
                                $id_item = $item['id_item'];
                                $nome_item = htmlspecialchars($item['Nome']);
                                $cat_item = htmlspecialchars($item['CategoriaNome'] ?? 'Sem Categoria');
                                $qntd_item = $item['Qntd'];
                                $img_item = "uploads/" . htmlspecialchars($item['Imagem']);
                                
                                if (empty($item['Imagem']) || !file_exists($img_item)) {
                                    $img_item = "LOGOCHECKADAP.jpg"; // Placeholder
                                }

                                $dados_item_json = htmlspecialchars(json_encode([
                                    'id_item' => $id_item, 'Nome' => $item['Nome'], 'id_cat' => $item['id_cat'], 
                                    'Descricao_Item' => $item['Descricao_Item'], 'Qntd' => $item['Qntd']
                                ]));
                        ?>
                            <tr>
                                <td><div class="item-thumb-container"><img src="<?php echo $img_item; ?>" alt="<?php echo $nome_item; ?>" class="item-thumb" style="object-fit:cover; width:100%; height:100%;"></div></td>
                                <td><strong><?php echo $nome_item; ?></strong></td>
                                <td><span style="background:#f0f2f5; padding:4px 8px; border-radius:6px; font-size:0.85rem;"><?php echo $cat_item; ?></span></td>
                                <td><?php echo $qntd_item; ?> <?php if($qntd_item <= 0): ?><span class="badge out-of-stock-badge">Esgotado</span><?php endif; ?></td>
                                <td class="action-buttons">
                                    <button class="btn-action edit" onclick="abrirModalEditItem('<?php echo $dados_item_json; ?>')"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn-action delete" onclick="acaoItem('excluir', <?php echo $id_item; ?>)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 20px;">Nenhum item cadastrado no estoque ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO BLINDADO NOVO ITEM -->
    <div id="tab-novo-item" class="spa-tab" style="display: none;">
        <div class="content-area form-container">
            <!-- Adicionei novalidate para forçar a nossa validação bonita em PHP -->
            <form method="POST" action="FECHECKADM.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="acao_item" value="adicionar">
                
                <?php if(isset($erros_lab['geral']) && $sub_aba_ativa === 'tab-novo-item'): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid #f5c6cb;">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erros_lab['geral']; ?>
                    </div>
                <?php endif; ?>

                <h3 class="form-section-title"><i class="bi bi-box-seam"></i> Dados do Novo Produto</h3>
                
                <div class="form-row">
                    <div class="input-group" style="flex: 2;">
                        <label>Nome Do Item</label>
                        <input type="text" name="add_item_nome" required class="<?php echo isset($erros_lab['item_nome']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['add_item_nome'] ?? ''); ?>">
                        <?php if(isset($erros_lab['item_nome'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['item_nome']; ?></div><?php endif; ?>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label>Quantidade Inicial</label>
                        <input type="number" name="add_item_qntd" required min="0" class="<?php echo isset($erros_lab['item_qntd']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['add_item_qntd'] ?? '1'); ?>">
                        <?php if(isset($erros_lab['item_qntd'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['item_qntd']; ?></div><?php endif; ?>
                    </div>
                </div>
                
                <div class="input-group full-width">
                    <label>Categoria</label>
                    <select name="add_item_categoria" required class="<?php echo isset($erros_lab['item_categoria']) ? 'input-error' : ''; ?>">
                        <option value="">Selecione uma Categoria...</option>
                        <?php 
                        $cat_selecionada = isset($_POST['add_item_categoria']) ? $_POST['add_item_categoria'] : '';
                        foreach($categorias_array as $cat): 
                        ?>
                            <option value="<?php echo $cat['id_cat']; ?>" <?php echo ($cat_selecionada == $cat['id_cat']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['Nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(isset($erros_lab['item_categoria'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['item_categoria']; ?></div><?php endif; ?>
                </div>
                
                <div class="input-group full-width">
                    <label>Descrição Detalhada</label>
                    <textarea name="add_item_descricao" rows="4" required placeholder="Descreva as características técnicas do item..." style="<?php echo isset($erros_lab['item_descricao']) ? 'border: 2px solid #dc3545 !important; background-color: #fff8f8 !important;' : ''; ?>"><?php echo htmlspecialchars($_POST['add_item_descricao'] ?? ''); ?></textarea>
                    <?php if(isset($erros_lab['item_descricao'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['item_descricao']; ?></div><?php endif; ?>
                </div>
                
                <div class="input-group full-width">
                    <label>Foto do Item (Recomendado: fundo branco)</label>
                    <input type="file" name="add_item_foto" accept="image/*" required style="padding: 10px;" class="<?php echo isset($erros_lab['item_foto']) ? 'input-error' : ''; ?>">
                    <?php if(isset($erros_lab['item_foto'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['item_foto']; ?></div><?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Salvar no Estoque</button>
                    <button type="button" class="btn-secondary-action" onclick="window.location.href='FECHECKADM.php'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- ABA DE CATEGORIAS (CRUD) -->
    <!-- ========================================================= -->
    <div id="tab-categorias" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="stock-list-header">
                <h3>Categorias Cadastradas</h3>
                <button onclick="switchLabTab('tab-nova-categoria', 'Adicionar Nova Categoria')" class="btn-add-item"><i class="bi bi-tags"></i> Nova Categoria</button>
            </div>
            
            <div class="table-responsive-wrapper">
                <table class="stock-table">
                    <thead><tr><th>Nome da Categoria</th><th>Descrição</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php
                        if (count($categorias_array) > 0):
                            foreach ($categorias_array as $cat):
                                $dados_cat_json = htmlspecialchars(json_encode([
                                    'id_cat' => $cat['id_cat'], 
                                    'Nome' => $cat['Nome'], 
                                    'Descricao_cat' => $cat['Descricao_cat']
                                ]));
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['Nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['Descricao_cat']); ?></td>
                                <td class="action-buttons">
                                    <button class="btn-action edit" onclick="abrirModalEditCategoria('<?php echo $dados_cat_json; ?>')"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn-action delete" onclick="acaoCategoria('excluir', <?php echo $cat['id_cat']; ?>)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" style="text-align:center; padding: 20px;">Nenhuma categoria cadastrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO BLINDADO NOVA CATEGORIA -->
    <div id="tab-nova-categoria" class="spa-tab" style="display: none;">
        <div class="content-area form-container" style="max-width: 600px;">
            <form method="POST" action="FECHECKADM.php" novalidate>
                <input type="hidden" name="acao_categoria" value="adicionar">
                
                <?php if(isset($erros_lab['geral']) && $sub_aba_ativa === 'tab-nova-categoria'): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid #f5c6cb;">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erros_lab['geral']; ?>
                    </div>
                <?php endif; ?>

                <h3 class="form-section-title"><i class="bi bi-tags"></i> Nova Categoria</h3>
                
                <div class="input-group full-width">
                    <label>Nome da Categoria</label>
                    <input type="text" name="cat_nome" required placeholder="Ex: Microcontroladores, Ferramentas..." class="<?php echo isset($erros_lab['cat_nome']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['cat_nome'] ?? ''); ?>">
                    <?php if(isset($erros_lab['cat_nome'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['cat_nome']; ?></div><?php endif; ?>
                </div>
                
                <div class="input-group full-width">
                    <label>Breve Descrição</label>
                    <textarea name="cat_desc" rows="3" required placeholder="Ex: Placas arduino, cabos jumper, etc." style="<?php echo isset($erros_lab['cat_desc']) ? 'border: 2px solid #dc3545 !important; background-color: #fff8f8 !important;' : ''; ?>"><?php echo htmlspecialchars($_POST['cat_desc'] ?? ''); ?></textarea>
                    <?php if(isset($erros_lab['cat_desc'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['cat_desc']; ?></div><?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Criar Categoria</button>
                    <button type="button" class="btn-secondary-action" onclick="window.location.href='FECHECKADM.php'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ABA USUÁRIOS E CADASTRO -->
    <div id="tab-usuarios" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="stock-list-header">
                <h3>Lista de Usuários Cadastrados</h3>
                <button onclick="switchLabTab('tab-novo-usuario', 'Adicionar Novo Usuário')" class="btn-add-item"><i class="bi bi-person-plus"></i> Adicionar Usuário</button>
            </div>
            <div class="table-responsive-wrapper">
                <table class="stock-table">
                    <thead><tr><th>Nome Completo</th><th>E-mail</th><th>Nível</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php
                        $res_usuarios = $conn->query("SELECT * FROM Usuarios ORDER BY Nome ASC");
                        while ($u = $res_usuarios->fetch_assoc()):
                            $id_u = $u['id_user'];
                            $nome_u = htmlspecialchars($u['Nome']);
                            $email_u = htmlspecialchars($u['Email']);
                            $tipo_u = ucfirst($u['Tipo_user']);
                            $status_u = $u['status'];
                            
                            $cor_status = ($status_u === 'ativo') ? 'green' : 'red';
                            $texto_status = ($status_u === 'ativo') ? 'Ativo' : 'Bloqueado';
                            $btn_bloqueio_txt = ($status_u === 'ativo') ? 'Bloquear' : 'Desbloquear';
                            $btn_bloqueio_icon = ($status_u === 'ativo') ? 'bi-slash-circle' : 'bi-check-circle';
                            
                            $dados_json = htmlspecialchars(json_encode([
                                'id' => $u['id_user'], 'nome' => $u['Nome'], 'email' => $u['Email'], 
                                'cpf' => $u['CPF'], 'matricula' => $u['Matricula'], 'siape' => $u['SIAPE'], 'tipo' => $u['Tipo_user']
                            ]));
                        ?>
                            <!-- LINHA VISÍVEL -->
                            <tr data-user-id="<?php echo $id_u; ?>">
                                <td><?php echo $nome_u; ?></td>
                                <td><?php echo $email_u; ?></td>
                                <td><?php echo $tipo_u; ?></td>
                                <td style="color: <?php echo $cor_status; ?>; font-weight: bold;"><?php echo $texto_status; ?></td>
                                <td><button type="button" class="btn-view-pedidos" onclick="togglePedidos('<?php echo $id_u; ?>')"><i class="bi bi-plus-lg"></i></button></td>
                            </tr>
                            
                            <!-- LINHA OCULTA EM GAVETA -->
                            <tr id="row-detail-<?php echo $id_u; ?>" class="row-detalhes" style="display: none; background-color: #f8f9fa;">
                                <td colspan="5" style="padding: 0; border: none;">
                                    <div class="pedidos-detail-container" style="margin: 0; border-radius: 0; box-shadow: inset 0 3px 6px rgba(0,0,0,0.05); border: none; border-left: 4px solid var(--primary-color);">
                                        <h4>Detalhes - <span class="user-name-placeholder"><?php echo $nome_u; ?></span></h4>
                                        <p style="margin-bottom: 10px;">
                                           <strong>CPF:</strong> <?php echo !empty($u['CPF']) ? htmlspecialchars($u['CPF']) : '-'; ?> | 
                                           <strong>Matrícula:</strong> <?php echo !empty($u['Matricula']) ? htmlspecialchars($u['Matricula']) : '-'; ?> | 
                                           <strong>SIAPE:</strong> <?php echo !empty($u['SIAPE']) ? htmlspecialchars($u['SIAPE']) : '-'; ?>
                                        </p>
                                        <div class="user-actions-footer">
                                            <button type="button" class="btn-user-opt btn-edit" onclick="abrirModalEditUser('<?php echo $dados_json; ?>')"><i class="bi bi-pencil-square"></i> Editar</button>
                                            <button type="button" class="btn-user-opt btn-block" onclick="acaoUsuario('bloquear', '<?php echo $id_u; ?>')"><i class="bi <?php echo $btn_bloqueio_icon; ?>"></i> <?php echo $btn_bloqueio_txt; ?></button>
                                            <button type="button" class="btn-user-opt btn-delete" onclick="acaoUsuario('excluir', '<?php echo $id_u; ?>')"><i class="bi bi-trash-fill"></i> Excluir</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO BLINDADO NOVO USUÁRIO -->
    <div id="tab-novo-usuario" class="spa-tab" style="display: none;">
        <div class="content-area form-container">
            <form id="add-user-form" method="POST" action="FECHECKADM.php" novalidate>
                <input type="hidden" name="acao_usuario" value="adicionar">
                <?php if(isset($erros_lab['geral']) && $sub_aba_ativa === 'tab-novo-usuario'): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid #f5c6cb;"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erros_lab['geral']; ?></div>
                <?php endif; ?>

                <h3 class="form-section-title"><i class="bi bi-person-circle"></i> Informações Básicas</h3>
                
                <div class="form-row">
                    <div class="input-group">
                        <label>Tipo de Usuário</label>
                        <select name="add_tipo" id="add_tipo" required onchange="mudarCampoDinamicoAddUser(this.value)" class="<?php echo isset($erros_lab['add_tipo']) ? 'input-error' : ''; ?>">
                            <option value="">Selecione...</option>
                            <option value="padrao" <?php echo (isset($_POST['add_tipo']) && $_POST['add_tipo'] === 'padrao') ? 'selected' : ''; ?>>Comum (Aluno)</option>
                            <option value="resp" <?php echo (isset($_POST['add_tipo']) && $_POST['add_tipo'] === 'resp') ? 'selected' : ''; ?>>Responsável LEPEP</option>
                            <option value="admin" <?php echo (isset($_POST['add_tipo']) && $_POST['add_tipo'] === 'admin') ? 'selected' : ''; ?>>Admin LEPEP</option>
                        </select>
                        <?php if(isset($erros_lab['add_tipo'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_tipo']; ?></div><?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label>CPF</label>
                        <input type="text" name="add_cpf" id="add_cpf" placeholder="000.000.000-00" maxlength="14" required class="<?php echo isset($erros_lab['add_cpf']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['add_cpf'] ?? ''); ?>">
                        <?php if(isset($erros_lab['add_cpf'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_cpf']; ?></div><?php endif; ?>
                    </div>
                </div>
                
                <div class="input-group full-width" id="grupo-dinamico-add-user" style="display: <?php echo (isset($_POST['add_tipo']) && $_POST['add_tipo'] !== '') ? 'block' : 'none'; ?>;">
                    <label id="label-dinamico-add-user"><?php echo (isset($_POST['add_tipo']) && $_POST['add_tipo'] === 'padrao') ? 'Matrícula' : 'SIAPE'; ?></label>
                    <input type="text" name="add_dinamico" id="input-dinamico-add-user" class="<?php echo isset($erros_lab['add_dinamico']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['add_dinamico'] ?? ''); ?>">
                    <?php if(isset($erros_lab['add_dinamico'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_dinamico']; ?></div><?php endif; ?>
                </div>
                <div class="input-group full-width">
                    <label>Nome Completo</label>
                    <input type="text" name="add_nome" required class="<?php echo isset($erros_lab['add_nome']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['add_nome'] ?? ''); ?>">
                    <?php if(isset($erros_lab['add_nome'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_nome']; ?></div><?php endif; ?>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label>Data de Nascimento</label>
                        <input type="date" name="add_nascimento" required class="<?php echo isset($erros_lab['add_nascimento']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['add_nascimento'] ?? ''); ?>">
                        <?php if(isset($erros_lab['add_nascimento'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_nascimento']; ?></div><?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label>E-mail</label>
                        <input type="email" name="add_email" required class="<?php echo isset($erros_lab['add_email']) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($_POST['add_email'] ?? ''); ?>">
                        <?php if(isset($erros_lab['add_email'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_email']; ?></div><?php endif; ?>
                    </div>
                </div>
                <h3 class="form-section-title" style="margin-top: 1rem;"><i class="bi bi-lock"></i> Dados de Acesso</h3>
                <div class="form-row">
                    <div class="input-group">
                        <label>Senha</label>
                        <input type="password" name="add_senha" placeholder="Mín. 8 caracteres" required class="<?php echo isset($erros_lab['add_senha']) ? 'input-error' : ''; ?>">
                        <?php if(isset($erros_lab['add_senha'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_senha']; ?></div><?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label>Confirmação</label>
                        <input type="password" name="add_confirma" required class="<?php echo isset($erros_lab['add_confirma']) ? 'input-error' : ''; ?>">
                        <?php if(isset($erros_lab['add_confirma'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['add_confirma']; ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary-action"><i class="bi bi-person-plus"></i> Cadastrar Usuário</button>
                    <button type="button" class="btn-secondary-action" onclick="window.location.href='FECHECKADM.php'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- ========================================================= -->
<!-- MODAIS DO LABORATÓRIO (ESCONDIDOS) -->
<!-- ========================================================= -->

<!-- Formulário Oculto para Ações Genéricas (Itens, Usuários, Categorias) -->
<form id="form-acao-generica" method="POST" action="FECHECKADM.php" style="display:none;">
    <input type="hidden" id="input-acao">
    <input type="hidden" id="input-id">
</form>

<!-- Modal Editar CATEGORIA -->
<div id="modalEditCategoria" class="modal-overlay">
    <div class="modal-content form-container" style="max-width: 500px;">
        <h3 class="form-section-title"><i class="bi bi-tags"></i> Editar Categoria</h3>
        <form method="POST" action="FECHECKADM.php" style="margin-top: 15px;">
            <input type="hidden" name="acao_categoria" value="editar">
            <input type="hidden" name="id_alvo_cat" id="edit_id_alvo_cat">
            
            <div class="input-group full-width">
                <label>Nome da Categoria</label>
                <input type="text" name="edit_cat_nome" id="edit_cat_nome" required>
            </div>
            <div class="input-group full-width" style="margin-top:10px;">
                <label>Descrição</label>
                <textarea name="edit_cat_desc" id="edit_cat_desc" rows="3" style="width:100%; border:1px solid #ccc; border-radius:8px; padding:10px;" required></textarea>
            </div>
            
            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Atualizar Categoria</button>
                <button type="button" class="btn-secondary-action" onclick="document.getElementById('modalEditCategoria').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar ITEM -->
<div id="modalEditItem" class="modal-overlay">
    <div class="modal-content form-container" style="max-width: 600px;">
        <h3 class="form-section-title"><i class="bi bi-pencil-square"></i> Editar Produto</h3>
        <form method="POST" action="FECHECKADM.php" enctype="multipart/form-data" style="margin-top: 15px;">
            <input type="hidden" name="acao_item" value="editar">
            <input type="hidden" name="id_alvo_item" id="edit_id_alvo_item">
            
            <div class="form-row" style="display:flex; gap:10px;">
                <div class="input-group" style="flex:2;"><label>Nome Do Item</label><input type="text" name="edit_item_nome" id="edit_item_nome" required style="width:100%; height:45px;"></div>
                <div class="input-group" style="flex:1;"><label>Quantidade</label><input type="number" name="edit_item_qntd" id="edit_item_qntd" required min="0" style="width:100%; height:45px;"></div>
            </div>
            
            <div class="input-group" style="margin-top:10px;">
                <label>Categoria</label>
                <select name="edit_item_categoria" id="edit_item_categoria" required style="width:100%; height:45px; border-radius:5px;">
                    <?php foreach($categorias_array as $cat): ?>
                        <option value="<?php echo $cat['id_cat']; ?>"><?php echo htmlspecialchars($cat['Nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="input-group" style="margin-top:10px;">
                <label>Descrição Detalhada</label>
                <textarea name="edit_item_descricao" id="edit_item_descricao" rows="4" style="width:100%; border:1px solid #ccc; border-radius:8px; padding:10px;" required></textarea>
            </div>

            <div class="input-group" style="margin-top:10px;">
                <label>Substituir Foto (deixe vazio para manter a atual)</label>
                <input type="file" name="edit_item_foto" accept="image/*" style="width:100%; padding:10px;">
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn-primary-action" style="padding: 10px 20px; background-color:#0d005f; color:white; border:none; border-radius:5px;"><i class="bi bi-save"></i> Atualizar Item</button>
                <button type="button" class="btn-secondary-action" onclick="document.getElementById('modalEditItem').style.display='none'" style="padding: 10px 20px; background-color:#ccc; border:none; border-radius:5px; margin-left:10px;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Usuário -->
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
                <button type="button" class="btn-secondary-action" onclick="document.getElementById('modalEditUser').style.display='none'" style="padding: 10px 20px; background-color:#ccc; border:none; border-radius:5px; margin-left:10px;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // As funções que disparam a ação de exclusão
    function acaoUsuario(acao, id) {
        if (acao === 'excluir' && !confirm("Tem certeza que deseja apagar este usuário DEFINITIVAMENTE?")) return;
        if (acao === 'bloquear' && !confirm("Tem certeza que deseja mudar o status de bloqueio deste usuário?")) return;
        const form = document.getElementById('form-acao-generica');
        document.getElementById('input-acao').name = 'acao_usuario';
        document.getElementById('input-acao').value = acao;
        document.getElementById('input-id').name = 'id_alvo';
        document.getElementById('input-id').value = id;
        form.submit();
    }

    function acaoItem(acao, id) {
        if (acao === 'excluir' && !confirm("Tem certeza que deseja apagar este ITEM do estoque DEFINITIVAMENTE?")) return;
        const form = document.getElementById('form-acao-generica');
        document.getElementById('input-acao').name = 'acao_item';
        document.getElementById('input-acao').value = acao;
        document.getElementById('input-id').name = 'id_alvo_item';
        document.getElementById('input-id').value = id;
        form.submit();
    }

    function acaoCategoria(acao, id) {
        if (acao === 'excluir' && !confirm("ATENÇÃO: Deseja realmente apagar esta CATEGORIA?\nItens vinculados a ela poderão impedir a exclusão.")) return;
        const form = document.getElementById('form-acao-generica');
        document.getElementById('input-acao').name = 'acao_categoria';
        document.getElementById('input-acao').value = acao;
        document.getElementById('input-id').name = 'id_alvo_cat';
        document.getElementById('input-id').value = id;
        form.submit();
    }

    // Modal de Edição (Item)
    function abrirModalEditItem(jsonData) {
        const dados = JSON.parse(jsonData);
        document.getElementById('edit_id_alvo_item').value = dados.id_item;
        document.getElementById('edit_item_nome').value = dados.Nome;
        document.getElementById('edit_item_categoria').value = dados.id_cat;
        document.getElementById('edit_item_descricao').value = dados.Descricao_Item;
        document.getElementById('edit_item_qntd').value = dados.Qntd;
        document.getElementById('modalEditItem').style.display = 'flex';
    }

    // Modal de Edição (Categoria)
    function abrirModalEditCategoria(jsonData) {
        const dados = JSON.parse(jsonData);
        document.getElementById('edit_id_alvo_cat').value = dados.id_cat;
        document.getElementById('edit_cat_nome').value = dados.Nome;
        document.getElementById('edit_cat_desc').value = dados.Descricao_cat;
        document.getElementById('modalEditCategoria').style.display = 'flex';
    }
</script>
