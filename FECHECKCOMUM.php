<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'padrao') {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$aba_ativa = "tab-catalogo";

$sql_cat = "SELECT i.*, c.Nome as nome_categoria FROM Item i LEFT JOIN Categoria c ON i.id_cat = c.id_cat ORDER BY i.Nome ASC";
$resultado_itens = $conn->query($sql_cat);

$categorias_disponiveis = $conn->query("SELECT * FROM Categoria ORDER BY Nome ASC");
$categorias_array = [];
if ($categorias_disponiveis) {
    while ($cat = $categorias_disponiveis->fetch_assoc()) {
        $categorias_array[] = $cat;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_conta'])) {
    $aba_ativa = "tab-conta";
    $novo_email = trim($_POST['email']);
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    $stmt_check = $conn->prepare("SELECT Email, Senha FROM Usuarios WHERE id_user = ?");
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $user_db = $stmt_check->get_result()->fetch_assoc();

    try {
        if (!empty($nova_senha)) {
            if ($nova_senha !== $confirma_senha) {
                $_SESSION['msg_erro'] = "As novas senhas não coincidem.";
            } else if (!password_verify($senha_atual, $user_db['Senha'])) {
                $_SESSION['msg_erro'] = "A senha atual está incorreta.";
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE Usuarios SET Email = ?, Senha = ? WHERE id_user = ?");
                $stmt_upd->bind_param("ssi", $novo_email, $senha_hash, $id_usuario);
                $stmt_upd->execute();
                $_SESSION['msg_sucesso'] = "Dados atualizados com sucesso!";
            }
        } else {
            if ($novo_email !== $user_db['Email']) {
                $stmt_upd = $conn->prepare("UPDATE Usuarios SET Email = ? WHERE id_user = ?");
                $stmt_upd->bind_param("si", $novo_email, $id_usuario);
                $stmt_upd->execute();
                $_SESSION['msg_sucesso'] = "E-mail atualizado com sucesso!";
            } else {
                $_SESSION['msg_warning'] = "Nenhuma alteração foi feita.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['msg_erro'] = "Erro: O E-mail digitado já está em uso.";
    }
    header("Location: FECHECKCOMUM.php?aba=tab-conta");
    exit();
}

if (isset($_GET['aba'])) {
    $aba_ativa = $_GET['aba'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno - CHECK</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="FECHECKCOMUMCSS.css?v=<?php echo time(); ?>"> 
</head>
<body>

    <header class="main-header">
        <div class="logo-container-header">
            <img src="LOGOCHECKSEMDESCR.jpg" alt="Logo Check" class="logo-header"> 
        </div>
        
        <div class="search-container" id="global-search-bar">
            <input type="search" id="search-bar" placeholder="Buscar item...">
            <button type="button" class="filter-btn" id="filter-btn" onclick="toggleFilterMenu(event)" title="Filtrar por categoria">
                <i class="bi bi-funnel"></i>
                <span class="filter-btn-label">Categorias</span>
            </button>
            <button type="button" class="search-btn" onclick="aplicarFiltros()">
                <i class="bi bi-search"></i> Buscar
            </button>

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
        
        <div id="tab-catalogo" class="spa-tab" style="<?php echo $aba_ativa === 'tab-catalogo' ? 'display: block;' : 'display: none;'; ?>">
            <div class="item-grid">
                <?php if ($resultado_itens && $resultado_itens->num_rows > 0): ?>
                    <?php while($item = $resultado_itens->fetch_assoc()): ?>
                        <?php 
                            $quantidade = (int)$item['Qntd']; 
                            $indisponivel = ($quantidade <= 0);
                            $imagem_nome = trim($item['Imagem'] ?? '');
                            
                        
                            if (empty($imagem_nome)) {
                                $imagem = 'LOGOCHECKSEMDESCR.jpg';
                            } elseif (file_exists($imagem_nome)) {
                                $imagem = htmlspecialchars($imagem_nome);
                            } elseif (file_exists('uploads/' . $imagem_nome)) {
                                $imagem = 'uploads/' . htmlspecialchars($imagem_nome);
                            } else {
                                $imagem = 'uploads/' . htmlspecialchars($imagem_nome);
                            }
                            
                            $nome_completo = htmlspecialchars($item['Nome']);
                            $categoria = htmlspecialchars($item['nome_categoria'] ?? 'Sem Categoria');
                            $descricao = htmlspecialchars($item['Descricao_Item']);
                        ?>
                        <div class="item-card" onclick="openProductModal(this)" 
                             data-id="<?php echo (int)$item['id_item']; ?>"
                             data-name="<?php echo $nome_completo; ?>" 
                             data-img="<?php echo $imagem; ?>" 
                             data-qty="<?php echo $quantidade; ?>" 
                             data-cat="<?php echo $categoria; ?>"
                             data-cat-id="<?php echo (int)($item['id_cat'] ?? 0); ?>"
                             data-desc="<?php echo $descricao; ?>"
                             style="cursor: pointer;">
                            <div class="item-image-container">
                                <img src="<?php echo $imagem; ?>" alt="<?php echo $nome_completo; ?>" style="width: 100%; height: 100%; object-fit: contain;">
                            </div>
                            <div class="item-info">
                                <strong class="item-name"><?php echo $nome_completo; ?></strong>
                                <span class="item-quantity">Quantidade disponível: <?php echo $quantidade; ?></span>
                            </div>
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <div id="msg-vazia" style="grid-column: 1/-1; text-align: center; padding: 50px 20px; color: #666; width: 100%;">
                        <i class="bi bi-search" style="font-size: 2.5rem; display: block; margin-bottom: 10px; color: #ccc;"></i>
                        Nenhum item corresponde aos critérios de filtragem.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php include 'CARRINHOCHECKCOMUM.php'; ?>
        <?php include 'PEDIDOSCHECKCOMUM.php'; ?>
        <?php include 'CONTACHECKCOMUM.php'; ?>

    </main>

    <div id="productModal" class="modal-overlay" onclick="if(event.target === this) closeModal('productModal')">
        <div class="modal-content large">
            <div class="modal-header-nav" onclick="closeModal('productModal')">
                <i class="bi bi-arrow-left"></i> Voltar ao Catálogo
            </div>
            
            <div class="modal-body-grid">
                <div class="modal-img-col">
                    <img id="modal-img" src="" alt="Detalhe">
                </div>
                
                <div class="modal-info-col">
                    <h2 id="modal-title">Nome</h2>
                    <p class="modal-meta"><strong>Categoria:</strong> <span id="modal-cat">Geral</span></p>
                    <p class="modal-meta"><strong>Disponibilidade:</strong> <span id="modal-stock" class="text-green">0 unidades em estoque</span></p>
                    
                    <hr class="modal-divider">
                    
                    <h3 class="modal-subtitle">Descrição do Item</h3>
                    <p id="modal-desc" class="modal-description">...</p>
                    
                    <div class="modal-actions-row">
                        <div class="qty-group">
                            <label>Quantidade:</label>
                            <input type="number" id="modal-qty" value="1" min="1">
                        </div>
                        
                        <?php if (isset($status_exibicao) && $status_exibicao === 'bloqueado'): ?>
                            <button class="btn-submit disabled" onclick="showToast('Sua conta está bloqueada pelo Administrador.', 'error')">
                                <i class="bi bi-slash-circle"></i> Bloqueado
                            </button>
                        <?php else: ?>
                            <button id="modal-btn-adicionar" class="btn-submit" onclick="addToCartFromModal()">
                                <i class="bi bi-cart-plus"></i> Adicionar ao Carrinho
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="checkoutModal" class="modal-overlay" onclick="if(event.target === this) closeModal('checkoutModal')">
        <div class="modal-content">
            <h3><i class="bi bi-calendar-check"></i> Agendamento do Pedido</h3>
            
            <div class="modal-section" style="margin-top: 20px; text-align: left;">
                <label class="modal-label" style="display:block; font-weight:bold; margin-bottom:10px;">Tipo de Retirada:</label>
                <div style="display: flex; align-items: center; gap: 10px; background: #fff8f8; padding: 12px; border-radius: 8px; border: 1px solid #f5c6cb;">
                    <input type="checkbox" id="retirada-expressa" style="width: 20px; height: 20px; accent-color: #dc3545; cursor:pointer;">
                    <label for="retirada-expressa" style="cursor: pointer; font-weight: 600; color: #dc3545; margin:0;">
                        <i class="bi bi-lightning-charge-fill"></i> Retirada Expressa (Urgente)
                    </label>
                </div>
                <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">* Marque apenas se precisar de separação imediata dos materiais.</p>
            </div>

            <div class="modal-section" style="margin-top: 20px;">
                <label class="modal-label" style="display:block; text-align:left; font-weight:bold; margin-bottom:10px;">Prazo de devolução desejado:</label>
                <div class="days-options" id="devolucaoOptions">
                    <button class="day-btn selected" onclick="selectOption('devolucao', this, '7')">7 Dias</button>
                    <button class="day-btn" onclick="selectOption('devolucao', this, '15')">15 Dias</button>
                    <button class="day-btn" onclick="selectOption('devolucao', this, '30')">30 Dias</button>
                    <button class="day-btn" onclick="selectOption('devolucao', this, 'dinamico')">Dinâmico</button>
                </div>
                
                <div id="container-dinamico-devolucao" style="display: none; margin-top: 15px; text-align: left;">
                    <label class="modal-label">Data Específica:</label>
                    <input type="date" id="data-dinamica-devolucao" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; margin-bottom:10px; outline:none;">
                    <label class="modal-label">Justificativa (Obrigatória):</label>
                    <textarea id="justificativa-devolucao" rows="3" placeholder="Explique a necessidade deste prazo..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; outline:none; resize:none;"></textarea>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%; border-top: 1px solid #eee; padding-top: 15px;">
                <button class="btn-cancel" onclick="closeModal('checkoutModal')">Cancelar</button>
                <button class="btn-submit" onclick="confirmOrder()">Confirmar Pedido</button>
            </div>
        </div>
    </div>

    <div id="modalRenovacao" class="modal-overlay" onclick="if(event.target === this) closeModal('modalRenovacao')">
        <div class="modal-content">
            <h3 style="color:#0f006d;"><i class="bi bi-calendar-event"></i> Renovação</h3>
            <p style="margin: 15px 0;">Escolha o novo prazo de devolução desejado:</p>
            
            <div class="days-options" id="renovacaoOptions" style="margin-bottom: 15px;">
                <button class="day-btn selected" onclick="selectOption('renovacao', this, '7')">7 Dias</button>
                <button class="day-btn" onclick="selectOption('renovacao', this, '15')">15 Dias</button>
                <button class="day-btn" onclick="selectOption('renovacao', this, '30')">30 Dias</button>
                <button class="day-btn" onclick="selectOption('renovacao', this, 'dinamico')">Dinâmico</button>
            </div>
            
            <div id="container-dinamico-renovacao" style="display: none; margin-top: 15px; text-align: left;">
                <label class="modal-label">Nova Data de Devolução:</label>
                <input type="date" id="data-dinamica-renovacao" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; margin-bottom:10px; outline:none;">
                <label class="modal-label">Justificativa (Obrigatória):</label>
                <textarea id="justificativa-renovacao" rows="3" placeholder="Por que o prazo precisa ser estendido até esta data?" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; outline:none; resize:none;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%; border-top: 1px solid #eee; padding-top: 15px;">
                <button class="btn-cancel" onclick="closeModal('modalRenovacao')">Cancelar</button>
                <button class="btn-submit" onclick="confirmRenewal()">Solicitar</button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        // Sistema de Carrinho Dinâmico
        let cart = JSON.parse(localStorage.getItem('cartComum')) || [];

        function saveCart() {
            localStorage.setItem('cartComum', JSON.stringify(cart));
        }

        function addToCart(item) {
            const existingItem = cart.find(i => i.id === item.id);
            if (existingItem) {
                existingItem.qty += item.qty;
            } else {
                cart.push(item);
            }
            saveCart();
            renderCart();
            showToast('Item adicionado ao carrinho!', 'success');
        }

        function removeFromCart(itemId) {
            cart = cart.filter(i => i.id !== itemId);
            saveCart();
            renderCart();
            showToast('Item removido do carrinho', 'warning');
        }

        function updateCartQty(itemId, change) {
            const item = cart.find(i => i.id === itemId);
            if (item) {
                if (change < 0 && item.qty === 1) {
                    if (confirm('Deseja remover este item do carrinho?')) {
                        removeFromCart(itemId);
                    }
                } else {
                    item.qty += change;
                    if (item.qty < 1) item.qty = 1;
                    saveCart();
                    renderCart();
                }
            }
        }

        function renderCart() {
            const cartList = document.querySelector('#tab-carrinho .cart-list');
            if (!cartList) return;

            if (cart.length === 0) {
                cartList.innerHTML = '<p style="text-align:center; padding: 40px; color: #666;">Seu carrinho está vazio.</p>';
                return;
            }

            cartList.innerHTML = cart.map(item => `
                <div class="cart-item" data-id="${item.id}">
                    <img src="${item.img}" alt="${item.name}" class="cart-item-img">
                    <div class="cart-item-info">
                        <strong class="cart-item-name">${item.name}</strong>
                        <span class="cart-item-code">Cód: #${item.code}</span>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn minus" onclick="updateCartQty('${item.id}', -1)">-</button>
                        <input type="text" value="${item.qty}" class="qty-input" readonly>
                        <button class="qty-btn plus" onclick="updateCartQty('${item.id}', 1)">+</button>
                    </div>
                    <button class="remove-btn" title="Remover item" onclick="removeFromCart('${item.id}')"><i class="bi bi-trash"></i></button>
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
            }, 3500); 
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
            const inputBusca = document.getElementById('search-bar');
            const termo = inputBusca ? inputBusca.value.toLowerCase().trim() : '';
            const cards = document.querySelectorAll('.item-card');
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

            const grid = document.querySelector('.item-grid');
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

        function switchTab(tabId, titleText, navId) {
            document.querySelectorAll('.spa-tab').forEach(t => t.style.display = 'none');
            document.getElementById(tabId).style.display = 'block';
            document.getElementById('page-main-title').innerText = titleText;
            
            const searchBar = document.getElementById('global-search-bar');
            if(searchBar) searchBar.style.display = (tabId === 'tab-catalogo') ? 'flex' : 'none';

            document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
            if(navId) document.getElementById(navId).classList.add('active');

            window.scrollTo({ top: 0, behavior: 'smooth' }); 
        }

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

        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
            
            const dropdown = document.getElementById('filter-dropdown');
            const filterBtn = document.getElementById('filter-btn');
            if (dropdown && filterBtn && dropdown.classList.contains('open') && !dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        }

        function openProductModal(el) {
            document.getElementById('modal-img').src = el.getAttribute('data-img');
            document.getElementById('modal-title').innerText = el.getAttribute('data-name');
            document.getElementById('modal-cat').innerText = el.getAttribute('data-cat');
            document.getElementById('modal-desc').innerText = el.getAttribute('data-desc');
            
            const qty = parseInt(el.getAttribute('data-qty'));
            const stockEl = document.getElementById('modal-stock');
            const qtyInput = document.getElementById('modal-qty');
            const btnEl = document.getElementById('modal-btn-adicionar');

            if (qty > 0) {
                stockEl.innerText = qty + ' unidades em estoque';
                stockEl.className = 'text-green';
                qtyInput.value = 1;
                qtyInput.max = qty;
                qtyInput.disabled = false;
                
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.className = 'btn-submit';
                    btnEl.innerHTML = '<i class="bi bi-cart-plus"></i> Adicionar ao Carrinho';
                }
            } else {
                stockEl.innerText = 'Indisponível no momento';
                stockEl.className = 'text-red';
                qtyInput.value = 0;
                qtyInput.disabled = true;
                
                if (btnEl) {
                    btnEl.disabled = true;
                    btnEl.className = 'btn-submit disabled';
                    btnEl.innerHTML = 'Indisponível';
                }
            }
            
            document.getElementById('productModal').style.display = 'flex';
        }

        function addToCartFromModal() {
            const img = document.getElementById('modal-img').src;
            const name = document.getElementById('modal-title').innerText;
            const cat = document.getElementById('modal-cat').innerText;
            const qty = parseInt(document.getElementById('modal-qty').value);
            const card = document.querySelector('.item-card[data-name="' + name + '"]');
            const code = card ? card.getAttribute('data-cat-id') : '000';
            
            const item = {
                id: name,
                img: img,
                name: name,
                code: code,
                qty: qty
            };
            
            addToCart(item);
            closeModal('productModal');
        }

        function addToCartFromCard(card) {
            const img = card.getAttribute('data-img');
            const name = card.getAttribute('data-name');
            const code = card.getAttribute('data-cat-id');
            const qty = 1;
            
            const item = {
                id: name,
                img: img,
                name: name,
                code: code,
                qty: qty
            };
            
            addToCart(item);
        }

        function updateQty(btn, change) {
            const input = btn.parentElement.querySelector('.qty-input');
            let val = parseInt(input.value) + change;
            if (val < 1) val = 1;
            input.value = val;
        }

        let prazosSelecionados = { devolucao: '7', renovacao: '7' }; 

        function selectOption(group, btn, val) {
            const container = document.getElementById(group + 'Options');
            container.querySelectorAll('.day-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            
            prazosSelecionados[group] = val;

            const dinamicoContainer = document.getElementById('container-dinamico-' + group);
            if (dinamicoContainer) {
                dinamicoContainer.style.display = (val === 'dinamico') ? 'block' : 'none';
            }
        }
        
        function openCheckoutModal() { 
            <?php if (isset($status_exibicao) && $status_exibicao === 'bloqueado'): ?>
                showToast('Sua conta está bloqueada pelo Administrador.', 'error');
            <?php else: ?>
                document.getElementById('checkoutModal').style.display = 'flex'; 
            <?php endif; ?>
        }
        
        function confirmOrder() {
            if (prazosSelecionados['devolucao'] === 'dinamico') {
                const data = document.getElementById('data-dinamica-devolucao').value;
                const just = document.getElementById('justificativa-devolucao').value.trim();
                if (!data || !just) {
                    showToast('Data e Justificativa são obrigatórias para prazos dinâmicos!', 'error');
                    return;
                }
            }

            const isExpress = document.getElementById('retirada-expressa').checked;
            if(isExpress) {
                showToast('Pedido Express registrado com sucesso! (Urgente)', 'success');
            } else {
                showToast('Pedido Finalizado com sucesso!', 'success');
            }
            
            closeModal('checkoutModal'); 
            switchTab('tab-pedidos', 'Meus Pedidos', 'nav-pedidos');
        }

        function openRenewalModal() { document.getElementById('modalRenovacao').style.display = 'flex'; }
        
        function confirmRenewal() { 
            if (prazosSelecionados['renovacao'] === 'dinamico') {
                const data = document.getElementById('data-dinamica-renovacao').value;
                const just = document.getElementById('justificativa-renovacao').value.trim();
                if (!data || !just) {
                    showToast('Data e Justificativa são obrigatórias para prazos dinâmicos!', 'error');
                    return;
                }
            }
            showToast(`Solicitação de renovação enviada com sucesso!`, 'success');
            closeModal('modalRenovacao'); 
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderCart();
            const inputBusca = document.getElementById('search-bar');
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

        <?php if ($aba_ativa === "tab-conta"): ?>
            window.addEventListener('DOMContentLoaded', function() {
                switchTab('tab-conta', 'Minha Conta', 'nav-conta');
            });
        <?php endif; ?>
    </script>
</body>
</html>
