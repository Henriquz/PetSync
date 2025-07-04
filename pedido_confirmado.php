<?php
// ======================================================================
// PetSync - Confirmação de Pedido v1.0
// ======================================================================

include 'config.php'; // Ajuste o caminho se necessário
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Pega o ID do pedido da sessão, se não existir, redireciona.
$pedido_id = $_SESSION['ultimo_pedido_id'] ?? 0;
if (!$pedido_id) {
    header('Location: loja.php');
    exit;
}
unset($_SESSION['ultimo_pedido_id']); // Limpa para não mostrar de novo ao recarregar

$page_title = 'Pedido Confirmado! - PetSync';
require 'header.php'; // Ajuste o caminho se necessário
?>

<main class="bg-gray-50 py-16">
<div class="container mx-auto px-4 text-center">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">

        <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>

        <h1 class="text-3xl font-bold text-petGray mb-4">Pedido Realizado com Sucesso!</h1>
        <p class="text-gray-600 text-lg mb-2">Obrigado por comprar conosco!</p>
        <p class="text-gray-800 font-bold text-xl mb-8">
            Número do seu pedido: <span class="text-petOrange">#<?= htmlspecialchars($pedido_id) ?></span>
        </p>

        <div class="bg-gray-100 p-6 rounded-md text-left mb-8">
            <h3 class="font-semibold text-lg mb-4">Próximos Passos:</h3>
            <p class="text-gray-700">
                Seu pedido foi recebido e está com o status "Pendente". Em breve, nossa equipe iniciará a separação dos seus produtos. Você pode acompanhar o status do seu pedido na área "Meus Pedidos".
            </p>
            </div>

        <div class="flex justify-center gap-4">
            <a href="loja.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                Continuar Comprando
            </a>
            <a href="meus_pedidos.php" class="bg-gray-200 hover:bg-gray-300 text-petGray font-bold py-3 px-6 rounded-lg transition-colors">
                Ver Meus Pedidos
            </a>
        </div>
        
    </div>
</div>
</main>

<?php 
require 'footer.php'; // Ajuste o caminho se necessário
?>