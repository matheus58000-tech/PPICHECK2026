<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_user = $_SESSION['usuario_id'];
    $tipo_user = $_SESSION['usuario_tipo'] ?? 'padrao';
    
    $itens_selecionados = $_POST['itens'] ?? []; 
    $retirada_expressa = isset($_POST['retirada_expressa']) ? 1 : 0;
    $prazo_dinamico = isset($_POST['prazo_dinamico']) ? 1 : 0;
    $data_ret = $_POST['data_ret'] ?? null;
    $data_dev = $_POST['data_dev'] ?? null;
    $justificativa = trim($_POST['justificativa'] ?? '');

    if (empty($itens_selecionados)) {
        $_SESSION['erro_pedido'] = "Selecione pelo menos um item para realizar o pedido.";
        header("Location: " . ($tipo_user === 'padrao' ? 'FECHECKCOMUM.php' : 'FECHECKADM.php'));
        exit();
    }

    $conn->begin_transaction();

    try {

        $codigo_pedido = 'CHECK-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $aprovacao = 'Nao';
        $status_pedido = 'Pendente';

    
        $stmt_pedido = $conn->prepare("INSERT INTO Pedido (codigo_pedido, Aprovacao, Status_pedido, Data_ret, Data_dev, Retirada_expressa, Prazo_dinamico, Justificativa, id_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_pedido->bind_param("sssssiisi", $codigo_pedido, $aprovacao, $status_pedido, $data_ret, $data_dev, $retirada_expressa, $prazo_dinamico, $justificativa, $id_user);
        $stmt_pedido->execute();
        $id_pedido = $stmt_pedido->insert_id;
        $stmt_pedido->close();

        $stmt_contem = $conn->prepare("INSERT INTO Contem (id_pedido, id_item, quantidade) VALUES (?, ?, ?)");
        $stmt_estoque_check = $conn->prepare("SELECT Qntd, Nome FROM Item WHERE id_item = ? FOR UPDATE");
        $stmt_estoque_update = $conn->prepare("UPDATE Item SET Qntd = Qntd - ? WHERE id_item = ?");

        foreach ($itens_selecionados as $id_item => $quantidade) {
            $quantidade = intval($quantidade);
            if ($quantidade <= 0) continue;

            $stmt_estoque_check->bind_param("i", $id_item);
            $stmt_estoque_check->execute();
            $res_estoque = $stmt_estoque_check->get_result();
            
            if ($res_estoque->num_rows === 0) {
                throw new Exception("Item selecionado não foi encontrado no sistema.");
            }
            
            $item_data = $res_estoque->fetch_assoc();
            if ($item_data['Qntd'] < $quantidade) {
                throw new Exception("Estoque insuficiente para o item: " . $item_data['Nome'] . " (Disponível: " . $item_data['Qntd'] . ")");
            }

            $stmt_contem->bind_param("iii", $id_pedido, $id_item, $quantidade);
            $stmt_contem->execute();

            $stmt_estoque_update->bind_param("ii", $quantidade, $id_item);
            $stmt_estoque_update->execute();
        }

        $stmt_contem->close();
        $stmt_estoque_check->close();
        $stmt_estoque_update->close();

        $conn->commit();

        $_SESSION['sucesso_pedido'] = "Pedido realizado com sucesso! Código de rastreio: " . $codigo_pedido;
        header("Location: " . ($tipo_user === 'padrao' ? 'FECHECKCOMUM.php' : 'FECHECKADM.php'));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['erro_pedido'] = "Erro ao processar o pedido: " . $e->getMessage();
        header("Location: " . ($tipo_user === 'padrao' ? 'FECHECKCOMUM.php' : 'FECHECKADM.php'));
        exit();
    }
}
?>