<?php
// ======================================================================
// PetSync - Página do Carrinho de Compras v1.0
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php'; // Ajuste o caminho se necessário
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    header('Location: login.php');
    exit;
}
$page_title = 'Meu Carrinho - PetSync';

// 2. PROCESSAR ITENS DO CARRINHO
// ----------------------------------------------------------------------
$itens_carrinho = [];
$subtotal_pedido = 0.0;
$ids_produtos_no_carrinho = $_SESSION['carrinho'] ?? [];

if (!empty($ids_produtos_no_carrinho)) {
    // Pega as chaves (IDs dos produtos) para usar na query
    $ids = array_keys($ids_produtos_no_carrinho);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    // Busca todos os produtos do carrinho no banco de uma só vez
    $stmt = $mysqli->prepare("SELECT id, nome, preco, imagem, estoque FROM produtos WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result_produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Organiza os produtos por ID para fácil acesso
    $produtos_info = [];
    foreach ($result_produtos as $p) {
        $produtos_info[$p['id']] = $p;
    }

    // Monta o array final do carrinho com todos os detalhes
    foreach ($ids_produtos_no_carrinho as $id => $quantidade) {
        if (isset($produtos_info[$id])) {
            $produto = $produtos_info[$id];
            $subtotal_item = $produto['preco'] * $quantidade;
            $subtotal_pedido += $subtotal_item;

            $itens_carrinho[] = [
                'id' => $id,
                'nome' => $produto['nome'],
                'preco' => $produto['preco'],
                'imagem' => $produto['imagem'],
                'estoque' => $produto['estoque'],
                'quantidade' => $quantidade,
                'subtotal' => $subtotal_item
            ];
        } else {
            // Se um produto do carrinho não existe mais no banco, remove da sessão
            unset($_SESSION['carrinho'][$id]);
        }
    }
}


require 'header.php'; // Ajuste o caminho se necessário
?>

<div id="toast-notification-container" class="fixed top-20 right-5 z-[100] space-y-2"></div>

<main class="bg-gray-50 py-12">
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-petGray mb-8">Meu Carrinho</h1>

    <?php if (empty($itens_carrinho)): ?>
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Seu carrinho está vazio.</h2>
            <p class="text-gray-500 mb-6">Que tal adicionar alguns produtos para seu pet?</p>
            <a href="loja.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg transition-colors">Ver Produtos</a>
        </div>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-2/3">
                <div class="bg-white rounded-lg shadow-md">
                    <?php foreach ($itens_carrinho as $item): ?>
                    <div class="flex items-center p-4 border-b last:border-b-0">
                        <img src="Imagens/produtos/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>" class="w-20 h-20 object-cover rounded-md mr-4">
                        <div class="flex-grow">
                            <h3 class="font-semibold text-lg text-petGray"><?= htmlspecialchars($item['nome']) ?></h3>
                            <p class="text-gray-500">R$ <?= number_format($item['preco'], 2, ',', '.') ?></p>
                        </div>
                        <div class="flex items-center gap-4">
                            <input type="number" value="<?= $item['quantidade'] ?>" min="1" max="<?= $item['estoque'] ?>" 
                                   onchange="atualizarQuantidade(<?= $item['id'] ?>, this.value)"
                                   class="w-20 p-2 border rounded-md text-center">
                            <p class="w-24 text-right font-semibold">R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></p>
                            <button onclick="removerItem(<?= $item['id'] ?>)" class="text-gray-400 hover:text-red-500 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="lg:w-1/3">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                    <h2 class="text-2xl font-bold border-b pb-4 mb-4">Resumo do Pedido</h2>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold">R$ <?= number_format($subtotal_pedido, 2, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between text-xl font-bold mt-4 pt-4 border-t">
                        <span>Total</span>
                        <span>R$ <?= number_format($subtotal_pedido, 2, ',', '.') ?></span>
                    </div>
                    <a href="checkout.php" class="block w-full text-center bg-petOrange hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg mt-6 transition-colors">
                        Finalizar Compra
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</main>

<script>
// Funções para gerenciar o carrinho nesta página

// Função para mostrar notificação (pode ser a mesma da loja.php)
function showToast(message, type = 'success') { /* ... mesma função da loja.php ... */ }

async function apiCall(action, produtoId, quantidade = 1) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('produto_id', produtoId);
    formData.append('quantidade', quantidade);

    try {
        const response = await fetch('gerenciar_carrinho.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.sucesso) {
            location.reload(); // Recarrega a página para mostrar as mudanças
        } else {
            alert(result.mensagem); // Mostra um alerta simples em caso de erro
        }
    } catch (error) {
        console.error('Erro na API do carrinho:', error);
        alert('Ocorreu um erro de comunicação.');
    }
}

function atualizarQuantidade(produtoId, quantidade) {
    apiCall('atualizar_quantidade', produtoId, quantidade);
}

function removerItem(produtoId) {
    if (confirm('Tem certeza que deseja remover este item?')) {
        apiCall('remover', produtoId);
    }
}
</script>

<?php 
require 'footer.php'; // Ajuste o caminho se necessário
?>