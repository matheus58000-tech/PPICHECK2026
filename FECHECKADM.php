<?php
ob_start(); 
session_start();
require_once 'conexao.php'; 

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'resp')) {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$aba_ativa = "view-catalogo"; 
$sub_aba_ativa = "";

$msg_sucesso_pedido = $_SESSION['sucesso_pedido'] ?? '';
$msg_erro_pedido = $_SESSION['erro_pedido'] ?? '';
unset($_SESSION['sucesso_pedido'], $_SESSION['erro_pedido']);

$categorias_disponiveis = $conn->query("SELECT * FROM Categoria WHERE status_cat = 'ativo' OR status_cat IS NULL ORDER BY Nome ASC");
$categorias_array = [];
if ($categorias_disponiveis) {
    while ($cat = $categorias_disponiveis->fetch_assoc()) {
        $categorias_array[] = $cat;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do administrador - CHECK</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="FECHECKADMCSS.css?v=<?php echo time(); ?>"> 

    <style>
        .main-nav .nav-dropdown .dropdown-content {
            display: flex !important; 
            flex-direction: column;
            visibility: hidden;
            opacity: 0;
            pointer-events: none; 
            transition: visibility 0s 0.5s, opacity 0.2s linear 0.3s;
        }
        .main-nav .nav-dropdown:hover .dropdown-content {
            visibility: visible;
            opacity: 1;
            pointer-events: auto; 
            transition: visibility 0s 0s, opacity 0.2s linear 0s;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo-container-header">
            <img src="LOGOCHECKSEMDESCR.jpg" alt="Logo Check" class="logo-header"> 
        </div>
        
        <div class="search-container" id="global-search" style="display: none;">
            <input type="search" id="input-busca" placeholder="Buscar item...">
            <button type="button" class="filter-btn" id="filter-btn" onclick="toggleFilterMenu(event)" title="Filtrar por categoria">
                <i class="bi bi-funnel"></i>
                <span class="filter-btn-label">Categorias</span>
            </button>
            <button type="button" class="search-btn" onclick="aplicarFiltros()"><i class="bi bi-search"></i> Buscar</button>

            <div id="filter-dropdown" class="filter-dropdown">
                <div class="filter-dropdown-header">
                    <span>Filtrar por Categoria</span>
                    <button type="button" class="filter-close-btn" onclick="toggleFilterMenu(event)" aria-label="Fechar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="filter-options">
                    <button type="button" class="filter-option active" data-cat-id="" onclick="selecionarCategoria('', 'Todas')">
                        <i class="bi bi-grid"></i> Todas as Categorias
                    </button>
                    <?php foreach ($categorias_array as $cat): ?>
                        <button type="button" class="filter-option" data-cat-id="<?php echo (int)$cat['id_cat']; ?>" onclick="selecionarCategoria('<?php echo (int)$cat['id_cat']; ?>', '<?php echo htmlspecialchars($cat['Nome'], ENT_QUOTES); ?>')">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($cat['Nome']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
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
                    <a href="#" onclick="navigateToLab('tab-categorias', 'Gerenciamento de Categorias')"><i class="bi bi-tags"></i> Categorias</a>
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
        <div class="page-header-row">
            <h2>Catálogo de Itens</h2>
        </div>
        <div class="item-grid">
            <?php
            $sql = "SELECT i.*, c.Nome as nome_categoria 
                    FROM Item i 
                    LEFT JOIN Categoria c ON i.id_cat = c.id_cat 
                    WHERE (i.status_item = 'ativo' OR i.status_item IS NULL) 
                    AND (c.status_cat = 'ativo' OR c.status_cat IS NULL)
                    ORDER BY i.Nome ASC";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0):
                while($item = $result->fetch_assoc()): 
                    $img_db = $item['Imagem'];
                    $img_final = 'item_sem_foto.png'; 

                    if (!empty($img_db)) {
                        if (strpos($img_db, 'uploads/') === false && $img_db !== 'LOGOCHECKSEMDESCR.jpg') {
                            $img_final = "uploads/" . $img_db;
                        } else {
                            $img_final = $img_db;
                        }
                    }

                    $nome_limpo = htmlspecialchars($item['Nome']);
                    $cat_limpa = htmlspecialchars($item['nome_categoria'] ?? 'Sem Categoria');
                    $desc_limpa = htmlspecialchars($item['Descricao_Item'] ?? '');
            ?>

                <div class="item-card" style="position: relative;"
                     onclick="openProductModal(this)"
                     data-id="<?php echo (int)$item['id_item']; ?>"
                     data-name="<?php echo $nome_limpo; ?>"
                     data-img="<?php echo $img_final; ?>"
                     data-qty="<?php echo $item['Qntd']; ?>"
                     data-cat="<?php echo $cat_limpa; ?>"
                     data-cat-id="<?php echo (int)($item['id_cat'] ?? 0); ?>"
                     data-desc="<?php echo $desc_limpa; ?>"
                     data-stock="<?php echo $item['Qntd']; ?>">

                    <div class="item-image-container">
                        <img src="<?php echo $img_final; ?>" alt="<?php echo $nome_limpo; ?>">
                    </div>
                    
                    <div class="item-info">
                        <strong class="item-name"><?php echo $nome_limpo; ?></strong>
                        <span class="item-quantity">Quantidade disponível: <?php echo $item['Qntd']; ?></span>
                    </div>
                    
                    <?php $indisponivel = ((int)$item['Qntd'] <= 0); ?>
                    <?php if ($indisponivel): ?>
                        <div class="add-to-cart-btn out-of-stock" onclick="event.stopPropagation(); openProductModal(this.closest('.item-card'));">
                            <i class="bi bi-x-lg"></i> Indisponível
                        </div>
                    <?php else: ?>
                        <div class="add-to-cart-btn" onclick="event.stopPropagation(); addToCartFromCard(this.closest('.item-card'));">
                            <i class="bi bi-plus"></i> Adicionar
                        </div>
                    <?php endif; ?>
                </div>

            <?php 
                endwhile; 
            else: 
            ?>
                <div id="msg-vazia" style="grid-column: 1/-1; text-align: center; padding: 50px 20px; color: #666; width: 100%;">
                    <i class="bi bi-search" style="font-size: 2.5rem; display: block; margin-bottom: 10px; color: #ccc;"></i>
                    Nenhum item disponível no catálogo.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'CARRINHOCHECKADM.php'; ?>
    <?php include 'PEDIDOSCHECKADM.php'; ?>
    <?php include 'CONTACHECKADM.php'; ?>
    <?php include 'LABCHECKADM.php'; ?>

    <div id="product-modal" class="modal-overlay" onclick="if(event.target === this) closeProductModal()">
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

    <!-- Modal de Checkout do Admin com Formulário Real -->
    <div id="checkoutModal" class="modal-overlay" onclick="if(event.target === this) closeCheckoutModal()">
        <div class="modal-content">
            <h3 class="modal-heading-blue"><i class="bi bi-calendar-check"></i> Agendamento do Pedido</h3>
            
            <form id="form-realizar-pedido-adm" action="processar_pedido.php" method="POST">
                <div id="inputs-carrinho-ocultos-adm"></div>

                <div class="modal-section" style="margin-top: 20px; text-align: left;">
                    <label class="modal-label" style="display:block; font-weight:bold; margin-bottom:10px;">Tipo de Retirada:</label>
                    <div style="display: flex; align-items: center; gap: 10px; background: #fff8f8; padding: 12px; border-radius: 8px; border: 1px solid #f5c6cb;">
                        <input type="checkbox" name="retirada_expressa" value="1" id="retirada-expressa-adm" style="width: 20px; height: 20px; accent-color: #dc3545; cursor:pointer;">
                        <label for="retirada-expressa-adm" style="cursor: pointer; font-weight: 600; color: #dc3545; margin:0;">
                            <i class="bi bi-lightning-charge-fill"></i> Retirada Expressa (Urgente)
                        </label>
                    </div>
                    <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">* Marque apenas se precisar de separação imediata dos materiais.</p>
                </div>

                <div class="modal-section" style="margin-top: 20px;">
                    <label class="modal-label" style="display:block; text-align:left; font-weight:bold; margin-bottom:10px;">Prazo de devolução desejado:</label>
                    <div class="days-options" id="devolucaoAdmOptions">
                        <button type="button" class="day-btn selected" onclick="selectCheckoutOption('devolucaoAdm', this, '7')">7 Dias</button>
                        <button type="button" class="day-btn" onclick="selectCheckoutOption('devolucaoAdm', this, '15')">15 Dias</button>
                        <button type="button" class="day-btn" onclick="selectCheckoutOption('devolucaoAdm', this, '30')">30 Dias</button>
                        <button type="button" class="day-btn" onclick="selectCheckoutOption('devolucaoAdm', this, 'dinamico')">Dinâmico</button>
                    </div>
                    
                    <input type="hidden" name="prazo_dinamico" id="input-prazo-dinamico-adm" value="0">
                    <input type="hidden" name="data_ret" value="<?php echo date('Y-m-d'); ?>">
                    <input type="hidden" name="data_dev" id="input-data-dev-adm" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">

                    <div id="container-dinamico-devolucaoAdm" style="display: none; margin-top: 15px; text-align: left;">
                        <label class="modal-label">Data Específica:</label>
                        <input type="date" id="data-dinamica-devolucaoAdm" onchange="atualizarDataDevAdm(this.value)" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; margin-bottom:10px; outline:none;">
                        <label class="modal-label">Justificativa (Obrigatória):</label>
                        <textarea name="justificativa" id="justificativa-devolucaoAdm" rows="3" placeholder="Explique a necessidade deste prazo..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; outline:none; resize:none;"></textarea>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%; border-top: 1px solid #eee; padding-top: 15px;">
                    <button type="button" class="btn-cancel" onclick="closeCheckoutModal()">Cancelar</button>
                    <button type="button" class="btn-confirm" onclick="confirmOrder()">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalRenovacao" class="modal-overlay" onclick="if(event.target === this) closeRenewalModal()">
        <div class="modal-content">
            <h3 class="modal-heading-blue"><i class="bi bi-calendar-event"></i> Renovação de Empréstimo</h3>
            <p style="margin-bottom:15px; color:#555;">Escolha o novo prazo de devolução:</p>
            
            <div class="days-options" id="renovaOptions" style="margin-bottom: 15px;">
                <button type="button" class="day-btn selected" onclick="selectCheckoutOption('renova', this, '7')">7 Dias</button>
                <button type="button" class="day-btn" onclick="selectCheckoutOption('renova', this, '15')">15 Dias</button>
                <button type="button" class="day-btn" onclick="selectCheckoutOption('renova', this, '30')">30 Dias</button>
                <button type="button" class="day-btn" onclick="selectCheckoutOption('renova', this, 'dinamico')">Dinâmico</button>
            </div>
            
            <div id="container-dinamico-renova" style="display: none; margin-top: 15px; text-align: left;">
                <label class="modal-label">Nova Data de Devolução:</label>
                <input type="date" id="data-dinamica-renova" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; margin-bottom:10px; outline:none;">
                <label class="modal-label">Justificativa (Obrigatória):</label>
                <textarea id="justificativa-renova" rows="3" placeholder="Por que o prazo precisa ser estendido até esta data?" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; outline:none; resize:none;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%; border-top: 1px solid #eee; padding-top: 15px;">
                <button type="button" class="btn-cancel" onclick="closeRenewalModal()">Cancelar</button>
                <button type="button" class="btn-confirm" onclick="confirmRenewal()">Confirmar</button>
            </div>
        </div>
    </div>

    <div id="modalConfirmCart" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 class="modal-heading-red" style="margin-top:0; color: #0f006d;">
                <i class="bi bi-exclamation-triangle"></i> Atenção
            </h3>
            <p id="modal-cart-confirm-msg" style="margin-bottom:15px; color:#555; text-align:center; font-size: 1.1rem;">
                Tem certeza que deseja remover este item do carrinho?
            </p>
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%;">
                <button class="btn-cancel" onclick="fecharModalCart()">Cancelar</button>
                <button class="btn-modal-confirm-delete" id="btn-cart-confirmar" style="background-color: #0f006d; color: white;">Remover</button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        let cart = JSON.parse(localStorage.getItem('cartAdmin')) || [];

        function saveCart() {
            localStorage.setItem('cartAdmin', JSON.stringify(cart));
        }

        function addToCart(item) {
            const existingItem = cart.find(i => i.id_item === item.id_item);
            if (existingItem) {
                existingItem.qty += item.qty;
            } else {
                cart.push(item);
            }
            saveCart();
            renderCart();
            showToast('Item adicionado ao carrinho!', 'success');
        }

        function abrirModalCartConfirm(mensagem, callback) {
            const modal = document.getElementById('modalConfirmCart');
            const msgEl = document.getElementById('modal-cart-confirm-msg');
            const btnConfirmar = document.getElementById('btn-cart-confirmar');

            msgEl.innerText = mensagem;
            modal.style.display = 'flex';

            btnConfirmar.onclick = function() {
                fecharModalCart();
                if (callback) callback();
            };
        }

        function fecharModalCart() {
            const modal = document.getElementById('modalConfirmCart');
            if (modal) modal.style.display = 'none';
        }

        function removeFromCart(itemId) {
            abrirModalCartConfirm('Tem certeza que deseja excluir este item do carrinho?', function() {
                cart = cart.filter(i => i.id_item !== itemId);
                saveCart();
                renderCart();
                showToast('Item removido do carrinho.', 'warning');
            });
        }

        function updateCartQty(itemId, change) {
            const item = cart.find(i => i.id_item === itemId);
            if (item) {
                const newQty = item.qty + change;
                if (change > 0 && newQty > item.stock) {
                    showToast(`Estoque insuficiente! Disponível: ${item.stock} unidades.`, 'error');
                    return;
                }
                if (change < 0 && item.qty === 1) {
                    abrirModalCartConfirm('Deseja remover este item do carrinho?', function() {
                        cart = cart.filter(i => i.id_item !== itemId);
                        saveCart();
                        renderCart();
                        showToast('Item removido do carrinho.', 'warning');
                    });
                } else {
                    item.qty = newQty;
                    if (item.qty < 1) item.qty = 1;
                    saveCart();
                    renderCart();
                }
            }
        }

        function renderCart() {
            const cartList = document.querySelector('#view-carrinho .cart-list');
            if (!cartList) return;

            if (cart.length === 0) {
                cartList.innerHTML = '<p style="text-align:center; padding: 40px; color: #666;">Seu carrinho está vazio.</p>';
                return;
            }

            cartList.innerHTML = cart.map(item => `
                <div class="cart-item" data-id="${item.id_item}" onclick="openCartProductModal(${item.id_item})">
                    <img src="${item.img}" alt="${item.name}" class="cart-item-img">
                    <div class="cart-item-info">
                        <strong class="cart-item-name">${item.name}</strong>
                        <span class="cart-item-code">Cód: #${item.code}</span>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn minus" onclick="event.stopPropagation(); updateCartQty(${item.id_item}, -1)">-</button>
                        <input type="text" value="${item.qty}" class="qty-input" readonly>
                        <button class="qty-btn plus" onclick="event.stopPropagation(); updateCartQty(${item.id_item}, 1)">+</button>
                    </div>
                    <button class="remove-btn" title="Remover item" onclick="event.stopPropagation(); removeFromCart(${item.id_item})"><i class="bi bi-trash"></i></button>
                </div>
            `).join('');
        }

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

        function openProductModal(card) {
            document.getElementById('modal-img').src = card.getAttribute('data-img');
            document.getElementById('modal-title').innerText = card.getAttribute('data-name');
            document.getElementById('modal-cat').innerText = card.getAttribute('data-cat');
            document.getElementById('modal-desc').innerText = card.getAttribute('data-desc');
            
            const idItem = parseInt(card.getAttribute('data-id'));
            const qty = parseInt(card.getAttribute('data-qty'));
            const stockEl = document.getElementById('modal-stock');
            const btnEl = document.querySelector('.modal-add-btn');
            
            document.getElementById('product-modal').setAttribute('data-id-item', idItem);
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
        
        function addToCartFromModal() {
            const modal = document.getElementById('product-modal');
            const idItem = parseInt(modal.getAttribute('data-id-item'));
            const img = document.getElementById('modal-img').src;
            const name = document.getElementById('modal-title').innerText;
            const qty = parseInt(document.getElementById('modal-qty').value);
            const card = document.querySelector(`.item-card[data-id="${idItem}"]`);
            const code = card ? card.getAttribute('data-cat-id') : '000';
            const stock = card ? parseInt(card.getAttribute('data-stock')) : 0;

            const existingItem = cart.find(i => i.id_item === idItem);
            const currentQty = existingItem ? existingItem.qty : 0;

            if (currentQty + qty > stock) {
                showToast(`Estoque insuficiente! Disponível: ${stock} unidades.`, 'error');
                return;
            }

            const item = {
                id_item: idItem,
                img: img,
                name: name,
                code: code,
                qty: qty,
                stock: stock,
                desc: card ? card.getAttribute('data-desc') : ''
            };

            addToCart(item);
            closeProductModal();
        }

        function addToCartFromCard(card) {
            const idItem = parseInt(card.getAttribute('data-id'));
            const img = card.getAttribute('data-img');
            const name = card.getAttribute('data-name');
            const code = card.getAttribute('data-cat-id');
            const stock = parseInt(card.getAttribute('data-stock'));
            const desc = card.getAttribute('data-desc');
            const qty = 1;

            const existingItem = cart.find(i => i.id_item === idItem);
            const currentQty = existingItem ? existingItem.qty : 0;

            if (currentQty + qty > stock) {
                showToast(`Estoque insuficiente! Disponível: ${stock} unidades.`, 'error');
                return;
            }

            const item = {
                id_item: idItem,
                img: img,
                name: name,
                code: code,
                qty: qty,
                stock: stock,
                desc: desc
            };

            addToCart(item);
        }

        function openCartProductModal(itemId) {
            const item = cart.find(i => i.id_item === itemId);
            if (!item) return;

            document.getElementById('modal-img').src = item.img;
            document.getElementById('modal-title').innerText = item.name;
            document.getElementById('modal-cat').innerText = item.code;
            document.getElementById('modal-desc').innerText = item.desc || '';

            const qtyInput = document.getElementById('modal-qty');
            const stockEl = document.getElementById('modal-stock');
            const btnEl = document.querySelector('.modal-add-btn');

            qtyInput.value = item.qty;
            qtyInput.max = item.stock;

            if (item.stock > 0) {
                stockEl.innerText = `${item.stock} unidades em estoque`;
                stockEl.className = 'stock-green';
                qtyInput.disabled = false;

                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.style.backgroundColor = '#0f006d';
                    btnEl.innerHTML = '<i class="bi bi-cart-plus"></i> Atualizar Carrinho';
                    btnEl.onclick = function() {
                        const newQty = parseInt(qtyInput.value);
                        if (newQty > item.stock) {
                            showToast(`Estoque insuficiente! Disponível: ${item.stock} unidades.`, 'error');
                            return;
                        }
                        item.qty = newQty;
                        saveCart();
                        renderCart();
                        showToast('Quantidade atualizada!', 'success');
                        closeProductModal();
                    };
                }
            } else {
                stockEl.innerText = 'Indisponível no momento';
                stockEl.className = 'stock-red';
                qtyInput.value = 0;
                qtyInput.disabled = true;

                if (btnEl) {
                    btnEl.disabled = true;
                    btnEl.style.backgroundColor = '#ccc';
                    btnEl.innerHTML = 'Indisponível';
                }
            }

            document.getElementById('product-modal').style.display = 'flex';
        }

        let prazosAdmSelecionados = { devolucaoAdm: '7', renova: '7' }; 

        function openCheckoutModal(e) { 
            if (e) e.preventDefault();
            if (cart.length === 0) {
                showToast('Seu carrinho está vazio!', 'error');
                return;
            }
            document.getElementById('checkoutModal').style.display = 'flex'; 
        }
        
        function closeCheckoutModal() { document.getElementById('checkoutModal').style.display = 'none'; }
        
        function selectCheckoutOption(group, btn, val) {
            btn.parentElement.querySelectorAll('.day-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            
            prazosAdmSelecionados[group] = val;

            const dinamicoContainer = document.getElementById('container-dinamico-' + group);
            const inputDev = document.getElementById('input-data-dev-adm');
            const inputDinamicoFlag = document.getElementById('input-prazo-dinamico-adm');

            if (group === 'devolucaoAdm') {
                if (val === 'dinamico') {
                    if (dinamicoContainer) dinamicoContainer.style.display = 'block';
                    if (inputDinamicoFlag) inputDinamicoFlag.value = "1";
                } else {
                    if (dinamicoContainer) dinamicoContainer.style.display = 'none';
                    if (inputDinamicoFlag) inputDinamicoFlag.value = "0";

                    let dias = parseInt(val);
                    let dataHoje = new Date();
                    dataHoje.setDate(dataHoje.getDate() + dias);
                    if (inputDev) inputDev.value = dataHoje.toISOString().split('T')[0];
                }
            } else {
                if (dinamicoContainer) {
                    dinamicoContainer.style.display = (val === 'dinamico') ? 'block' : 'none';
                }
            }
        }
        
        function atualizarDataDevAdm(valor) {
            const inputDev = document.getElementById('input-data-dev-adm');
            if (inputDev) inputDev.value = valor;
        }
        
        function confirmOrder() { 
            if (cart.length === 0) {
                showToast('Seu carrinho está vazio!', 'error');
                return;
            }

            if (prazosAdmSelecionados['devolucaoAdm'] === 'dinamico') {
                const data = document.getElementById('data-dinamica-devolucaoAdm').value;
                const just = document.getElementById('justificativa-devolucaoAdm').value.trim();
                if (!data || !just) {
                    showToast('Data e Justificativa são obrigatórias para prazos dinâmicos!', 'error');
                    return;
                }
            }

            const containerOculto = document.getElementById('inputs-carrinho-ocultos-adm');
            containerOculto.innerHTML = '';
            
            cart.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `itens[${item.id_item}]`;
                input.value = item.qty;
                containerOculto.appendChild(input);
            });

            localStorage.removeItem('cartAdmin');
            document.getElementById('form-realizar-pedido-adm').submit();
        }

        function openRenewalModal() { document.getElementById('modalRenovacao').style.display = 'flex'; }
        function closeRenewalModal() { document.getElementById('modalRenovacao').style.display = 'none'; }
        
        function confirmRenewal() { 
            if (prazosAdmSelecionados['renova'] === 'dinamico') {
                const data = document.getElementById('data-dinamica-renova').value;
                const just = document.getElementById('justificativa-renova').value.trim();
                if (!data || !just) {
                    showToast('Data e Justificativa são obrigatórias para prazos dinâmicos!', 'error');
                    return;
                }
            }
            showToast('Renovação solicitada com sucesso!', 'success'); 
            closeRenewalModal(); 
        }

        let categoriaAtiva = '';

        function toggleFilterMenu(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('filter-dropdown');
            dropdown.classList.toggle('open');
        }

        function selecionarCategoria(catId, catNome) {
            categoriaAtiva = catId;
            document.querySelectorAll('.filter-option').forEach(opt => {
                opt.classList.toggle('active', opt.getAttribute('data-cat-id') === catId);
            });
            const filterBtn = document.getElementById('filter-btn');
            if (filterBtn) {
                filterBtn.classList.toggle('has-filter', catId !== '');
                const label = filterBtn.querySelector('.filter-btn-label');
                if (label) label.textContent = catId === '' ? 'Categorias' : catNome;
            }
            aplicarFiltros();
            document.getElementById('filter-dropdown').classList.remove('open');
        }

        function aplicarFiltros() {
            const inputBusca = document.getElementById('input-busca');
            const termo = inputBusca ? inputBusca.value.toLowerCase().trim() : '';
            const cards = document.querySelectorAll('#view-catalogo .item-card');
            let encontrou = false;

            cards.forEach(card => {
                const nome = (card.getAttribute('data-name') || '').toLowerCase();
                const catId = String(card.getAttribute('data-cat-id') || '');
                const matchTexto = !termo || nome.startsWith(termo);
                const matchCat = !categoriaAtiva || catId === String(categoriaAtiva);

                if (matchTexto && matchCat) {
                    card.style.display = 'flex';
                    encontrou = true;
                } else {
                    card.style.display = 'none';
                }
            });

            const grid = document.querySelector('#view-catalogo .item-grid');
            let msg = document.getElementById('msg-vazia');
            if (!encontrou) {
                if (!msg && grid) {
                    msg = document.createElement('p');
                    msg.id = 'msg-vazia';
                    msg.style.cssText = 'grid-column: 1/-1; text-align: center; padding: 40px; color: #666;';
                    msg.innerHTML = '<i class="bi bi-search" style="font-size: 2rem; display:block;"></i> Nenhum item encontrado.';
                    grid.appendChild(msg);
                }
            } else if (msg) {
                msg.remove();
            }
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('filter-dropdown');
            const filterBtn = document.getElementById('filter-btn');
            if (dropdown && filterBtn && dropdown.classList.contains('open') && !dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($msg_sucesso_pedido)): ?>
                showToast(<?php echo json_encode($msg_sucesso_pedido); ?>, 'success');
            <?php endif; ?>

            <?php if (!empty($msg_erro_pedido)): ?>
                showToast(<?php echo json_encode($msg_erro_pedido); ?>, 'error');
            <?php endif; ?>

            const inputBusca = document.getElementById('input-busca');
            if (inputBusca) {
                inputBusca.addEventListener('input', aplicarFiltros);
                inputBusca.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        aplicarFiltros();
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            renderCart();
            <?php if ($aba_ativa === "view-conta"): ?>
                switchAppView('view-conta', document.getElementById('nav-conta-btn'));
            <?php elseif ($aba_ativa === "view-lab"): ?>
                switchAppView('view-lab', document.getElementById('nav-lab-btn'));
                switchLabTab('<?php echo $sub_aba_ativa; ?>', 'Gerenciamento');
            <?php else: ?>
                switchAppView('view-catalogo', document.getElementById('nav-catalogo-btn'));
            <?php endif; ?>
        });
    </script>
</body>
</html>
