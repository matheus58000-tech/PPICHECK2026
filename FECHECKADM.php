<?php
session_start();
require_once 'conexao.php'; // Conecta com o banco de dados

// =========================================================================
// 1. SEGURANÇA E CONTROLE DE ACESSO
// =========================================================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'resp')) {
    header("Location: index.html");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$mensagem_conta = "";
$mensagem_lab = "";
$aba_ativa = "view-catalogo"; 
$sub_aba_ativa = "";

// =========================================================================
// 2. PROCESSAMENTO: ATUALIZAÇÃO DA PRÓPRIA CONTA (Aba Conta)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_conta'])) {
    $aba_ativa = "view-conta"; 
    $novo_email = $_POST['email'];
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    $stmt_check = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    $user_db = $resultado_check->fetch_assoc();

    if (!empty($nova_senha)) {
        if ($nova_senha !== $confirma_senha) {
            $mensagem_conta = "<div style='color: #d9534f; font-weight: bold; margin-bottom: 15px;'><i class='bi bi-exclamation-triangle'></i> As novas senhas não coincidem.</div>";
        } else if (!password_verify($senha_atual, $user_db['senha'])) {
            $mensagem_conta = "<div style='color: #d9534f; font-weight: bold; margin-bottom: 15px;'><i class='bi bi-exclamation-triangle'></i> A senha atual está incorreta.</div>";
        } else {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt_upd = $conn->prepare("UPDATE usuarios SET email = ?, senha = ? WHERE id = ?");
            $stmt_upd->bind_param("ssi", $novo_email, $senha_hash, $id_usuario);
            $stmt_upd->execute();
            $mensagem_conta = "<div style='color: #28a745; font-weight: bold; margin-bottom: 15px;'><i class='bi bi-check-circle'></i> Dados atualizados com sucesso!</div>";
        }
    } else {
        $stmt_upd = $conn->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $novo_email, $id_usuario);
        $stmt_upd->execute();
        $mensagem_conta = "<div style='color: #28a745; font-weight: bold; margin-bottom: 15px;'><i class='bi bi-check-circle'></i> Email atualizado com sucesso!</div>";
    }
}

// =========================================================================
// 3. PROCESSAMENTO: GERENCIAMENTO DE USUÁRIOS (Laboratório)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao_usuario'])) {
    $aba_ativa = "view-lab"; 
    $sub_aba_ativa = "tab-usuarios";
    $acao = $_POST['acao_usuario'];
    
    // Pega o ID alvo (pode não existir na ação de adicionar, então usamos o operador ??)
    $id_alvo = isset($_POST['id_alvo']) ? intval($_POST['id_alvo']) : 0;

    // --- BLOQUEAR, EXCLUIR E EDITAR ---
    if ($id_alvo === $id_usuario && ($acao === 'excluir' || $acao === 'bloquear')) {
        $mensagem_lab = "<script>alert('Você não pode bloquear ou excluir a própria conta!');</script>";
    } else {
        if ($acao === 'bloquear') {
            $conn->query("UPDATE usuarios SET status = IF(status='ativo', 'bloqueado', 'ativo') WHERE id = $id_alvo");
            $mensagem_lab = "<script>alert('Status do usuário alterado com sucesso!');</script>";
        
        } elseif ($acao === 'excluir') {
            $conn->query("DELETE FROM usuarios WHERE id = $id_alvo");
            $mensagem_lab = "<script>alert('Usuário excluído permanentemente!');</script>";
        
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
            
            if ($stmt_edit->execute()) {
                $mensagem_lab = "<script>alert('Dados do usuário atualizados com sucesso!');</script>";
            } else {
                $mensagem_lab = "<script>alert('Erro ao atualizar: Verifique se CPF, Matrícula ou E-mail já existem.');</script>";
            }
        
        // --- NOVO: ADICIONAR USUÁRIO ---
        } elseif ($acao === 'adicionar') {
            $tipo = $_POST['add_tipo'];
            $cpf = $_POST['add_cpf'];
            $nome = $_POST['add_nome'];
            $email = $_POST['add_email'];
            $nascimento = $_POST['add_nascimento'];
            $senha = $_POST['add_senha'];
            $confirma = $_POST['add_confirma'];
            
            // Lógica do campo dinâmico
            $dinamico = isset($_POST['add_dinamico']) ? $_POST['add_dinamico'] : null;
            $matricula = ($tipo === 'padrao') ? $dinamico : null;
            $siape = ($tipo === 'admin') ? $dinamico : null;

            if ($senha !== $confirma) {
                $mensagem_lab = "<script>alert('As senhas não coincidem!');</script>";
                $sub_aba_ativa = "tab-novo-usuario"; // Mantém na tela de adicionar caso erre
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt_add = $conn->prepare("INSERT INTO usuarios (nome, cpf, matricula, siape, email, data_nascimento, senha, tipo_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_add->bind_param("ssssssss", $nome, $cpf, $matricula, $siape, $email, $nascimento, $hash, $tipo);
                
                if ($stmt_add->execute()) {
                    $mensagem_lab = "<script>alert('Usuário cadastrado com sucesso!');</script>";
                } else {
                    $mensagem_lab = "<script>alert('Erro ao cadastrar: E-mail, CPF, Matrícula ou SIAPE já estão em uso.');</script>";
                    $sub_aba_ativa = "tab-novo-usuario";
                }
            }
        }
    }
}

// Busca os dados da PRÓPRIA CONTA para preencher a aba "Conta"
$stmt = $conn->prepare("SELECT nome, cpf, siape, email FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$dados_usuario = $stmt->get_result()->fetch_assoc();

$nome_exibicao = htmlspecialchars($dados_usuario['nome'] ?? '');
$cpf_exibicao = htmlspecialchars($dados_usuario['cpf'] ?? '');
$siape_exibicao = htmlspecialchars($dados_usuario['siape'] ?? '');
$email_exibicao = htmlspecialchars($dados_usuario['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check - Sistema Integrado</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="FECHECKADMCSS.css"> 
    <?php echo $mensagem_lab; ?>
</head>
<body>

    <header class="main-header">
        <div class="logo-container-header">
            <img src="LOGOCHECKSEMDESCR.jpg" alt="Logo Check" class="logo-header"> 
        </div>
        
        <div class="search-container" id="global-search" style="display: none;">
            <input type="search" id="search-bar" placeholder="Buscar item...">
            <button type="button" class="search-btn">
                <i class="bi bi-search"></i> Buscar
            </button>
        </div>
        
        <nav class="main-nav">
            <div class="nav-dropdown">
                <a href="#" class="nav-btn" id="nav-lab-btn" onclick="navigateToLab('tab-inicio', 'LEPEP de Hardware')">
                    <i class="bi bi-motherboard"></i>
                    <span class="nav-text">Laboratório</span>
                </a>
                <div class="dropdown-content">
                    <a href="#" onclick="navigateToLab('tab-inicio', 'LEPEP de Hardware')"><i class="bi bi-house-door"></i> Início Lab</a>
                    <a href="#" onclick="navigateToLab('tab-pedidos-lab', 'Gerenciamento de Pedidos')"><i class="bi bi-archive"></i> Pedidos</a>
                    <a href="#" onclick="navigateToLab('tab-usuarios', 'Gerenciamento de Usuários')"><i class="bi bi-people"></i> Usuários</a>
                    <a href="#" onclick="navigateToLab('tab-estoque', 'Gerenciamento de Estoque')"><i class="bi bi-box-seam"></i> Estoque</a>
                </div>
            </div>
            
            <a href="#" class="nav-btn active" id="nav-catalogo-btn" onclick="switchAppView('view-catalogo', this)">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span class="nav-text">Catálogo</span>
            </a>
            
            <a href="#" class="nav-btn" id="nav-carrinho-btn" onclick="switchAppView('view-carrinho', this)">
                <i class="bi bi-cart-check"></i>
                <span class="nav-text">Carrinho</span>
            </a>
            
            <a href="#" class="nav-btn" id="nav-pedidos-btn" onclick="switchAppView('view-pedidos', this)">
                <i class="bi bi-archive"></i>
                <span class="nav-text">Meus Pedidos</span>
            </a>
            
            <a href="#" class="nav-btn" id="nav-conta-btn" onclick="switchAppView('view-conta', this)">
                <i class="bi bi-person"></i>
                <span class="nav-text">Conta</span>
            </a>
            
            <div class="nav-separator"></div>
            
            <a href="index.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-text">Sair</span>
            </a>
        </nav>
    </header>

    <main id="view-catalogo" class="main-view catalog-main-container">
        <h2>Catálogo de Itens</h2>
        <div class="item-grid">

            <div class="item-card" onclick="openProductModal(this)" data-name="LED Amarelo de Alta Luminosidade" data-img="LEDAMARELO.jpg" data-qty="10" data-cat="Componentes Ópticos" data-desc="LED (Diodo Emissor de Luz) na cor amarela. Ideal para projetos de prototipagem e sinalização. Possui baixo consumo de energia.">
                <div class="item-image-container"><img src="LEDAMARELO.jpg" alt="Led Amarelo"></div>
                <div class="item-info">
                    <strong class="item-name">Led Amarelo</strong>
                    <span class="item-quantity">Quantidade disponível: 10</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="LED Azul Difuso" data-img="LEDAZUL.jpg" data-qty="5" data-cat="Componentes Ópticos" data-desc="LED Azul de 5mm. Perfeito para indicação de status em circuitos eletrônicos. Tensão de operação típica de 3.0V a 3.2V.">
                <div class="item-image-container"><img src="LEDAZUL.jpg" alt="Led Azul"></div>
                <div class="item-info">
                    <strong class="item-name">Led Azul</strong>
                    <span class="item-quantity">Quantidade disponível: 5</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="LED Verde Standard" data-img="LEDVERDE.jpg" data-qty="22" data-cat="Componentes Ópticos" data-desc="LED Verde clássico para uso geral. Alta durabilidade e fácil aplicação em protoboards.">
                <div class="item-image-container"><img src="LEDVERDE.jpg" alt="Led Verde"></div>
                <div class="item-info">
                    <strong class="item-name">Led Verde</strong>
                    <span class="item-quantity">Quantidade disponível: 22</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="Placa Arduino Uno R3" data-img="ARDUINO.webp" data-qty="0" data-cat="Microcontroladores" data-desc="Placa microcontroladora baseada no ATmega328P. Possui 14 pinos de entrada/saída digital.">
                <div class="item-image-container"><img src="ARDUINO.webp" alt="Arduino"></div>
                <div class="item-info">
                    <strong class="item-name">Arduino</strong>
                    <span class="item-quantity">Quantidade disponível: 0</span>
                </div>
                <div class="add-to-cart-btn out-of-stock"><i class="bi bi-x-lg"></i> Indisponível</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="Fio Jumper Vermelho" data-img="FIOVERMELHO.webp" data-qty="8" data-cat="Cabos e Conectores" data-desc="Fio flexível vermelho para conexões em protoboard. Bitola ideal para eletrônica de baixa potência.">
                <div class="item-image-container"><img src="FIOVERMELHO.webp" alt="Fio Vermelho"></div>
                <div class="item-info">
                    <strong class="item-name">Fio Vermelho</strong>
                    <span class="item-quantity">Quantidade disponível: 8</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="Kit Parafusos M3" data-img="PARAFUSO1.jpg" data-qty="12" data-cat="Ferramentas e Fixação" data-desc="Parafusos pequenos padrão M3, utilizados para fixação de placas e suportes em cases de acrílico ou metal.">
                <div class="item-image-container"><img src="PARAFUSO1.jpg" alt="Parafuso Pequeno"></div>
                <div class="item-info">
                    <strong class="item-name">Parafuso Pequeno</strong>
                    <span class="item-quantity">Quantidade disponível: 12</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="Fonte de Alimentação ATX" data-img="FontePcLABCHECK.jpg" data-qty="3" data-cat="Hardware / Energia" data-desc="Fonte de alimentação para computadores Desktop. Padrão ATX, 500W de potência real. Bivolt chaveado.">
                <div class="item-image-container"><img src="FontePcLABCHECK.jpg" alt="Fonte PC"></div>
                <div class="item-info">
                    <strong class="item-name">Fonte PC</strong>
                    <span class="item-quantity">Quantidade disponível: 3</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="Cooler Fan 120mm" data-img="CoolerLABCHECK.webp" data-qty="1" data-cat="Hardware / Refrigeração" data-desc="Ventoinha para gabinete 120mm. Alta rotação e baixo ruído. Conector Molex/3 pinos.">
                <div class="item-image-container"><img src="CoolerLABCHECK.webp" alt="Cooler"></div>
                <div class="item-info">
                    <strong class="item-name">Cooler</strong>
                    <span class="item-quantity">Quantidade disponível: 1</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="Memória RAM DDR4 8GB" data-img="MemoriaramLABCHECK.jpg" data-qty="30" data-cat="Hardware" data-desc="Pente de memória RAM DDR4 com 8GB de capacidade. Frequência de 2666MHz. Ideal para upgrades em desktops.">
                <div class="item-image-container"><img src="MemoriaramLABCHECK.jpg" alt="Memória RAM"></div>
                <div class="item-info">
                    <strong class="item-name">Memória RAM</strong>
                    <span class="item-quantity">Quantidade disponível: 30</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

            <div class="item-card" onclick="openProductModal(this)" data-name="HD Interno 1TB SATA" data-img="HDDLABCHECK.webp" data-qty="7" data-cat="Hardware / Armazenamento" data-desc="Disco Rígido (HD) interno de 1TB. Conexão SATA III. Ideal para armazenamento em massa de arquivos e backups.">
                <div class="item-image-container"><img src="HDDLABCHECK.webp" alt="HDD 1TB"></div>
                <div class="item-info">
                    <strong class="item-name">HDD 1TB</strong>
                    <span class="item-quantity">Quantidade disponível: 7</span>
                </div>
                <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
            </div>

        </div> 
    </main>

    <main id="view-carrinho" class="main-view cart-main-container" style="display:none;">
        <h2 class="cart-title"><i class="bi bi-cart3"></i> Seu Carrinho</h2>
        <div class="cart-list">

            <div class="cart-item">
                <img src="LEDAMARELO.jpg" alt="Led Amarelo" class="cart-item-img">
                <div class="cart-item-info">
                    <strong class="cart-item-name">Led Amarelo</strong>
                    <span class="cart-item-code">Cód: #L001</span>
                </div>
                <div class="quantity-controls">
                    <button class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                    <input type="text" value="10" class="qty-input" readonly>
                    <button class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
                </div>
                <button class="remove-btn" title="Remover item"><i class="bi bi-trash"></i></button>
            </div>

            <div class="cart-item">
                <img src="LEDAZUL.jpg" alt="Led Azul" class="cart-item-img">
                <div class="cart-item-info">
                    <strong class="cart-item-name">Led Azul</strong>
                    <span class="cart-item-code">Cód: #L002</span>
                </div>
                <div class="quantity-controls">
                    <button class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                    <input type="text" value="5" class="qty-input" readonly>
                    <button class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
                </div>
                <button class="remove-btn" title="Remover item"><i class="bi bi-trash"></i></button>
            </div>

            <div class="cart-item">
                <img src="LEDVERDE.jpg" alt="Led Verde" class="cart-item-img">
                <div class="cart-item-info">
                    <strong class="cart-item-name">Led Verde</strong>
                    <span class="cart-item-code">Cód: #L003</span>
                </div>
                <div class="quantity-controls">
                    <button class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                    <input type="text" value="20" class="qty-input" readonly>
                    <button class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
                </div>
                <button class="remove-btn" title="Remover item"><i class="bi bi-trash"></i></button>
            </div>

        </div>

        <div class="cart-footer">
            <a href="#" class="continue-btn" onclick="switchAppView('view-catalogo', document.getElementById('nav-catalogo-btn'))">Continuar escolhendo</a>
            <a href="#" class="checkout-btn" onclick="openCheckoutModal(event)">
                Finalizar Pedido <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </main>

    <main id="view-pedidos" class="main-view pedidos-main-container" style="display:none;">
        <div class="page-header-row">
            <h2>Meus Pedidos</h2>
            <div class="filter-container">
                <div class="filter-actions">
                    <label for="statusFilter" class="filter-label">Filtrar por:</label>
                    <select id="statusFilter" class="filter-select">
                        <option value="todos">Todos</option>
                        <option value="aguardando">Aguardando</option>
                        <option value="producao">Em Produção</option>
                        <option value="retirado">Retirados (Comigo)</option>
                        <option value="finalizado">Finalizados</option>
                        <option value="recusado">Recusados</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="pedidos-list">

            <div class="pedido-card">
                <div class="pedido-header">
                    <div class="id-group">
                        <h3>Pedido #2024-005</h3>
                        <span class="pedido-date">Solicitado em: 26/10/2025</span>
                    </div>
                    <span class="status-tag status-aguardando">Em Análise</span>
                </div>
                <div class="pedido-body">
                    <ul class="item-list">
                        <li>
                            <div class="item-info-left">
                                <img src="FontePcLABCHECK.jpg" alt="Fonte" class="item-thumb">
                                <div class="item-details">
                                    <span class="item-nome">Fonte para computador</span>
                                    <span class="item-qtde">Qtd: 1</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="pedido-timeline-area">
                    <div class="timeline-wrapper">
                        <div class="step current"><div class="step-icon"><i class="bi bi-clipboard-data"></i></div><span class="step-label">Análise</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-gear-wide-connected"></i></div><span class="step-label">Produção</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-box-arrow-up"></i></div><span class="step-label">Retirado</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-arrow-return-left"></i></div><span class="step-label">Devolvido</span></div>
                    </div>
                </div>
            </div>

            <div class="pedido-card">
                <div class="pedido-header">
                    <div class="id-group">
                        <h3>Pedido #2024-004</h3>
                        <span class="pedido-date">Solicitado em: 25/10/2025</span>
                    </div>
                    <span class="status-tag status-preparacao">Em Produção</span>
                </div>
                <div class="pedido-body">
                    <ul class="item-list">
                        <li>
                            <div class="item-info-left">
                                <img src="ARDUINO.webp" alt="Arduino" class="item-thumb">
                                <div class="item-details">
                                    <span class="item-nome">Arduino</span>
                                    <span class="item-qtde">Quantidade: 1</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="pedido-timeline-area">
                    <div class="timeline-wrapper">
                        <div class="step completed"><div class="step-icon"><i class="bi bi-check-lg"></i></div><span class="step-label">Aprovado</span></div>
                        <div class="step current"><div class="step-icon"><i class="bi bi-gear-wide-connected"></i></div><span class="step-label">Produção</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-box-arrow-up"></i></div><span class="step-label">Retirado</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-arrow-return-left"></i></div><span class="step-label">Devolvido</span></div>
                    </div>
                </div>
            </div>

            <div class="pedido-card">
                <div class="pedido-header">
                    <div class="id-group">
                        <h3>Pedido #2024-003</h3>
                        <span class="pedido-date">Solicitado em: 24/10/2025</span>
                    </div>
                    <span class="status-tag status-recusado">Recusado</span>
                </div>
                <div class="pedido-body">
                    <ul class="item-list">
                        <li>
                            <div class="item-info-left">
                                <img src="FIOVERMELHO.webp" alt="Fio" class="item-thumb">
                                <div class="item-details">
                                    <span class="item-nome">Fio Vermelho</span>
                                    <span class="item-qtde">Quantidade: 2</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="pedido-timeline-area">
                    <div class="prazo-devolucao error">
                        <i class="bi bi-x-circle-fill"></i>
                        <span>Pedido reprovado pelo administrador.</span>
                    </div>
                    <div class="timeline-wrapper">
                        <div class="step cancelled"><div class="step-icon"><i class="bi bi-x-lg"></i></div><span class="step-label">Recusado</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-gear-wide-connected"></i></div><span class="step-label">Produção</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-box-arrow-up"></i></div><span class="step-label">Retirado</span></div>
                        <div class="step"><div class="step-icon"><i class="bi bi-arrow-return-left"></i></div><span class="step-label">Devolvido</span></div>
                    </div>
                </div>
            </div>

            <div class="pedido-card">
                <div class="pedido-header">
                    <div class="id-group">
                        <h3>Pedido #2024-002</h3>
                        <span class="pedido-date">Solicitado em: 23/10/2025</span>
                    </div>
                    <span class="status-tag status-pronto">Retirado</span>
                </div>
                <div class="pedido-body">
                    <ul class="item-list">
                        <li>
                            <div class="item-info-left">
                                <img src="LEDAMARELO.jpg" alt="Led" class="item-thumb">
                                <div class="item-details">
                                    <span class="item-nome">Led amarelo</span>
                                    <span class="item-qtde">Quantidade: 5</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="pedido-timeline-area">
                    <div class="prazo-devolucao warning">
                        <i class="bi bi-alarm-fill"></i>
                        <span>Faltam <strong>2 dias</strong> para a devolução</span>
                    </div>
                    <div class="pedido-actions">
                        <button class="btn-renovar" onclick="openRenewalModal()">
                            <i class="bi bi-arrow-repeat"></i> Renovar Empréstimo
                        </button>
                    </div>
                    <div class="timeline-wrapper">
                        <div class="step completed"><div class="step-icon"><i class="bi bi-check-lg"></i></div><span class="step-label">Aprovado</span></div>
                        <div class="step completed"><div class="step-icon"><i class="bi bi-gear-wide-connected"></i></div><span class="step-label">Produção</span></div>
                        <div class="step completed"> <div class="step-icon"><i class="bi bi-box-arrow-up"></i></div><span class="step-label">Retirado</span></div>
                        <div class="step current"><div class="step-icon"><i class="bi bi-arrow-return-left"></i></div><span class="step-label">Devolver</span></div>
                    </div>
                </div>
            </div>

            <div class="pedido-card">
                <div class="pedido-header">
                    <div class="id-group">
                        <h3>Pedido #2024-001</h3>
                        <span class="pedido-date">Solicitado em: 10/10/2025</span>
                    </div>
                    <span class="status-tag status-finalizado">Finalizado</span>
                </div>
                <div class="pedido-body">
                    <ul class="item-list">
                        <li>
                            <div class="item-info-left">
                                <img src="LEDAZUL.jpg" alt="Led" class="item-thumb">
                                <div class="item-details">
                                    <span class="item-nome">Led azul</span>
                                    <span class="item-qtde">Quantidade: 10</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="pedido-timeline-area">
                    <div class="prazo-devolucao success">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Item devolvido com sucesso!</span>
                    </div>
                    <div class="timeline-wrapper">
                        <div class="step completed"><div class="step-icon"><i class="bi bi-check-lg"></i></div><span class="step-label">Aprovado</span></div>
                        <div class="step completed"><div class="step-icon"><i class="bi bi-gear-wide-connected"></i></div><span class="step-label">Produção</span></div>
                        <div class="step completed"><div class="step-icon"><i class="bi bi-box-arrow-up"></i></div><span class="step-label">Retirado</span></div>
                        <div class="step completed"><div class="step-icon"><i class="bi bi-check-all"></i></div><span class="step-label">Devolvido</span></div>
                    </div>
                </div>
            </div>

        </div> 
    </main>

    <main id="view-conta" class="main-view conta-main-container" style="display:none;">
        <h2>Minha Conta</h2>
        <div class="form-card" style="max-width: 700px; margin: 0 auto;">
            <form method="POST" action="FECHECKADM.php">
                <input type="hidden" name="atualizar_conta" value="1">
                
                <?php echo $mensagem_conta; ?>

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


    <div id="product-modal" class="modal-overlay">
        <div class="modal-card-detail">
            <div class="modal-header-nav" onclick="closeProductModal()"><i class="bi bi-arrow-left"></i> Voltar ao Catálogo</div>
            <div class="modal-body-grid">
                <div class="modal-col-img"><img id="modal-img" src="" alt="Produto"></div>
                <div class="modal-col-info">
                    <h2 id="modal-title">Nome do Produto</h2>
                    <p class="modal-meta"><strong>Categoria:</strong> <span id="modal-cat">Geral</span></p>
                    <p class="modal-meta"><strong>Disponibilidade:</strong> <span id="modal-stock" class="stock-green">0 unidades</span></p>
                    <hr class="modal-divider">
                    <h3 class="modal-subtitle">Descrição do Item</h3>
                    <p id="modal-desc" class="modal-description">Descrição aqui...</p>
                    <div class="modal-actions-row">
                        <div class="qty-group"><label>Quantidade:</label><input type="number" id="modal-qty" value="1" min="1"></div>
                        <button class="modal-add-btn" onclick="addToCartFromModal()"><i class="bi bi-cart-plus"></i> Adicionar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="checkoutModal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="modal-heading-blue"><i class="bi bi-calendar-check"></i> Agendamento</h3>
            <div class="modal-section">
                <label class="modal-label">Agendar retirada para:</label>
                <div class="days-options" id="retiradaOptions">
                    <button class="day-btn" onclick="selectCheckoutOption('retirada', this)">7 Dias</button>
                    <button class="day-btn" onclick="selectCheckoutOption('retirada', this)">15 Dias</button>
                    <button class="day-btn" onclick="selectCheckoutOption('retirada', this)">30 Dias</button>
                </div>
            </div>
            <div class="modal-section" style="margin-top: 15px;">
                <label class="modal-label">Prazo de devolução desejado:</label>
                <div class="days-options" id="devolucaoOptions">
                    <button class="day-btn" onclick="selectCheckoutOption('devolucao', this)">7 Dias</button>
                    <button class="day-btn" onclick="selectCheckoutOption('devolucao', this)">15 Dias</button>
                    <button class="day-btn" onclick="selectCheckoutOption('devolucao', this)">30 Dias</button>
                </div>
            </div>
            <div class="modal-footer" style="margin-top: 2rem;">
                <button class="btn-cancel" onclick="closeCheckoutModal()">Cancelar</button>
                <button class="btn-confirm" onclick="confirmOrder()">Confirmar</button>
            </div>
        </div>
    </div>

    <div id="modalRenovacao" class="modal-overlay">
        <div class="modal-content">
            <h3 class="modal-heading-blue"><i class="bi bi-calendar-event"></i> Renovação de Empréstimo</h3>
            <p style="margin-bottom:15px; color:#555;">Escolha o novo prazo de devolução:</p>
            <div class="days-options">
                <button class="day-btn selected" onclick="selectCheckoutOption('renova', this)">7 Dias</button>
                <button class="day-btn" onclick="selectCheckoutOption('renova', this)">15 Dias</button>
            </div>
            <div class="modal-footer" style="margin-top:20px;">
                <button class="btn-cancel" onclick="closeRenewalModal()">Cancelar</button>
                <button class="btn-confirm" onclick="confirmRenewal()">Confirmar</button>
            </div>
        </div>
    </div>

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


    <script>
        // ================= NAVEGAÇÃO E SPA =================
        function switchAppView(viewId, element) {
            document.querySelectorAll('.main-view').forEach(v => v.style.display = 'none');
            document.getElementById(viewId).style.display = 'block';
            document.getElementById('global-search').style.display = (viewId === 'view-catalogo') ? 'flex' : 'none';
            document.querySelectorAll('.main-nav .nav-btn').forEach(btn => btn.classList.remove('active'));
            if(element) element.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function navigateToLab(tabId, titleText) {
            switchAppView('view-lab', document.getElementById('nav-lab-btn'));
            switchLabTab(tabId, titleText);
        }

        function switchLabTab(tabId, titleText) {
            document.querySelectorAll('#view-lab .spa-tab').forEach(tab => tab.style.display = 'none');
            document.getElementById(tabId).style.display = 'block';
            document.getElementById('page-main-title').innerText = titleText;
        }

        // ================= FUNÇÃO CAMPO DINÂMICO ADD USUÁRIO =================
        function mudarCampoDinamicoAddUser(tipo) {
            const grupo = document.getElementById('grupo-dinamico-add-user');
            const label = document.getElementById('label-dinamico-add-user');
            const input = document.getElementById('input-dinamico-add-user');

            if (tipo === 'padrao') {
                grupo.style.display = 'block';
                label.innerText = 'Matrícula';
                input.required = true;
                input.placeholder = 'Digite a Matrícula';
            } else if (tipo === 'admin') {
                grupo.style.display = 'block';
                label.innerText = 'SIAPE';
                input.required = true;
                input.placeholder = 'Digite o SIAPE';
            } else {
                // Responsável ou vazio não pede nem Matrícula nem SIAPE
                grupo.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }

        // ================= MÁSCARA DE CPF =================
        const addCpfInput = document.getElementById('add_cpf');
        const editCpfInput = document.getElementById('edit_cpf');
        
        function aplicarMascaraCPF(e) {
            let value = e.target.value.replace(/\D/g, ''); 
            if (value.length > 11) value = value.slice(0, 11); 
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        }
        
        if(addCpfInput) addCpfInput.addEventListener('input', aplicarMascaraCPF);
        if(editCpfInput) editCpfInput.addEventListener('input', aplicarMascaraCPF);

        // ================= OUTRAS FUNÇÕES JS =================
        function openProductModal(card) {
            // ... [código mantido intacto]
            document.getElementById('modal-img').src = card.getAttribute('data-img');
            document.getElementById('modal-title').innerText = card.getAttribute('data-name');
            document.getElementById('modal-cat').innerText = card.getAttribute('data-cat');
            document.getElementById('modal-desc').innerText = card.getAttribute('data-desc');
            const qty = parseInt(card.getAttribute('data-qty'));
            const stockEl = document.getElementById('modal-stock');
            const btnEl = document.querySelector('.modal-add-btn');
            document.getElementById('modal-qty').max = qty;
            document.getElementById('modal-qty').value = 1;

            if(qty > 0) {
                stockEl.innerText = `${qty} unidades em estoque`;
                stockEl.className = 'stock-green';
                btnEl.disabled = false;
                btnEl.style.backgroundColor = '#0f006d';
                btnEl.innerHTML = '<i class="bi bi-cart-plus"></i> Adicionar ao Carrinho';
            } else {
                stockEl.innerText = "Indisponível no momento";
                stockEl.className = 'stock-red';
                btnEl.disabled = true;
                btnEl.style.backgroundColor = '#ccc';
                btnEl.innerHTML = 'Indisponível';
            }
            document.getElementById('product-modal').style.display = 'flex';
        }
        function closeProductModal() { document.getElementById('product-modal').style.display = 'none'; }
        function addToCartFromModal() { alert('Item adicionado!'); closeProductModal(); }

        function updateQty(btn, change) {
            const input = btn.parentElement.querySelector('.qty-input');
            let newValue = parseInt(input.value) + change;
            input.value = newValue < 1 ? 1 : newValue;
        }
        function openCheckoutModal(e) { e.preventDefault(); document.getElementById('checkoutModal').style.display = 'flex'; }
        function closeCheckoutModal() { document.getElementById('checkoutModal').style.display = 'none'; }
        function selectCheckoutOption(group, btn) {
            btn.parentElement.querySelectorAll('.day-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        }
        function confirmOrder() { alert('Pedido Confirmado!'); closeCheckoutModal(); switchAppView('view-pedidos', document.getElementById('nav-pedidos-btn')); }

        function openRenewalModal() { document.getElementById('modalRenovacao').style.display = 'flex'; }
        function closeRenewalModal() { document.getElementById('modalRenovacao').style.display = 'none'; }
        function confirmRenewal() { alert('Renovação solicitada com sucesso!'); closeRenewalModal(); }

        function switchLabFilter(targetId, btn) {
            document.querySelectorAll('#tab-pedidos-lab .btn-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('#tab-pedidos-lab .orders-container').forEach(c => c.style.display = 'none');
            document.getElementById(targetId).style.display = 'block';
        }

        function aprovarPedido(id) { if(confirm('Aprovar pedido #' + id + '?')) { alert('Pedido Aprovado!'); } }
        function devolverPedido(id) { if(confirm('Confirmar devolução do pedido #' + id + '?')) { alert('Devolução registrada!'); } }
        
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
                alert(`Pedido #${pedidoAtualId} RECUSADO.\nJustificativa: ` + (document.getElementById('justificativa').value || 'Nenhuma')); 
                fecharModalRecusa(); 
            }
        }

        function togglePedidos(userId) {
            const detailElement = document.getElementById(`pedidos-detail-${userId}`);
            const buttonIcon = document.querySelector(`tr[data-user-id="${userId}"] .btn-view-pedidos i`);
            
            if (detailElement.style.display === 'none' || detailElement.style.display === '') {
                document.querySelectorAll('.pedidos-detail-container').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.btn-view-pedidos i').forEach(icon => { icon.className = 'bi bi-plus-lg'; });
                detailElement.style.display = 'block';
                buttonIcon.className = 'bi bi-dash-lg';
            } else {
                detailElement.style.display = 'none';
                buttonIcon.className = 'bi bi-plus-lg';
            }
        }

        function acaoUsuario(acao, id) {
            if (acao === 'excluir' && !confirm("Tem certeza que deseja apagar este usuário DEFINITIVAMENTE?")) return;
            if (acao === 'bloquear' && !confirm("Tem certeza que deseja mudar o status de bloqueio deste usuário?")) return;

            document.getElementById('form-acao-val').value = acao;
            document.getElementById('form-id-alvo').value = id;
            document.getElementById('form-acao-usuario').submit();
        }

        function abrirModalEditUser(jsonData) {
            const dados = JSON.parse(jsonData);
            document.getElementById('edit_id_alvo').value = dados.id;
            document.getElementById('edit_nome').value = dados.nome;
            document.getElementById('edit_email').value = dados.email;
            document.getElementById('edit_cpf').value = dados.cpf || '';
            document.getElementById('edit_matricula').value = dados.matricula || '';
            document.getElementById('edit_siape').value = dados.siape || '';
            document.getElementById('edit_tipo').value = dados.tipo;
            
            document.getElementById('modalEditUser').style.display = 'flex';
        }

        function fecharModalEdit() {
            document.getElementById('modalEditUser').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }

        // ================= RETORNO DA ABA APÓS RECARREGAR (PHP) =================
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($aba_ativa === "view-conta"): ?>
                switchAppView('view-conta', document.getElementById('nav-conta-btn'));
            <?php elseif ($aba_ativa === "view-lab"): ?>
                switchAppView('view-lab', document.getElementById('nav-lab-btn'));
                switchLabTab('<?php echo $sub_aba_ativa; ?>', 'Gerenciamento de Usuários');
            <?php else: ?>
                switchAppView('view-catalogo', document.getElementById('nav-catalogo-btn'));
            <?php endif; ?>
        });
    </script>
</body>
</html>