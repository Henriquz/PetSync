<?php
// ======================================================================
// PetSync - Processador de Pedidos v1.0
// ======================================================================

include 'config.php'; // Ajuste o caminho se necessário
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. VERIFICAÇÕES INICIAIS
// ----------------------------------------------------------------------
// Garante que o método é POST e que o usuário está logado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario'])) {
    header('Location: loja.php');
    exit;
}

// Garante que o carrinho não está vazio
if (empty($_SESSION['carrinho'])) {
    header('Location: loja.php');
    exit;
}

// 2. COLETA E VALIDAÇÃO DOS DADOS DO FORMULÁRIO
// ----------------------------------------------------------------------
$id_usuario = $_SESSION['usuario']['id'];
$carrinho = $_SESSION['carrinho'];

$agendamento_id = !empty($_POST['agendamento_id']) ? (int)$_POST['agendamento_id'] : null;
$endereco_id = !empty($_POST['endereco_id']) ? (int)$_POST['endereco_id'] : null;
$forma_pagamento = $_POST['forma_pagamento'] ?? null;

// Validação básica
if (!$forma_pagamento || (!$agendamento_id && !$endereco_id)) {
    $_SESSION['erro_checkout'] = "Por favor, preencha todos os campos obrigatórios.";
    header('Location: checkout.php');
    exit;
}


// 3. PROCESSAMENTO DO PEDIDO NO BANCO DE DADOS
// ----------------------------------------------------------------------

$mysqli->begin_transaction(); // Inicia a transação!

try {
    // A. Re-valida o carrinho (preços e estoque) para evitar fraudes ou erros
    $ids_produtos = array_keys($carrinho);
    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));
    $types = str_repeat('i', count($ids_produtos));
    
    $stmt_valida = $mysqli->prepare("SELECT id, preco, estoque FROM produtos WHERE id IN ($placeholders)");
    $stmt_valida->bind_param($types, ...$ids_produtos);
    $stmt_valida->execute();
    $produtos_db = $stmt_valida->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_valida->close();
    
    // Mapeia para fácil acesso e verifica estoque
    $produtos_info = [];
    foreach($produtos_db as $p) { $produtos_info[$p['id']] = $p; }

    $valor_total_real = 0;
    foreach ($carrinho as $id => $quantidade) {
        if (!isset($produtos_info[$id])) throw new Exception("Produto com ID $id não existe mais.");
        if ($quantidade > $produtos_info[$id]['estoque']) throw new Exception("Estoque insuficiente para o produto ID $id.");
        $valor_total_real += $produtos_info[$id]['preco'] * $quantidade;
    }

    // B. Insere o pedido na tabela `pedidos`
    $stmt_pedido = $mysqli->prepare(
        "INSERT INTO pedidos (usuario_id, endereco_id, agendamento_id, valor_total, forma_pagamento, status) 
         VALUES (?, ?, ?, ?, ?, 'Pendente')"
    );
    $stmt_pedido->bind_param("iiids", $id_usuario, $endereco_id, $agendamento_id, $valor_total_real, $forma_pagamento);
    $stmt_pedido->execute();
    $pedido_id = $mysqli->insert_id; // Pega o ID do pedido recém-criado
    $stmt_pedido->close();

    // --- INÍCIO DO CÓDIGO DE NOTIFICAÇÃO DE VENDA PARA ADMINS ---
    $nome_cliente = htmlspecialchars($_SESSION['usuario']['nome']);
    $result_admins = $mysqli->query("SELECT id FROM usuarios WHERE is_admin = 1");
    $admins = $result_admins ? $result_admins->fetch_all(MYSQLI_ASSOC) : [];
    if (!empty($admins)) {
        $mensagem_notificacao = "Nova venda realizada por $nome_cliente (Pedido #$pedido_id).";
        $link_notificacao = "admin/gerenciar_pedidos.php#pedido-" . $pedido_id;
        foreach ($admins as $admin) {
            criar_notificacao($mysqli, $admin['id'], $mensagem_notificacao, $link_notificacao, 'automatica', null);
        }
    }
    // --- FIM DO CÓDIGO DE NOTIFICAÇÃO ---


    if (!$pedido_id) throw new Exception("Falha ao criar o pedido.");

    // C. Insere cada item na tabela `pedido_itens`
    $stmt_itens = $mysqli->prepare(
        "INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)"
    );
    foreach ($carrinho as $id => $quantidade) {
        $preco_unitario = $produtos_info[$id]['preco'];
        $stmt_itens->bind_param("iiid", $pedido_id, $id, $quantidade, $preco_unitario);
        $stmt_itens->execute();
    }
    $stmt_itens->close();
    
    // D. Atualiza o estoque na tabela `produtos`
    $stmt_estoque = $mysqli->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");
    foreach ($carrinho as $id => $quantidade) {
        $stmt_estoque->bind_param("ii", $quantidade, $id);
        $stmt_estoque->execute();
    }
    $stmt_estoque->close();

    // E. Se tudo deu certo, confirma as alterações
    $mysqli->commit();

    // F. Limpa o carrinho e redireciona para a página de sucesso
    unset($_SESSION['carrinho']);
    $_SESSION['ultimo_pedido_id'] = $pedido_id; // Salva o ID para a página de sucesso
    header('Location: pedido_confirmado.php');
    exit;

} catch (Exception $e) {
    // G. Se algo deu errado, desfaz todas as alterações
    $mysqli->rollback();
    
    // Guarda o erro e redireciona de volta ao checkout
    $_SESSION['erro_checkout'] = "Ocorreu um erro ao processar seu pedido: " . $e->getMessage();
    header('Location: checkout.php');
    exit;
}

?>