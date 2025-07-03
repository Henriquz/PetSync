<?php
// admin/ajax_notificacoes_admin.php
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Validação de segurança: Apenas administradores logados podem acessar.
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$id_admin_logado = $_SESSION['usuario']['id'];
$action = $_REQUEST['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'get_notificacoes') {
    // Busca a contagem de notificações não lidas e visíveis
    $stmt_unread_count = $mysqli->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0 AND visivel = 1");
    $stmt_unread_count->bind_param("i", $id_admin_logado);
    $stmt_unread_count->execute();
    $unread_count = $stmt_unread_count->get_result()->fetch_row()[0];
    $stmt_unread_count->close();
    
    // Busca as 5 mais recentes que ainda são visíveis
    $stmt_notif = $mysqli->prepare("SELECT id, mensagem, link, data_criacao, lida FROM notificacoes WHERE usuario_id = ? AND visivel = 1 ORDER BY data_criacao DESC LIMIT 5");
    $stmt_notif->bind_param("i", $id_admin_logado);
    $stmt_notif->execute();
    $notificacoes = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_notif->close();

    echo json_encode(['unread_count' => $unread_count, 'notificacoes' => $notificacoes]);

} elseif ($action === 'marcar_todas_lidas') {
    // Marca todas as notificações visíveis como lidas
    $stmt = $mysqli->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND lida = 0 AND visivel = 1");
    $stmt->bind_param("i", $id_admin_logado);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['sucesso' => true]);

} elseif ($action === 'limpar_lidas') {
    // INÍCIO: NOVA AÇÃO ADICIONADA
    // Marca todas as notificações JÁ LIDAS do admin como NÃO VISÍVEIS.
    $stmt = $mysqli->prepare("UPDATE notificacoes SET visivel = 0 WHERE usuario_id = ? AND lida = 1 AND visivel = 1");
    $stmt->bind_param("i", $id_admin_logado);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    echo json_encode(['sucesso' => true, 'cleared_count' => $affected_rows]);
    // FIM: NOVA AÇÃO ADICIONADA
    
} else {
    echo json_encode(['erro' => 'Ação inválida.']);
}
?>