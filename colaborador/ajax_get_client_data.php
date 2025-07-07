<?php
include '../config.php';
include 'check_colaborador.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (isset($_GET['cliente_id'])) {
    $cliente_id = $_GET['cliente_id'];

    // Buscar dados do cliente
    // Alterado: Apenas verifica se não é admin, permitindo que colaboradores busquem qualquer usuário que não seja admin.
    $stmt_cliente = $mysqli->prepare("SELECT id, nome, email, telefone FROM usuarios WHERE id = ? AND is_admin = 0");
    $stmt_cliente->bind_param("i", $cliente_id);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    $cliente_data = $result_cliente->fetch_assoc();
    $stmt_cliente->close();

    if ($cliente_data) {
        // Buscar pets do cliente
        $stmt_pets = $mysqli->prepare("SELECT id, nome, especie, raca, data_nascimento FROM pets WHERE dono_id = ?");
        $stmt_pets->bind_param("i", $cliente_id);
        $stmt_pets->execute();
        $pets_data = $stmt_pets->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_pets->close();

        // Buscar endereços do cliente
        $stmt_enderecos = $mysqli->prepare("SELECT id, rua, numero, complemento, bairro, cidade, estado, cep FROM enderecos WHERE usuario_id = ?");
        $stmt_enderecos->bind_param("i", $cliente_id);
        $stmt_enderecos->execute();
        $enderecos_data = $stmt_enderecos->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_enderecos->close();

        $response['success'] = true;
        $response['cliente'] = $cliente_data;
        $response['pets'] = $pets_data;
        $response['enderecos'] = $enderecos_data;
    } else {
        $response['message'] = 'Cliente não encontrado ou não é um cliente válido.';
    }
} else {
    $response['message'] = 'ID do cliente não fornecido.';
}

echo json_encode($response);
?>

