<?php require_once 'pedidos_helpers.php'; ?>
<div id="tab-pedidos" class="spa-tab" style="display: none;">

    <div class="filter-actions" style="margin-bottom: 1.5rem;">
        <label for="statusFilter" class="filter-label">Filtrar por:</label>
        <select id="statusFilter" class="filter-select" onchange="filterPedidos(this.value)">
            <option value="todos">Todos</option>
            <option value="aguardando">Aguardando (Análise)</option>
            <option value="producao">Em Produção</option>
            <option value="retirado">Retirados (Comigo)</option>
            <option value="finalizado">Devolvidos/Finalizados</option>
            <option value="recusado">Recusados/Cancelados</option>
        </select>
    </div>

    <div class="pedidos-list" id="lista-de-pedidos-aluno">
        <?php renderPedidosUsuario($conn, $id_usuario); ?>
    </div>
</div>
