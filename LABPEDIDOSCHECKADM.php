<?php
require_once 'pedidos_helpers.php';
estilosPedidos();

if (!function_exists('lpTipoLabel')) {
    function lpTipoLabel($t) { return ['padrao' => 'Comum', 'resp' => 'Responsável', 'admin' => 'Admin'][$t] ?? ucfirst($t); }

    function lpCard($p, $itens, $secao) {
        $info     = statusPedidoInfo($p['Status_pedido']);
        $id       = (int)$p['id_pedido'];
        $cod      = htmlspecialchars($p['codigo_pedido'] ?: $id);
        $expresso = (int)$p['Retirada_expressa'] === 1 && $secao !== 'historico';
        $linhas = []; $js = [];
        foreach ($itens as $it) {
            $linhas[] = htmlspecialchars($it['Nome']) . ' (x' . (int)$it['quantidade'] . ')';
            $js[] = ['id_item' => (int)$it['id_item'], 'nome' => $it['Nome'], 'quantidade' => (int)$it['quantidade']];
        }
        $dataItens = htmlspecialchars(json_encode($js), ENT_QUOTES, 'UTF-8');
?>
        <div class="order-card <?php echo $expresso ? 'express' : ''; ?>" id="lp-card-<?php echo $id; ?>" data-itens="<?php echo $dataItens; ?>" data-codigo="<?php echo $cod; ?>" data-user="<?php echo htmlspecialchars($p['nome_user']); ?>" data-ret="<?php echo fmtData($p['Data_ret']); ?>">
            <?php if ($expresso): ?><div class="express-badge"><i class="bi bi-lightning-charge-fill"></i> Retirada Expressa</div><?php endif; ?>
            <div class="order-header" <?php echo $expresso ? 'style="border-bottom:none; padding-bottom:0;"' : ''; ?>>
                <span class="order-code">#<?php echo $cod; ?></span>
                <span class="order-user">Usuário: <?php echo htmlspecialchars($p['nome_user']); ?> (<?php echo lpTipoLabel($p['Tipo_user']); ?>)</span>

                <?php if ($secao === 'pendentes'): ?>
                    <div class="action-buttons">
                        <button class="btn-action approve" onclick="lpAprovar(<?php echo $id; ?>)"><i class="bi bi-check-lg"></i> Aprovar</button>
                        <button class="btn-action delete" onclick="lpAbrirRecusa(<?php echo $id; ?>, '<?php echo $cod; ?>')"><i class="bi bi-x-lg"></i> Recusar</button>
                    </div>
                <?php elseif ($secao === 'andamento'): ?>
                    <div class="status-selector">
                        <label>Status:</label>
                        <select id="lp-status-<?php echo $id; ?>" style="padding:6px; border-radius:6px; border:1px solid #ccc;">
                            <option value="Separacao" <?php echo $p['Status_pedido'] === 'Separacao' ? 'selected' : ''; ?>>Em Separação</option>
                            <option value="Pronto" <?php echo $p['Status_pedido'] === 'Pronto' ? 'selected' : ''; ?>>Pronto para Retirada</option>
                            <option value="Retirado" <?php echo $p['Status_pedido'] === 'Retirado' ? 'selected' : ''; ?>>Retirado (Em uso)</option>
                        </select>
                        <button class="btn-primary-action" style="padding:6px 12px; font-size:0.9rem;" onclick="lpAtualizarStatus(<?php echo $id; ?>)"><i class="bi bi-arrow-repeat"></i> Atualizar</button>
                        <?php if ($p['Status_pedido'] === 'Retirado'): ?>
                            <button class="btn-primary-action" style="padding:6px 12px; font-size:0.9rem; background:#10ac84;" onclick="lpAbrirDevolucao(<?php echo $id; ?>)"><i class="bi bi-arrow-counterclockwise"></i> Devolver</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="status-selector"><span class="status-tag <?php echo $info[1]; ?>"><?php echo $info[2]; ?></span></div>
                <?php endif; ?>
            </div>

            <div class="order-details" style="margin-top:10px; border-top:1px dashed #eee; padding-top:10px;">
                <p><strong>Itens:</strong> <?php echo implode(', ', $linhas); ?></p>
                <p>Data do Pedido: <?php echo fmtData($p['Data_pedido']); ?></p>
                <?php if ($secao !== 'historico' || $p['Status_pedido'] === 'Finalizado'): ?>
                    <p>
                        <?php if ($p['Status_pedido'] === 'Finalizado'): ?>
                            Devolvido em: <?php echo fmtData($p['Data_devolvida']); ?> (prazo era <?php echo fmtData($p['Data_dev']); ?>)
                        <?php else: ?>
                            Previsão de Devolução: <?php echo fmtData($p['Data_dev']); ?><?php echo $p['Status_pedido'] === 'Retirado' ? '' : ' (conta a partir da retirada)'; ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if ((int)$p['Prazo_dinamico'] === 1 && !empty($p['Justificativa'])): ?>
                    <p class="pedido-obs"><strong>Prazo dinâmico — justificativa:</strong> <?php echo htmlspecialchars($p['Justificativa']); ?></p>
                <?php endif; ?>
                <?php if (in_array($p['Status_pedido'], ['Recusado', 'Cancelado'], true)): ?>
                    <p class="pedido-obs" style="color:#dc3545;"><strong><?php echo $info[2]; ?>:</strong> <?php echo htmlspecialchars($p['Justificativa_recusa'] ?? ''); ?></p>
                <?php endif; ?>
                <?php if ($p['Status_pedido'] === 'Finalizado'): foreach ($itens as $it): $r = resumoDevolucaoItem($it); if ($r): ?>
                    <p class="pedido-obs"><strong><?php echo htmlspecialchars($it['Nome']); ?>:</strong> <?php echo $r; ?></p>
                <?php endif; endforeach; endif; ?>
            </div>
        </div>
<?php
    }
}

$res = $conn->query("SELECT p.*, u.Nome AS nome_user, u.Tipo_user
                     FROM Pedido p JOIN Usuarios u ON u.id_user = p.id_user
                     ORDER BY p.Retirada_expressa DESC, p.id_pedido ASC");
$pendentes = $andamento = $historico = [];
while ($p = $res->fetch_assoc()) {
    $s = $p['Status_pedido'];
    if ($s === 'Pendente') $pendentes[] = $p;
    elseif (in_array($s, ['Separacao', 'Pronto', 'Retirado'], true)) $andamento[] = $p;
    else $historico[] = $p;
}
usort($historico, fn($a, $b) => $b['id_pedido'] <=> $a['id_pedido']);

$itensPorPedido = [];
$ri = $conn->query("SELECT c.*, i.Nome, i.Imagem FROM Contem c JOIN Item i ON i.id_item = c.id_item");
while ($r = $ri->fetch_assoc()) $itensPorPedido[$r['id_pedido']][] = $r;
?>

<div id="tab-pedidos-lab" class="spa-tab" style="display: none;">
    <div class="content-area">
        <div class="filter-buttons">
            <button class="btn-filter active" data-target="lp-pendentes" onclick="lpFiltro('lp-pendentes')"><i class="bi bi-hourglass-split"></i> Pendentes de Aprovação (<?php echo count($pendentes); ?>)</button>
            <button class="btn-filter" data-target="lp-andamento" onclick="lpFiltro('lp-andamento')"><i class="bi bi-box-seam"></i> Em Andamento (<?php echo count($andamento); ?>)</button>
            <button class="btn-filter" data-target="lp-historico" onclick="lpFiltro('lp-historico')"><i class="bi bi-clock-history"></i> Histórico</button>
        </div>

        <div class="orders-container" id="lp-pendentes">
            <h3 class="order-section-title">Aguardando Aprovação</h3>
            <?php if (!$pendentes): ?><p style="color:#666; padding:15px 0;">Nenhum pedido aguardando aprovação.</p><?php endif; ?>
            <?php foreach ($pendentes as $p) lpCard($p, $itensPorPedido[$p['id_pedido']] ?? [], 'pendentes'); ?>
        </div>

        <div class="orders-container" id="lp-andamento" style="display:none;">
            <h3 class="order-section-title">Pedidos Em Andamento (Em Separação / Retirados)</h3>
            <?php if (!$andamento): ?><p style="color:#666; padding:15px 0;">Nenhum pedido em andamento.</p><?php endif; ?>
            <?php foreach ($andamento as $p) lpCard($p, $itensPorPedido[$p['id_pedido']] ?? [], 'andamento'); ?>
        </div>

        <div class="orders-container" id="lp-historico" style="display:none;">
            <h3 class="order-section-title">Pedidos Finalizados, Recusados e Cancelados</h3>
            <?php if (!$historico): ?><p style="color:#666; padding:15px 0;">Nenhum pedido no histórico.</p><?php endif; ?>
            <?php foreach ($historico as $p) lpCard($p, $itensPorPedido[$p['id_pedido']] ?? [], 'historico'); ?>
        </div>
    </div>
</div>

<div id="tab-devolucao" class="spa-tab" style="display: none;">
    <div class="content-area">
        <a href="#" onclick="switchLabTab('tab-pedidos-lab', 'Gerenciamento de Pedidos')" style="color:#666; text-decoration:none; font-size:0.95rem; margin-bottom:15px; display:inline-block;">
            <i class="bi bi-arrow-left"></i> Voltar aos Pedidos
        </a>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="color:var(--primary-color); font-family:'Archivo Black', sans-serif; margin:0; font-size:1.8rem;">Conferência de Devolução</h2>
            <span id="dev-pedido-id" style="background-color:var(--primary-color); color:white; padding:6px 16px; border-radius:20px; font-weight:bold; font-size:0.9rem;">Pedido #---</span>
        </div>

        <div style="background-color:#f8f9fa; border-left:4px solid var(--primary-color); padding:15px; border-radius:0 8px 8px 0; margin-bottom:20px;">
            <p style="margin:0 0 5px 0; color:#444;"><strong>Usuário:</strong> <span id="dev-pedido-user">---</span></p>
            <p style="margin:0; color:#444;"><strong>Data da Retirada:</strong> <span id="dev-pedido-data">---</span></p>
        </div>

        <div class="table-responsive-wrapper" style="border:none; box-shadow:none;">
            <table class="devolucao-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align:center;">Qtd. Emprestada</th>
                        <th style="text-align:center;">Voltam ao Estoque</th>
                        <th style="text-align:center;">Descartar (Defeito)</th>
                        <th>Motivo do Descarte</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody id="dev-tbody"></tbody>
            </table>
        </div>

        <div id="dev-resumo" style="display:flex; gap:25px; flex-wrap:wrap; margin-top:20px; padding:15px; background:#f8f9fa; border-radius:8px; font-weight:600;"></div>

        <div style="display:flex; justify-content:flex-end; gap:15px; margin-top:30px; padding-top:20px; border-top:1px solid #eee;">
            <button class="btn-secondary-action" style="background-color:white; border:1px solid #ccc; color:#555;" onclick="switchLabTab('tab-pedidos-lab', 'Gerenciamento de Pedidos')">Cancelar Devolução</button>
            <button class="btn-primary-action" onclick="lpFinalizarDevolucao()"><i class="bi bi-check2-all"></i> Finalizar e Atualizar Estoque</button>
        </div>
    </div>
</div>

<div id="lp-modal-recusa" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-heading-red"><i class="bi bi-exclamation-triangle"></i> Recusar Pedido</h3>
        <p id="lp-recusa-titulo" style="margin-bottom:15px; color:#555;"></p>
        <div class="modal-input-group">
            <label for="lp-justificativa" style="text-align:left; display:block; margin-bottom:5px; font-weight:bold;">Justificativa da recusa (obrigatória):</label>
            <textarea id="lp-justificativa" rows="4" style="width:100%; border-radius:8px; border:1px solid #ccc; padding:10px; resize:vertical;"></textarea>
            <div id="lp-recusa-erro" style="display:none; color:#dc3545; font-weight:700; font-size:0.8rem; margin-top:5px;">Informe a justificativa (mín. 5 caracteres).</div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:15px; margin-top:25px; width:100%;">
            <button class="btn-cancel" onclick="document.getElementById('lp-modal-recusa').style.display='none'">Cancelar</button>
            <button class="btn-modal-confirm-delete" onclick="lpConfirmarRecusa()">Confirmar Recusa</button>
        </div>
    </div>
</div>

<script>
    let lpPedidoId = null;

    function lpEsc(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function lpFiltro(target) {
        document.querySelectorAll('#tab-pedidos-lab .btn-filter').forEach(b => b.classList.toggle('active', b.dataset.target === target));
        document.querySelectorAll('#tab-pedidos-lab .orders-container').forEach(c => c.style.display = (c.id === target) ? 'block' : 'none');
    }

    function lpRecarregar(msg, tipo) {
        const ativo = document.querySelector('#tab-pedidos-lab .btn-filter.active');
        try {
            sessionStorage.setItem('abrirLab', 'tab-pedidos-lab');
            sessionStorage.setItem('lpFiltro', ativo ? ativo.dataset.target : 'lp-pendentes');
        } catch (e) {}
        recarregarComMsg(msg, tipo, 'FECHECKADM.php');
    }

    async function lpAprovar(id) {
        if (!confirm('Aprovar este pedido e enviá-lo para separação?')) return;
        const r = await apiPedidos({ acao: 'aprovar', id_pedido: id });
        r.ok ? lpRecarregar(r.msg, 'success') : showToast(r.msg, 'error');
    }

    function lpAbrirRecusa(id, codigo) {
        lpPedidoId = id;
        document.getElementById('lp-recusa-titulo').innerText = 'Você está recusando o pedido #' + codigo;
        document.getElementById('lp-justificativa').value = '';
        document.getElementById('lp-recusa-erro').style.display = 'none';
        document.getElementById('lp-modal-recusa').style.display = 'flex';
    }

    async function lpConfirmarRecusa() {
        const just = document.getElementById('lp-justificativa').value.trim();
        if (just.length < 5) { document.getElementById('lp-recusa-erro').style.display = 'block'; return; }
        const r = await apiPedidos({ acao: 'recusar', id_pedido: lpPedidoId, justificativa: just });
        if (r.ok) lpRecarregar(r.msg, 'warning');
        else showToast(r.msg, 'error');
    }

    async function lpAtualizarStatus(id) {
        const novo = document.getElementById('lp-status-' + id).value;
        const r = await apiPedidos({ acao: 'status', id_pedido: id, status: novo });
        r.ok ? lpRecarregar(r.msg, 'success') : showToast(r.msg, 'error');
    }

    function lpAbrirDevolucao(id) {
        const card = document.getElementById('lp-card-' + id);
        const itens = JSON.parse(card.dataset.itens);
        lpPedidoId = id;
        document.getElementById('dev-pedido-id').innerText = 'Pedido #' + card.dataset.codigo;
        document.getElementById('dev-pedido-user').innerText = card.dataset.user;
        document.getElementById('dev-pedido-data').innerText = card.dataset.ret;

        document.getElementById('dev-tbody').innerHTML = itens.map(it => `
            <tr data-id="${it.id_item}" data-q="${it.quantidade}">
                <td><strong>${lpEsc(it.nome)}</strong></td>
                <td style="text-align:center; font-weight:bold;">${it.quantidade}</td>
                <td style="text-align:center;"><input type="number" class="qtd-input lp-est" min="0" max="${it.quantidade}" value="${it.quantidade}" oninput="lpRecalc(this,'est')"></td>
                <td style="text-align:center;"><input type="number" class="qtd-input lp-desc" min="0" max="${it.quantidade}" value="0" oninput="lpRecalc(this,'desc')"></td>
                <td><input type="text" class="lp-motivo" placeholder="Ex.: queimado, quebrado..." disabled style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px;"></td>
                <td class="lp-situacao"><span class="status-ok"><i class="bi bi-check-circle-fill"></i> Ok</span></td>
            </tr>`).join('');
        lpResumo();
        switchLabTab('tab-devolucao', 'Conferência de Devolução');
    }

    function lpRecalc(input, qual) {
        const tr = input.closest('tr');
        const q = parseInt(tr.dataset.q);
        const est = tr.querySelector('.lp-est'), desc = tr.querySelector('.lp-desc'), mot = tr.querySelector('.lp-motivo');
        let e = Math.min(Math.max(parseInt(est.value) || 0, 0), q);
        let d = Math.min(Math.max(parseInt(desc.value) || 0, 0), q);
        if (e + d > q) { if (qual === 'est') e = q - d; else d = q - e; }
        est.value = e; desc.value = d;
        mot.disabled = (d === 0);
        if (d === 0) mot.value = '';

        const falta = q - e - d;
        let html = '';
        if (falta === 0 && d === 0) html = '<span class="status-ok"><i class="bi bi-check-circle-fill"></i> Ok</span>';
        else {
            if (d > 0) html += `<span class="status-falta"><i class="bi bi-trash"></i> ${d} descartado(s)</span><br>`;
            if (falta > 0) html += `<span class="status-falta" style="color:#dc3545;">Não devolvido: ${falta}</span>`;
            if (falta === 0 && d > 0 && e > 0) html += `<span class="status-ok">${e} ao estoque</span>`;
        }
        tr.querySelector('.lp-situacao').innerHTML = html;
        lpResumo();
    }

    function lpResumo() {
        let e = 0, d = 0, f = 0;
        document.querySelectorAll('#dev-tbody tr').forEach(tr => {
            const q = parseInt(tr.dataset.q);
            const ee = parseInt(tr.querySelector('.lp-est').value) || 0;
            const dd = parseInt(tr.querySelector('.lp-desc').value) || 0;
            e += ee; d += dd; f += (q - ee - dd);
        });
        document.getElementById('dev-resumo').innerHTML =
            `<span style="color:#10ac84;"><i class="bi bi-box-seam"></i> Voltam ao estoque: ${e}</span>` +
            `<span style="color:#e67e22;"><i class="bi bi-trash"></i> Descartados: ${d}</span>` +
            `<span style="color:#dc3545;"><i class="bi bi-exclamation-circle"></i> Não devolvidos: ${f}</span>`;
    }

    async function lpFinalizarDevolucao() {
        const itens = [];
        let erro = null, faltas = 0;
        document.querySelectorAll('#dev-tbody tr').forEach(tr => {
            const q = parseInt(tr.dataset.q);
            const e = parseInt(tr.querySelector('.lp-est').value) || 0;
            const d = parseInt(tr.querySelector('.lp-desc').value) || 0;
            const m = tr.querySelector('.lp-motivo').value.trim();
            if (d > 0 && !m) erro = 'Informe o motivo do descarte dos itens com defeito.';
            faltas += (q - e - d);
            itens.push({ id_item: parseInt(tr.dataset.id), estoque: e, descarte: d, motivo: m });
        });
        if (erro) { showToast(erro, 'error'); return; }
        if (faltas > 0 && !confirm(faltas + ' unidade(s) não foram devolvidas nem descartadas e serão registradas como extraviadas. Continuar?')) return;

        const r = await apiPedidos({ acao: 'devolver', id_pedido: lpPedidoId, itens });
        if (r.ok) {
            try { sessionStorage.setItem('lpFiltro', 'lp-historico'); sessionStorage.setItem('abrirLab', 'tab-pedidos-lab'); } catch (e) {}
            recarregarComMsg(r.msg, 'success', 'FECHECKADM.php');
        } else showToast(r.msg, 'error');
    }

    window.addEventListener('DOMContentLoaded', () => {
        const m = document.getElementById('lp-modal-recusa');
        if (m) document.body.appendChild(m);
        let f = null;
        try { f = sessionStorage.getItem('lpFiltro'); sessionStorage.removeItem('lpFiltro'); } catch (e) {}
        if (f) lpFiltro(f);
    });
</script>