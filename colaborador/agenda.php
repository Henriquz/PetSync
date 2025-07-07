<?php
include '../config.php';
include 'check_colaborador.php';
$page_title = 'Colaborador - Agenda do Dia';

$colaborador_nome = $_SESSION['usuario']['nome'];
$hoje = date('Y-m-d');

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
                <h1 class="text-4xl font-bold text-petGray">Agenda do Dia</h1>
                <div class="bg-petBlue text-white px-3 py-1 rounded-full text-sm font-medium">
                    <?= date('d/m/Y') ?>
                </div>
            </div>
        </div>
        
        <!-- Seletor de Data -->
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <div class="flex items-center gap-4">
                <label for="data-agenda" class="text-lg font-medium text-petGray">Selecionar Data:</label>
                <input type="date" id="data-agenda" value="<?= $hoje ?>" class="p-2 border rounded-md form-input">
                <button onclick="carregarAgenda()" class="bg-petBlue hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    Carregar Agenda
                </button>
            </div>
        </div>
        
        <!-- Loading -->
        <div id="loading" class="text-center py-8 hidden">
            <svg class="animate-spin h-8 w-8 text-petBlue mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-petGray mt-2">Carregando agenda...</p>
        </div>
        
        <!-- Resumo do Dia -->
        <div id="resumo-dia" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 hidden">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-2xl font-bold text-petBlue" id="total-agendamentos">0</p>
                <p class="text-sm text-gray-600">Total</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-2xl font-bold text-yellow-600" id="pendentes">0</p>
                <p class="text-sm text-gray-600">Pendentes</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-2xl font-bold text-orange-600" id="em-andamento">0</p>
                <p class="text-sm text-gray-600">Em Andamento</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-2xl font-bold text-green-600" id="concluidos">0</p>
                <p class="text-sm text-gray-600">Concluídos</p>
            </div>
        </div>
        
        <!-- Timeline da Agenda -->
        <div id="timeline-container" class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold text-petGray mb-6">Timeline do Dia</h3>
            <div id="timeline-content">
                <!-- O conteúdo será carregado via JavaScript -->
            </div>
        </div>
        
        <!-- Mensagem quando não há agendamentos -->
        <div id="sem-agenda" class="text-center py-8 hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-600">Nenhum agendamento para esta data.</p>
        </div>
    </div>
</div>

<script>
// Carrega agenda ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    carregarAgenda();
});

function carregarAgenda() {
    const loading = document.getElementById('loading');
    const resumo = document.getElementById('resumo-dia');
    const timeline = document.getElementById('timeline-content');
    const semAgenda = document.getElementById('sem-agenda');
    
    loading.classList.remove('hidden');
    resumo.classList.add('hidden');
    timeline.innerHTML = '';
    semAgenda.classList.add('hidden');
    
    const data = document.getElementById('data-agenda').value;
    
    fetch(`ajax_get_agendamentos.php?data=${data}`)
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');
            
            if (data.sucesso && data.agendamentos.length > 0) {
                renderizarResumo(data.agendamentos);
                renderizarTimeline(data.agendamentos);
                resumo.classList.remove('hidden');
            } else {
                semAgenda.classList.remove('hidden');
            }
        })
        .catch(error => {
            loading.classList.add('hidden');
            console.error('Erro:', error);
            alert('Erro ao carregar agenda');
        });
}

function renderizarResumo(agendamentos) {
    const total = agendamentos.length;
    const pendentes = agendamentos.filter(a => a.status === 'Pendente' || a.status === 'Confirmado').length;
    const emAndamento = agendamentos.filter(a => a.status === 'Em Andamento').length;
    const concluidos = agendamentos.filter(a => a.status === 'Concluído').length;
    
    document.getElementById('total-agendamentos').textContent = total;
    document.getElementById('pendentes').textContent = pendentes;
    document.getElementById('em-andamento').textContent = emAndamento;
    document.getElementById('concluidos').textContent = concluidos;
}

function renderizarTimeline(agendamentos) {
    const timeline = document.getElementById('timeline-content');
    
    // Ordena por horário
    agendamentos.sort((a, b) => a.hora_formatada.localeCompare(b.hora_formatada));
    
    agendamentos.forEach((agendamento, index) => {
        const item = criarItemTimeline(agendamento, index === agendamentos.length - 1);
        timeline.appendChild(item);
    });
}

function criarItemTimeline(agendamento, isLast) {
    const div = document.createElement('div');
    div.className = 'flex items-start mb-6';
    
    const meuAgendamento = agendamento.meu_agendamento ? 
        '<span class="bg-petBlue text-white px-2 py-1 rounded-full text-xs font-medium ml-2">Meu</span>' : '';
    
    const enderecoInfo = agendamento.tipo_entrega === 'delivery' && agendamento.endereco_completo ?
        `<p class="text-sm text-gray-600 mt-1"><i class="fas fa-map-marker-alt mr-1"></i>${agendamento.endereco_completo}</p>` : '';
    
    div.innerHTML = `
        <div class="flex flex-col items-center mr-4">
            <div class="w-4 h-4 rounded-full ${getCorStatusTimeline(agendamento.status)} border-2 border-white shadow"></div>
            ${!isLast ? '<div class="w-0.5 h-16 bg-gray-200 mt-2"></div>' : ''}
        </div>
        
        <div class="flex-1 bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="font-semibold text-petGray">
                        ${agendamento.hora_formatada} - ${agendamento.cliente_nome}
                        ${meuAgendamento}
                    </h4>
                    <p class="text-sm text-gray-600">${agendamento.pet_nome} (${agendamento.pet_especie})</p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium ${getClasseStatus(agendamento.status)}">
                    ${agendamento.status}
                </span>
            </div>
            
            <p class="text-sm text-gray-700 mb-2"><strong>Serviços:</strong> ${agendamento.servico}</p>
            <p class="text-sm text-gray-600"><strong>Tipo:</strong> ${agendamento.tipo_entrega === 'loja' ? 'Retirada na loja' : 'Delivery'}</p>
            ${enderecoInfo}
            
            ${agendamento.observacoes ? `<p class="text-sm text-gray-600 mt-2"><strong>Obs:</strong> ${agendamento.observacoes}</p>` : ''}
            
            <div class="flex justify-end mt-3 space-x-2">
                ${getAcoesRapidas(agendamento)}
            </div>
        </div>
    `;
    
    return div;
}

function getCorStatusTimeline(status) {
    switch(status) {
        case 'Pendente': return 'bg-yellow-400';
        case 'Confirmado': return 'bg-blue-400';
        case 'Em Andamento': return 'bg-orange-400';
        case 'Concluído': return 'bg-green-400';
        case 'Cancelado': return 'bg-red-400';
        default: return 'bg-gray-400';
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

function getAcoesRapidas(agendamento) {
    let acoes = '';
    
    if (agendamento.status === 'Pendente' || agendamento.status === 'Confirmado') {
        acoes += `<button onclick="atualizarStatusRapido(${agendamento.id}, 'Em Andamento')" 
                         class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-xs transition-colors">
                    Iniciar
                  </button>`;
    }
    
    if (agendamento.status === 'Em Andamento') {
        acoes += `<button onclick="atualizarStatusRapido(${agendamento.id}, 'Concluído')" 
                         class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs transition-colors">
                    Concluir
                  </button>`;
    }
    
    acoes += `<button onclick="window.location.href='agendamentos.php'" 
                     class="bg-petBlue hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">
                Detalhes
              </button>`;
    
    return acoes;
}

function atualizarStatusRapido(agendamentoId, novoStatus) {
    const formData = new FormData();
    formData.append('agendamento_id', agendamentoId);
    formData.append('novo_status', novoStatus);
    formData.append('observacoes_admin', '');
    
    fetch('ajax_atualizar_status_agendamento.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            carregarAgenda(); // Recarrega a agenda
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
</script>

<?php require '../footer.php'; ?>

