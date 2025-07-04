<?php
// ======================================================================
// PetSync - Admin: Gerenciamento de Pedidos v1.2 (com Modal)
// ======================================================================
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Gerenciar Pedidos';

// Lógica de Filtro
$filtro_status = $_GET['status'] ?? '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($filtro_status)) {
    $where_clause = 'WHERE p.status = ?';
    $params[] = $filtro_status;
    $types .= 's';
}

// Busca dos pedidos com JOIN para pegar nome do cliente
$sql = "SELECT p.*, u.nome as nome_cliente, u.email as email_cliente, u.telefone as telefone_cliente 
        FROM pedidos p 
        JOIN usuarios u ON p.usuario_id = u.id 
        $where_clause 
        ORDER BY p.data_pedido DESC";
        
$stmt_pedidos = $mysqli->prepare($sql);
if (!empty($params)) $stmt_pedidos->bind_param($types, ...$params);
$stmt_pedidos->execute();
$pedidos = $stmt_pedidos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pedidos->close();

// Busca otimizada de itens, endereços e agendamentos
$itens_por_pedido = [];
$detalhes_entrega = [];

if (!empty($pedidos)) {
    $pedido_ids = array_column($pedidos, 'id');
    $placeholders = implode(',', array_fill(0, count($pedido_ids), '?'));
    $types = str_repeat('i', count($pedido_ids));

    // Busca Itens
    $stmt_itens = $mysqli->prepare("SELECT pi.*, pr.nome, pr.imagem FROM pedido_itens pi JOIN produtos pr ON pi.produto_id = pr.id WHERE pi.pedido_id IN ($placeholders)");
    $stmt_itens->bind_param($types, ...$pedido_ids);
    $stmt_itens->execute();
    $todos_os_itens = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_itens->close();
    foreach ($todos_os_itens as $item) $itens_por_pedido[$item['pedido_id']][] = $item;

    // Busca Detalhes de Entrega
    foreach ($pedidos as $pedido) {
        if ($pedido['endereco_id']) {
            $stmt_end = $mysqli->prepare("SELECT * FROM enderecos WHERE id = ?");
            $stmt_end->bind_param("i", $pedido['endereco_id']);
            $stmt_end->execute();
            $endereco = $stmt_end->get_result()->fetch_assoc();
            $detalhes_entrega[$pedido['id']] = "<b>Endereço:</b> " . htmlspecialchars($endereco['rua'] . ', ' . $endereco['numero'] . ' - ' . $endereco['bairro'] . ', ' . $endereco['cidade']);
            $stmt_end->close();
        } elseif ($pedido['agendamento_id']) {
            $stmt_ag = $mysqli->prepare("SELECT p.nome FROM agendamentos a JOIN pets p ON a.pet_id = p.id WHERE a.id = ?");
            $stmt_ag->bind_param("i", $pedido['agendamento_id']);
            $stmt_ag->execute();
            $pet_nome = $stmt_ag->get_result()->fetch_assoc()['nome'];
            $detalhes_entrega[$pedido['id']] = "<b>Entrega Associada:</b> Junto com o pet " . htmlspecialchars($pet_nome);
            $stmt_ag->close();
        } else {
            $detalhes_entrega[$pedido['id']] = "Entrega não especificada.";
        }
    }
}

$status_list = ['Pendente', 'Confirmado', 'Enviado', 'Concluído', 'Cancelado'];

function getStatusClass($status) {
    switch ($status) {
        case 'Pendente': return 'border-blue-500 text-blue-700 bg-blue-100';
        case 'Confirmado': return 'border-yellow-500 text-yellow-700 bg-yellow-100';
        case 'Enviado': return 'border-indigo-500 text-indigo-700 bg-indigo-100';
        case 'Concluído': return 'border-green-500 text-green-700 bg-green-100';
        case 'Cancelado': return 'border-red-500 text-red-700 bg-red-100';
        default: return 'border-gray-500 text-gray-700 bg-gray-100';
    }
}

require '../header.php';
?>
<div id="toast-notification-container" class="fixed top-20 right-5 z-[100] space-y-2"></div>

<div class="container mx-auto px-4 py-12">
    <h1 class="text-4xl font-bold text-petGray mb-8">Gerenciar Pedidos</h1>

    <div class="mb-6 flex flex-wrap gap-2">
        <a href="gerenciar_pedidos.php" class="py-2 px-4 rounded-full text-sm font-semibold <?= empty($filtro_status) ? 'bg-petBlue text-white shadow' : 'bg-white text-petGray hover:bg-gray-100' ?>">Todos</a>
        <?php foreach($status_list as $status): ?>
            <a href="?status=<?= urlencode($status) ?>" class="py-2 px-4 rounded-full text-sm font-semibold <?= ($filtro_status === $status) ? 'bg-petBlue text-white shadow' : 'bg-white text-petGray hover:bg-gray-100' ?>"><?= $status ?></a>
        <?php endforeach; ?>
    </div>

    <div class="space-y-4">
    <?php if(empty($pedidos)): ?>
        <p class="text-center text-gray-500 py-10">Nenhum pedido encontrado com este status.</p>
    <?php else: ?>
        <?php foreach($pedidos as $pedido): ?>
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <p class="font-bold text-lg text-petBlue">Pedido #<?= $pedido['id'] ?></p>
                    <p class="text-sm text-gray-600">Cliente: <span class="font-medium"><?= htmlspecialchars($pedido['nome_cliente']) ?></span></p>
                    <p class="text-sm text-gray-500">Data: <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-bold text-xl">R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></span>
                    <select onchange="abrirModalStatus(<?= $pedido['id'] ?>, this)" class="status-select font-bold text-sm rounded-md border-2 p-2 focus:outline-none <?= getStatusClass($pedido['status']) ?>">
                        <?php foreach($status_list as $status_option): ?>
                            <option value="<?= $status_option ?>" <?= $pedido['status'] === $status_option ? 'selected' : '' ?>><?= $status_option ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="toggleDetails(this)" class="p-2 rounded-full hover:bg-gray-200">
                        <svg class="details-arrow w-6 h-6 text-gray-600 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                </div>
            </div>
            <div class="order-details hidden border-t p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold mb-2 text-petGray">Itens do Pedido:</h4>
                    <div class="space-y-3">
                    <?php if (isset($itens_por_pedido[$pedido['id']])): ?>
                        <?php foreach($itens_por_pedido[$pedido['id']] as $item): ?>
                        <div class="flex items-center text-sm">
                            <img src="../Imagens/produtos/<?= htmlspecialchars($item['imagem']) ?>" class="w-12 h-12 rounded object-cover mr-3">
                            <div class="flex-grow">
                                <p class="font-medium"><?= htmlspecialchars($item['nome']) ?></p>
                                <p class="text-gray-500"><?= $item['quantidade'] ?> x R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">Nenhum item encontrado para este pedido.</p>
                    <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-2 text-petGray">Detalhes da Entrega e Cliente:</h4>
                    <div class="text-sm space-y-2 text-gray-700">
                        <p><?= $detalhes_entrega[$pedido['id']] ?? 'Informação de entrega indisponível.' ?></p>
                        <p><b>Contato:</b> <?= htmlspecialchars($pedido['email_cliente']) ?><?= !empty($pedido['telefone_cliente']) ? ' / '.htmlspecialchars($pedido['telefone_cliente']) : '' ?></p>
                        <p><b>Pagamento:</b> <?= htmlspecialchars($pedido['forma_pagamento']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<div id="custom-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-opacity hidden opacity-0">
    <div class="bg-white p-8 rounded-lg shadow-xl max-w-sm w-full mx-4 text-center transform transition-transform scale-95">
        <div id="modal-icon" class="mx-auto mb-4"></div>
        <h3 id="modal-title" class="text-2xl font-bold text-petGray mb-2"></h3>
        <p id="modal-message" class="text-gray-600 mb-6"></p>
        <div id="modal-actions" class="flex justify-center gap-4"></div>
    </div>
</div>

<script>
function toggleDetails(button) {
    const detailsPanel = button.closest('.bg-white').querySelector('.order-details');
    const arrow = button.querySelector('.details-arrow');
    detailsPanel.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-notification-container');
    const toast = document.createElement('div');
    toast.className = (type === 'error' ? 'bg-red-500' : 'bg-green-500') + ' text-white p-4 rounded-lg shadow-lg';
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

// MODIFICADO: Lógica do Modal adicionada aqui
const modal = document.getElementById('custom-modal');
const modalTitle = document.getElementById('modal-title');
const modalMessage = document.getElementById('modal-message');
const modalActions = document.getElementById('modal-actions');
const modalIcon = document.getElementById('modal-icon');

function showModal(config) {
    modalTitle.textContent = config.title;
    modalMessage.textContent = config.message;
    modalActions.innerHTML = ''; 

    let iconHtml = '';
    if (config.icon === 'warning') {
        iconHtml = `<div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto"><svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>`;
    }
    modalIcon.innerHTML = iconHtml;

    const cancelButton = document.createElement('button');
    cancelButton.textContent = config.cancelText || 'Cancelar';
    cancelButton.className = 'bg-gray-200 hover:bg-gray-300 text-petGray font-bold py-2 px-6 rounded-lg transition-colors';
    cancelButton.onclick = () => {
        hideModal();
        if(config.onCancel) config.onCancel();
    };
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

// MODIFICADO: Função principal agora abre o modal
function abrirModalStatus(pedidoId, selectElement) {
    const novoStatus = selectElement.value;
    const originalOption = selectElement.querySelector('option[selected]');
    const originalStatus = originalOption ? originalOption.value : selectElement.options[0].value;

    // Se o usuário não mudou o valor, não faz nada
    if (novoStatus === originalStatus) return;
    
    let message = `Tem certeza que deseja alterar o status do pedido #${pedidoId} para "${novoStatus}"?`;
    if (novoStatus === 'Cancelado') {
        message += ' Ao cancelar, os itens deste pedido retornarão ao estoque.';
    }

    showModal({
        title: 'Confirmar Alteração',
        message: message,
        icon: 'warning',
        confirmText: 'Sim, alterar',
        onConfirm: () => {
            processarAtualizacao(pedidoId, novoStatus, originalStatus, selectElement);
        },
        onCancel: () => {
            selectElement.value = originalStatus; // Reverte a seleção visual se o admin cancelar
        }
    });
}

// MODIFICADO: A lógica de fetch foi movida para esta nova função
async function processarAtualizacao(pedidoId, novoStatus, originalStatus, selectElement) {
    const formData = new FormData();
    formData.append('pedido_id', pedidoId);
    formData.append('novo_status', novoStatus);
    
    try {
        const response = await fetch('ajax_atualizar_status_pedido.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.sucesso) {
            showToast(result.mensagem);
            const statusClasses = {
                'Pendente': 'border-blue-500 text-blue-700 bg-blue-100',
                'Confirmado': 'border-yellow-500 text-yellow-700 bg-yellow-100',
                'Enviado': 'border-indigo-500 text-indigo-700 bg-indigo-100',
                'Concluído': 'border-green-500 text-green-700 bg-green-100',
                'Cancelado': 'border-red-500 text-red-700 bg-red-100'
            };
            selectElement.className = `status-select font-bold text-sm rounded-md border-2 p-2 focus:outline-none ${statusClasses[novoStatus] || 'border-gray-500 text-gray-700 bg-gray-100'}`;
            const originalOption = selectElement.querySelector('option[selected]');
            if(originalOption) originalOption.removeAttribute('selected');
            selectElement.querySelector(`option[value="${novoStatus}"]`).setAttribute('selected', 'selected');
        } else {
            showToast(result.mensagem, 'error');
            selectElement.value = originalStatus;
        }
    } catch (error) {
        showToast('Erro de comunicação.', 'error');
        selectElement.value = originalStatus;
    }
}
</script>

<?php require '../footer.php'; ?>