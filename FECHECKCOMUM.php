<?php
session_start();
require_once 'conexao.php'; // Conecta com o banco de dados

// Segurança
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'padrao') {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$aba_ativa = "tab-catalogo"; 

// =========================================================================
// PROCESSAMENTO DO FORMULÁRIO DA CONTA (PADRÃO PRG)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_conta'])) {
    $novo_email = $_POST['email'];
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    $stmt_check = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $user_db = $stmt_check->get_result()->fetch_assoc();

    try {
        if (!empty($nova_senha)) {
            if ($nova_senha !== $confirma_senha) {
                $_SESSION['msg_erro'] = "As novas senhas não coincidem.";
            } else if (!password_verify($senha_atual, $user_db['senha'])) {
                $_SESSION['msg_erro'] = "A senha atual está incorreta.";
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE usuarios SET email = ?, senha = ? WHERE id = ?");
                $stmt_upd->bind_param("ssi", $novo_email, $senha_hash, $id_usuario);
                $stmt_upd->execute();
                $_SESSION['msg_sucesso'] = "Dados e senha atualizados com sucesso!";
            }
        } else {
            $stmt_upd = $conn->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $novo_email, $id_usuario);
            $stmt_upd->execute();
            $_SESSION['msg_sucesso'] = "Email atualizado com sucesso!";
        }
    } catch (\Exception $e) {
        $_SESSION['msg_erro'] = "Erro: O E-mail digitado já está em uso.";
    }

    $_SESSION['aba_ativa'] = 'tab-conta';
    header("Location: FECHECKCOMUM.php");
    exit();
}

// =========================================================================
// LÊ AS MENSAGENS E PREPARA O TOAST
// =========================================================================
$mensagem_toast = "";
if (isset($_SESSION['msg_sucesso'])) {
    $mensagem_toast = "<script>window.addEventListener('DOMContentLoaded', () => showToast('" . addslashes($_SESSION['msg_sucesso']) . "', 'success'));</script>";
    unset($_SESSION['msg_sucesso']);
} elseif (isset($_SESSION['msg_erro'])) {
    $mensagem_toast = "<script>window.addEventListener('DOMContentLoaded', () => showToast('" . addslashes($_SESSION['msg_erro']) . "', 'error'));</script>";
    unset($_SESSION['msg_erro']);
}

if (isset($_SESSION['aba_ativa'])) {
    $aba_ativa = $_SESSION['aba_ativa'];
    unset($_SESSION['aba_ativa']);
}

// Exporta variáveis globais para as abas incluídas
global $aba_ativa, $conn, $id_usuario;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno - Check</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="FECHECKCOMUMCSS.css?v=<?php echo time(); ?>"> 
    
    <!-- INJETA O TOAST AQUI -->
    <?php echo $mensagem_toast; ?>
</head>
<body>

    <header class="main-header">
        <div class="logo-container-header">
            <img src="LOGOCHECKSEMDESCR.jpg" alt="Logo Check" class="logo-header"> 
        </div>
        
        <div class="search-container" id="global-search-bar">
            <input type="search" id="search-bar" placeholder="Buscar item...">
            <button type="submit" class="search-btn">
                <i class="bi bi-search"></i> Buscar
            </button>
        </div>

        <nav class="main-nav">
            <a href="#" onclick="switchTab('tab-catalogo', 'Catálogo de Itens', 'nav-catalogo')" class="nav-btn active" id="nav-catalogo">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span class="nav-text">Catálogo</span>
            </a>
            <a href="#" onclick="switchTab('tab-carrinho', 'Meu Carrinho', 'nav-carrinho')" class="nav-btn" id="nav-carrinho">
                <i class="bi bi-cart-check"></i>
                <span class="nav-text">Carrinho</span>
            </a>
            <a href="#" onclick="switchTab('tab-pedidos', 'Meus Pedidos', 'nav-pedidos')" class="nav-btn" id="nav-pedidos">
                <i class="bi bi-archive"></i>
                <span class="nav-text">Meus Pedidos</span>
            </a>
            <a href="#" onclick="switchTab('tab-conta', 'Minha Conta', 'nav-conta')" class="nav-btn" id="nav-conta">
                <i class="bi bi-person"></i>
                <span class="nav-text">Conta</span>
            </a>
            
            <div class="nav-separator"></div>
            <a href="logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-text">Sair</span>
            </a>
        </nav>
    </header>
    
    <main class="app-main-container">
        <div class="page-title-container">
            <h1 id="page-main-title">Catálogo de Itens</h1>
        </div>

        <div id="tab-catalogo" class="spa-tab">
            <div class="item-grid">
                
                <div class="item-card" onclick="openProductModal(this)" data-name="LED Amarelo de Alta Luminosidade" data-img="LEDAMARELO.jpg" data-qty="10" data-cat="Componentes Ópticos" data-desc="LED (Diodo Emissor de Luz) na cor amarela. Ideal para projetos de prototipagem e sinalização. Possui baixo consumo de energia.">
                    <div class="item-image-container"><img src="LEDAMARELO.jpg" alt="Foto do Item 1"></div>
                    <div class="item-info">
                        <strong class="item-name">Led Amarelo</strong>
                        <span class="item-quantity">Quantidade disponível: 10</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="LED Azul Difuso" data-img="LEDAZUL.jpg" data-qty="5" data-cat="Componentes Ópticos" data-desc="LED Azul de 5mm. Perfeito para indicação de status em circuitos eletrônicos. Tensão de operação típica de 3.0V a 3.2V.">
                    <div class="item-image-container"><img src="LEDAZUL.jpg" alt="Foto do Item 2"></div>
                    <div class="item-info">
                        <strong class="item-name">Led Azul</strong>
                        <span class="item-quantity">Quantidade disponível: 5</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="LED Verde Standard" data-img="LEDVERDE.jpg" data-qty="22" data-cat="Componentes Ópticos" data-desc="LED Verde clássico para uso geral. Alta durabilidade e fácil aplicação em protoboards.">
                    <div class="item-image-container"><img src="LEDVERDE.jpg" alt="Foto do Item 3"></div>
                    <div class="item-info">
                        <strong class="item-name">Led Verde</strong>
                        <span class="item-quantity">Quantidade disponível: 22</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="Placa Arduino Uno R3" data-img="ARDUINO.webp" data-qty="0" data-cat="Microcontroladores" data-desc="Placa microcontroladora baseada no ATmega328P. Possui 14 pinos de entrada/saída digital.">
                    <div class="item-image-container"><img src="ARDUINO.webp" alt="Foto do Item 4"></div>
                    <div class="item-info">
                        <strong class="item-name">Arduino</strong>
                        <span class="item-quantity">Quantidade disponível: 0</span>
                    </div>
                    <div class="add-to-cart-btn out-of-stock"><i class="bi bi-x-lg"></i> Indisponível</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="Fio Jumper Vermelho" data-img="FIOVERMELHO.webp" data-qty="8" data-cat="Cabos e Conectores" data-desc="Fio flexível vermelho para conexões em protoboard. Bitola ideal para eletrônica de baixa potência.">
                    <div class="item-image-container"><img src="FIOVERMELHO.webp" alt="Foto do Item 5"></div>
                    <div class="item-info">
                        <strong class="item-name">Fio Vermelho</strong>
                        <span class="item-quantity">Quantidade disponível: 8</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="Kit Parafusos M3" data-img="PARAFUSO1.jpg" data-qty="12" data-cat="Ferramentas e Fixação" data-desc="Parafusos pequenos padrão M3, utilizados para fixação de placas e suportes em cases de acrílico ou metal.">
                    <div class="item-image-container"><img src="PARAFUSO1.jpg" alt="Foto do Item 6"></div>
                    <div class="item-info">
                        <strong class="item-name">Parafuso Pequeno</strong>
                        <span class="item-quantity">Quantidade disponível: 12</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="Fonte de Alimentação ATX" data-img="FontePcLABCHECK.jpg" data-qty="3" data-cat="Hardware / Energia" data-desc="Fonte de alimentação para computadores Desktop. Padrão ATX, 500W de potência real. Bivolt chaveado.">
                    <div class="item-image-container"><img src="FontePcLABCHECK.jpg" alt="Foto do Item 7"></div>
                    <div class="item-info">
                        <strong class="item-name">Fonte PC</strong>
                        <span class="item-quantity">Quantidade disponível: 3</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="Cooler Fan 120mm" data-img="CoolerLABCHECK.webp" data-qty="1" data-cat="Hardware / Refrigeração" data-desc="Ventoinha para gabinete 120mm. Alta rotação e baixo ruído. Conector Molex/3 pinos.">
                    <div class="item-image-container"><img src="CoolerLABCHECK.webp" alt="Foto do Item 8"></div>
                    <div class="item-info">
                        <strong class="item-name">Cooler</strong>
                        <span class="item-quantity">Quantidade disponível: 1</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="Memória RAM DDR4 8GB" data-img="MemoriaramLABCHECK.jpg" data-qty="30" data-cat="Hardware" data-desc="Pente de memória RAM DDR4 com 8GB de capacidade. Frequência de 2666MHz. Ideal para upgrades em desktops.">
                    <div class="item-image-container"><img src="MemoriaramLABCHECK.jpg" alt="Foto do Item 9"></div>
                    <div class="item-info">
                        <strong class="item-name">Memória RAM</strong>
                        <span class="item-quantity">Quantidade disponível: 30</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

                <div class="item-card" onclick="openProductModal(this)" data-name="HD Interno 1TB SATA" data-img="HDDLABCHECK.webp" data-qty="7" data-cat="Hardware / Armazenamento" data-desc="Disco Rígido (HD) interno de 1TB. Conexão SATA III. Ideal para armazenamento em massa de arquivos e backups.">
                    <div class="item-image-container"><img src="HDDLABCHECK.webp" alt="Foto do Item 10"></div>
                    <div class="item-info">
                        <strong class="item-name">HDD 1TB</strong>
                        <span class="item-quantity">Quantidade disponível: 7</span>
                    </div>
                    <div class="add-to-cart-btn"><i class="bi bi-plus"></i> Adicionar</div>
                </div>

            </div>
        </div>

        <!-- Módulos incluídos do Painel do Aluno -->
        <?php include 'CARRINHOCHECKCOMUM.php'; ?>
        <?php include 'PEDIDOSCHECKCOMUM.php'; ?>
        <?php include 'CONTACHECKCOMUM.php'; ?>

    </main>

    <!-- Modais -->
    <div id="productModal" class="modal-overlay">
        <div class="modal-content large">
            <div class="modal-header-nav" onclick="closeModal('productModal')"><i class="bi bi-arrow-left"></i> Voltar</div>
            <div class="modal-body-grid">
                <div class="modal-img-col"><img id="modal-img" src="" alt="Detalhe"></div>
                <div class="modal-info-col">
                    <h2 id="modal-title">Nome</h2>
                    <p><strong>Categoria:</strong> <span id="modal-cat">Geral</span></p>
                    <p><strong>Disponibilidade:</strong> <span id="modal-stock" class="text-green">0</span></p>
                    <hr class="divider">
                    <h3>Descrição</h3>
                    <p id="modal-desc">...</p>
                    <div class="modal-actions-row">
                        <div class="qty-group">
                            <label>Quantidade:</label>
                            <input type="number" id="modal-qty" value="1" min="1">
                        </div>
                        
                        <?php if (isset($status_exibicao) && $status_exibicao === 'bloqueado'): ?>
                            <button class="btn-submit" style="background-color: #ccc; cursor: not-allowed;" onclick="showToast('Sua conta está bloqueada pelo Administrador.', 'error')"><i class="bi bi-slash-circle"></i> Bloqueado</button>
                        <?php else: ?>
                            <button class="btn-submit" onclick="showToast('Adicionado ao Carrinho!', 'success'); closeModal('productModal');"><i class="bi bi-cart-plus"></i> Adicionar</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="checkoutModal" class="modal-overlay">
        <div class="modal-content">
            <h3><i class="bi bi-calendar-check"></i> Agendamento</h3>
            <div class="modal-section">
                <label class="modal-label">Agendar retirada para:</label>
                <div class="days-options" id="retiradaOptions">
                    <button class="day-btn" onclick="selectOption('retirada', this)">7 Dias</button>
                    <button class="day-btn" onclick="selectOption('retirada', this)">15 Dias</button>
                    <button class="day-btn" onclick="selectOption('retirada', this)">30 Dias</button>
                </div>
            </div>
            <div class="modal-section">
                <label class="modal-label">Prazo de devolução desejado:</label>
                <div class="days-options" id="devolucaoOptions">
                    <button class="day-btn" onclick="selectOption('devolucao', this)">7 Dias</button>
                    <button class="day-btn" onclick="selectOption('devolucao', this)">15 Dias</button>
                    <button class="day-btn" onclick="selectOption('devolucao', this)">30 Dias</button>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('checkoutModal')">Cancelar</button>
                <button class="btn-submit" onclick="confirmOrder()">Confirmar Pedido</button>
            </div>
        </div>
    </div>

    <div id="modalRenovacao" class="modal-overlay">
        <div class="modal-content">
            <h3 style="color:#0f006d;"><i class="bi bi-calendar-event"></i> Renovação</h3>
            <p style="margin: 15px 0;">Escolha o novo prazo de devolução desejado:</p>
            <div class="days-options" id="renovacaoOptions" style="margin-bottom: 25px;">
                <button class="day-btn selected" onclick="selectOption('renovacao', this, 7)">7 Dias</button>
                <button class="day-btn" onclick="selectOption('renovacao', this, 15)">15 Dias</button>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('modalRenovacao')">Cancelar</button>
                <button class="btn-submit" onclick="confirmRenewal()">Solicitar</button>
            </div>
        </div>
    </div>

    <!-- Container dos Toasts -->
    <div id="toast-container"></div>

    <script>
        // Função Central de Notificações Toast
        function showToast(msg, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `custom-toast ${type}`;
            
            let icon = 'bi-check-circle';
            if(type === 'error') icon = 'bi-exclamation-octagon';
            if(type === 'warning') icon = 'bi-exclamation-triangle';

            toast.innerHTML = `<i class="bi ${icon}" style="font-size:1.2rem;"></i> <span>${msg}</span>`;
            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3500); 
        }

        // Lógica de SPA (Troca de Abas)
        function switchTab(tabId, titleText, navId) {
            document.querySelectorAll('.spa-tab').forEach(t => t.style.display = 'none');
            document.getElementById(tabId).style.display = 'block';
            document.getElementById('page-main-title').innerText = titleText;
            
            // Barra de busca só no Catálogo
            const searchBar = document.getElementById('global-search-bar');
            if(searchBar) searchBar.style.display = (tabId === 'tab-catalogo') ? 'flex' : 'none';

            // Menu Active
            document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
            if(navId) document.getElementById(navId).classList.add('active');

            window.scrollTo({ top: 0, behavior: 'smooth' }); 
        }

        // Lógica Filtro de Pedidos
        function filterPedidos(status) {
            const cards = document.querySelectorAll('#lista-de-pedidos-aluno .pedido-card');
            cards.forEach(card => {
                if (status === 'todos' || card.getAttribute('data-status') === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Lógica Modais Gerais
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none';
        }

        // Modal Catálogo
        function openProductModal(el) {
            document.getElementById('modal-img').src = el.getAttribute('data-img');
            document.getElementById('modal-title').innerText = el.getAttribute('data-name');
            document.getElementById('modal-cat').innerText = el.getAttribute('data-cat');
            document.getElementById('modal-desc').innerText = el.getAttribute('data-desc');
            const qty = parseInt(el.getAttribute('data-qty'));
            document.getElementById('modal-stock').innerText = qty + ' unidades em estoque';
            document.getElementById('modal-stock').className = qty > 0 ? 'text-green' : 'text-red';
            document.getElementById('productModal').style.display = 'flex';
        }

        // Carrinho: Quantidade e Checkout
        function updateQty(btn, change) {
            const input = btn.parentElement.querySelector('.qty-input');
            let val = parseInt(input.value) + change;
            if (val < 1) val = 1;
            input.value = val;
        }
        
        function openCheckoutModal() { 
            <?php if (isset($status_exibicao) && $status_exibicao === 'bloqueado'): ?>
                showToast('Sua conta está bloqueada pelo Administrador.', 'error');
            <?php else: ?>
                document.getElementById('checkoutModal').style.display = 'flex'; 
            <?php endif; ?>
        }
        
        function confirmOrder() {
            if (!document.querySelector('#retiradaOptions .selected') || !document.querySelector('#devolucaoOptions .selected')) {
                showToast('Selecione os prazos para agendamento.', 'warning');
                return;
            }
            showToast('Pedido Finalizado com sucesso!', 'success');
            closeModal('checkoutModal'); 
            switchTab('tab-pedidos', 'Meus Pedidos', 'nav-pedidos');
        }

        // Pedidos: Seleção de Dias e Renovação
        let renovacaoDias = 7;
        function selectOption(group, btn, diasVal) {
            const container = document.getElementById(group + 'Options');
            container.querySelectorAll('.day-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            if(diasVal) renovacaoDias = diasVal;
        }
        
        function openRenewalModal() { document.getElementById('modalRenovacao').style.display = 'flex'; }
        function confirmRenewal() { 
            showToast(`Solicitação de +${renovacaoDias} dias enviada com sucesso!`, 'success');
            closeModal('modalRenovacao'); 
        }

        // Se o PHP processou uma atualização de conta, forçamos a aba da conta a ficar aberta
        <?php if ($aba_ativa === "tab-conta"): ?>
            window.addEventListener('DOMContentLoaded', function() {
                switchTab('tab-conta', 'Minha Conta', 'nav-conta');
            });
        <?php endif; ?>
    </script>
</body>
</html>