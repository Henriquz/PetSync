<?php
include '../config.php';
include 'check_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

try {
    // Busca todos os colaboradores ativos
    $query = "SELECT id, nome, email, telefone FROM usuarios WHERE is_colaborador = 1 AND is_active = 1 ORDER BY nome ASC";
    $result = $mysqli->query($query);
    
    $colaboradores = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $colaboradores[] = $row;
        }
    }
    
    echo json_encode(['sucesso' => true, 'colaboradores' => $colaboradores]);
    
} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>

