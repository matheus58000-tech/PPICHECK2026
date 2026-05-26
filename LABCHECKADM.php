<?php
$mensagem_lab = "";
$erros_lab = [];

// =========================================================================
// LÊ AS MENSAGENS E ABAS DA SESSÃO (Pós-Redirecionamento Anti-F5)
// =========================================================================
if (isset($_SESSION['msg_sucesso_lab'])) {
    $mensagem_lab .= "<script>window.addEventListener('DOMContentLoaded', () => showToast('" . addslashes($_SESSION['msg_sucesso_lab']) . "', 'success'));</script>";
    unset($_SESSION['msg_sucesso_lab']);
}
if (isset($_SESSION['msg_erro_lab'])) {
    $mensagem_lab .= "<script>window.addEventListener('DOMContentLoaded', () => showToast('" . addslashes($_SESSION['msg_erro_lab']) . "', 'error'));</script>";
    unset($_SESSION['msg_erro_lab']);
}
if (isset($_SESSION['aba_ativa'])) {
    $aba_ativa = $_SESSION['aba_ativa'];
    unset($_SESSION['aba_ativa']);
}
if (isset($_SESSION['sub_aba_ativa'])) {
    $sub_aba_ativa = $_SESSION['sub_aba_ativa'];
    unset($_SESSION['sub_aba_ativa']);
}

if (!function_exists('redirectLab')) {
    function redirectLab($aba, $sub_aba) {
        $_SESSION['aba_ativa'] = $aba;
        $_SESSION['sub_aba_ativa'] = $sub_aba;
        header("Location: FECHECKADM.php");
        exit(); 
    }
}

// [O CÓDIGO PHP DE PROCESSAMENTO DO BANCO DE DADOS FOI MANTIDO INTACTO AQUI]
// (Processamento de Usuários, Itens e Categorias)

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_usuario'])) {
    $aba_ativa = "view-lab"; $sub_aba_ativa = "tab-usuarios"; $acao = $_POST['acao_usuario'];
    $id_alvo = isset($_POST['id_alvo']) ? intval($_POST['id_alvo']) : 0;
    if ($id_alvo === $id_usuario && ($acao === 'excluir' || $acao === 'bloquear')) {
        $_SESSION['msg_erro_lab'] = "Você não pode bloquear ou excluir a própria conta!"; redirectLab('view-lab', 'tab-usuarios');
    } else {
        try {
            if ($acao === 'bloquear') {
                $conn->query("UPDATE Usuarios SET status = IF(status='ativo', 'bloqueado', 'ativo') WHERE id_user = $id_alvo");
                $_SESSION['msg_sucesso_lab'] = "Status do utilizador alterado!"; redirectLab('view-lab', 'tab-usuarios');
            } elseif ($acao === 'excluir') {
                $conn->query("DELETE FROM Usuarios WHERE id_user = $id_alvo");
                $_SESSION['msg_sucesso_lab'] = "Utilizador excluído permanentemente!"; redirectLab('view-lab', 'tab-usuarios');
            } elseif ($acao === 'editar') {
                $nome = trim($_POST['edit_nome']); $email = trim($_POST['edit_email']); $cpf = trim($_POST['edit_cpf']);
                $matricula = trim($_POST['edit_matricula']); $siape = trim($_POST['edit_siape']); $tipo = trim($_POST['edit_tipo']); $nova_senha = $_POST['edit_senha'];
                if (!empty($nova_senha)) {
                    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt_edit = $conn->prepare("UPDATE Usuarios SET Nome=?, Email=?, CPF=?, Matricula=?, SIAPE=?, Tipo_user=?, Senha=? WHERE id_user=?");
                    $stmt_edit->bind_param("sssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $hash, $id_alvo);
                } else {
                    $stmt_edit = $conn->prepare("UPDATE Usuarios SET Nome=?, Email=?, CPF=?, Matricula=?, SIAPE=?, Tipo_user=? WHERE id_user=?");
                    $stmt_edit->bind_param("ssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $id_alvo);
                }
                $stmt_edit->execute(); $_SESSION['msg_sucesso_lab'] = "Dados atualizados!"; redirectLab('view-lab', 'tab-usuarios');
            } elseif ($acao === 'adicionar') {
                $tipo = trim($_POST['add_tipo']); $cpf = trim($_POST['add_cpf']); $nome = trim($_POST['add_nome']);
                $email = trim($_POST['add_email']); $nascimento = $_POST['add_nascimento']; $senha = $_POST['add_senha']; $confirma = $_POST['add_confirma'];
                $dinamico = isset($_POST['add_dinamico']) ? trim($_POST['add_dinamico']) : null;
                $matricula = ($tipo === 'padrao') ? $dinamico : null; $siape = ($tipo === 'admin' || $tipo === 'resp') ? $dinamico : null;

                if (empty($nome) || strlen($nome) < 3) $erros_lab['add_nome'] = "Mín. 3 letras.";
                $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf); 
                if (empty($cpf_limpo) || strlen($cpf_limpo) !== 11) $erros_lab['add_cpf'] = "Exatos 11 dígitos.";
                if (empty($tipo)) $erros_lab['add_tipo'] = "Selecione o nível.";
                elseif ($tipo === 'padrao' && (empty($matricula) || strlen($matricula) !== 10 || !is_numeric($matricula))) $erros_lab['add_dinamico'] = "Exatos 10 números.";
                elseif (($tipo === 'admin' || $tipo === 'resp') && (empty($siape) || strlen($siape) !== 7 || !is_numeric($siape))) $erros_lab['add_dinamico'] = "Exatos 7 números.";
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros_lab['add_email'] = "E-mail inválido.";
                $data_atual = date("Y-m-d"); if (empty($nascimento) || $nascimento > $data_atual) $erros_lab['add_nascimento'] = "Data inválida.";
                if (empty($senha) || strlen($senha) < 8) $erros_lab['add_senha'] = "Mín. 8 caracteres."; if ($senha !== $confirma) $erros_lab['add_confirma'] = "Senhas não coincidem.";

                if (count($erros_lab) > 0) { $erros_lab['geral'] = "Preencha todos os campos corretamente."; $sub_aba_ativa = "tab-novo-usuario"; } 
                else {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt_add = $conn->prepare("INSERT INTO Usuarios (Nome, CPF, Matricula, SIAPE, Email, Data_nasc, Senha, Tipo_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_add->bind_param("ssssssss", $nome, $cpf, $matricula, $siape, $email, $nascimento, $hash, $tipo);
                    $stmt_add->execute(); $_SESSION['msg_sucesso_lab'] = "Utilizador cadastrado!"; redirectLab('view-lab', 'tab-usuarios'); 
                }
            }
        } catch (\Exception $e) {
            if ($acao === 'adicionar') { $erros_lab['geral'] = "ERRO: O CPF, Matrícula, SIAPE ou E-mail digitado já estão em uso."; $sub_aba_ativa = "tab-novo-usuario"; } 
            else { $_SESSION['msg_erro_lab'] = "ERRO: O CPF, Matrícula, SIAPE ou E-mail já pertencem a outra conta."; redirectLab('view-lab', 'tab-usuarios'); }
        }
    }
}

$abrir_modal_edit_item = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_item'])) {
    $aba_ativa = "view-lab"; $sub_aba_ativa = "tab-estoque"; $acao_item = $_POST['acao_item'];
    try {
        if ($acao_item === 'adicionar') {
            $nome = trim($_POST['add_item_nome']); $categoria = isset($_POST['add_item_categoria']) ? intval($_POST['add_item_categoria']) : 0;
            $descricao = trim($_POST['add_item_descricao']); $qntd = isset($_POST['add_item_qntd']) ? intval($_POST['add_item_qntd']) : -1;
            if (empty($nome) || strlen($nome) < 3) $erros_lab['item_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if ($categoria <= 0) $erros_lab['item_categoria'] = "Selecione uma categoria válida.";
            if ($qntd < 0) $erros_lab['item_qntd'] = "A quantidade não pode ser negativa.";
            if (empty($descricao) || strlen($descricao) < 10) $erros_lab['item_descricao'] = "Forneça uma descrição técnica (mín. 10 caracteres).";
            if (!isset($_FILES['add_item_foto']) || $_FILES['add_item_foto']['error'] != 0) $erros_lab['item_foto'] = "É obrigatório enviar uma foto do produto.";

            if (count($erros_lab) > 0) { $erros_lab['geral'] = "Verifique os campos."; $sub_aba_ativa = "tab-novo-item"; } 
            else {
                $ext = pathinfo($_FILES['add_item_foto']['name'], PATHINFO_EXTENSION); $imagem = uniqid() . "." . $ext;
                move_uploaded_file($_FILES['add_item_foto']['tmp_name'], "uploads/" . $imagem);
                $stmt_item = $conn->prepare("INSERT INTO Item (Nome, Descricao_Item, Qntd, id_cat, Imagem) VALUES (?, ?, ?, ?, ?)");
                $stmt_item->bind_param("ssiis", $nome, $descricao, $qntd, $categoria, $imagem); $stmt_item->execute();
                $_SESSION['msg_sucesso_lab'] = "Item adicionado!"; redirectLab('view-lab', 'tab-estoque');
            }
        } elseif ($acao_item === 'excluir') {
            $id_alvo_item = intval($_POST['id_alvo_item']); $conn->query("DELETE FROM Item WHERE id_item = $id_alvo_item");
            $_SESSION['msg_sucesso_lab'] = "Item excluído!"; redirectLab('view-lab', 'tab-estoque');
        } elseif ($acao_item === 'editar') {
            $id_alvo_item = intval($_POST['id_alvo_item']); $nome = trim($_POST['edit_item_nome']); $categoria = intval($_POST['edit_item_categoria']);
            $descricao = trim($_POST['edit_item_descricao']); $qntd = intval($_POST['edit_item_qntd']);
            if (empty($nome) || strlen($nome) < 3) $erros_lab['edit_item_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if ($categoria <= 0) $erros_lab['edit_item_categoria'] = "Selecione uma categoria válida.";
            if ($qntd < 0) $erros_lab['edit_item_qntd'] = "A quantidade não pode ser negativa.";
            if (empty($descricao) || strlen($descricao) < 10) $erros_lab['edit_item_descricao'] = "Forneça uma descrição técnica.";

            if (count($erros_lab) > 0) { $erros_lab['geral_edit_item'] = "Erro na edição."; $sub_aba_ativa = "tab-estoque"; $abrir_modal_edit_item = true; } 
            else {
                if (isset($_FILES['edit_item_foto']) && $_FILES['edit_item_foto']['error'] == 0) {
                    $ext = pathinfo($_FILES['edit_item_foto']['name'], PATHINFO_EXTENSION); $imagem = uniqid() . "." . $ext;
                    move_uploaded_file($_FILES['edit_item_foto']['tmp_name'], "uploads/" . $imagem);
                    $stmt_edit_item = $conn->prepare("UPDATE Item SET Nome=?, Descricao_Item=?, Qntd=?, id_cat=?, Imagem=? WHERE id_item=?");
                    $stmt_edit_item->bind_param("ssiisi", $nome, $descricao, $qntd, $categoria, $imagem, $id_alvo_item);
                } else {
                    $stmt_edit_item = $conn->prepare("UPDATE Item SET Nome=?, Descricao_Item=?, Qntd=?, id_cat=? WHERE id_item=?");
                    $stmt_edit_item->bind_param("ssiii", $nome, $descricao, $qntd, $categoria, $id_alvo_item);
                }
                $stmt_edit_item->execute(); $_SESSION['msg_sucesso_lab'] = "Item atualizado!"; redirectLab('view-lab', 'tab-estoque');
            }
        }
    } catch (\Exception $e) {
        if ($acao_item === 'adicionar') { $erros_lab['geral'] = "ERRO ao cadastrar."; $sub_aba_ativa = "tab-novo-item"; } 
        else { $_SESSION['msg_erro_lab'] = "ERRO: Item bloqueado por estar num pedido."; redirectLab('view-lab', 'tab-estoque'); }
    }
}

$abrir_modal_edit_cat = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_categoria'])) {
    $aba_ativa = "view-lab"; $sub_aba_ativa = "tab-categorias"; $acao_cat = $_POST['acao_categoria'];
    try {
        if ($acao_cat === 'adicionar') {
            $nome_cat = trim($_POST['cat_nome']); $desc_cat = trim($_POST['cat_desc']);
            if (empty($nome_cat) || strlen($nome_cat) < 3) $erros_lab['cat_nome'] = "Mín. 3 caracteres.";
            if (empty($desc_cat) || strlen($desc_cat) < 10) $erros_lab['cat_desc'] = "Mín. 10 caracteres.";

            if (count($erros_lab) > 0) { $erros_lab['geral'] = "Preencha corretamente."; $sub_aba_ativa = "tab-nova-categoria"; } 
            else {
                $stmt_cat = $conn->prepare("INSERT INTO Categoria (Nome, Descricao_cat) VALUES (?, ?)");
                $stmt_cat->bind_param("ss", $nome_cat, $desc_cat); $stmt_cat->execute();
                $_SESSION['msg_sucesso_lab'] = "Categoria criada!"; redirectLab('view-lab', 'tab-categorias');
            }
        } elseif ($acao_cat === 'editar') {
            $id_cat = intval($_POST['id_alvo_cat']); $nome_cat = trim($_POST['edit_cat_nome']); $desc_cat = trim($_POST['edit_cat_desc']);
            if (empty($nome_cat) || strlen($nome_cat) < 3) $erros_lab['edit_cat_nome'] = "Mín. 3 caracteres.";
            if (empty($desc_cat) || strlen($desc_cat) < 10) $erros_lab['edit_cat_desc'] = "Mín. 10 caracteres.";

            if (count($erros_lab) > 0) { $erros_lab['geral_edit_cat'] = "Erro na edição."; $sub_aba_ativa = "tab-categorias"; $abrir_modal_edit_cat = true; } 
            else {
                $stmt_edit_cat = $conn->prepare("UPDATE Categoria SET Nome=?, Descricao_cat=? WHERE id_cat=?");
                $stmt_edit_cat->bind_param("ssi", $nome_cat, $desc_cat, $id_cat); $stmt_edit_cat->execute();
                $_SESSION['msg_sucesso_lab'] = "Categoria atualizada!"; redirectLab('view-lab', 'tab-categorias');
            }
        } elseif ($acao_cat === 'excluir') {
            $id_cat = intval($_POST['id_alvo_cat']); $conn->query("DELETE FROM Categoria WHERE id_cat = $id_cat");
            $_SESSION['msg_sucesso_lab'] = "Categoria excluída!"; redirectLab('view-lab', 'tab-categorias');
        }
    } catch (\Exception $e) {
        if ($acao_cat === 'adicionar') { $erros_lab['geral'] = "ERRO ao criar a categoria."; $sub_aba_ativa = "tab-nova-categoria"; } 
        else { $_SESSION['msg_erro_lab'] = "ERRO: Categoria possui itens cadastrados."; redirectLab('view-lab', 'tab-categorias'); }
    }
}

echo $mensagem_lab;

$res_categorias = $conn->query("SELECT * FROM Categoria ORDER BY Nome ASC");
$categorias_array = [];
while ($cat = $res_categorias->fetch_assoc()) { $categorias_array[] = $cat; }
?>

<style>
    /* Borda e Badge de Retirada Expressa */
    .order-card.express { border: 2px solid #dc3545; background-color: #fff8f8; }
    .express-badge {
        background-color: #dc3545; color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: 900;
        text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 15px;
        animation: pulse-red 1.5s infinite;
    }
    @keyframes pulse-red {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }

    /* Estilos da Tabela de Devolução */
    .devolucao-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .devolucao-table th, .devolucao-table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
    .devolucao-table th { background-color: #f8f9fa; color: #555; font-size: 0.9rem; font-weight: 700;}
    .qtd-input { width: 60px; padding: 8px; border: 1px solid #ccc; border-radius: 6px; text-align: center; font-weight: bold;}
    .status-ok { color: #10ac84; font-weight: bold; }
    .status-falta { color: #e67e22; font-weight: bold; }

    /* Switch CSS (Botão "Voltar ao Estoque") */
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; margin-bottom: 5px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #10ac84; }
    input:checked + .slider:before { transform: translateX(20px); }
</style>

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
                <button class="btn-filter active" onclick="switchLabFilter('pendentes', this)"><i class="bi bi-hourglass-split"></i> Pendentes de Aprovação</button>
                <button class="btn-filter" onclick="switchLabFilter('separacao', this)"><i class="bi bi-box-seam"></i> Em Separação</button>
                <button class="btn-filter" onclick="switchLabFilter('historico-lab', this)"><i class="bi bi-clock-history"></i> Histórico</button>
            </div>
            
            <div class="orders-container" id="pendentes">
                <h3 class="order-section-title">Aguardando Aprovação</h3>
                
                <div class="order-card express" id="card-2025-001">
                    <div class="express-badge"><i class="bi bi-lightning-charge-fill"></i> Retirada Expressa</div>
                    <div class="order-header" style="border-bottom: none; padding-bottom: 0;">
                        <span class="order-code">#2025-001</span>
                        <span class="order-user">Usuário: Lucas Ribolli (Comum)</span>
                        <div class="action-buttons">
                            <button class="btn-action approve" onclick="aprovarPedido('2025-001')"><i class="bi bi-check-lg"></i> Aprovar</button>
                            <button class="btn-action delete" onclick="abrirModalRecusa('2025-001')"><i class="bi bi-x-lg"></i> Recusar</button>
                        </div>
                    </div>
                    <div class="order-details" style="margin-top: 10px; border-top: 1px dashed #eee; padding-top: 10px;">
                        <p><strong>Itens:</strong> Arduino Uno R3 (x2), Fio Jumper (x10)</p>
                        <p>Data do Pedido: 26/11/2025</p>
                    </div>
                </div>

                <div class="order-card" id="card-2025-002">
                    <div class="order-header">
                        <span class="order-code">#2025-002</span>
                        <span class="order-user">Usuário: João Silva (Comum)</span>
                        <div class="action-buttons">
                            <button class="btn-action approve" onclick="aprovarPedido('2025-002')"><i class="bi bi-check-lg"></i> Aprovar</button>
                            <button class="btn-action delete" onclick="abrirModalRecusa('2025-002')"><i class="bi bi-x-lg"></i> Recusar</button>
                        </div>
                    </div>
                    <div class="order-details">
                        <p><strong>Itens:</strong> LED Amarelo (x10), Resistor 1k (x5)</p>
                        <p>Data do Pedido: 25/11/2025</p>
                    </div>
                </div>
            </div>

            <div class="orders-container" id="separacao" style="display: none;">
                <h3 class="order-section-title">Pedidos em Separação / Retirados</h3>
                
                <div class="order-card express">
                    <div class="express-badge"><i class="bi bi-lightning-charge-fill"></i> Retirada Expressa</div>
                    <div class="order-header" style="border-bottom: none; padding-bottom: 0;">
                        <span class="order-code">#2025-003</span>
                        <span class="order-user">Usuário: Maria Clara</span>
                        <div class="status-selector">
                            <label>Status:</label>
                            <select style="padding:6px; border-radius:6px; border:1px solid #ccc;">
                                <option value="producao" selected>Em Separação</option>
                                <option value="retirada">Pronto para Retirada</option>
                                <option value="retirado">Retirado (Em uso)</option>
                            </select>
                            <button class="btn-primary-action" style="padding: 6px 12px; margin-left:10px; font-size: 0.9rem;" onclick="abrirDevolucao('2025-003', 'Maria Clara', '26/11/2025')">
                                <i class="bi bi-arrow-counterclockwise"></i> Devolver
                            </button>
                        </div>
                    </div>
                    <div class="order-details" style="margin-top: 10px; border-top: 1px dashed #eee; padding-top: 10px;">
                        <p><strong>Itens:</strong> Alicate de Corte (x1)</p>
                        <p>Previsão de Devolução: 03/12/2025</p>
                    </div>
                </div>

                <div class="order-card">
                    <div class="order-header">
                        <span class="order-code">#2025-004</span>
                        <span class="order-user">Usuário: Stella Lyana Montenegro</span>
                        <div class="status-selector">
                            <label>Status:</label>
                            <select style="padding:6px; border-radius:6px; border:1px solid #ccc;">
                                <option value="retirado" selected>Retirado (Em uso)</option>
                                <option value="producao">Em Separação</option>
                            </select>
                            <button class="btn-primary-action" style="padding: 6px 12px; margin-left:10px; font-size: 0.9rem;" onclick="abrirDevolucao('2025-004', 'Stella Lyana Montenegro', '25/11/2025')">
                                <i class="bi bi-arrow-counterclockwise"></i> Devolver
                            </button>
                        </div>
                    </div>
                    <div class="order-details">
                        <p><strong>Itens:</strong> Arduino Uno R3 (x1), Fio Jumper (x20)</p>
                        <p>Previsão de Devolução: 02/12/2025</p>
                    </div>
                </div>
            </div>

            <div class="orders-container" id="historico-lab" style="display: none;">
                <h3 class="order-section-title">Pedidos Finalizados e Devolvidos</h3>
                
                <div class="order-card devolvido">
                    <div class="order-header">
                        <span class="order-code">#2025-005</span>
                        <span class="order-user">Usuário: Marcos Paulo</span>
                        <div class="status-selector"><span class="status-badge devolvido-badge" style="background:#6c757d;">Pedido Finalizado</span></div>
                    </div>
                    <div class="order-details">
                        <p><strong>Itens:</strong> Resistor 10k (x100)</p>
                        <p>Data da Devolução Final: 18/11/2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-devolucao" class="spa-tab" style="display: none;">
        <div class="content-area">
            <a href="#" onclick="switchLabTab('tab-pedidos-lab', 'Gerenciamento de Pedidos')" style="color: #666; text-decoration: none; font-size: 0.95rem; margin-bottom: 15px; display: inline-block;">
                <i class="bi bi-arrow-left"></i> Voltar aos Pedidos
            </a>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: var(--primary-color); font-family: 'Archivo Black', sans-serif; margin: 0; font-size: 1.8rem;">Conferência de Devolução</h2>
                <span id="dev-pedido-id" style="background-color: var(--primary-color); color: white; padding: 6px 16px; border-radius: 20px; font-weight: bold; font-size: 0.9rem;">Pedido #---</span>
            </div>

            <div style="background-color: #f8f9fa; border-left: 4px solid var(--primary-color); padding: 15px; border-radius: 0 8px 8px 0; margin-bottom: 20px;">
                <p style="margin: 0 0 5px 0; color: #444;"><strong>Usuário:</strong> <span id="dev-pedido-user">---</span></p>
                <p style="margin: 0; color: #444;"><strong>Data do Empréstimo:</strong> <span id="dev-pedido-data">---</span></p>
            </div>

            <div class="table-responsive-wrapper" style="border:none; box-shadow:none;">
                <table class="devolucao-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align: center;">Qtd. Emprestada</th>
                            <th style="text-align: center;">Qtd. Devolvida</th>
                            <th style="text-align: center;">Destino do Item</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Arduino Uno R3</strong><br>
                                <span style="font-size: 0.8rem; color: #888;">Cód: ARD-01</span>
                            </td>
                            <td style="text-align: center; font-weight: bold;">1</td>
                            <td style="text-align: center;">
                                <input type="number" class="qtd-input" value="1" min="0" max="1" oninput="calcularStatusDevolucao(this, 1)">
                            </td>
                            <td style="text-align: center;">
                                <label class="switch"><input type="checkbox" checked><span class="slider"></span></label><br>
                                <span style="font-size: 0.75rem; color: #10ac84; font-weight: 600;">Voltar ao Estoque</span>
                            </td>
                            <td class="status-cell"><span class="status-ok"><i class="bi bi-check-circle-fill"></i> Ok</span></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Fio Jumper Macho-Macho</strong><br>
                                <span style="font-size: 0.8rem; color: #888;">Cód: JMP-20</span>
                            </td>
                            <td style="text-align: center; font-weight: bold;">20</td>
                            <td style="text-align: center;">
                                <input type="number" class="qtd-input" value="18" min="0" max="20" oninput="calcularStatusDevolucao(this, 20)">
                            </td>
                            <td style="text-align: center;">
                                <label class="switch"><input type="checkbox" checked><span class="slider"></span></label><br>
                                <span style="font-size: 0.75rem; color: #10ac84; font-weight: 600;">Voltar ao Estoque</span>
                            </td>
                            <td class="status-cell"><span class="status-falta">Faltam 2</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <button class="btn-secondary-action" style="background-color: white; border: 1px solid #ccc; color: #555;" onclick="switchLabTab('tab-pedidos-lab', 'Gerenciamento de Pedidos')">Cancelar Devolução</button>
                <button class="btn-primary-action" onclick="finalizarDevolucao()"><i class="bi bi-check2-all"></i> Finalizar e Atualizar Estoque</button>
            </div>
        </div>
    </div>


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
                                    $img_item = "LOGOCHECKADAP.jpg"; 
                                }

                                $dados_item_json = htmlspecialchars(json_encode([
                                    'id_item' => $id_item, 'Nome' => $item['Nome'], 'id_cat' => $item['id_cat'], 
                                    'Descricao_Item' => $item['Descricao_Item'], 'Qntd' => $item['Qntd']
                                ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td><div class="item-thumb-container"><img src="<?php echo $img_item; ?>" alt="<?php echo $nome_item; ?>" class="item-thumb" style="object-fit:cover; width:100%; height:100%;"></div></td>
                                <td><strong><?php echo $nome_item; ?></strong></td>
                                <td><span style="background:#f0f2f5; padding:4px 8px; border-radius:6px; font-size:0.85rem;"><?php echo $cat_item; ?></span></td>
                                <td><?php echo $qntd_item; ?> <?php if($qntd_item <= 0): ?><span class="badge out-of-stock-badge">Esgotado</span><?php endif; ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="btn-action edit" data-info="<?php echo $dados_item_json; ?>" onclick="abrirModalEditItem(this)"><i class="bi bi-pencil-square"></i> Editar</button>
                                    <button type="button" class="btn-action delete" onclick="acaoItem('excluir', <?php echo $id_item; ?>)"><i class="bi bi-trash"></i> Excluir</button>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 20px;">Nenhum item cadastrado no estoque.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-novo-item" class="spa-tab" style="display: none;">
        <div class="content-area form-container">
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
                        <?php $cat_selecionada = isset($_POST['add_item_categoria']) ? $_POST['add_item_categoria'] : '';
                        foreach($categorias_array as $cat): ?>
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
                    <button type="button" class="btn-secondary-action" onclick="switchLabTab('tab-estoque', 'Gerenciamento de Estoque')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

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
                                    'id_cat' => $cat['id_cat'], 'Nome' => $cat['Nome'], 'Descricao_cat' => $cat['Descricao_cat']
                                ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['Nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['Descricao_cat']); ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="btn-action edit" data-info="<?php echo $dados_cat_json; ?>" onclick="abrirModalEditCategoria(this)"><i class="bi bi-pencil-square"></i> Editar</button>
                                    <button type="button" class="btn-action delete" onclick="acaoCategoria('excluir', <?php echo $cat['id_cat']; ?>)"><i class="bi bi-trash"></i> Excluir</button>
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
                    <button type="button" class="btn-secondary-action" onclick="switchLabTab('tab-categorias', 'Gerenciamento de Categorias')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="tab-usuarios" class="spa-tab" style="display: none;">
        <div class="content-area">
            <div class="stock-list-header">
                <h3>Lista de Utilizadores Cadastrados</h3>
                <button onclick="switchLabTab('tab-novo-usuario', 'Adicionar Novo Usuário')" class="btn-add-item"><i class="bi bi-person-plus"></i> Adicionar Utilizador</button>
            </div>
            <div class="table-responsive-wrapper">
                <table class="stock-table">
                    <thead><tr><th>Nome Completo</th><th>E-mail</th><th>Nível</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php
                        $res_usuarios = $conn->query("SELECT * FROM Usuarios ORDER BY Nome ASC");
                        while ($u = $res_usuarios->fetch_assoc()):
                            $id_u = $u['id_user']; $nome_u = htmlspecialchars($u['Nome']); $email_u = htmlspecialchars($u['Email']);
                            $tipo_u = ucfirst($u['Tipo_user']); $status_u = $u['status'];
                            $cor_status = ($status_u === 'ativo') ? 'green' : 'red';
                            $texto_status = ($status_u === 'ativo') ? 'Ativo' : 'Bloqueado';
                            $btn_bloqueio_txt = ($status_u === 'ativo') ? 'Bloquear' : 'Desbloquear';
                            $btn_bloqueio_icon = ($status_u === 'ativo') ? 'bi-slash-circle' : 'bi-check-circle';
                            
                            $dados_json = htmlspecialchars(json_encode([
                                'id' => $u['id_user'], 'nome' => $u['Nome'], 'email' => $u['Email'], 
                                'cpf' => $u['CPF'], 'matricula' => $u['Matricula'], 'siape' => $u['SIAPE'], 'tipo' => $u['Tipo_user']
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr data-user-id="<?php echo $id_u; ?>">
                                <td><?php echo $nome_u; ?></td>
                                <td><?php echo $email_u; ?></td>
                                <td><?php echo $tipo_u; ?></td>
                                <td style="color: <?php echo $cor_status; ?>; font-weight: bold;"><?php echo $texto_status; ?></td>
                                <td>
                                    <button type="button" class="btn-view-pedidos" onclick="togglePedidos(this, <?php echo $id_u; ?>)"><i class="bi bi-plus-lg"></i></button>
                                </td>
                            </tr>
                            <tr id="row-detail-<?php echo $id_u; ?>" class="row-detalhes" style="display: none !important; background-color: #f8f9fa;">
                                <td colspan="5" style="padding: 0; border: none;">
                                    <div class="pedidos-detail-container" style="margin: 0; border-radius: 0; box-shadow: inset 0 3px 6px rgba(0,0,0,0.05); border: none; border-left: 4px solid var(--primary-color);">
                                        <h4>Detalhes - <span class="user-name-placeholder"><?php echo $nome_u; ?></span></h4>
                                        <p style="margin-bottom: 10px;">
                                           <strong>CPF:</strong> <?php echo !empty($u['CPF']) ? htmlspecialchars($u['CPF']) : '-'; ?> | 
                                           <strong>Matrícula:</strong> <?php echo !empty($u['Matricula']) ? htmlspecialchars($u['Matricula']) : '-'; ?> | 
                                           <strong>SIAPE:</strong> <?php echo !empty($u['SIAPE']) ? htmlspecialchars($u['SIAPE']) : '-'; ?>
                                        </p>
                                        <div class="user-actions-footer">
                                            <button type="button" class="btn-user-opt btn-edit" data-info="<?php echo $dados_json; ?>" onclick="abrirModalEditUser(this)"><i class="bi bi-pencil-square"></i> Editar</button>
                                            <button type="button" class="btn-user-opt btn-block" onclick="acaoUsuario('bloquear', <?php echo $id_u; ?>)"><i class="bi <?php echo $btn_bloqueio_icon; ?>"></i> <?php echo $btn_bloqueio_txt; ?></button>
                                            <button type="button" class="btn-user-opt btn-delete" onclick="acaoUsuario('excluir', <?php echo $id_u; ?>)"><i class="bi bi-trash-fill"></i> Excluir</button>
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
                        <label>Tipo de Utilizador</label>
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
                    <button type="submit" class="btn-primary-action"><i class="bi bi-person-plus"></i> Cadastrar Utilizador</button>
                    <button type="button" class="btn-secondary-action" onclick="switchLabTab('tab-usuarios', 'Gerenciamento de Usuários')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</main>

<form id="form-acao-generica" method="POST" action="FECHECKADM.php" style="display:none;">
    <input type="hidden" name="" id="input-acao">
    <input type="hidden" name="" id="input-id">
</form>

<div id="modal-recusa" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-heading-red"><i class="bi bi-exclamation-triangle"></i> Recusar Pedido</h3>
        <p id="modal-msg-titulo" style="margin-bottom:15px; color:#555;">Recusando pedido.</p>
        <div class="modal-input-group">
            <label for="justificativa" style="text-align:left; display:block; margin-bottom:5px; font-weight:bold;">Justificativa da recusa (Opcional):</label>
            <textarea id="justificativa" rows="4" style="width:100%; border-radius:8px; border:1px solid #ccc; padding:10px; resize:vertical;"></textarea>
        </div>
        <div class="modal-footer" style="margin-top:20px;">
            <button class="btn-cancel" onclick="document.getElementById('modal-recusa').style.display='none'">Cancelar</button>
            <button class="btn-modal-confirm-delete" onclick="confirmarRecusa()">Confirmar Recusa</button>
        </div>
    </div>
</div>

<div id="modalEditCategoria" class="modal-overlay">
    <div class="modal-content form-container" style="max-width: 500px;">
        <h3 class="form-section-title"><i class="bi bi-tags"></i> Editar Categoria</h3>
        <form method="POST" action="FECHECKADM.php" novalidate style="margin-top: 15px;">
            <input type="hidden" name="acao_categoria" value="editar">
            <input type="hidden" name="id_alvo_cat" id="edit_id_alvo_cat">
            
            <?php if(isset($erros_lab['geral_edit_cat'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: bold; font-size: 0.9rem; text-align: center; border: 1px solid #f5c6cb;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erros_lab['geral_edit_cat']; ?>
                </div>
            <?php endif; ?>

            <div class="input-group full-width">
                <label>Nome da Categoria</label>
                <input type="text" name="edit_cat_nome" id="edit_cat_nome" required class="<?php echo isset($erros_lab['edit_cat_nome']) ? 'input-error' : ''; ?>">
                <?php if(isset($erros_lab['edit_cat_nome'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_cat_nome']; ?></div><?php endif; ?>
            </div>
            <div class="input-group full-width" style="margin-top:10px;">
                <label>Descrição</label>
                <textarea name="edit_cat_desc" id="edit_cat_desc" rows="3" style="width:100%; border-radius:8px; padding:10px; <?php echo isset($erros_lab['edit_cat_desc']) ? 'border: 2px solid #dc3545 !important; background-color: #fff8f8 !important;' : 'border:1px solid #ccc;'; ?>" required></textarea>
                <?php if(isset($erros_lab['edit_cat_desc'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_cat_desc']; ?></div><?php endif; ?>
            </div>
            
            <div class="form-actions" style="margin-
