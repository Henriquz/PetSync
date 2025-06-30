<?php
// ======================================================================
// PetSync - AJAX Endpoint v1.2 (Versão Robusta com Output Buffering)
// ======================================================================

// Inicia o buffer de saída para capturar qualquer saída indesejada (erros de PHP, etc.)
ob_start();

// Força a exibição de erros (para depuração, se necessário), mas eles serão capturados pelo buffer
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclui a configuração do banco de dados
include 'config.php';

// Inicia a sessão apenas se não houver uma ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SEGURANÇA: Garante que apenas um administrador logado pode usar este endpoint.
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) {
    ob_end_clean(); // Limpa o buffer antes de sair
    http_response_code(403); // Código de erro "Proibido"
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Acesso não autorizado. A sessão de admin não foi encontrada.']);
    exit;
}

// 2. VALIDAÇÃO: Garante que um ID de cliente válido foi fornecido na URL.
$cliente_id = $_GET['cliente_id'] ?? null;
if (!$cliente_id || !is_numeric($cliente_id)) {
    ob_end_clean(); // Limpa o buffer antes de sair
    http_response_code(400); // Código de erro "Requisição Inválida"
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'ID de cliente inválido.']);
    exit;
}

// Inicializa a resposta
$response = [
    'pets' => [],
    'enderecos' => [],
];

try {
    // 3. BUSCA DE DADOS NO BANCO
    $stmt_pets = $mysqli->prepare("SELECT * FROM pets WHERE dono_id = ? ORDER BY nome ASC");
    $stmt_pets->bind_param("i", $cliente_id);
    $stmt_pets->execute();
    $response['pets'] = $stmt_pets->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_pets->close();

    $stmt_enderecos = $mysqli->prepare("SELECT * FROM enderecos WHERE usuario_id = ? ORDER BY id ASC");
    $stmt_enderecos->bind_param("i", $cliente_id);
    $stmt_enderecos->execute();
    $response['enderecos'] = $stmt_enderecos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_enderecos->close();

} catch (Exception $e) {
    ob_end_clean(); // Limpa o buffer em caso de exceção
    http_response_code(500); // Erro interno do servidor
    header('Content-Type: application/json');
    // Não exponha o erro detalhado ao usuário final em produção
    echo json_encode(['erro' => 'Ocorreu um erro no servidor ao buscar os dados.', 'debug_info' => $e->getMessage()]);
    exit;
}

// 4. LIMPEZA E RETORNO
// Limpa qualquer saída que possa ter sido gerada (avisos, espaços, etc.)
ob_end_clean();

// Define o cabeçalho como JSON e envia a resposta limpa.
header('Content-Type: application/json');
echo json_encode($response);
exit;