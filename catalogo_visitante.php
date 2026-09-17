<?php
require_once 'conexao.php';


$sql_cat = "SELECT i.*, c.Nome as nome_categoria 
            FROM Item i 
            LEFT JOIN Categoria c ON i.id_cat = c.id_cat 
            WHERE (i.status_item = 'ativo' OR i.status_item IS NULL) 
            AND (c.status_cat = 'ativo' OR c.status_cat IS NULL)
            ORDER BY i.Nome ASC";
$resultado_itens = $conn->query($sql_cat);


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
    <title>Catálogo Público - CHECK</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="FECHECKCOMUMCSS.css?v=<?php echo time(); ?>">

    <style>
        .add-to-cart-btn {
            display: none !important;
        }
    </style>
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
            <a href="index.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-text">Sair</span>
            </a>
        </nav>
    </header>
    
    <main class="app-main-container">
        <div class="page-title-container">
            <h1 id="page-main-title">Catálogo de Itens</h1>
        </div>
        
        <div id="tab-catalogo" class="spa-tab" style="display: block;">
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
                             data-stock="<?php echo $quantidade; ?>"
                             style="cursor: pointer;">
                            <div class="item-image-container">
                                <img src="<?php echo $imagem; ?>" alt="<?php echo $nome_completo; ?>" style="width: 100%; height: 100%; object-fit: contain;">
                            </div>
                            <div class="item-info">
                                <strong class="item-name"><?php echo $nome_completo; ?></strong>
                                <span class="item-quantity">Quantidade disponível: <?php echo $quantidade; ?></span>
                            </div>
                            <?php if ($indisponivel): ?>
                                <div class="add-to-cart-btn out-of-stock">
                                    <i class="bi bi-x-lg"></i> Indisponível
                                </div>
                            <?php else: ?>
                                <div class="add-to-cart-btn">
                                    <i class="bi bi-plus"></i> Adicionar
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div id="msg-vazia" style="grid-column: 1/-1; text-align: center; padding: 50px 20px; color: #666; width: 100%;">
                        <i class="bi bi-search" style="font-size: 2.5rem; display: block; margin-bottom: 10px; color: #ccc;"></i>
                        Nenhum item cadastrado no sistema.
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
                            <input type="number" id="modal-qty" value="1" min="1" disabled>
                        </div>
                        
                        <button class="btn-submit disabled">
                            <i class="bi bi-lock"></i> Faça login para reservar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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

            if (qty > 0) {
                stockEl.innerText = qty + ' unidades em estoque';
                stockEl.className = 'text-green';
                qtyInput.value = 1;
                qtyInput.max = qty;
                qtyInput.disabled = true;
            } else {
                stockEl.innerText = 'Indisponível no momento';
                stockEl.className = 'text-red';
                qtyInput.value = 0;
                qtyInput.disabled = true;
            }
            
            document.getElementById('productModal').style.display = 'flex';
        }

        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>
</html>
