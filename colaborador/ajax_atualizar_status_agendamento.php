<?php
include '../config.php';
include 'check_colaborador.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$agendamento_id = $_POST['agendamento_id'] ?? null;
$novo_status = $_POST['novo_status'] ?? null;
$observacoes_admin = $_POST['observacoes_admin'] ?? '';
$colaborador_id = $_SESSION['usuario']['id'];

// Validações
if (!$agendamento_id || !$novo_status) {
    echo json_encode(['erro' => 'Dados obrigatórios não fornecidos']);
    exit;
}

$status_permitidos = ['Pendente', 'Confirmado', 'Em Andamento', 'Concluído', 'Cancelado'];
if (!in_array($novo_status, $status_permitidos)) {
    echo json_encode(['erro' => 'Status inválido']);
    exit;
}

try {
    // Verifica se o agendamento existe
    $stmt_check = $mysqli->prepare("SELECT id, usuario_id, pet_id, status FROM agendamentos WHERE id = ?");
    $stmt_check->bind_param("i", $agendamento_id);
    $stmt_check->execute();
    $agendamento = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$agendamento) {
        echo json_encode(['erro' => 'Agendamento não encontrado']);
        exit;
    }

    // Atualiza o status do agendamento e vincula ao colaborador
    $stmt_update = $mysqli->prepare("UPDATE agendamentos SET status = ?, observacoes_admin = ?, colaborador_id = ? WHERE id = ?");
    $stmt_update->bind_param("ssii", $novo_status, $observacoes_admin, $colaborador_id, $agendamento_id);
    
    if ($stmt_update->execute()) {
        $stmt_update->close();
        
        // Cria notificação para o cliente baseada no status
        $mensagem_notificacao = '';
        $link_notificacao = 'meus_agendamentos.php';
        
        // Busca o nome do pet para a notificação
        $stmt_pet = $mysqli->prepare("SELECT nome FROM pets WHERE id = ?");
        $stmt_pet->bind_param("i", $agendamento['pet_id']);
        $stmt_pet->execute();
        $nome_pet = $stmt_pet->get_result()->fetch_assoc()['nome'] ?? 'seu pet';
        $stmt_pet->close();
        
        switch ($novo_status) {
            case 'Em Andamento':
                $mensagem_notificacao = "O atendimento para $nome_pet foi iniciado!";
                break;
            case 'Concluído':
                $mensagem_notificacao = "Oba! O atendimento para $nome_pet foi concluído e seu pet já pode ser retirado!";
                break;
            case 'Cancelado':
                $motivo = !empty($observacoes_admin) ? " Motivo: $observacoes_admin" : "";
                $mensagem_notificacao = "Atenção: o agendamento para $nome_pet foi cancelado.$motivo";
                break;
        }
        
        if ($mensagem_notificacao) {
            criar_notificacao($mysqli, $agendamento['usuario_id'], $mensagem_notificacao, $link_notificacao, 'automatica', null);
        }
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado com sucesso']);
    } else {
        echo json_encode(['erro' => 'Erro ao atualizar o status']);
    }
    
} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>

