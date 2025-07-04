<?php
// ======================================================================
// PetSync - AJAX para Confirmar Entrega de Pedido v1.0
// ======================================================================

include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

// 1. Validação da sessão e do método
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}

// 2. Coleta de dados
$id_usuario_logado = $_SESSION['usuario']['id'];
$pedido_id = $_POST['pedido_id'] ?? 0;

if (empty($pedido_id)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do pedido não fornecido.']);
    exit;
}

// 3. Lógica de atualização
try {
    // Primeiro, verifica se o pedido realmente pertence ao usuário logado (SEGURANÇA)
    $stmt_check = $mysqli->prepare("SELECT id FROM pedidos WHERE id = ? AND usuario_id = ?");
    $stmt_check->bind_param("ii", $pedido_id, $id_usuario_logado);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 1) {
        // Se o pedido pertence ao usuário, atualiza o status para 'Concluído'
        $stmt_update = $mysqli->prepare("UPDATE pedidos SET status = 'Concluído' WHERE id = ?");
        $stmt_update->bind_param("i", $pedido_id);
        
        if ($stmt_update->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Pedido confirmado com sucesso!']);
        } else {
            throw new Exception('Falha ao atualizar o status do pedido.');
        }
        $stmt_update->close();
    } else {
        throw new Exception('Pedido não encontrado ou não pertence a este usuário.');
    }
    $stmt_check->close();

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
?>