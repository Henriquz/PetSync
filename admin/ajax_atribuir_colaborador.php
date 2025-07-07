<?php
include '../config.php';
include 'check_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$agendamento_id = $_POST['agendamento_id'] ?? null;
$colaborador_id = $_POST['colaborador_id'] ?? null;

// Validações
if (!$agendamento_id || !$colaborador_id) {
    echo json_encode(['erro' => 'Dados obrigatórios não fornecidos']);
    exit;
}

try {
    // Verifica se o agendamento existe
    $stmt_check = $mysqli->prepare("SELECT id, usuario_id FROM agendamentos WHERE id = ?");
    $stmt_check->bind_param("i", $agendamento_id);
    $stmt_check->execute();
    $agendamento = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$agendamento) {
        echo json_encode(['erro' => 'Agendamento não encontrado']);
        exit;
    }

    // Verifica se o colaborador existe e é ativo
    $stmt_colab = $mysqli->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND is_colaborador = 1 AND is_active = 1");
    $stmt_colab->bind_param("i", $colaborador_id);
    $stmt_colab->execute();
    $colaborador = $stmt_colab->get_result()->fetch_assoc();
    $stmt_colab->close();

    if (!$colaborador) {
        echo json_encode(['erro' => 'Colaborador não encontrado ou inativo']);
        exit;
    }

    // Atualiza o agendamento com o colaborador
    $stmt_update = $mysqli->prepare("UPDATE agendamentos SET colaborador_id = ? WHERE id = ?");
    $stmt_update->bind_param("ii", $colaborador_id, $agendamento_id);
    
    if ($stmt_update->execute()) {
        $stmt_update->close();
        
        // Cria notificação para o colaborador
        $mensagem_colaborador = "Você foi atribuído a um novo agendamento. Verifique sua agenda.";
        criar_notificacao($mysqli, $colaborador_id, $mensagem_colaborador, 'colaborador/agendamentos.php', 'automatica', null);
        
        echo json_encode([
            'sucesso' => true, 
            'mensagem' => 'Colaborador atribuído com sucesso',
            'colaborador_nome' => $colaborador['nome']
        ]);
    } else {
        echo json_encode(['erro' => 'Erro ao atribuir colaborador']);
    }
    
} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>

