<?php require_once 'pedidos_helpers.php'; ?>
<main id="view-pedidos" class="main-view pedidos-main-container" style="display:none;">
    <div class="page-header-row">
        <h2>Meus Pedidos</h2>
        <div class="filter-container">
            <div class="filter-actions">
                <label for="statusFilter" class="filter-label">Filtrar por:</label>
                <select id="statusFilter" class="filter-select" onchange="filterPedidos(this.value)">
                    <option value="todos">Todos</option>
                    <option value="aguardando">Aguardando</option>
                    <option value="producao">Em Produção</option>
                    <option value="retirado">Retirados (Comigo)</option>
                    <option value="finalizado">Finalizados</option>
                    <option value="recusado">Recusados/Cancelados</option>
                </select>
            </div>
        </div>
    </div>

    <div class="pedidos-list" id="lista-de-pedidos-aluno" style="margin-left:0;">
        <?php renderPedidosUsuario($conn, $id_usuario); ?>
    </div>
</main>
