<?php
ob_start(); 
session_start();
require_once 'conexao.php'; 

// Segurança: Apenas Admin ou Resp podem aceder
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

    <style>
        /* ============================================================== */
        /* MÁGICA DO MENU: Mantém o Dropdown aberto por um tempinho       */
        /* ============================================================== */
        .main-nav .nav-dropdown .dropdown-content {
            display: flex !important; /* Garante que existe no DOM */
            flex-direction: column;
            visibility: hidden;
            opacity: 0;
            pointer-events: none; /* Ignora cliques quando invisível */
            
            /* Ao tirar o mouse: segura 0.3s, depois apaga em 0.2s */
            transition: visibility 0s 0.5s, opacity 0.2s linear 0.3s;
        }

        .main-nav .nav-dropdown:hover .dropdown-content {
            visibility: visible;
            opacity: 1;
            pointer-events: auto; /* Permite cliques */
            
            /* Ao colocar o mouse: aparece imediatamente */
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
            <button type="button" class="search-btn"><i class="bi bi-search"></i> Buscar</button>
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
        <h2>Catálogo de Itens</h2>
        <div class="item-grid">
            <?php
            $sql = "SELECT i.*, c.Nome as nome_categoria 
                    FROM Item i 
                    INNER JOIN Categoria c ON i.id_cat = c.id_cat 
                    ORDER BY i.Nome ASC";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0):
                while($item = $result->fetch_assoc()): 
                    
                    $img_db = $item['Imagem'];
                    $img_final = 'LOGOCHECKSEMDESCR.jpg'; 

                    if (!empty($img_db)) {
                        if (strpos($img_db, 'uploads/') === false && $img_db !== 'LOGOCHECKSEMDESCR.jpg') {
                            $img_final = "uploads/" . $img_db;
                        } else {
                            $img_final = $img_db;
                        }
                    }

                    $nome_limpo = htmlspecialchars($item['Nome']);
                    $cat_limpa = htmlspecialchars($item['nome_categoria']);
                    $desc_limpa = htmlspecialchars($item['Descricao_Item'] ?? '');
            ?>

                <div class="item-card" 
                     onclick="openProductModal(this)" 
                     data-name="<?php echo $nome_limpo; ?>" 
                     data-img="<?php echo $img_final; ?>" 
                     data-qty="<?php echo $item['Qntd']; ?>" 
                     data-cat="<?php echo $cat_limpa; ?>" 
                     data-desc="<?php echo $desc_limpa; ?>">
                     
                    <div class="item-image-container">
                        <img src="<?php echo $img_final; ?>" alt="<?php echo $nome_limpo; ?>">
                    </div>
                    
                    <div class="item-info">
                        <strong class="item-name"><?php echo $nome_limpo; ?></strong>
                        <span class="item-quantity">Quantidade disponível: <?php echo $item['Qntd']; ?></span>
                    </div>
                    
                    <div class="add-to-cart-btn">
                        <i class="bi bi-plus"></i> Adicionar
                    </div>
                </div>

            <?php 
                endwhile; 
            else: 
            ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 50px;">Nenhum item cadastrado.</p>
            <?php endif; ?>
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
            <h3 class="modal-heading-blue"><i class="bi bi-calendar-check"></i> Agendamento do Pedido</h3>
            
            <div class="modal-section" style="margin-top: 20px; text-align: left;">
                <label class="modal-label" style="display:block; font-weight:bold; margin-bottom:10px;">Tipo de Retirada:</label>
                <div style="display: flex; align-items: center; gap: 10px; background: #fff8f8; padding: 12px; border-radius: 8px; border: 1px solid #f5c6cb;">
                    <input type="checkbox" id="retirada-expressa-adm" style="width: 20px; height: 20px; accent-color: #dc3545; cursor:pointer;">
                    <label for="retirada-expressa-adm" style="cursor: pointer; font-weight: 600; color: #dc3545; margin:0;">
                        <i class="bi bi-lightning-charge-fill"></i> Retirada Expressa (Urgente)
                    </label>
                </div>
                <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">* Marque apenas se precisar de separação imediata dos materiais.</p>
            </div>

            <div class="modal-section" style="margin-top: 20px;">
                <label class="modal-label" style="display:block; text-align:left; font-weight:bold; margin-bottom:10px;">Prazo de devolução desejado:</label>
                <div class="days-options" id="devolucaoAdmOptions">
                    <button class="day-btn selected" onclick="selectCheckoutOption('devolucaoAdm', this, '7')">7 Dias</button>
                    <button class="day-btn" onclick="selectCheckoutOption('devolucaoAdm', this, '15')">15 Dias</button>
                    <button class="day-btn" onclick="selectCheckoutOption('devolucaoAdm', this, '30')">30 Dias</button>
                    <button class="day-btn" onclick="selectCheckoutOption('devolucaoAdm', this, 'dinamico')">Dinâmico</button>
                </div>
                
                <div id="container-dinamico-devolucaoAdm" style="display: none; margin-top: 15px; text-align: left;">
                    <label class="modal-label">Data Específica:</label>
                    <input type="date" id="data-dinamica-devolucaoAdm" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; margin-bottom:10px; outline:none;">
                    <label class="modal-label">Justificativa (Obrigatória):</label>
                    <textarea id="justificativa-devolucaoAdm" rows="3" placeholder="Explique a necessidade deste prazo..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; outline:none; resize:none;"></textarea>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%; border-top: 1px solid #eee; padding-top: 15px;">
                <button class="btn-cancel" onclick="closeCheckoutModal()">Cancelar</button>
                <button class="btn-confirm" onclick="confirmOrder()">Confirmar</button>
            </div>
        </div>
    </div>

    <div id="modalRenovacao" class="modal-overlay">
        <div class="modal-content">
            <h3 class="modal-heading-blue"><i class="bi bi-calendar-event"></i> Renovação de Empréstimo</h3>
            <p style="margin-bottom:15px; color:#555;">Escolha o novo prazo de devolução:</p>
            
            <div class="days-options" id="renovaOptions" style="margin-bottom: 15px;">
                <button class="day-btn selected" onclick="selectCheckoutOption('renova', this, '7')">7 Dias</button>
                <button class="day-btn" onclick="selectCheckoutOption('renova', this, '15')">15 Dias</button>
                <button class="day-btn" onclick="selectCheckoutOption('renova', this, '30')">30 Dias</button>
                <button class="day-btn" onclick="selectCheckoutOption('renova', this, 'dinamico')">Dinâmico</button>
            </div>
            
            <div id="container-dinamico-renova" style="display: none; margin-top: 15px; text-align: left;">
                <label class="modal-label">Nova Data de Devolução:</label>
                <input type="date" id="data-dinamica-renova" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; margin-bottom:10px; outline:none;">
                <label class="modal-label">Justificativa (Obrigatória):</label>
                <textarea id="justificativa-renova" rows="3" placeholder="Por que o prazo precisa ser estendido até esta data?" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; outline:none; resize:none;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; width: 100%; border-top: 1px solid #eee; padding-top: 15px;">
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

        // ================= MODAIS DE PEDIDO (CHECKOUT E RENOVAÇÃO) ADM =================
        let prazosAdmSelecionados = { devolucaoAdm: '7', renova: '7' }; 

        function openCheckoutModal(e) { e.preventDefault(); document.getElementById('checkoutModal').style.display = 'flex'; }
        function closeCheckoutModal() { document.getElementById('checkoutModal').style.display = 'none'; }
        
        function selectCheckoutOption(group, btn, val) {
            btn.parentElement.querySelectorAll('.day-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            
            prazosAdmSelecionados[group] = val;

            const dinamicoContainer = document.getElementById('container-dinamico-' + group);
            if (dinamicoContainer) {
                dinamicoContainer.style.display = (val === 'dinamico') ? 'block' : 'none';
            }
        }
        
        function confirmOrder() { 
            if (prazosAdmSelecionados['devolucaoAdm'] === 'dinamico') {
                const data = document.getElementById('data-dinamica-devolucaoAdm').value;
                const just = document.getElementById('justificativa-devolucaoAdm').value.trim();
                if (!data || !just) {
                    showToast('Data e Justificativa são obrigatórias para prazos dinâmicos!', 'error');
                    return;
                }
            }

            const isExpress = document.getElementById('retirada-expressa-adm').checked;
            if(isExpress) {
                showToast('Pedido Express registrado com sucesso! (Urgente)', 'success');
            } else {
                showToast('Pedido Finalizado com sucesso!', 'success');
            }

            closeCheckoutModal(); 
            switchAppView('view-pedidos', document.getElementById('nav-pedidos-btn')); 
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

        // ================= LÓGICA DE BUSCA INTEGRADA =================
        document.addEventListener('DOMContentLoaded', function() {
            const inputBusca = document.getElementById('input-busca');
            
            if (inputBusca) {
                inputBusca.addEventListener('input', function() {
                    const termo = this.value.toLowerCase().trim();
                    const cards = document.querySelectorAll('.item-card');
                    let encontrou = false;

                    cards.forEach(card => {
                        const nome = card.getAttribute('data-name').toLowerCase();
                        const categoria = card.getAttribute('data-cat').toLowerCase();
                        
                        if (nome.includes(termo) || categoria.includes(termo)) {
                            card.style.display = "flex"; 
                            encontrou = true;
                        } else {
                            card.style.display = "none";
                        }
                    });

                    const grid = document.querySelector('.item-grid');
                    let msg = document.getElementById('msg-vazia');
                    if (!encontrou) {
                        if (!msg) {
                            msg = document.createElement('p');
                            msg.id = 'msg-vazia';
                            msg.style.cssText = "grid-column: 1/-1; text-align: center; padding: 40px; color: #666;";
                            msg.innerHTML = '<i class="bi bi-search" style="font-size: 2rem; display:block;"></i> Nenhum item encontrado.';
                            grid.appendChild(msg);
                        }
                    } else if (msg) {
                        msg.remove();
                    }
                });
            }
        });

        // ================= RETORNO DA ABA APÓS RECARREGAR =================
        document.addEventListener('DOMContentLoaded', () => {
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
