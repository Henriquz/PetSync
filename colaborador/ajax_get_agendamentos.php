<?php
include '../config.php';
include 'check_colaborador.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$data_filtro = $_GET['data'] ?? null;
$status_filtro = $_GET['status'] ?? null;
$colaborador_id = $_SESSION['usuario']['id'];

try {
    // Monta a query base
    $query = "
        SELECT 
            a.id,
            a.servico,
            a.data_agendamento,
            a.status,
            a.observacoes,
            a.observacoes_admin,
            a.tipo_entrega,
            a.colaborador_id,
            u.nome as cliente_nome,
            u.telefone as cliente_telefone,
            p.nome as pet_nome,
            p.especie as pet_especie,
            p.raca as pet_raca,
            e.rua as endereco_rua,
            e.numero as endereco_numero,
            e.bairro as endereco_bairro
        FROM agendamentos a
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN pets p ON a.pet_id = p.id
        LEFT JOIN enderecos e ON a.endereco_id = e.id
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';
    
    // Filtros opcionais
    if ($data_filtro) {
        $query .= " AND DATE(a.data_agendamento) = ?";
        $params[] = $data_filtro;
        $types .= 's';
    }
    
    if ($status_filtro && $status_filtro !== 'todos') {
        $query .= " AND a.status = ?";
        $params[] = $status_filtro;
        $types .= 's';
    }
    
    // Mostra apenas agendamentos não cancelados por padrão, ou os atribuídos ao colaborador
    if (!$status_filtro || $status_filtro === 'todos') {
        $query .= " AND (a.status != 'Cancelado' OR a.colaborador_id = ?)";
        $params[] = $colaborador_id;
        $types .= 'i';
    }
    
    $query .= " ORDER BY a.data_agendamento ASC";
    
    $stmt = $mysqli->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $agendamentos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Formata os dados para o frontend
    foreach ($agendamentos as &$agendamento) {
        $agendamento['data_formatada'] = date('d/m/Y', strtotime($agendamento['data_agendamento']));
        $agendamento['hora_formatada'] = date('H:i', strtotime($agendamento['data_agendamento']));
        $agendamento['meu_agendamento'] = ($agendamento['colaborador_id'] == $colaborador_id);
        
        // Formata endereço se for delivery
        if ($agendamento['tipo_entrega'] === 'delivery' && $agendamento['endereco_rua']) {
            $agendamento['endereco_completo'] = $agendamento['endereco_rua'] . ', ' . 
                                               $agendamento['endereco_numero'] . ' - ' . 
                                               $agendamento['endereco_bairro'];
        } else {
            $agendamento['endereco_completo'] = null;
        }
    }
    
    echo json_encode(['sucesso' => true, 'agendamentos' => $agendamentos]);
    
} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>

