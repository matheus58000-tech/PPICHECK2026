<?php
$mensagem_lab = "";
$erros_lab = [];

// =========================================================================
// LÊ AS MENSAGENS E ABAS DA SESSÃO (Pós-Redirecionamento Anti-F5)
// =========================================================================
if (isset($_SESSION['msg_sucesso_lab'])) {
    $m = addslashes($_SESSION['msg_sucesso_lab']);
    $mensagem_lab .= "<script>window.addEventListener('DOMContentLoaded', () => { setTimeout(() => showToast('$m', 'success'), 300); });</script>";
    unset($_SESSION['msg_sucesso_lab']);
}
if (isset($_SESSION['msg_erro_lab'])) {
    $m = addslashes($_SESSION['msg_erro_lab']);
    $mensagem_lab .= "<script>window.addEventListener('DOMContentLoaded', () => { setTimeout(() => showToast('$m', 'error'), 300); });</script>";
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

// Função auxiliar para redirecionar rápido e matar o POST (Evita F5 duplicar)
if (!function_exists('redirectLab')) {
    function redirectLab($aba, $sub_aba) {
        $_SESSION['aba_ativa'] = $aba;
        $_SESSION['sub_aba_ativa'] = $sub_aba;
        header("Location: FECHECKADM.php");
        exit(); 
    }
}

// =========================================================================
// 1. PROCESSAMENTO DE UTILIZADORES
// =========================================================================
$abrir_modal_edit_user = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_usuario'])) {
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-usuarios";
    $acao = $_POST['acao_usuario'];
    $id_alvo = isset($_POST['id_alvo']) ? intval($_POST['id_alvo']) : 0;

    if ($id_alvo === $id_usuario && ($acao === 'excluir' || $acao === 'bloquear')) {
        $_SESSION['msg_erro_lab'] = "Você não pode bloquear ou excluir a própria conta!";
        redirectLab('view-lab', 'tab-usuarios');
    } else {
        try {
            if ($acao === 'bloquear') {
                $conn->query("UPDATE Usuarios SET status = IF(status='ativo', 'bloqueado', 'ativo') WHERE id_user = $id_alvo");
                $_SESSION['msg_sucesso_lab'] = "Status do utilizador alterado com sucesso!";
                redirectLab('view-lab', 'tab-usuarios');
                
            } elseif ($acao === 'excluir') {
                $conn->query("DELETE FROM Usuarios WHERE id_user = $id_alvo");
                $_SESSION['msg_sucesso_lab'] = "Utilizador excluído permanentemente!";
                redirectLab('view-lab', 'tab-usuarios');
                
            } elseif ($acao === 'editar') {
                $nome = trim($_POST['edit_nome']);
                $email = trim($_POST['edit_email']);
                $cpf = trim($_POST['edit_cpf']);
                $matricula = trim($_POST['edit_matricula']);
                $siape = trim($_POST['edit_siape']);
                $tipo = trim($_POST['edit_tipo']);
                $nova_senha = $_POST['edit_senha'];

                // Validações
                if (empty($nome) || strlen($nome) < 3) $erros_lab['edit_nome'] = "Mín. 3 letras.";
                $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf); 
                if (empty($cpf_limpo) || strlen($cpf_limpo) !== 11) $erros_lab['edit_cpf'] = "Exatos 11 dígitos.";
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros_lab['edit_email'] = "E-mail inválido.";
                
                if (count($erros_lab) > 0) {
                    $erros_lab['geral_edit_user'] = "Erro na edição. Verifique os campos preenchidos.";
                    $sub_aba_ativa = "tab-usuarios";
                    $abrir_modal_edit_user = true; // Força o modal a reabrir
                } else {
                    if (!empty($nova_senha)) {
                        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                        $stmt_edit = $conn->prepare("UPDATE Usuarios SET Nome=?, Email=?, CPF=?, Matricula=?, SIAPE=?, Tipo_user=?, Senha=? WHERE id_user=?");
                        $stmt_edit->bind_param("sssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $hash, $id_alvo);
                    } else {
                        $stmt_edit = $conn->prepare("UPDATE Usuarios SET Nome=?, Email=?, CPF=?, Matricula=?, SIAPE=?, Tipo_user=? WHERE id_user=?");
                        $stmt_edit->bind_param("ssssssi", $nome, $email, $cpf, $matricula, $siape, $tipo, $id_alvo);
                    }
                    $stmt_edit->execute();
                    $_SESSION['msg_sucesso_lab'] = "Dados do utilizador atualizados com sucesso!";
                    redirectLab('view-lab', 'tab-usuarios');
                }
                
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
                    
                    $_SESSION['msg_sucesso_lab'] = "Utilizador cadastrado com sucesso!";
                    redirectLab('view-lab', 'tab-usuarios'); 
                }
            }
        } catch (\Exception $e) {
            if ($acao === 'adicionar') { 
                $erros_lab['geral'] = "ERRO: O CPF, Matrícula, SIAPE ou E-mail digitado já estão em uso.";
                $sub_aba_ativa = "tab-novo-usuario"; 
            } elseif ($acao === 'editar') {
                $erros_lab['geral_edit_user'] = "ERRO: Este E-mail ou CPF já pertence a outro usuário.";
                $sub_aba_ativa = "tab-usuarios";
                $abrir_modal_edit_user = true;
            } else {
                $_SESSION['msg_erro_lab'] = "ERRO Crítico de banco de dados.";
                redirectLab('view-lab', 'tab-usuarios');
            }
        }
    }
}

// =========================================================================
// 2. PROCESSAMENTO DO ESTOQUE (ITENS)
// =========================================================================
$abrir_modal_edit_item = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_item'])) {
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-estoque";
    $acao_item = $_POST['acao_item'];

    try {
        if ($acao_item === 'adicionar') {
            $nome = trim($_POST['add_item_nome']);
            $categoria = isset($_POST['add_item_categoria']) ? intval($_POST['add_item_categoria']) : 0;
            $descricao = trim($_POST['add_item_descricao']);
            $qntd = isset($_POST['add_item_qntd']) ? intval($_POST['add_item_qntd']) : -1;
            
            if (empty($nome) || strlen($nome) < 3) $erros_lab['item_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if ($categoria <= 0) $erros_lab['item_categoria'] = "Selecione uma categoria válida.";
            if ($qntd < 0) $erros_lab['item_qntd'] = "A quantidade não pode ser negativa.";
            if (empty($descricao) || strlen($descricao) < 10) $erros_lab['item_descricao'] = "Forneça uma descrição técnica (mín. 10 caracteres).";

            if (count($erros_lab) > 0) {
                $erros_lab['geral'] = "Por favor, verifique os campos destacados e tente novamente.";
                $sub_aba_ativa = "tab-novo-item"; 
            } else {
                $imagem = ''; // Por padrão, a string fica vazia (o sistema carregará a imagem padrão no frontend)
                
                // Se o usuário mandou uma foto, a gente salva
                if (isset($_FILES['add_item_foto']) && $_FILES['add_item_foto']['error'] == 0) {
                    $ext = pathinfo($_FILES['add_item_foto']['name'], PATHINFO_EXTENSION);
                    $imagem = uniqid() . "." . $ext;
                    move_uploaded_file($_FILES['add_item_foto']['tmp_name'], "uploads/" . $imagem);
                }

                $stmt_item = $conn->prepare("INSERT INTO Item (Nome, Descricao_Item, Qntd, id_cat, Imagem) VALUES (?, ?, ?, ?, ?)");
                $stmt_item->bind_param("ssiis", $nome, $descricao, $qntd, $categoria, $imagem);
                $stmt_item->execute();
                
                $_SESSION['msg_sucesso_lab'] = "Item adicionado ao estoque!";
                redirectLab('view-lab', 'tab-estoque');
            }
            
        } elseif ($acao_item === 'excluir') {
            $id_alvo_item = intval($_POST['id_alvo_item']);
            $conn->query("DELETE FROM Item WHERE id_item = $id_alvo_item");
            $_SESSION['msg_sucesso_lab'] = "Item excluído com sucesso!";
            redirectLab('view-lab', 'tab-estoque');
            
        } elseif ($acao_item === 'editar') {
            $id_alvo_item = intval($_POST['id_alvo_item']);
            $nome = trim($_POST['edit_item_nome']);
            $categoria = intval($_POST['edit_item_categoria']);
            $descricao = trim($_POST['edit_item_descricao']);
            $qntd = intval($_POST['edit_item_qntd']);

            if (empty($nome) || strlen($nome) < 3) $erros_lab['edit_item_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if ($categoria <= 0) $erros_lab['edit_item_categoria'] = "Selecione uma categoria válida.";
            if ($qntd < 0) $erros_lab['edit_item_qntd'] = "A quantidade não pode ser negativa.";
            if (empty($descricao) || strlen($descricao) < 10) $erros_lab['edit_item_descricao'] = "Forneça uma descrição técnica (mín. 10 caracteres).";

            if (count($erros_lab) > 0) {
                $erros_lab['geral_edit_item'] = "Erro na edição. Verifique os campos.";
                $sub_aba_ativa = "tab-estoque"; 
                $abrir_modal_edit_item = true; 
            } else {
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
                $_SESSION['msg_sucesso_lab'] = "Item atualizado com sucesso!";
                redirectLab('view-lab', 'tab-estoque');
            }
        }
    } catch (\Exception $e) {
        if ($acao_item === 'adicionar') {
             $erros_lab['geral'] = "ERRO inesperado ao cadastrar o item.";
             $sub_aba_ativa = "tab-novo-item"; 
        } else {
             $_SESSION['msg_erro_lab'] = "ERRO: Este item faz parte do histórico de um pedido e não pode ser apagado.";
             redirectLab('view-lab', 'tab-estoque');
        }
    }
}

// =========================================================================
// 3. PROCESSAMENTO DE CATEGORIAS
// =========================================================================
$abrir_modal_edit_cat = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_categoria'])) {
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-categorias";
    $acao_cat = $_POST['acao_categoria'];

    try {
        if ($acao_cat === 'adicionar') {
            $nome_cat = trim($_POST['cat_nome']);
            $desc_cat = trim($_POST['cat_desc']);

            if (empty($nome_cat) || strlen($nome_cat) < 3) $erros_lab['cat_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if (empty($desc_cat) || strlen($desc_cat) < 10) $erros_lab['cat_desc'] = "A descrição deve ter no mínimo 10 caracteres.";

            if (count($erros_lab) > 0) {
                $erros_lab['geral'] = "Por favor, preencha as informações corretamente.";
                $sub_aba_ativa = "tab-nova-categoria";
            } else {
                $stmt_cat = $conn->prepare("INSERT INTO Categoria (Nome, Descricao_cat) VALUES (?, ?)");
                $stmt_cat->bind_param("ss", $nome_cat, $desc_cat);
                $stmt_cat->execute();
                
                $_SESSION['msg_sucesso_lab'] = "Categoria criada com sucesso!";
                redirectLab('view-lab', 'tab-categorias');
            }
            
        } elseif ($acao_cat === 'editar') {
            $id_cat = intval($_POST['id_alvo_cat']);
            $nome_cat = trim($_POST['edit_cat_nome']);
            $desc_cat = trim($_POST['edit_cat_desc']);

            if (empty($nome_cat) || strlen($nome_cat) < 3) $erros_lab['edit_cat_nome'] = "O nome deve ter no mínimo 3 caracteres.";
            if (empty($desc_cat) || strlen($desc_cat) < 10) $erros_lab['edit_cat_desc'] = "A descrição deve ter no mínimo 10 caracteres.";

            if (count($erros_lab) > 0) {
                $erros_lab['geral_edit_cat'] = "Erro na edição. Verifique os campos.";
                $sub_aba_ativa = "tab-categorias";
                $abrir_modal_edit_cat = true;
            } else {
                $stmt_edit_cat = $conn->prepare("UPDATE Categoria SET Nome=?, Descricao_cat=? WHERE id_cat=?");
                $stmt_edit_cat->bind_param("ssi", $nome_cat, $desc_cat, $id_cat);
                $stmt_edit_cat->execute();
                
                $_SESSION['msg_sucesso_lab'] = "Categoria atualizada!";
                redirectLab('view-lab', 'tab-categorias');
            }

        } elseif ($acao_cat === 'excluir') {
            $id_cat = intval($_POST['id_alvo_cat']);
            $conn->query("DELETE FROM Categoria WHERE id_cat = $id_cat");
            
            $_SESSION['msg_sucesso_lab'] = "Categoria excluída com sucesso!";
            redirectLab('view-lab', 'tab-categorias');
        }
    } catch (\Exception $e) {
        if ($acao_cat === 'adicionar') {
            $erros_lab['geral'] = "ERRO inesperado ao criar a categoria.";
            $sub_aba_ativa = "tab-nova-categoria";
        } else {
            $_SESSION['msg_erro_lab'] = "ERRO: Não é possível excluir uma categoria que possui itens associados.";
            redirectLab('view-lab', 'tab-categorias');
        }
    }
}

// IMPRIME AS MENSAGENS GUARDADAS NA VARIÁVEL
echo $mensagem_lab;

// Busca categorias para os Dropdowns
$res_categorias = $conn->query("SELECT * FROM Categoria ORDER BY Nome ASC");
$categorias_array = [];
while ($cat = $res_categorias->fetch_assoc()) {
    $categorias_array[] = $cat;
}
?>

<style>
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

    .devolucao-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .devolucao-table th, .devolucao-table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
    .devolucao-table th { background-color: #f8f9fa; color: #555; font-size: 0.9rem; font-weight: 700;}
    .qtd-input { width: 60px; padding: 8px; border: 1px solid #ccc; border-radius: 6px; text-align: center; font-weight: bold;}
    .status-ok { color: #10ac84; font-weight: bold; }
    .status-falta { color: #e67e22; font-weight: bold; }

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
                <button class="btn-filter" onclick="switchLabFilter('andamento', this)"><i class="bi bi-box-seam"></i> Em Andamento</button>
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

            <div class="orders-container" id="andamento" style="display: none;">
                <h3 class="order-section-title">Pedidos Em Andamento (Em Separação / Retirados)</h3>
                
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

            <div class="filter-estoque-container" style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center; background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                <div class="search-box" style="flex: 2; position: relative;">
                    <i class="bi bi-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #888;"></i>
                    <input type="text" id="estoque-search" placeholder="Buscar por nome do item..." style="width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #ccc; border-radius: 6px; outline: none;">
                </div>
                <div class="filter-box" style="flex: 1;">
                    <select id="estoque-category-filter" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; outline: none;">
                        <option value="todas">Todas as Categorias</option>
                        <?php foreach ($categorias_array as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['Nome']); ?>"><?php echo htmlspecialchars($cat['Nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                    <label>Foto do Item (Opcional - Se não enviar, usará a imagem do sistema)</label>
                    <input type="file" name="add_item_foto" accept="image/*" style="padding: 10px;" class="<?php echo isset($erros_lab['item_foto']) ? 'input-error' : ''; ?>">
                    <?php if(isset($erros_lab['item_foto'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['item_foto']; ?></div><?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Salvar no Estoque</button>
                    <button type="button" class="btn-secondary-action" onclick="window.location.href='FECHECKADM.php'">Cancelar</button>
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
                                    'id_cat' => $cat['id_cat'], 
                                    'Nome' => $cat['Nome'], 
                                    'Descricao_cat' => $cat['Descricao_cat']
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
                    <button type="button" class="btn-secondary-action" onclick="window.location.href='FECHECKADM.php'">Cancelar</button>
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
                                    <div class="pedidos-detail-container" style="margin: 0; padding: 25px; border-radius: 0; box-shadow: inset 0 3px 6px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                                        
                                        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                                            
                                            <div style="flex: 1; min-width: 300px;">
                                                <h4 style="margin-top:0; color: var(--primary-color); font-size: 1.2rem;"><i class="bi bi-person-vcard"></i> Ficha do Utilizador</h4>
                                                
                                                <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 20px;">
                                                    <p style="margin: 0 0 8px 0; font-size: 0.95rem;"><strong>Nome:</strong> <?php echo $nome_u; ?></p>
                                                    <p style="margin: 0 0 8px 0; font-size: 0.95rem;"><strong>CPF:</strong> <?php echo !empty($u['CPF']) ? htmlspecialchars($u['CPF']) : 'Não informado'; ?></p>
                                                    <p style="margin: 0 0 8px 0; font-size: 0.95rem;"><strong>Matrícula:</strong> <?php echo !empty($u['Matricula']) ? htmlspecialchars($u['Matricula']) : 'N/A'; ?></p>
                                                    <p style="margin: 0; font-size: 0.95rem;"><strong>SIAPE:</strong> <?php echo !empty($u['SIAPE']) ? htmlspecialchars($u['SIAPE']) : 'N/A'; ?></p>
                                                </div>

                                                <h4 style="color: #444; font-size: 1rem; margin-bottom: 10px;">Ações Administrativas</h4>
                                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                                    <button type="button" class="btn-primary-action" style="padding: 8px 15px; font-size: 0.9rem;" data-info="<?php echo $dados_json; ?>" onclick="abrirModalEditUser(this)">
                                                        <i class="bi bi-pencil-square"></i> Editar
                                                    </button>
                                                    
                                                    <button type="button" class="btn-secondary-action" style="background: <?php echo ($status_u === 'ativo') ? '#e67e22' : '#10ac84'; ?>; color: white; border: none; padding: 8px 15px; font-size: 0.9rem;" onclick="acaoUsuario('bloquear', <?php echo $id_u; ?>)">
                                                        <i class="bi <?php echo $btn_bloqueio_icon; ?>"></i> <?php echo $btn_bloqueio_txt; ?>
                                                    </button>
                                                    
                                                    <button type="button" class="btn-secondary-action" style="background: #dc3545; color: white; border: none; padding: 8px 15px; font-size: 0.9rem;" onclick="acaoUsuario('excluir', <?php echo $id_u; ?>)">
                                                        <i class="bi bi-trash"></i> Excluir
                                                    </button>
                                                </div>
                                            </div>

                                            <div style="flex: 1.5; min-width: 350px; background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                                                <h4 style="margin-top:0; margin-bottom: 20px; color: #444; font-size: 1.1rem;"><i class="bi bi-clock-history"></i> Histórico Recente de Pedidos</h4>
                                                
                                                <div style="border-left: 3px solid #10ac84; padding-left: 15px; margin-bottom: 20px;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                        <strong style="color: var(--primary-color); font-size: 1.1rem;">Pedido #2025-014</strong>
                                                        <span style="background: #e6f7f2; color: #10ac84; border: 1px solid #10ac84; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">Devolvido Ok</span>
                                                    </div>
                                                    <p style="margin: 0 0 8px 0; font-size: 0.9rem; color: #555;"><strong>Itens:</strong> Arduino Uno R3 (x1), Multímetro (x1)</p>
                                                    <div style="display: flex; gap: 20px; font-size: 0.85rem; color: #777;">
                                                        <span><i class="bi bi-calendar-arrow-up"></i> Retirado: 12/11/2025</span>
                                                        <span><i class="bi bi-calendar-check"></i> Devolvido: 19/11/2025</span>
                                                    </div>
                                                </div>

                                                <div style="border-left: 3px solid #dc3545; padding-left: 15px; margin-bottom: 10px;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                        <strong style="color: var(--primary-color); font-size: 1.1rem;">Pedido #2025-018</strong>
                                                        <span style="background: #fff8f8; color: #dc3545; border: 1px solid #dc3545; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">Atrasado (Em Uso)</span>
                                                    </div>
                                                    <p style="margin: 0 0 8px 0; font-size: 0.9rem; color: #555;"><strong>Itens:</strong> Osciloscópio Digital (x1), Fio Jumper (x30)</p>
                                                    <div style="display: flex; gap: 20px; font-size: 0.85rem; color: #777;">
                                                        <span><i class="bi bi-calendar-arrow-up"></i> Retirado: 25/11/2025</span>
                                                        <span style="color: #dc3545; font-weight: bold;"><i class="bi bi-exclamation-circle"></i> Prazo Venceu: 02/12/2025</span>
                                                    </div>
                                                </div>

                                            </div>
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
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%;">
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
            
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%;">
                <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Atualizar Categoria</button>
                <button type="button" class="btn-secondary-action" onclick="document.getElementById('modalEditCategoria').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditItem" class="modal-overlay">
    <div class="modal-content form-container" style="max-width: 600px;">
        <h3 class="form-section-title"><i class="bi bi-pencil-square"></i> Editar Produto</h3>
        <form method="POST" action="FECHECKADM.php" enctype="multipart/form-data" novalidate style="margin-top: 15px;">
            <input type="hidden" name="acao_item" value="editar">
            <input type="hidden" name="id_alvo_item" id="edit_id_alvo_item">

            <?php if(isset($erros_lab['geral_edit_item'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: bold; font-size: 0.9rem; text-align: center; border: 1px solid #f5c6cb;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erros_lab['geral_edit_item']; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-row" style="display:flex; gap:10px;">
                <div class="input-group" style="flex:2;">
                    <label>Nome Do Item</label>
                    <input type="text" name="edit_item_nome" id="edit_item_nome" required style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_item_nome']) ? 'input-error' : ''; ?>">
                    <?php if(isset($erros_lab['edit_item_nome'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_item_nome']; ?></div><?php endif; ?>
                </div>
                <div class="input-group" style="flex:1;">
                    <label>Quantidade</label>
                    <input type="number" name="edit_item_qntd" id="edit_item_qntd" required min="0" style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_item_qntd']) ? 'input-error' : ''; ?>">
                    <?php if(isset($erros_lab['edit_item_qntd'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_item_qntd']; ?></div><?php endif; ?>
                </div>
            </div>
            
            <div class="input-group" style="margin-top:10px;">
                <label>Categoria</label>
                <select name="edit_item_categoria" id="edit_item_categoria" required style="width:100%; height:45px; border-radius:5px;" class="<?php echo isset($erros_lab['edit_item_categoria']) ? 'input-error' : ''; ?>">
                    <?php foreach($categorias_array as $cat): ?>
                        <option value="<?php echo $cat['id_cat']; ?>"><?php echo htmlspecialchars($cat['Nome']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if(isset($erros_lab['edit_item_categoria'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_item_categoria']; ?></div><?php endif; ?>
            </div>
            
            <div class="input-group" style="margin-top:10px;">
                <label>Descrição Detalhada</label>
                <textarea name="edit_item_descricao" id="edit_item_descricao" rows="4" style="width:100%; border-radius:8px; padding:10px; <?php echo isset($erros_lab['edit_item_descricao']) ? 'border: 2px solid #dc3545 !important; background-color: #fff8f8 !important;' : 'border:1px solid #ccc;'; ?>" required></textarea>
                <?php if(isset($erros_lab['edit_item_descricao'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_item_descricao']; ?></div><?php endif; ?>
            </div>

            <div class="input-group" style="margin-top:10px;">
                <label>Substituir Foto (deixe vazio para manter a atual)</label>
                <input type="file" name="edit_item_foto" accept="image/*" style="width:100%; padding:10px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%;">
                <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Atualizar Item</button>
                <button type="button" class="btn-secondary-action" onclick="document.getElementById('modalEditItem').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditUser" class="modal-overlay">
    <div class="modal-content form-container" style="max-width: 600px;">
        <h3 class="form-section-title"><i class="bi bi-pencil-square"></i> Editar Usuário</h3>
        <form method="POST" action="FECHECKADM.php" novalidate style="margin-top: 15px;">
            <input type="hidden" name="acao_usuario" value="editar">
            <input type="hidden" name="id_alvo" id="edit_id_alvo">

            <?php if(isset($erros_lab['geral_edit_user'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: bold; font-size: 0.9rem; text-align: center; border: 1px solid #f5c6cb;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erros_lab['geral_edit_user']; ?>
                </div>
            <?php endif; ?>
            
            <div class="input-row" style="display:flex; gap:10px;">
                <div class="input-group" style="flex:1;">
                    <label>Tipo de Utilizador</label>
                    <select name="edit_tipo" id="edit_tipo" required style="width:100%; height:45px; border-radius:5px;" class="<?php echo isset($erros_lab['edit_tipo']) ? 'input-error' : ''; ?>">
                        <option value="padrao">Padrão (Aluno)</option>
                        <option value="resp">Responsável LEPEP</option>
                        <option value="admin">Admin LEPEP</option>
                    </select>
                </div>
                <div class="input-group" style="flex:1;">
                    <label>CPF</label>
                    <input type="text" name="edit_cpf" id="edit_cpf" style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_cpf']) ? 'input-error' : ''; ?>">
                    <?php if(isset($erros_lab['edit_cpf'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_cpf']; ?></div><?php endif; ?>
                </div>
            </div>
            
            <div class="input-row" style="display:flex; gap:10px; margin-top:10px;">
                <div class="input-group" style="flex:1;">
                    <label>Matrícula</label>
                    <input type="text" name="edit_matricula" id="edit_matricula" style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_matricula']) ? 'input-error' : ''; ?>">
                </div>
                <div class="input-group" style="flex:1;">
                    <label>SIAPE</label>
                    <input type="text" name="edit_siape" id="edit_siape" style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_siape']) ? 'input-error' : ''; ?>">
                </div>
            </div>
            
            <div class="input-group" style="margin-top:10px;">
                <label>Nome Completo</label>
                <input type="text" name="edit_nome" id="edit_nome" required style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_nome']) ? 'input-error' : ''; ?>">
                <?php if(isset($erros_lab['edit_nome'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_nome']; ?></div><?php endif; ?>
            </div>

            <div class="input-group" style="margin-top:10px;">
                <label>E-mail</label>
                <input type="email" name="edit_email" id="edit_email" required style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_email']) ? 'input-error' : ''; ?>">
                <?php if(isset($erros_lab['edit_email'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_email']; ?></div><?php endif; ?>
            </div>

            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

            <div class="input-group">
                <label>Forçar Nova Senha (deixe vazio para não alterar)</label>
                <input type="password" name="edit_senha" placeholder="Digite apenas se quiser redefinir" style="width:100%; height:45px;" class="<?php echo isset($erros_lab['edit_senha']) ? 'input-error' : ''; ?>">
                <?php if(isset($erros_lab['edit_senha'])): ?><div class="error-text"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $erros_lab['edit_senha']; ?></div><?php endif; ?>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%;">
                <button type="submit" class="btn-primary-action"><i class="bi bi-save"></i> Salvar Alterações</button>
                <button type="button" class="btn-secondary-action" onclick="document.getElementById('modalEditUser').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica de Filtro do Estoque
        const searchInput = document.getElementById('estoque-search');
        const categoryFilter = document.getElementById('estoque-category-filter');
        
        function filterEstoque() {
            if (!searchInput || !categoryFilter) return;
            
            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedCategory = categoryFilter.value.toLowerCase();
            const rows = document.querySelectorAll('#tab-estoque .stock-table tbody tr');
            
            let visibleCount = 0;

            rows.forEach(row => {
                if (row.cells.length === 1) return; // Ignora linha vazia

                const itemName = row.querySelector('td:nth-child(2) strong').textContent.toLowerCase();
                const itemCategory = row.querySelector('td:nth-child(3) span').textContent.toLowerCase();
                
                const matchesSearch = itemName.includes(searchTerm);
                const matchesCategory = selectedCategory === 'todas' || itemCategory === selectedCategory;
                
                if (matchesSearch && matchesCategory) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            let emptyMsgRow = document.getElementById('empty-estoque-msg');
            const tbody = document.querySelector('#tab-estoque .stock-table tbody');
            
            if (visibleCount === 0 && rows.length > 0 && rows[0].cells.length > 1) {
                if (!emptyMsgRow) {
                    emptyMsgRow = document.createElement('tr');
                    emptyMsgRow.id = 'empty-estoque-msg';
                    emptyMsgRow.innerHTML = '<td colspan="5" style="text-align:center; padding: 30px; color:#666;"><i class="bi bi-search" style="font-size:2rem; display:block; margin-bottom:10px;"></i>Nenhum item encontrado com estes filtros.</td>';
                    tbody.appendChild(emptyMsgRow);
                }
                emptyMsgRow.style.display = '';
            } else if (emptyMsgRow) {
                emptyMsgRow.style.display = 'none';
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterEstoque);
        if (categoryFilter) categoryFilter.addEventListener('change', filterEstoque);
    });

    // 1. Lógica Filtro de Pedidos - Alterado id para 'andamento'
    function switchLabFilter(targetId, btn) {
        document.querySelectorAll('#tab-pedidos-lab .btn-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('#tab-pedidos-lab .orders-container').forEach(c => c.style.display = 'none');
        document.getElementById(targetId).style.display = 'block';
    }

    // 2. Ações Básicas de Pedido
    function aprovarPedido(id) { 
        if(confirm('Aprovar pedido #' + id + '?')) showToast('Pedido Aprovado!', 'success'); 
    }
    
    let pedidoAtualId = null;
    function abrirModalRecusa(id) {
        pedidoAtualId = id;
        document.getElementById('modal-msg-titulo').innerText = `Você está recusando o pedido #${id}`;
        document.getElementById('justificativa').value = '';
        document.getElementById('modal-recusa').style.display = 'flex'; 
    }
    function fecharModalRecusa() { document.getElementById('modal-recusa').style.display = 'none'; pedidoAtualId = null;}
    function confirmarRecusa() { 
        if(pedidoAtualId) {
            showToast(`Pedido #${pedidoAtualId} RECUSADO.`, 'error'); 
            fecharModalRecusa(); 
        }
    }

    // 3. Funções da Página de Devolução
    function abrirDevolucao(idPedido, usuario, data) {
        document.getElementById('dev-pedido-id').innerText = `Pedido #${idPedido}`;
        document.getElementById('dev-pedido-user').innerText = usuario;
        document.getElementById('dev-pedido-data').innerText = data;
        switchLabTab('tab-devolucao', 'Conferência de Devolução');
    }

    function calcularStatusDevolucao(inputElement, maxQtd) {
        let devolvida = parseInt(inputElement.value) || 0;
        if (devolvida > maxQtd) { devolvida = maxQtd; inputElement.value = maxQtd; }
        if (devolvida < 0) { devolvida = 0; inputElement.value = 0; }
        
        const cell = inputElement.closest('tr').querySelector('.status-cell');
        const diff = maxQtd - devolvida;
        
        if (diff === 0) {
            cell.innerHTML = '<span class="status-ok"><i class="bi bi-check-circle-fill"></i> Ok</span>';
        } else {
            cell.innerHTML = `<span class="status-falta">Faltam ${diff}</span>`;
        }
    }

    function finalizarDevolucao() {
        showToast('Devolução finalizada e estoque atualizado com sucesso!', 'success');
        switchLabTab('tab-pedidos-lab', 'Gerenciamento de Pedidos');
    }

    // 4. Gaveta de Utilizadores
    function togglePedidos(btn, userId) {
        const rowDetail = document.getElementById('row-detail-' + userId);
        const icon = btn.querySelector('i');

        if (!rowDetail) {
            console.error('Row não encontrada: row-detail-' + userId);
            return;
        }

        const estaAberto = rowDetail.classList.contains('row-aberta');

        // Fecha todos
        document.querySelectorAll('.row-detalhes').forEach(el => {
            el.classList.remove('row-aberta');
            el.style.setProperty('display', 'none', 'important');
        });
        document.querySelectorAll('.btn-view-pedidos i').forEach(ic => {
            ic.classList.remove('bi-dash-lg');
            ic.classList.add('bi-plus-lg');
        });

        // Se estava fechado, abre o clicado
        if (!estaAberto) {
            rowDetail.classList.add('row-aberta');
            rowDetail.style.setProperty('display', 'table-row', 'important');
            if (icon) {
                icon.classList.remove('bi-plus-lg');
                icon.classList.add('bi-dash-lg');
            }
        }
    }

    // 5. Acionar Comandos com Criação de Formulário Dinâmico
    function enviarAcaoDinamicamente(nomeAcao, valorAcao, nomeId, valorId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'FECHECKADM.php';
        
        const inputAcao = document.createElement('input');
        inputAcao.type = 'hidden';
        inputAcao.name = nomeAcao;
        inputAcao.value = valorAcao;
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = nomeId;
        inputId.value = valorId;
        
        form.appendChild(inputAcao);
        form.appendChild(inputId);
        document.body.appendChild(form);
        form.submit();
    }

    function acaoUsuario(acao, id) {
        if (acao === 'excluir' && !confirm("Tem certeza que deseja apagar este utilizador DEFINITIVAMENTE?")) return;
        if (acao === 'bloquear' && !confirm("Tem certeza que deseja mudar o status de bloqueio deste utilizador?")) return;
        enviarAcaoDinamicamente('acao_usuario', acao, 'id_alvo', id);
    }

    function acaoItem(acao, id) {
        if (acao === 'excluir' && !confirm("Tem certeza que deseja apagar este ITEM do estoque DEFINITIVAMENTE?")) return;
        enviarAcaoDinamicamente('acao_item', acao, 'id_alvo_item', id);
    }

    function acaoCategoria(acao, id) {
        if (acao === 'excluir' && !confirm("ATENÇÃO: Deseja realmente apagar esta CATEGORIA?\nItens vinculados a ela poderão impedir a exclusão.")) return;
        enviarAcaoDinamicamente('acao_categoria', acao, 'id_alvo_cat', id);
    }

    // 6. Preenchimento Seguro dos Modais usando data-info
    function abrirModalEditUser(btn) {
        try {
            const rawData = btn.getAttribute('data-info');
            const dados = JSON.parse(rawData);
            document.getElementById('edit_id_alvo').value = dados.id;
            document.getElementById('edit_nome').value = dados.nome;
            document.getElementById('edit_email').value = dados.email;
            document.getElementById('edit_cpf').value = dados.cpf || '';
            document.getElementById('edit_matricula').value = dados.matricula || '';
            document.getElementById('edit_siape').value = dados.siape || '';
            document.getElementById('edit_tipo').value = dados.tipo;
            document.getElementById('modalEditUser').style.display = 'flex';
        } catch(e) {
            console.error("Erro ao ler JSON de utilizador:", e);
            alert("Não foi possível carregar os dados para edição.");
        }
    }

    function abrirModalEditItem(btn) {
        try {
            const rawData = btn.getAttribute('data-info');
            const dados = JSON.parse(rawData);
            document.getElementById('edit_id_alvo_item').value = dados.id_item;
            document.getElementById('edit_item_nome').value = dados.Nome;
            document.getElementById('edit_item_categoria').value = dados.id_cat;
            document.getElementById('edit_item_descricao').value = dados.Descricao_Item;
            document.getElementById('edit_item_qntd').value = dados.Qntd;
            document.getElementById('modalEditItem').style.display = 'flex';
        } catch(e) {
            console.error("Erro ao ler JSON de item:", e);
        }
    }

    function abrirModalEditCategoria(btn) {
        try {
            const rawData = btn.getAttribute('data-info');
            const dados = JSON.parse(rawData);
            document.getElementById('edit_id_alvo_cat').value = dados.id_cat;
            document.getElementById('edit_cat_nome').value = dados.Nome;
            document.getElementById('edit_cat_desc').value = dados.Descricao_cat;
            document.getElementById('modalEditCategoria').style.display = 'flex';
        } catch(e) {
            console.error("Erro ao ler JSON de categoria:", e);
        }
    }

    // Injetar modais no body ao carregar para evitar problemas com display: none
    window.addEventListener('DOMContentLoaded', () => {
        const modaisParaMover = ['modal-recusa', 'modalEditCategoria', 'modalEditItem', 'modalEditUser'];
        modaisParaMover.forEach(id => {
            const modal = document.getElementById(id);
            if (modal) { document.body.appendChild(modal); }
        });
    });
</script>

<?php 
// SCRIPT DE REABERTURA AUTOMÁTICA DOS MODAIS (Pós-Erros de Validação)
if (isset($abrir_modal_edit_item) && $abrir_modal_edit_item): 
?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        document.getElementById('edit_id_alvo_item').value = <?php echo json_encode($_POST['id_alvo_item'] ?? ''); ?>;
        document.getElementById('edit_item_nome').value = <?php echo json_encode($_POST['edit_item_nome'] ?? ''); ?>;
        document.getElementById('edit_item_qntd').value = <?php echo json_encode($_POST['edit_item_qntd'] ?? ''); ?>;
        document.getElementById('edit_item_categoria').value = <?php echo json_encode($_POST['edit_item_categoria'] ?? ''); ?>;
        document.getElementById('edit_item_descricao').value = <?php echo json_encode($_POST['edit_item_descricao'] ?? ''); ?>;
        document.getElementById('modalEditItem').style.display = 'flex';
    });
</script>
<?php endif; ?>

<?php if (isset($abrir_modal_edit_cat) && $abrir_modal_edit_cat): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        document.getElementById('edit_id_alvo_cat').value = <?php echo json_encode($_POST['id_alvo_cat'] ?? ''); ?>;
        document.getElementById('edit_cat_nome').value = <?php echo json_encode($_POST['edit_cat_nome'] ?? ''); ?>;
        document.getElementById('edit_cat_desc').value = <?php echo json_encode($_POST['edit_cat_desc'] ?? ''); ?>;
        document.getElementById('modalEditCategoria').style.display = 'flex';
    });
</script>
<?php endif; ?>

<?php if (isset($abrir_modal_edit_user) && $abrir_modal_edit_user): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        document.getElementById('edit_id_alvo').value = <?php echo json_encode($_POST['id_alvo'] ?? ''); ?>;
        document.getElementById('edit_nome').value = <?php echo json_encode($_POST['edit_nome'] ?? ''); ?>;
        document.getElementById('edit_cpf').value = <?php echo json_encode($_POST['edit_cpf'] ?? ''); ?>;
        document.getElementById('edit_matricula').value = <?php echo json_encode($_POST['edit_matricula'] ?? ''); ?>;
        document.getElementById('edit_siape').value = <?php echo json_encode($_POST['edit_siape'] ?? ''); ?>;
        document.getElementById('edit_tipo').value = <?php echo json_encode($_POST['edit_tipo'] ?? ''); ?>;
        document.getElementById('edit_email').value = <?php echo json_encode($_POST['edit_email'] ?? ''); ?>;
        document.getElementById('modalEditUser').style.display = 'flex';
    });
</script>
<?php endif; ?>
