<?php
// ======================================================================
// PetSync - Gerenciador de Carrinho (AJAX) v1.0
// ======================================================================

include 'config.php'; // Ajuste o caminho se necessário
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

// Garante que o carrinho exista na sessão
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$action = $_POST['action'] ?? '';
$produto_id = isset($_POST['produto_id']) ? (int)$_POST['produto_id'] : 0;
$quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 1;

if (!$produto_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do produto inválido.']);
    exit;
}

// Busca o produto no banco para verificar o estoque
$stmt = $mysqli->prepare("SELECT estoque FROM produtos WHERE id = ? AND ativo = 1");
$stmt->bind_param('i', $produto_id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produto) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Produto não encontrado ou indisponível.']);
    exit;
}
$estoque_disponivel = (int)$produto['estoque'];

switch ($action) {
    case 'adicionar':
        $qtd_no_carrinho = $_SESSION['carrinho'][$produto_id] ?? 0;
        if (($qtd_no_carrinho + 1) > $estoque_disponivel) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Estoque insuficiente!']);
            exit;
        }
        $_SESSION['carrinho'][$produto_id] = $qtd_no_carrinho + 1;
        $mensagem = 'Produto adicionado ao carrinho!';
        break;

    case 'atualizar_quantidade':
        if ($quantidade <= 0) {
            unset($_SESSION['carrinho'][$produto_id]);
            $mensagem = 'Produto removido do carrinho.';
        } elseif ($quantidade > $estoque_disponivel) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Estoque insuficiente. Apenas ' . $estoque_disponivel . ' unidades disponíveis.']);
            exit;
        } else {
            $_SESSION['carrinho'][$produto_id] = $quantidade;
            $mensagem = 'Quantidade atualizada.';
        }
        break;

    case 'remover':
        unset($_SESSION['carrinho'][$produto_id]);
        $mensagem = 'Produto removido do carrinho.';
        break;

    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação desconhecida.']);
        exit;
}

// Resposta de sucesso
$total_itens = count($_SESSION['carrinho']);
echo json_encode(['sucesso' => true, 'mensagem' => $mensagem, 'total_itens' => $total_itens]);

?>