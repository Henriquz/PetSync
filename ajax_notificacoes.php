<?php
// ajax_notificacoes.php - Versão Corrigida
include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$id_usuario_logado = $_SESSION['usuario']['id'];
$action = $_REQUEST['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'get_notificacoes') {
    $stmt_unread_count = $mysqli->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0 AND visivel = 1");
    $stmt_unread_count->bind_param("i", $id_usuario_logado);
    $stmt_unread_count->execute();
    $unread_count = $stmt_unread_count->get_result()->fetch_row()[0];
    $stmt_unread_count->close();

    // ==========================================================
    // A CORREÇÃO ESTÁ AQUI: Adicionamos 'imagem_url' à consulta SQL
    // ==========================================================
    $stmt_notif = $mysqli->prepare("SELECT id, mensagem, link, data_criacao, lida, tipo, imagem_url 
                                    FROM notificacoes 
                                    WHERE usuario_id = ? AND visivel = 1 
                                    ORDER BY data_criacao DESC LIMIT 10");
    $stmt_notif->bind_param("i", $id_usuario_logado);
    $stmt_notif->execute();
    $notificacoes = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_notif->close();

    echo json_encode(['unread_count' => $unread_count, 'notificacoes' => $notificacoes]);

} elseif ($action === 'marcar_uma_lida') {
    $notificacao_id = $_POST['notificacao_id'] ?? 0;
    if ($notificacao_id > 0) {
        $stmt = $mysqli->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ? AND lida = 0");
        $stmt->bind_param("ii", $notificacao_id, $id_usuario_logado);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        echo json_encode(['sucesso' => true, 'updated' => $affected_rows > 0]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'ID da notificação inválido.']);
    }

} elseif ($action === 'limpar_lidas') {
    $stmt = $mysqli->prepare("UPDATE notificacoes SET visivel = 0 WHERE usuario_id = ? AND lida = 1 AND visivel = 1");
    $stmt->bind_param("i", $id_usuario_logado);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    echo json_encode(['sucesso' => true, 'cleared_count' => $affected_rows]);
    
} else {
    echo json_encode(['erro' => 'Ação inválida.']);
}
?>