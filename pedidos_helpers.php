<?php
function estilosPedidos() {
    static $feito = false;
    if ($feito) return;
    $feito = true; ?>
<style>
    .pedido-card.express { border: 2px solid #dc3545; background-color: #fff8f8; }
    .express-badge { background-color: #dc3545; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: 900; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 12px; animation: pulse-red 1.5s infinite; }
    @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220,53,69,.7); } 70% { box-shadow: 0 0 0 6px rgba(220,53,69,0); } 100% { box-shadow: 0 0 0 0 rgba(220,53,69,0); } }
    .btn-cancelar-pedido { background: transparent; border: 2px solid #dc3545; color: #dc3545; padding: 8px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; gap: 8px; align-items: center; transition: .2s; }
    .btn-cancelar-pedido:hover { background: #dc3545; color: #fff; }
    .pedido-obs { font-size: 0.85rem; color: #666; margin-top: 10px; }
    .item-extra { font-size: 0.8rem; color: #888; display: block; }
</style>
<?php }

function statusPedidoInfo($status) {
    // [data-status do filtro, classe da tag, rótulo, índice na timeline]
    $map = [
        'Pendente'   => ['aguardando', 'status-aguardando', 'Em Análise', 0],
        'Separacao'  => ['producao',   'status-preparacao', 'Em Separação', 1],
        'Pronto'     => ['producao',   'status-preparacao', 'Pronto p/ Retirada', 2],
        'Retirado'   => ['retirado',   'status-pronto',     'Retirado', 3],
        'Finalizado' => ['finalizado', 'status-finalizado', 'Finalizado', 4],
        'Recusado'   => ['recusado',   'status-recusado',   'Recusado', -1],
        'Cancelado'  => ['recusado',   'status-recusado',   'Cancelado', -1],
    ];
    return $map[$status] ?? $map['Pendente'];
}

function fmtData($d) { return $d ? date('d/m/Y', strtotime($d)) : '—'; }

function imagemItem($img) {
    $img = trim($img ?? '');
    if ($img === '') return 'LOGOCHECKSEMDESCR.jpg';
    if (file_exists('uploads/' . $img)) return 'uploads/' . $img;
    if (file_exists($img)) return $img;
    return 'LOGOCHECKSEMDESCR.jpg';
}

function buscarItensDoPedido($conn, $id_pedido) {
    $stmt = $conn->prepare("SELECT c.*, i.Nome, i.Imagem FROM Contem c JOIN Item i ON i.id_item = c.id_item WHERE c.id_pedido = ?");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function resumoDevolucaoItem($it) {
    if ($it['qtd_estoque'] === null) return '';
    $q = (int)$it['quantidade'];
    $e = (int)$it['qtd_estoque'];
    $d = (int)$it['qtd_descartada'];
    $f = $q - $e - $d;
    $partes = [];
    if ($e > 0) $partes[] = "$e voltou ao estoque";
    if ($d > 0) $partes[] = "$d descartado(s)" . (!empty($it['motivo_descarte']) ? ' (' . htmlspecialchars($it['motivo_descarte']) . ')' : '');
    if ($f > 0) $partes[] = "$f não devolvido(s)";
    return implode(' • ', $partes);
}

function renderPedidosUsuario($conn, $id_user) {
    estilosPedidos();
    $stmt = $conn->prepare("SELECT * FROM Pedido WHERE id_user = ? ORDER BY id_pedido DESC");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo '<div style="text-align:center; padding:50px 20px; color:#666;"><i class="bi bi-archive" style="font-size:2.5rem; display:block; margin-bottom:10px; color:#ccc;"></i>Você ainda não fez nenhum pedido.</div>';
        return;
    }

    $passos = [
        ['Análise', 'bi-clipboard-data'], ['Separação', 'bi-gear-wide-connected'], ['Pronto', 'bi-bag-check'],
        ['Retirado', 'bi-box-arrow-up'], ['Devolvido', 'bi-arrow-return-left']
    ];

    while ($p = $res->fetch_assoc()):
        $info    = statusPedidoInfo($p['Status_pedido']);
        $idx     = $info[3];
        $itens   = buscarItensDoPedido($conn, $p['id_pedido']);
        $codigo  = htmlspecialchars($p['codigo_pedido'] ?: $p['id_pedido']);
        $expresso = (int)$p['Retirada_expressa'] === 1 && in_array($p['Status_pedido'], ['Pendente', 'Separacao', 'Pronto'], true);
        $dataPed = $p['Data_pedido'] ?? null;
?>
    <div class="pedido-card <?php echo $expresso ? 'express' : ''; ?>" data-status="<?php echo $info[0]; ?>">
        <div class="pedido-header">
            <div class="id-group">
                <h3>Pedido #<?php echo $codigo; ?></h3>
                <span class="pedido-date">Solicitado em: <?php echo fmtData($dataPed); ?></span>
            </div>
            <span class="status-tag <?php echo $info[1]; ?>"><?php echo $info[2]; ?></span>
        </div>
        <div class="pedido-body">
            <?php if ($expresso): ?>
                <div class="express-badge"><i class="bi bi-lightning-charge-fill"></i> Retirada Expressa</div>
            <?php endif; ?>
            <ul class="item-list">
                <?php foreach ($itens as $it): ?>
                    <li>
                        <div class="item-info-left">
                            <img src="<?php echo htmlspecialchars(imagemItem($it['Imagem'])); ?>" alt="" class="item-thumb">
                            <div class="item-details">
                                <span class="item-nome"><?php echo htmlspecialchars($it['Nome']); ?></span>
                                <span class="item-qtde">Quantidade: <?php echo (int)$it['quantidade']; ?></span>
                                <?php if ($p['Status_pedido'] === 'Finalizado'): ?>
                                    <span class="item-extra"><?php echo resumoDevolucaoItem($it); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="pedido-timeline-area">
            <?php if (in_array($p['Status_pedido'], ['Recusado', 'Cancelado'], true)): ?>
                <div class="prazo-devolucao error">
                    <i class="bi bi-x-circle-fill"></i>
                    <span><strong><?php echo $info[2]; ?>:</strong> <?php echo htmlspecialchars($p['Justificativa_recusa'] ?: 'Sem justificativa informada.'); ?></span>
                </div>
            <?php elseif ($p['Status_pedido'] === 'Retirado'):
                $diff = (int)round((strtotime($p['Data_dev']) - strtotime(date('Y-m-d'))) / 86400);
                if ($diff < 0)       { $cls = 'error';   $ico = 'bi-exclamation-octagon-fill'; $msg = 'Devolução <strong>atrasada há ' . abs($diff) . ' dia(s)</strong> (prazo: ' . fmtData($p['Data_dev']) . ')'; }
                elseif ($diff === 0) { $cls = 'warning'; $ico = 'bi-alarm-fill'; $msg = 'A devolução é <strong>hoje</strong>'; }
                elseif ($diff <= 3)  { $cls = 'warning'; $ico = 'bi-alarm-fill'; $msg = 'Faltam <strong>' . $diff . ' dia(s)</strong> para a devolução (' . fmtData($p['Data_dev']) . ')'; }
                else                 { $cls = 'success'; $ico = 'bi-calendar-check'; $msg = 'Devolver até <strong>' . fmtData($p['Data_dev']) . '</strong> (' . $diff . ' dias)'; }
            ?>
                <div class="prazo-devolucao <?php echo $cls; ?>"><i class="bi <?php echo $ico; ?>"></i><span><?php echo $msg; ?></span></div>
                <div class="pedido-actions">
                    <button class="btn-renovar" onclick="openRenewalModal(<?php echo (int)$p['id_pedido']; ?>)"><i class="bi bi-arrow-repeat"></i> Renovar Empréstimo</button>
                </div>
            <?php elseif ($p['Status_pedido'] === 'Finalizado'): ?>
                <div class="prazo-devolucao success"><i class="bi bi-check-circle-fill"></i><span>Pedido encerrado em <?php echo fmtData($p['Data_devolvida']); ?>.</span></div>
            <?php elseif ($p['Status_pedido'] === 'Pendente'): ?>
                <div class="pedido-actions">
                    <button class="btn-cancelar-pedido" onclick="cancelarPedido(<?php echo (int)$p['id_pedido']; ?>)"><i class="bi bi-x-circle"></i> Cancelar Pedido</button>
                </div>
            <?php endif; ?>

            <div class="timeline-wrapper">
                <?php foreach ($passos as $i => $passo):
                    if ($idx === -1) {
                        $cls = ($i === 0) ? 'cancelled' : '';
                        $icone = ($i === 0) ? 'bi-x-lg' : $passo[1];
                        $label = ($i === 0) ? $info[2] : $passo[0];
                    } else {
                        $cls = ($i < $idx || $idx === 4) ? 'completed' : (($i === $idx) ? 'current' : '');
                        $icone = ($cls === 'completed') ? (($i === 4) ? 'bi-check-all' : 'bi-check-lg') : $passo[1];
                        $label = ($i === 3 && $idx === 3) ? $passo[0] : $passo[0];
                    }
                ?>
                    <div class="step <?php echo $cls; ?>"><div class="step-icon"><i class="bi <?php echo $icone; ?>"></i></div><span class="step-label"><?php echo $label; ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php
    endwhile;
}