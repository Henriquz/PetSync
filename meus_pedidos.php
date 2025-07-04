<?php
// ======================================================================
// PetSync - Página de Meus Pedidos v2.2 (com Modal Personalizado)
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    header('Location: login.php');
    exit;
}
$page_title = 'Meus Pedidos - PetSync';
$id_usuario_logado = $_SESSION['usuario']['id'];

// 2. BUSCAR E ATUALIZAR PEDIDOS
// ----------------------------------------------------------------------
$stmt_pedidos = $mysqli->prepare(
    "SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY data_pedido DESC"
);
$stmt_pedidos->bind_param("i", $id_usuario_logado);
$stmt_pedidos->execute();
$pedidos = $stmt_pedidos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pedidos->close();

// LÓGICA DE ATUALIZAÇÃO AUTOMÁTICA
$agora = new DateTime();
$ids_para_atualizar = [];
$status_elegiveis_automacao = ['Pendente', 'Confirmado', 'Em Andamento', 'Enviado'];

foreach ($pedidos as &$pedido) {
    $data_pedido = new DateTime($pedido['data_pedido']);
    $diferenca = $agora->diff($data_pedido);
    $horas_passadas = ($diferenca->days * 24) + $diferenca->h;

    if (in_array($pedido['status'], $status_elegiveis_automacao) && $horas_passadas >= 24) {
        $ids_para_atualizar[] = $pedido['id'];
        $pedido['status'] = 'Concluído';
        $pedido['concluido_automaticamente'] = true;
    }
}
unset($pedido);

if (!empty($ids_para_atualizar)) {
    $placeholders = implode(',', array_fill(0, count($ids_para_atualizar), '?'));
    $types = str_repeat('i', count($ids_para_atualizar));
    $stmt_update = $mysqli->prepare("UPDATE pedidos SET status = 'Concluído' WHERE id IN ($placeholders)");
    $stmt_update->bind_param($types, ...$ids_para_atualizar);
    $stmt_update->execute();
    $stmt_update->close();
}

$itens_por_pedido = [];
if (!empty($pedidos)) {
    // ... (lógica de busca de itens, inalterada) ...
    $pedido_ids = array_column($pedidos, 'id');
    $placeholders_itens = implode(',', array_fill(0, count($pedido_ids), '?'));
    $types_itens = str_repeat('i', count($pedido_ids));
    $stmt_itens = $mysqli->prepare(
        "SELECT pi.*, p.nome, p.imagem FROM pedido_itens pi JOIN produtos p ON pi.produto_id = p.id WHERE pi.pedido_id IN ($placeholders_itens)"
    );
    $stmt_itens->bind_param($types_itens, ...$pedido_ids);
    $stmt_itens->execute();
    $todos_os_itens = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_itens->close();
    foreach ($todos_os_itens as $item) {
        $itens_por_pedido[$item['pedido_id']][] = $item;
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'Pendente': return 'bg-blue-100 text-blue-800';
        case 'Pago':
        case 'Em Separação': return 'bg-yellow-100 text-yellow-800';
        case 'Enviado':
        case 'Em Andamento': return 'bg-indigo-100 text-indigo-800';
        case 'Concluído': return 'bg-green-100 text-green-800';
        case 'Cancelado': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

require 'header.php';
?>
<div id="toast-notification-container" class="fixed top-20 right-5 z-[100] space-y-2"></div>

<main class="py-12 bg-gray-50">
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-petGray mb-8">Meus Pedidos</h1>

    <?php if (empty($pedidos)): ?>
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Você ainda não fez nenhum pedido.</h2>
            <p class="text-gray-500 mb-6">Explore nossa loja e encontre os melhores produtos para o seu pet!</p>
            <a href="loja.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg transition-colors">Ir para a Loja</a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($pedidos as $pedido): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden" id="pedido-<?= $pedido['id'] ?>">
                    <div class="p-4 md:p-6 flex flex-col md:flex-row justify-between items-start md:items-center bg-gray-50 border-b cursor-pointer" onclick="toggleDetails(this)">
                        <div class="space-y-1">
                            <p class="font-bold text-lg text-petBlue">Pedido #<?= htmlspecialchars($pedido['id']) ?></p>
                            <p class="text-sm text-gray-500">Realizado em: <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                        </div>
                        <div class="flex items-center gap-4 mt-4 md:mt-0">
                            <span class="font-semibold text-lg">R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></span>
                            <span class="status-badge text-xs font-bold uppercase px-3 py-1 rounded-full <?= getStatusClass($pedido['status']) ?>">
                                <?= htmlspecialchars($pedido['status']) ?>
                            </span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform details-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                    
                    <div class="order-details hidden p-4 md:p-6">
                        <?php if (isset($pedido['concluido_automaticamente']) && $pedido['concluido_automaticamente']): ?>
                        <div class="bg-green-50 border-l-4 border-green-400 text-green-800 p-3 mb-4 rounded-r-lg">
                            <p class="text-sm">Este pedido foi marcado como concluído automaticamente.</p>
                        </div>
                        <?php endif; ?>

                        <h4 class="font-semibold text-lg mb-4">Itens do Pedido:</h4>
                        <div class="space-y-4 border-b pb-4 mb-4">
                            <?php if (isset($itens_por_pedido[$pedido['id']])): ?>
                                <?php foreach ($itens_por_pedido[$pedido['id']] as $item): ?>
                                <div class="flex items-center">
                                    <img src="Imagens/produtos/<?= htmlspecialchars($item['imagem']) ?>" class="w-16 h-16 rounded-md object-cover mr-4">
                                    <div class="flex-grow">
                                        <p class="font-medium text-petGray"><?= htmlspecialchars($item['nome']) ?></p>
                                        <p class="text-sm text-gray-500"><?= $item['quantidade'] ?> x R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></p>
                                    </div>
                                    <p class="font-semibold text-gray-700">R$ <?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?></p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4 flex flex-col sm:flex-row items-center justify-end gap-3">
                             <a href="https://wa.me/5533998112005?text=Ol%C3%A1!%20Tive%20um%20problema%20com%20meu%20pedido%20%23<?= $pedido['id'] ?>%2C%20pode%20me%20ajudar!%3F" 
                                target="_blank"
                                class="inline-flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-700 font-bold py-1.5 px-4 rounded-lg transition-colors duration-300 text-sm">
                                 <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                                 Tive um problema
                             </a>
                             
                             <?php if (in_array($pedido['status'], ['Pendente', 'Confirmado', 'Em Andamento', 'Enviado'])): ?>
                                <button onclick="abrirModalConfirmacao(<?= $pedido['id'] ?>, this)"
                                    class="confirm-btn inline-flex items-center justify-center bg-green-500 hover:bg-green-600 text-white font-bold py-1.5 px-4 rounded-lg transition-colors duration-300 text-sm">
                                     <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                     Confirmar Recebimento
                                </button>
                             <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</main>

<div id="custom-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-opacity hidden opacity-0">
    <div class="bg-white p-8 rounded-lg shadow-xl max-w-sm w-full mx-4 text-center transform transition-transform scale-95">
        <div id="modal-icon" class="mx-auto mb-4">
            </div>
        <h3 id="modal-title" class="text-2xl font-bold text-petGray mb-2"></h3>
        <p id="modal-message" class="text-gray-600 mb-6"></p>
        <div id="modal-actions" class="flex justify-center gap-4">
            </div>
    </div>
</div>

<script>
function toggleDetails(element) {
    const detailsPanel = element.nextElementSibling;
    const arrow = element.querySelector('.details-arrow');

    if (detailsPanel.classList.contains('hidden')) {
        detailsPanel.classList.remove('hidden');
        arrow.style.transform = 'rotate(180deg)';
    } else {
        detailsPanel.classList.add('hidden');
        arrow.style.transform = 'rotate(0deg)';
    }
}

// ADICIONADO: Funções para o Modal Personalizado
const modal = document.getElementById('custom-modal');
const modalTitle = document.getElementById('modal-title');
const modalMessage = document.getElementById('modal-message');
const modalActions = document.getElementById('modal-actions');
const modalIcon = document.getElementById('modal-icon');

function showModal(config) {
    modalTitle.textContent = config.title;
    modalMessage.textContent = config.message;
    modalActions.innerHTML = ''; // Limpa botões antigos

    if (config.icon === 'question') {
        modalIcon.innerHTML = `<div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto"><svg class="w-10 h-10 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>`;
    }

    const cancelButton = document.createElement('button');
    cancelButton.textContent = config.cancelText || 'Cancelar';
    cancelButton.className = 'bg-gray-200 hover:bg-gray-300 text-petGray font-bold py-2 px-6 rounded-lg transition-colors';
    cancelButton.onclick = () => hideModal();
    modalActions.appendChild(cancelButton);

    const confirmButton = document.createElement('button');
    confirmButton.textContent = config.confirmText || 'Confirmar';
    confirmButton.className = 'bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors';
    confirmButton.onclick = () => {
        hideModal();
        config.onConfirm();
    };
    modalActions.appendChild(confirmButton);

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
    }, 10);
}

function hideModal() {
    modal.classList.add('opacity-0');
    modal.querySelector('.transform').classList.add('scale-95');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-notification-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = (type === 'error' ? 'bg-red-500' : 'bg-green-500') + ' text-white p-4 rounded-lg shadow-lg';
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

// MODIFICADO: Função que abre o modal
function abrirModalConfirmacao(pedidoId, buttonElement) {
    showModal({
        title: 'Confirmar Recebimento',
        message: 'Você confirma o recebimento deste pedido? Esta ação não pode ser desfeita.',
        icon: 'question',
        confirmText: 'Sim, recebi',
        onConfirm: () => {
            // A ação de fato só acontece após a confirmação no modal
            processarConfirmacao(pedidoId, buttonElement);
        }
    });
}

async function processarConfirmacao(pedidoId, buttonElement) {
    buttonElement.disabled = true;
    buttonElement.textContent = 'Processando...';

    const formData = new FormData();
    formData.append('pedido_id', pedidoId);

    try {
        const response = await fetch('ajax_confirmar_entrega.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.sucesso) {
            showToast(result.mensagem || 'Pedido confirmado com sucesso!');
            const pedidoContainer = document.getElementById(`pedido-${pedidoId}`);
            if (pedidoContainer) {
                buttonElement.remove();
                const statusBadge = pedidoContainer.querySelector('.status-badge');
                statusBadge.textContent = 'Concluído';
                statusBadge.className = 'status-badge text-xs font-bold uppercase px-3 py-1 rounded-full bg-green-100 text-green-800';
            }
        } else {
            showToast(result.mensagem || 'Ocorreu um erro.', 'error');
            buttonElement.disabled = false;
            buttonElement.innerHTML = `<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>Confirmar Recebimento`;
        }
    } catch (error) {
        showToast('Ocorreu um erro de comunicação.', 'error');
        buttonElement.disabled = false;
        buttonElement.innerHTML = `<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>Confirmar Recebimento`;
    }
}
</script>

<?php 
require 'footer.php';
?>