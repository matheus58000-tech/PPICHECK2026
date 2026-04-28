<?php
session_start();
require_once 'conexao.php'; // Conecta com o banco de dados

// Segurança
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'resp')) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$aba_ativa = "view-catalogo"; 
$sub_aba_ativa = "";

global $aba_ativa, $sub_aba_ativa, $conn, $id_usuario;
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
    
    <link rel="stylesheet" href="FECHECKADMCSS.css?v=<?php echo time(); ?>"> 
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
            
            <a href="logout.php" class="logout-btn">
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

    <?php include 'CARRINHOCHECKADM.php'; ?>
    <?php include 'PEDIDOSCHECKADM.php'; ?>
    <?php include 'CONTACHECKADM.php'; ?>
    <?php include 'LABCHECKADM.php'; ?>

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

    <div id="toast-container"></div>

    <script>
        // ================= LÓGICA TOAST =================
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
            }, 3000);
        }

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
            } else if (tipo === 'admin' || tipo === 'resp') {
                grupo.style.display = 'block';
                label.innerText = 'SIAPE';
                input.required = true;
                input.placeholder = 'Digite o SIAPE';
            } else {
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
        function addToCartFromModal() { showToast('Item adicionado ao carrinho!', 'success'); closeProductModal(); }

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
        function confirmOrder() { 
            showToast('Pedido Finalizado com sucesso!', 'success'); 
            closeCheckoutModal(); 
            switchAppView('view-pedidos', document.getElementById('nav-pedidos-btn')); 
        }

        function openRenewalModal() { document.getElementById('modalRenovacao').style.display = 'flex'; }
        function closeRenewalModal() { document.getElementById('modalRenovacao').style.display = 'none'; }
        function confirmRenewal() { showToast('Renovação solicitada com sucesso!', 'success'); closeRenewalModal(); }

        function switchLabFilter(targetId, btn) {
            document.querySelectorAll('#tab-pedidos-lab .btn-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('#tab-pedidos-lab .orders-container').forEach(c => c.style.display = 'none');
            document.getElementById(targetId).style.display = 'block';
        }

        function aprovarPedido(id) { if(confirm('Aprovar pedido #' + id + '?')) { showToast('Pedido Aprovado!', 'success'); } }
        function devolverPedido(id) { if(confirm('Confirmar devolução do pedido #' + id + '?')) { showToast('Devolução registrada!', 'success'); } }
        
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

        function submitFormLab(e, returnTabId, msg) {
            e.preventDefault();
            showToast(msg, 'success');
            switchLabTab(returnTabId, 'Gerenciamento');
        }

        // ================= RETORNO DA ABA APÓS RECARREGAR =================
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
