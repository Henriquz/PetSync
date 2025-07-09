<?php
include '../config.php';
include 'check_colaborador.php';
$page_title = 'Colaborador - Agendamentos';

$colaborador_nome = $_SESSION['usuario']['nome'];

require '../header.php';
?>

<div class="bg-petLightGray min-h-full">
    <div class="container mx-auto px-4 py-12">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-petBlue hover:text-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-4xl font-bold text-petGray">Gerenciar Agendamentos</h1>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h3 class="text-lg font-semibold text-petGray mb-4">Filtros</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="filtro-data" class="block text-sm font-medium text-petGray mb-1">Data</label>
                    <input type="date" id="filtro-data" class="w-full p-2 border rounded-md form-input">
                </div>
                <div>
                    <label for="filtro-status" class="block text-sm font-medium text-petGray mb-1">Status</label>
                    <select id="filtro-status" class="w-full p-2 border rounded-md form-input">
                        <option value="todos">Todos</option>
                        <option value="Pendente">Pendente</option>
                        <option value="Confirmado">Confirmado</option>
                        <option value="Em Andamento">Em Andamento</option>
                        <option value="Concluído">Concluído</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="aplicarFiltros()" class="bg-petBlue hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                        Aplicar Filtros
                    </button>
                    <button onclick="limparFiltros()" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                        Limpar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Loading -->
        <div id="loading" class="text-center py-8 hidden">
            <svg class="animate-spin h-8 w-8 text-petBlue mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-petGray mt-2">Carregando agendamentos...</p>
        </div>
        
        <!-- Lista de Agendamentos -->
        <div id="agendamentos-container" class="space-y-4">
            <!-- Os agendamentos serão carregados aqui via JavaScript -->
        </div>
        
        <!-- Mensagem quando não há agendamentos -->
        <div id="sem-agendamentos" class="text-center py-8 hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-600">Nenhum agendamento encontrado com os filtros aplicados.</p>
        </div>
    </div>
</div>

<!-- Modal para Atualizar Status -->
<div id="modal-status" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Atualizar Status do Agendamento</h3>
            <div class="mb-4">
                <label for="novo-status" class="block text-sm font-medium text-gray-700 mb-1">Novo Status</label>
                <select id="novo-status" class="w-full p-2 border rounded-md form-input">
                    <option value="Pendente">Pendente</option>
                    <option value="Confirmado">Confirmado</option>
                    <option value="Em Andamento">Em Andamento</option>
                    <option value="Concluído">Concluído</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="observacoes-admin" class="block text-sm font-medium text-gray-700 mb-1">Observações (opcional)</label>
                <textarea id="observacoes-admin" rows="3" class="w-full p-2 border rounded-md form-input" placeholder="Adicione observações sobre o atendimento..."></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button onclick="fecharModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cancelar
                </button>
                <button onclick="confirmarAtualizacao()" class="px-4 py-2 bg-petBlue text-white rounded-md hover:bg-blue-700 transition-colors">
                    Atualizar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let agendamentoAtual = null;

// Carrega agendamentos ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Verifica se há filtros na URL
    const urlParams = new URLSearchParams(window.location.search);
    const statusParam = urlParams.get('status');
    const dataParam = urlParams.get('data');
    
    if (statusParam) {
        document.getElementById('filtro-status').value = statusParam;
    }
    if (dataParam) {
        document.getElementById('filtro-data').value = dataParam;
    }
    
    carregarAgendamentos();
});

function carregarAgendamentos() {
    const loading = document.getElementById('loading');
    const container = document.getElementById('agendamentos-container');
    const semAgendamentos = document.getElementById('sem-agendamentos');
    
    loading.classList.remove('hidden');
    container.innerHTML = '';
    semAgendamentos.classList.add('hidden');
    
    const data = document.getElementById('filtro-data').value;
    const status = document.getElementById('filtro-status').value;
    
    let url = 'ajax_get_agendamentos.php?';
    if (data) url += `data=${data}&`;
    if (status && status !== 'todos') url += `status=${status}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');
            
            if (data.sucesso && data.agendamentos.length > 0) {
                renderizarAgendamentos(data.agendamentos);
            } else {
                semAgendamentos.classList.remove('hidden');
            }
        })
        .catch(error => {
            loading.classList.add('hidden');
            console.error('Erro:', error);
            alert('Erro ao carregar agendamentos');
        });
}

function renderizarAgendamentos(agendamentos) {
    const container = document.getElementById('agendamentos-container');
    
    agendamentos.forEach(agendamento => {
        const card = criarCardAgendamento(agendamento);
        container.appendChild(card);
    });
}

function criarCardAgendamento(agendamento) {
    const div = document.createElement('div');
    div.className = `bg-white p-6 rounded-lg shadow-lg border-l-4 ${getCorStatus(agendamento.status)}`;
    
    const meuAgendamento = agendamento.meu_agendamento ? 
        '<span class="bg-petBlue text-white px-2 py-1 rounded-full text-xs font-medium ml-2">Meu</span>' : '';
    
    const enderecoInfo = agendamento.tipo_entrega === 'delivery' && agendamento.endereco_completo ?
        `<p class="text-sm text-gray-600"><strong>Endereço:</strong> ${agendamento.endereco_completo}</p>` : '';
    
    div.innerHTML = `
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold text-petGray">
                    ${agendamento.cliente_nome} - ${agendamento.pet_nome}
                    ${meuAgendamento}
                </h3>
                <p class="text-sm text-gray-600">${agendamento.pet_especie} - ${agendamento.pet_raca}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-medium ${getClasseStatus(agendamento.status)}">
                ${agendamento.status}
            </span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-sm text-gray-600"><strong>Data:</strong> ${agendamento.data_formatada}</p>
                <p class="text-sm text-gray-600"><strong>Horário:</strong> ${agendamento.hora_formatada}</p>
                <p class="text-sm text-gray-600"><strong>Serviços:</strong> ${agendamento.servico}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600"><strong>Telefone:</strong> ${agendamento.cliente_telefone || 'Não informado'}</p>
                <p class="text-sm text-gray-600"><strong>Tipo:</strong> ${agendamento.tipo_entrega === 'loja' ? 'Retirada na loja' : 'Delivery'}</p>
                ${enderecoInfo}
            </div>
        </div>
        
        ${agendamento.observacoes ? `<div class="mb-4"><p class="text-sm text-gray-600"><strong>Observações do cliente:</strong> ${agendamento.observacoes}</p></div>` : ''}
        ${agendamento.observacoes_admin ? `<div class="mb-4"><p class="text-sm text-gray-600"><strong>Observações do atendimento:</strong> ${agendamento.observacoes_admin}</p></div>` : ''}
        
        <div class="flex justify-end space-x-2">
            <button onclick="abrirModalStatus(${agendamento.id}, '${agendamento.status}')" 
                    class="bg-petBlue hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                Atualizar Status
            </button>
        </div>
    `;
    
    return div;
}

function getCorStatus(status) {
    switch(status) {
        case 'Pendente': return 'border-yellow-400';
        case 'Confirmado': return 'border-blue-400';
        case 'Em Andamento': return 'border-orange-400';
        case 'Concluído': return 'border-green-400';
        case 'Cancelado': return 'border-red-400';
        default: return 'border-gray-400';
    }
}

function getClasseStatus(status) {
    switch(status) {
        case 'Pendente': return 'bg-yellow-100 text-yellow-800';
        case 'Confirmado': return 'bg-blue-100 text-blue-800';
        case 'Em Andamento': return 'bg-orange-100 text-orange-800';
        case 'Concluído': return 'bg-green-100 text-green-800';
        case 'Cancelado': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function aplicarFiltros() {
    carregarAgendamentos();
}

function limparFiltros() {
    document.getElementById('filtro-data').value = '';
    document.getElementById('filtro-status').value = 'todos';
    carregarAgendamentos();
}

function abrirModalStatus(agendamentoId, statusAtual) {
    agendamentoAtual = agendamentoId;
    document.getElementById('novo-status').value = statusAtual;
    document.getElementById('observacoes-admin').value = '';
    document.getElementById('modal-status').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modal-status').classList.add('hidden');
    agendamentoAtual = null;
}

function confirmarAtualizacao() {
    if (!agendamentoAtual) return;
    
    const novoStatus = document.getElementById('novo-status').value;
    const observacoes = document.getElementById('observacoes-admin').value;
    
    const formData = new FormData();
    formData.append('agendamento_id', agendamentoAtual);
    formData.append('novo_status', novoStatus);
    formData.append('observacoes_admin', observacoes);
    
    fetch('ajax_atualizar_status_agendamento.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            fecharModal();
            carregarAgendamentos();
            alert('Status atualizado com sucesso!');
        } else {
            alert('Erro: ' + (data.erro || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar status');
    });
}

// Fecha modal ao clicar fora
document.getElementById('modal-status').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>

<?php require '../footer.php'; ?>