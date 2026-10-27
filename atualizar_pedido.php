<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] ?? '') !== 'adm') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pedido = $_POST['id_pedido'] ?? null;
    $novo_status = $_POST['status_pedido'] ?? null;
    $aprovacao = $_POST['aprovacao'] ?? null;

    if (!$id_pedido) {
        $_SESSION['erro_pedido'] = "Pedido não identificado.";
        header("Location: FECHECKADM.php");
        exit();
    }

    $conn->begin_transaction();

    try {

        $stmt_old = $conn->prepare("SELECT Status_pedido FROM Pedido WHERE id_pedido = ?");
        $stmt_old->bind_param("i", $id_pedido);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        if ($res_old->num_rows === 0) {
            throw new Exception("Pedido não encontrado.");
        }
        $pedido_antigo = $res_old->fetch_assoc();
        $stmt_old->close();

        if ($novo_status === 'Cancelado' && $pedido_antigo['Status_pedido'] !== 'Cancelado') {
            $stmt_itens = $conn->prepare("SELECT id_item, quantidade FROM Contem WHERE id_pedido = ?");
            $stmt_itens->bind_param("i", $id_pedido);
            $stmt_itens->execute();
            $res_itens = $stmt_itens->get_result();

            $stmt_est_dev = $conn->prepare("UPDATE Item SET Qntd = Qntd + ? WHERE id_item = ?");
            while ($item = $res_itens->fetch_assoc()) {
                $stmt_est_dev->bind_param("ii", $item['quantidade'], $item['id_item']);
                $stmt_est_dev->execute();
            }
            $stmt_itens->close();
            $stmt_est_dev->close();
        }

        $stmt_update = $conn->prepare("UPDATE Pedido SET Status_pedido = ?, Aprovacao = ? WHERE id_pedido = ?");
        $stmt_update->bind_param("ssi", $novo_status, $aprovacao, $id_pedido);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();

        $_SESSION['sucesso_pedido'] = "Pedido #{$id_pedido} atualizado com sucesso para: {$novo_status}!";
        header("Location: FECHECKADM.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['erro_pedido'] = "Erro ao atualizar o pedido: " . $e->getMessage();
        header("Location: FECHECKADM.php");
        exit();
    }
} else {
    header("Location: FECHECKADM.php");
    exit();
}
?>