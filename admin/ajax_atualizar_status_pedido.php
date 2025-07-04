<?php
// ======================================================================
// PetSync - AJAX para Atualizar Status de Pedido (Admin) v1.1
// ======================================================================

include '../config.php'; // Ajuste o caminho para o config
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

// 1. Validação de sessão e permissão de admin
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Apenas administradores.']);
    exit;
}

// 2. Validação dos dados recebidos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$pedido_id = $_POST['pedido_id'] ?? 0;
$novo_status = $_POST['novo_status'] ?? '';
// MODIFICADO: Removido "Em Separação" da lista de status válidos
$status_permitidos = ['Pendente', 'Confirmado', 'Enviado', 'Concluído', 'Cancelado'];

if (empty($pedido_id) || !in_array($novo_status, $status_permitidos)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit;
}

// 3. Lógica principal (inalterada, mas agora não aceitará 'Em Separação')
$mysqli->begin_transaction();

try {
    // ... (o restante da lógica para atualizar estoque e status permanece o mesmo)
    $stmt_status_atual = $mysqli->prepare("SELECT status FROM pedidos WHERE id = ?");
    $stmt_status_atual->bind_param("i", $pedido_id);
    $stmt_status_atual->execute();
    $result = $stmt_status_atual->get_result();
    if($result->num_rows == 0) throw new Exception("Pedido não encontrado.");
    $status_atual = $result->fetch_assoc()['status'];
    $stmt_status_atual->close();

    if ($novo_status === 'Cancelado' && !in_array($status_atual, ['Concluído', 'Cancelado'])) {
        $stmt_itens = $mysqli->prepare("SELECT produto_id, quantidade FROM pedido_itens WHERE pedido_id = ?");
        $stmt_itens->bind_param("i", $pedido_id);
        $stmt_itens->execute();
        $itens_do_pedido = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_itens->close();

        $stmt_estoque = $mysqli->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ?");
        foreach ($itens_do_pedido as $item) {
            $stmt_estoque->bind_param("ii", $item['quantidade'], $item['produto_id']);
            $stmt_estoque->execute();
        }
        $stmt_estoque->close();
    }
    
    $stmt_update = $mysqli->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $novo_status, $pedido_id);
    $stmt_update->execute();
    $stmt_update->close();

    $mysqli->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => "Status do pedido #$pedido_id atualizado para $novo_status."]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
?>