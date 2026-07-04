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
                    <option value="recusado">Recusados</option>
                </select>
            </div>
        </div>
    </div>

    <div class="pedidos-list" id="lista-de-pedidos-aluno">
        <div class="pedido-card" data-status="aguardando">
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

        <div class="pedido-card" data-status="producao">
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

        <div class="pedido-card" data-status="recusado">
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

        <div class="pedido-card" data-status="retirado">
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

        <div class="pedido-card" data-status="finalizado">
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
