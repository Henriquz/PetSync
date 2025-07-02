<?php
// ajax_notificacoes.php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$id_usuario_logado = $_SESSION['usuario']['id'];
$action = $_GET['action'] ?? '';

if ($action === 'get_notificacoes') {
    // Busca notificações não lidas
    $stmt_unread_count = $mysqli->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0");
    $stmt_unread_count->bind_param("i", $id_usuario_logado);
    $stmt_unread_count->execute();
    $unread_count = $stmt_unread_count->get_result()->fetch_row()[0];
    $stmt_unread_count->close();

    // Busca as 5 últimas notificações
    $stmt_notif = $mysqli->prepare("SELECT id, mensagem, link, data_criacao, lida FROM notificacoes WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 5");
    $stmt_notif->bind_param("i", $id_usuario_logado);
    $stmt_notif->execute();
    $notificacoes = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_notif->close();

    echo json_encode(['unread_count' => $unread_count, 'notificacoes' => $notificacoes]);

} elseif ($action === 'marcar_como_lida') {
    // Marca todas as notificações do usuário como lidas
    $stmt = $mysqli->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND lida = 0");
    $stmt->bind_param("i", $id_usuario_logado);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['sucesso' => true]);
}
?>