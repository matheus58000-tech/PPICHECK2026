<?php
session_start();
require_once 'conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json; charset=utf-8');

class RegraNegocio extends Exception {}

function resp($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra));
    exit;
}

function exigirStaff($is_staff) {
    if (!$is_staff) throw new RegraNegocio('Você não tem permissão para esta ação.');
}

function carregarPedido($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM Pedido WHERE id_pedido = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    if (!$p) throw new RegraNegocio('Pedido não encontrado.');
    return $p;
}

function calcularPrazo($in) {
    $p = (string)($in['prazo'] ?? '7');
    if ($p === 'dinamico') {
        $data = $in['data_dinamica'] ?? '';
        $just = trim($in['justificativa'] ?? '');
        $d = DateTime::createFromFormat('Y-m-d', $data);
        if (!$d || $d->format('Y-m-d') !== $data || $data <= date('Y-m-d')) {
            throw new RegraNegocio('Informe uma data de devolução futura e válida.');
        }
        if (mb_strlen($just) < 5) {
            throw new RegraNegocio('A justificativa é obrigatória para prazos dinâmicos.');
        }
        return ['data' => $data, 'dias' => null, 'dinamico' => 1, 'just' => $just];
    }
    if (!in_array($p, ['7', '15', '30'], true)) throw new RegraNegocio('Prazo inválido.');
    $dias = (int)$p;
    return ['data' => date('Y-m-d', strtotime("+$dias days")), 'dias' => $dias, 'dinamico' => 0, 'just' => null];
}

function devolverEstoque($conn, $id_pedido) {
    $stmt = $conn->prepare("UPDATE Item i JOIN Contem c ON c.id_item = i.id_item
                            SET i.Qntd = i.Qntd + c.quantidade WHERE c.id_pedido = ?");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
}

if (!isset($_SESSION['usuario_id'])) resp(false, 'Sessão expirada. Faça login novamente.');

$id_usuario = (int)$_SESSION['usuario_id'];
$tipo       = $_SESSION['usuario_tipo'] ?? '';
$is_staff   = in_array($tipo, ['admin', 'resp'], true);
$in         = json_decode(file_get_contents('php://input'), true) ?: [];
$acao       = $in['acao'] ?? '';
$hoje       = date('Y-m-d');

try {
    switch ($acao) {

        case 'criar':
            $u = $conn->query("SELECT status FROM Usuarios WHERE id_user = $id_usuario")->fetch_assoc();
            if (!$u || $u['status'] === 'bloqueado') {
                throw new RegraNegocio('Sua conta está bloqueada pelo Administrador.');
            }

            $agrupado = [];
            foreach (($in['itens'] ?? []) as $it) {
                $id = (int)($it['id_item'] ?? 0);
                $q  = (int)($it['qty'] ?? 0);
                if ($id > 0 && $q > 0) $agrupado[$id] = ($agrupado[$id] ?? 0) + $q;
            }
            if (empty($agrupado)) throw new RegraNegocio('Seu carrinho está vazio.');

            $prazo    = calcularPrazo($in);
            $expressa = !empty($in['expressa']) ? 1 : 0;

            $conn->begin_transaction();

            $stmtEst = $conn->prepare("UPDATE Item SET Qntd = Qntd - ?
                                       WHERE id_item = ? AND Qntd >= ? AND (status_item = 'ativo' OR status_item IS NULL)");
            foreach ($agrupado as $id => $q) {
                $stmtEst->bind_param("iii", $q, $id, $q);
                $stmtEst->execute();
                if ($stmtEst->affected_rows === 0) {
                    throw new RegraNegocio('Estoque insuficiente ou item indisponível. Atualize o catálogo e revise o carrinho.');
                }
            }

            $stmtP = $conn->prepare("INSERT INTO Pedido (Aprovacao, Status_pedido, Data_dev, Retirada_expressa, Prazo_dinamico, Justificativa, Prazo_dias, id_user)
                                     VALUES (NULL, 'Pendente', ?, ?, ?, ?, ?, ?)");
            $stmtP->bind_param("siisii", $prazo['data'], $expressa, $prazo['dinamico'], $prazo['just'], $prazo['dias'], $id_usuario);
            $stmtP->execute();
            $id_pedido = $conn->insert_id;

            $codigo = date('Y') . '-' . str_pad($id_pedido, 3, '0', STR_PAD_LEFT);
            $stmtC = $conn->prepare("UPDATE Pedido SET codigo_pedido = ? WHERE id_pedido = ?");
            $stmtC->bind_param("si", $codigo, $id_pedido);
            $stmtC->execute();

            $stmtI = $conn->prepare("INSERT INTO Contem (id_pedido, id_item, quantidade) VALUES (?, ?, ?)");
            foreach ($agrupado as $id => $q) {
                $stmtI->bind_param("iii", $id_pedido, $id, $q);
                $stmtI->execute();
            }

            $conn->commit();
            resp(true, 'Pedido criado.', ['codigo' => $codigo]);
            break;

        case 'cancelar':
            $ped = carregarPedido($conn, (int)($in['id_pedido'] ?? 0));
            if ((int)$ped['id_user'] !== $id_usuario) throw new RegraNegocio('Este pedido não é seu.');
            if ($ped['Status_pedido'] !== 'Pendente') throw new RegraNegocio('Só é possível cancelar pedidos em análise.');

            $conn->begin_transaction();
            devolverEstoque($conn, (int)$ped['id_pedido']);
            $stmt = $conn->prepare("UPDATE Pedido SET Status_pedido='Cancelado', Aprovacao='nao', Justificativa_recusa='Cancelado pelo solicitante.' WHERE id_pedido=?");
            $stmt->bind_param("i", $ped['id_pedido']);
            $stmt->execute();
            $conn->commit();
            resp(true, 'Pedido cancelado.');
            break;

        case 'renovar':
            $ped = carregarPedido($conn, (int)($in['id_pedido'] ?? 0));
            if ((int)$ped['id_user'] !== $id_usuario) throw new RegraNegocio('Este pedido não é seu.');
            if ($ped['Status_pedido'] !== 'Retirado') throw new RegraNegocio('Só é possível renovar pedidos retirados.');

            $prazo = calcularPrazo($in);
            $stmt = $conn->prepare("UPDATE Pedido SET Data_dev = ?, Prazo_dinamico = IF(?=1, 1, Prazo_dinamico),
                                    Justificativa = CONCAT_WS(' | ', Justificativa, ?) WHERE id_pedido = ?");
            $just = $prazo['just'] ? ('Renovação: ' . $prazo['just']) : null;
            $stmt->bind_param("sisi", $prazo['data'], $prazo['dinamico'], $just, $ped['id_pedido']);
            $stmt->execute();
            resp(true, 'Empréstimo renovado até ' . date('d/m/Y', strtotime($prazo['data'])) . '.');
            break;

        case 'aprovar':
            exigirStaff($is_staff);
            $ped = carregarPedido($conn, (int)($in['id_pedido'] ?? 0));
            if ($ped['Status_pedido'] !== 'Pendente') throw new RegraNegocio('Este pedido já foi analisado.');
            $stmt = $conn->prepare("UPDATE Pedido SET Aprovacao='sim', Status_pedido='Separacao' WHERE id_pedido=?");
            $stmt->bind_param("i", $ped['id_pedido']);
            $stmt->execute();
            resp(true, 'Pedido aprovado e enviado para separação.');
            break;

        case 'recusar':
            exigirStaff($is_staff);
            $just = trim($in['justificativa'] ?? '');
            if (mb_strlen($just) < 5) throw new RegraNegocio('Informe a justificativa da recusa (mín. 5 caracteres).');
            $ped = carregarPedido($conn, (int)($in['id_pedido'] ?? 0));
            if ($ped['Status_pedido'] !== 'Pendente') throw new RegraNegocio('Este pedido já foi analisado.');

            $conn->begin_transaction();
            devolverEstoque($conn, (int)$ped['id_pedido']);
            $stmt = $conn->prepare("UPDATE Pedido SET Aprovacao='nao', Status_pedido='Recusado', Justificativa_recusa=? WHERE id_pedido=?");
            $stmt->bind_param("si", $just, $ped['id_pedido']);
            $stmt->execute();
            $conn->commit();
            resp(true, 'Pedido recusado.');
            break;

        case 'status':
            exigirStaff($is_staff);
            $novo = $in['status'] ?? '';
            if (!in_array($novo, ['Separacao', 'Pronto', 'Retirado'], true)) throw new RegraNegocio('Status inválido.');
            $ped = carregarPedido($conn, (int)($in['id_pedido'] ?? 0));
            if (!in_array($ped['Status_pedido'], ['Separacao', 'Pronto', 'Retirado'], true)) {
                throw new RegraNegocio('O status deste pedido não pode mais ser alterado.');
            }

            if ($novo === 'Retirado') {
                $dataDev = $ped['Data_dev'];
                if (!empty($ped['Prazo_dias']) && !$ped['Prazo_dinamico']) {
                    $dataDev = date('Y-m-d', strtotime('+' . (int)$ped['Prazo_dias'] . ' days'));
                }
                $stmt = $conn->prepare("UPDATE Pedido SET Status_pedido='Retirado', Data_ret=?, Data_dev=? WHERE id_pedido=?");
                $stmt->bind_param("ssi", $hoje, $dataDev, $ped['id_pedido']);
            } else {
                $stmt = $conn->prepare("UPDATE Pedido SET Status_pedido=?, Data_ret=NULL WHERE id_pedido=?");
                $stmt->bind_param("si", $novo, $ped['id_pedido']);
            }
            $stmt->execute();
            resp(true, 'Status atualizado.');
            break;

        case 'devolver':
            exigirStaff($is_staff);
            $ped = carregarPedido($conn, (int)($in['id_pedido'] ?? 0));
            if ($ped['Status_pedido'] !== 'Retirado') throw new RegraNegocio('Só pedidos retirados podem ser devolvidos.');

            $enviados = [];
            foreach (($in['itens'] ?? []) as $x) $enviados[(int)($x['id_item'] ?? 0)] = $x;

            $conn->begin_transaction();
            $stmt = $conn->prepare("SELECT id_item, quantidade FROM Contem WHERE id_pedido = ? FOR UPDATE");
            $stmt->bind_param("i", $ped['id_pedido']);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $stmtC = $conn->prepare("UPDATE Contem SET qtd_estoque=?, qtd_descartada=?, motivo_descarte=? WHERE id_pedido=? AND id_item=?");
            $stmtI = $conn->prepare("UPDATE Item SET Qntd = Qntd + ? WHERE id_item = ?");

            foreach ($rows as $r) {
                $idi = (int)$r['id_item'];
                $q   = (int)$r['quantidade'];
                if (!isset($enviados[$idi])) throw new RegraNegocio('Confira todos os itens do pedido.');
                $e = max(0, (int)($enviados[$idi]['estoque'] ?? 0));
                $d = max(0, (int)($enviados[$idi]['descarte'] ?? 0));
                $motivo = trim($enviados[$idi]['motivo'] ?? '');
                if ($e + $d > $q) throw new RegraNegocio('Quantidades devolvidas + descartadas excedem o emprestado.');
                if ($d > 0 && $motivo === '') throw new RegraNegocio('Informe o motivo do descarte dos itens com defeito.');
                if ($d === 0) $motivo = null;

                $stmtC->bind_param("iisii", $e, $d, $motivo, $ped['id_pedido'], $idi);
                $stmtC->execute();
                if ($e > 0) {
                    $stmtI->bind_param("ii", $e, $idi);
                    $stmtI->execute();
                }
            }

            $stmtP = $conn->prepare("UPDATE Pedido SET Status_pedido='Finalizado', Data_devolvida=? WHERE id_pedido=?");
            $stmtP->bind_param("si", $hoje, $ped['id_pedido']);
            $stmtP->execute();
            $conn->commit();
            resp(true, 'Devolução registrada e estoque atualizado!');
            break;

        default:
            throw new RegraNegocio('Ação inválida.');
    }
} catch (RegraNegocio $e) {
    try { $conn->rollback(); } catch (Throwable $t) {}
    resp(false, $e->getMessage());
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $t) {}
    resp(false, 'Erro interno ao processar o pedido.');
}