<?php
// ======================================================================
// PetSync - Página de Checkout v1.0
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php'; // Ajuste o caminho se necessário
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    header('Location: login.php');
    exit;
}
$page_title = 'Finalizar Compra - PetSync';
$usuario_logado = $_SESSION['usuario'];
$id_usuario_logado = $usuario_logado['id'];

// Se o carrinho estiver vazio, não há o que finalizar. Volta para a loja.
if (empty($_SESSION['carrinho'])) {
    header('Location: loja.php');
    exit;
}

// 2. BUSCAR DADOS NECESSÁRIOS PARA O CHECKOUT
// ----------------------------------------------------------------------

// Buscar endereços do cliente
$stmt_enderecos = $mysqli->prepare("SELECT * FROM enderecos WHERE usuario_id = ? ORDER BY id DESC");
$stmt_enderecos->bind_param("i", $id_usuario_logado);
$stmt_enderecos->execute();
$enderecos_cliente = $stmt_enderecos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_enderecos->close();

// FUNCIONALIDADE ESPECIAL: Verificar se há agendamento para associar a entrega
$stmt_agendamento = $mysqli->prepare(
    "SELECT a.id, a.data_agendamento, p.nome as pet_nome 
     FROM agendamentos a 
     JOIN pets p ON a.pet_id = p.id 
     WHERE a.usuario_id = ? AND a.status IN ('Pendente', 'Em Andamento', 'Confirmado')
     ORDER BY a.data_agendamento ASC 
     LIMIT 1"
);
$stmt_agendamento->bind_param("i", $id_usuario_logado);
$stmt_agendamento->execute();
$agendamento_associado = $stmt_agendamento->get_result()->fetch_assoc();
$stmt_agendamento->close();


// Calcular o total do pedido para exibir no resumo
$subtotal_pedido = 0.0;
$ids_produtos_no_carrinho = array_keys($_SESSION['carrinho']);
$placeholders = implode(',', array_fill(0, count($ids_produtos_no_carrinho), '?'));
$types = str_repeat('i', count($ids_produtos_no_carrinho));

$stmt_total = $mysqli->prepare("SELECT id, preco FROM produtos WHERE id IN ($placeholders)");
$stmt_total->bind_param($types, ...$ids_produtos_no_carrinho);
$stmt_total->execute();
$result_produtos = $stmt_total->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_total->close();

$produtos_info = [];
foreach($result_produtos as $p) { $produtos_info[$p['id']] = $p; }

foreach($_SESSION['carrinho'] as $id => $quantidade) {
    $subtotal_pedido += ($produtos_info[$id]['preco'] ?? 0) * $quantidade;
}


require 'header.php'; // Ajuste o caminho se necessário
?>
<div id="toast-notification-container" class="fixed top-20 right-5 z-[100] space-y-2"></div>

<main class="bg-gray-50 py-12">
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-petGray mb-8 text-center">Finalizar Compra</h1>

        <form id="checkout-form" action="processa_pedido.php" method="POST">

            <div id="step1" class="step-content bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold text-petGray mb-6">1. Entrega</h2>
                
                <?php if ($agendamento_associado): ?>
                <div class="bg-blue-50 border-l-4 border-petBlue text-blue-800 p-4 mb-6 rounded-md">
                    <div class="flex">
                        <div class="py-1"><svg class="w-6 h-6 text-petBlue mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></div>
                        <div>
                            <p class="font-bold">Entrega Conveniente!</p>
                            <label class="flex items-center mt-2 cursor-pointer">
                                <input type="checkbox" id="entrega-com-pet" name="agendamento_id" value="<?= $agendamento_associado['id'] ?>" class="h-5 w-5 text-petBlue focus:ring-petBlue">
                                <span class="ml-3 text-sm">Quero receber junto com meu pet <strong class="text-petOrange"><?= htmlspecialchars($agendamento_associado['pet_nome']) ?></strong> no agendamento do dia <?= date('d/m/Y', strtotime($agendamento_associado['data_agendamento'])) ?>.</span>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div id="endereco-section">
                    <h3 class="font-semibold text-gray-700 mb-4">Selecione um endereço de entrega:</h3>
                    <?php if(empty($enderecos_cliente)): ?>
                        <p class="text-gray-500">Você ainda não tem endereços cadastrados.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                        <?php foreach($enderecos_cliente as $endereco): ?>
                            <label class="block border rounded-lg p-4 cursor-pointer hover:border-petBlue">
                                <input type="radio" name="endereco_id" value="<?= $endereco['id'] ?>" class="form-radio h-5 w-5 text-petBlue">
                                <span class="ml-3 font-medium"><?= htmlspecialchars($endereco['rua']) ?>, <?= htmlspecialchars($endereco['numero']) ?></span>
                                <span class="block ml-8 text-sm text-gray-600"><?= htmlspecialchars($endereco['bairro']) ?>, <?= htmlspecialchars($endereco['cidade']) ?> - <?= htmlspecialchars($endereco['estado']) ?></span>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <button type="button" id="btn-novo-endereco" class="text-sm text-petBlue hover:underline font-semibold mt-4">
                        + Adicionar novo endereço
                    </button>
                </div>
                
                <div id="novo-endereco-form" class="hidden mt-6 pt-6 border-t">
                    </div>

                <div class="mt-8 flex justify-end">
                    <button type="button" onclick="goToStep(2)" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700">Ir para Pagamento</button>
                </div>
            </div>

            <div id="step2" class="step-content hidden bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold text-petGray mb-6">2. Pagamento</h2>
                <div class="space-y-4">
                    <label class="block border rounded-lg p-4 cursor-pointer hover:border-petBlue">
                        <input type="radio" name="forma_pagamento" value="Pix" class="form-radio h-5 w-5 text-petBlue">
                        <span class="ml-3 font-medium text-lg">Pix</span>
                        <span class="block ml-8 text-sm text-gray-600">Você receberá a chave para pagamento na confirmação do pedido.</span>
                    </label>
                    <label class="block border rounded-lg p-4 cursor-pointer hover:border-petBlue">
                        <input type="radio" name="forma_pagamento" value="Cartão na Entrega" class="form-radio h-5 w-5 text-petBlue">
                        <span class="ml-3 font-medium text-lg">Cartão de Crédito/Débito (na entrega)</span>
                        <span class="block ml-8 text-sm text-gray-600">Pague com a maquininha quando o entregador chegar.</span>
                    </label>
                    <label class="block border rounded-lg p-4 cursor-pointer hover:border-petBlue">
                        <input type="radio" name="forma_pagamento" value="Dinheiro" class="form-radio h-5 w-5 text-petBlue">
                        <span class="ml-3 font-medium text-lg">Dinheiro</span>
                        <span class="block ml-8 text-sm text-gray-600">Pague em dinheiro no momento da entrega.</span>
                    </label>
                </div>
                <div class="mt-8 flex justify-between">
                    <button type="button" onclick="goToStep(1)" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300">Voltar</button>
                    <button type="button" onclick="goToStep(3)" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700">Revisar Pedido</button>
                </div>
            </div>

            <div id="step3" class="step-content hidden bg-white p-8 rounded-lg shadow-md">
                 <h2 class="text-2xl font-bold text-petGray mb-6">3. Resumo do Pedido</h2>
                 <div class="bg-petLightGray p-6 rounded-lg space-y-4">
                    <div class="border-b pb-4">
                        <p class="font-medium text-petGray">Entregar em:</p>
                        <p id="summary-entrega" class="font-semibold text-lg"></p>
                    </div>
                    <div class="border-b pb-4">
                        <p class="font-medium text-petGray">Forma de Pagamento:</p>
                        <p id="summary-pagamento" class="font-semibold text-lg"></p>
                    </div>
                     <div class="pt-2">
                        <p class="font-medium text-petGray">Total a Pagar:</p>
                        <p class="font-semibold text-2xl text-petOrange">R$ <?= number_format($subtotal_pedido, 2, ',', '.') ?></p>
                    </div>
                 </div>
                 <div class="mt-8 flex justify-between">
                    <button type="button" onclick="goToStep(2)" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300">Voltar</button>
                    <button type="submit" class="bg-petOrange text-white px-8 py-4 rounded-md font-medium hover:bg-orange-700 text-lg">Confirmar Pedido</button>
                </div>
            </div>
        </form>
    </div>
</div>
</main>

<script>
const steps = document.querySelectorAll('.step-content');
let currentStep = 1;

function showStep(stepNumber) {
    steps.forEach(step => step.classList.add('hidden'));
    document.getElementById(`step${stepNumber}`).classList.remove('hidden');
    currentStep = stepNumber;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToStep(stepNumber) {
    if (stepNumber > currentStep && !validateStep(currentStep)) {
        return; // Impede de avançar se a validação falhar
    }
    if (stepNumber === 3) {
        populateSummary();
    }
    showStep(stepNumber);
}

function validateStep(stepNumber) {
    if (stepNumber === 1) { // Validação da Entrega
        const entregaComPet = document.getElementById('entrega-com-pet');
        const enderecoSelecionado = document.querySelector('input[name="endereco_id"]:checked');
        if (entregaComPet && entregaComPet.checked) {
            return true; // OK, entrega com pet
        }
        if (enderecoSelecionado) {
            return true; // OK, endereço selecionado
        }
        alert('Por favor, selecione uma opção de entrega.');
        return false;
    }
    if (stepNumber === 2) { // Validação do Pagamento
        const pagamentoSelecionado = document.querySelector('input[name="forma_pagamento"]:checked');
        if (pagamentoSelecionado) {
            return true;
        }
        alert('Por favor, selecione uma forma de pagamento.');
        return false;
    }
    return true;
}

function populateSummary() {
    // Resumo da Entrega
    const entregaComPet = document.getElementById('entrega-com-pet');
    const summaryEntrega = document.getElementById('summary-entrega');
    if (entregaComPet && entregaComPet.checked) {
        summaryEntrega.textContent = entregaComPet.nextElementSibling.textContent;
    } else {
        const enderecoSelecionado = document.querySelector('input[name="endereco_id"]:checked');
        summaryEntrega.textContent = enderecoSelecionado ? enderecoSelecionado.nextElementSibling.textContent : 'Nenhum endereço selecionado.';
    }

    // Resumo do Pagamento
    const pagamentoSelecionado = document.querySelector('input[name="forma_pagamento"]:checked');
    const summaryPagamento = document.getElementById('summary-pagamento');
    summaryPagamento.textContent = pagamentoSelecionado ? pagamentoSelecionado.nextElementSibling.textContent : 'Nenhuma forma de pagamento selecionada.';
}

// Lógica para o checkbox de entrega com o pet
const entregaComPetCheckbox = document.getElementById('entrega-com-pet');
const enderecoSection = document.getElementById('endereco-section');

if (entregaComPetCheckbox) {
    entregaComPetCheckbox.addEventListener('change', function() {
        if (this.checked) {
            enderecoSection.style.opacity = '0.5';
            enderecoSection.style.pointerEvents = 'none';
            // Desmarca qualquer endereço que estivesse selecionado
            const enderecosRadio = document.querySelectorAll('input[name="endereco_id"]');
            enderecosRadio.forEach(radio => radio.checked = false);
            // Remove o valor do endereço do formulário
            document.querySelector('input[name="endereco_id"]:checked')?.removeAttribute('name');
        } else {
            enderecoSection.style.opacity = '1';
            enderecoSection.style.pointerEvents = 'auto';
        }
    });
}

// Inicializa no primeiro passo
showStep(1);
</script>

<?php 
require 'footer.php'; // Ajuste o caminho se necessário
?>