<?php
// ======================================================================
// PetSync - Loja de Produtos v1.2 (com segurança reativada)
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php'; // Ajuste o caminho se necessário
if (session_status() === PHP_SESSION_NONE) { session_start(); }


// Garante que apenas clientes logados (não admins) acessem a loja.
// Se não estiver logado como cliente, redireciona para a página de login.
if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    header('Location: login.php?redirect_url=loja.php'); // Redireciona para o login
    exit;
}

// --- CÓDIGO ADICIONADO PARA BUSCAR AS CONFIGURAÇÕES ---
$configuracoes = [];
$result_config = $mysqli->query("SELECT chave, valor FROM configuracoes");
if ($result_config) {
    while ($row = $result_config->fetch_assoc()) {
        $configuracoes[$row['chave']] = $row['valor'];
    }
}
// --- FIM DO CÓDIGO ADICIONADO ---

$page_title = 'Loja - PetSync';
$usuario_logado = $_SESSION['usuario'];

// 2. BUSCAR PRODUTOS PARA EXIBIÇÃO
// ----------------------------------------------------------------------
// Buscamos apenas produtos ativos e com estoque
$query_produtos = "SELECT * FROM produtos WHERE ativo = 1 AND estoque > 0 ORDER BY nome ASC";
$produtos = $mysqli->query($query_produtos)->fetch_all(MYSQLI_ASSOC);


// Inclui o header da página
require 'header.php'; // Ajuste o caminho se necessário
?>

<div id="toast-notification-container" class="fixed top-20 right-5 z-[100] space-y-2"></div>

<main class="py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-petGray mb-4">Nossos <span class="text-petOrange">Produtos</span></h1>
            <p class="text-lg text-gray-600">Tudo o que seu pet precisa, com a qualidade que você confia.</p>
        </div>

        <?php if (empty($produtos)): ?>
            <div class="text-center py-16">
                <p class="text-xl text-gray-500">Nenhum produto disponível no momento. Volte em breve!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php foreach ($produtos as $produto): ?>
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden flex flex-col transition-transform duration-300 hover:transform hover:-translate-y-1">
                        <div class="p-4 bg-gray-200">
                             <img src="Imagens/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.png') ?>" 
                                 alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                 class="w-full h-48 object-cover">
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <h3 class="text-xl font-bold text-petGray mb-2 flex-grow"><?= htmlspecialchars($produto['nome']) ?></h3>
                            <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars(substr($produto['descricao'], 0, 80)) . (strlen($produto['descricao']) > 80 ? '...' : '') ?></p>
                            <div class="mt-auto">
                                <p class="text-2xl font-bold text-petOrange mb-4">
                                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                </p>
                                <button 
                                    onclick="adicionarAoCarrinho(<?= $produto['id'] ?>)"
                                    class="w-full bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Adicionar ao Carrinho
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
// Função para mostrar notificação flutuante (toast)
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-notification-container');
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';

    toast.className = `${bgColor} text-white p-4 rounded-lg shadow-lg animate-pulse`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('animate-pulse');
        setTimeout(() => toast.remove(), 3000);
    }, 2000);
}

// Função para atualizar a aparência do ícone do carrinho no header
function atualizarIconeCarrinho(totalItens) {
    const cartContainer = document.getElementById('cart-icon-container');
    const cartCountSpan = document.getElementById('cart-count');

    if (cartContainer && cartCountSpan) {
        if (totalItens > 0) {
            cartCountSpan.textContent = totalItens;
            cartContainer.classList.remove('hidden');
        } else {
            cartContainer.classList.add('hidden');
        }
    }
}

// Função para adicionar item ao carrinho via AJAX
async function adicionarAoCarrinho(produtoId) {
    const formData = new FormData();
    formData.append('action', 'adicionar');
    formData.append('produto_id', produtoId);

    try {
        const response = await fetch('gerenciar_carrinho.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.sucesso) {
            showToast(result.mensagem);
            atualizarIconeCarrinho(result.total_itens);
        } else {
            showToast(result.mensagem, 'error');
        }
    } catch (error) {
        console.error('Erro ao adicionar ao carrinho:', error);
        showToast('Ocorreu um erro de comunicação.', 'error');
    }
}
</script>

<?php 
// Inclui o footer da página
require 'footer.php'; // Ajuste o caminho se necessário
?>