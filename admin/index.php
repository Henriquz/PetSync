<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Dashboard';

// ==================================================================
// INÍCIO DO CÓDIGO ADICIONADO PARA CORRIGIR O PROBLEMA
// Este bloco busca as configurações do banco de dados e as armazena na variável $configuracoes
$configuracoes = [];
$result_config = $mysqli->query("SELECT chave, valor FROM configuracoes");
if ($result_config) {
    while ($row = $result_config->fetch_assoc()) {
        $configuracoes[$row['chave']] = $row['valor'];
    }
}
// FIM DO CÓDIGO ADICIONADO
// ==================================================================

require '../header.php';

// Agora esta verificação funcionará corretamente, pois $configuracoes existe
$loja_ativa = isset($configuracoes['exibir_secao_produtos']) && $configuracoes['exibir_secao_produtos'];
?>

<div class="bg-petLightGray min-h-full">
    <div class="container mx-auto px-4 py-12">
        <div class="flex items-center gap-4 mb-8 border-b-4 border-petBlue pb-2">
            <h1 class="text-4xl font-bold text-petGray">Painel do Administrador</h1>
            <div class="relative" id="tooltip-container">
                <svg class="w-6 h-6 text-gray-400 cursor-help" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div id="tooltip-content" class="hidden absolute left-0 top-full mt-2 w-64 bg-gray-800 text-white text-sm rounded-lg shadow-lg p-3 z-20 transition-opacity duration-300">
                    <p class="font-bold mb-2">Legenda de Cores:</p>
                    <div class="flex items-center mb-1"><span class="w-4 h-4 rounded-full bg-petBlue mr-2"></span><span>Azul: Gestão Essencial</span></div>
                    <div class="flex items-center mb-1"><span class="w-4 h-4 rounded-full bg-petOrange mr-2"></span><span>Laranja: Ferramentas e Conteúdo</span></div>
                    <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-green-500 mr-2"></span><span>Verde: Análise e Dados</span></div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Gerenciar Agendamentos</h2>
                <p class="text-petGray text-sm flex-grow">Visualize e altere os agendamentos dos clientes.</p>
                <div class="mt-auto pt-4">
                    <a href="gerencia_agendamentos.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Agendamentos</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Configurar Agendamento</h2>
                <p class="text-petGray text-sm flex-grow">Ajuste tipos de serviço, horários e dias disponíveis.</p>
                <div class="mt-auto pt-4">
                    <a href="config_agendamento.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ajustar</a>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full <?= !$loja_ativa ? 'grayscale opacity-60' : '' ?>">
                <div class="w-16 h-16 <?= !$loja_ativa ? 'bg-gray-200 text-gray-500' : 'bg-blue-100 text-petBlue' ?> rounded-full flex items-center justify-center mb-4 mx-auto">
                    <?php if ($loja_ativa): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-2h8a1 1 0 001-1zM20.59 4.41a1.99 1.99 0 00-2.82 0L14 8.17l-2-2L9.17 9.83a1.99 1.99 0 000 2.82l2 2L15.83 18a1.99 1.99 0 002.82 0l2.83-2.83a1.99 1.99 0 000-2.82L18.17 9l2.41-2.41a1.99 1.99 0 00.01-2.82z" /></svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    <?php endif; ?>
                </div>
                <h2 class="text-2xl font-semibold <?= !$loja_ativa ? 'text-gray-500' : 'text-petBlue' ?> mb-2">Gerenciar Pedidos</h2>
                <p class="text-petGray text-sm flex-grow">Acompanhe e atualize o status dos pedidos da loja.</p>
                <div class="mt-auto pt-4">
                    <?php if ($loja_ativa): ?>
                        <a href="gerenciar_pedidos.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Pedidos</a>
                    <?php else: ?>
                        <button class="ativar-loja-btn bg-gray-400 cursor-pointer text-white font-bold py-2 px-6 rounded-lg">Ativar Loja</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full <?= !$loja_ativa ? 'grayscale opacity-60' : '' ?>">
                <div class="w-16 h-16 <?= !$loja_ativa ? 'bg-gray-200 text-gray-500' : 'bg-orange-100 text-petOrange' ?> rounded-full flex items-center justify-center mb-4 mx-auto">
                     <?php if ($loja_ativa): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    <?php endif; ?>
                </div>
                <h2 class="text-2xl font-semibold <?= !$loja_ativa ? 'text-gray-500' : 'text-petOrange' ?> mb-2">Configurar Produtos</h2>
                <p class="text-petGray text-sm flex-grow">Adicione, edite ou remova produtos da sua loja.</p>
                <div class="mt-auto pt-4">
                    <?php if ($loja_ativa): ?>
                        <a href="produtos.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Configurar</a>
                    <?php else: ?>
                        <button class="ativar-loja-btn bg-gray-400 cursor-pointer text-white font-bold py-2 px-6 rounded-lg">Ativar Loja</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.125-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.125-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Gerenciar Usuários</h2>
                <p class="text-petGray text-sm flex-grow">Crie, edite ou remova contas de clientes e funcionários.</p>
                <div class="mt-auto pt-4">
                    <a href="usuarios.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ir para Usuários</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Gerenciar Galeria</h2>
                <p class="text-petGray text-sm flex-grow">Adicione ou remova fotos de pets da galeria pública.</p>
                <div class="mt-auto pt-4"><a href="gerenciar_galeria.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ir para Galeria</a></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V4a2 2 0 00-2-2H7a2 2 0 00-2 2v1.882l-2.438 2.438a2 2 0 00-.562 1.414V14a2 2 0 002 2h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 001.414 0L13 16.293a1 1 0 01.707-.293H15a2 2 0 002-2v-4.266a2 2 0 00-.562-1.414L14.118 5.882H11z" /></svg></div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Disparar Notificações</h2>
                <p class="text-petGray text-sm flex-grow">Envie alertas e comunicados para um ou todos os clientes.</p>
                <div class="mt-auto pt-4"><a href="disparar_aviso.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Enviar Avisos</a></div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v-2m0 6V4" /></svg></div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Configurar Sistema</h2>
                <p class="text-petGray text-sm flex-grow">Defina serviços, horários de atendimento e outras opções.</p>
                <div class="mt-auto pt-4"><a href="configuracoes.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Configurar</a></div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4 mx-auto"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg></div>
                <h2 class="text-2xl font-semibold text-green-600 mb-2">Relatórios</h2>
                <p class="text-petGray text-sm flex-grow">Visualize dados e métricas importantes do seu sistema.</p>
                <div class="mt-auto pt-4"><a href="relatorios.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Relatórios</a></div>
            </div>
        </div>
    </div>
</div>

<div id="ativar-loja-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Ativar Módulo da Loja?</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Esta opção ativa todo o fluxo de produtos no sistema (loja, pedidos, etc.). Utilize apenas se sua loja realmente vender itens.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirm-ativar-loja" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                    Sim, Ativar Agora
                </button>
                <button id="cancel-ativar-loja" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300 mt-3">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tooltip de legenda
    const tooltipContainer = document.getElementById('tooltip-container');
    const tooltipContent = document.getElementById('tooltip-content');
    if (tooltipContainer && tooltipContent) {
        tooltipContainer.addEventListener('mouseenter', () => tooltipContent.classList.remove('hidden'));
        tooltipContainer.addEventListener('mouseleave', () => tooltipContent.classList.add('hidden'));
    }

    // --- LÓGICA DO MODAL DE ATIVAÇÃO ---
    const modal = document.getElementById('ativar-loja-modal');
    const ativarBtns = document.querySelectorAll('.ativar-loja-btn');
    const cancelBtn = document.getElementById('cancel-ativar-loja');
    const confirmBtn = document.getElementById('confirm-ativar-loja');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');

    ativarBtns.forEach(btn => btn.addEventListener('click', openModal));
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    confirmBtn.addEventListener('click', () => {
        // Mostra um feedback visual de carregamento no botão
        confirmBtn.textContent = 'Ativando...';
        confirmBtn.disabled = true;

        // Faz a chamada AJAX para o novo arquivo PHP
        fetch('ajax_ativar_loja.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Se deu certo, recarrega a página para refletir as mudanças
                window.location.reload();
            } else {
                // Se deu erro, exibe um alerta e restaura o botão
                alert('Ocorreu um erro ao ativar a loja. Tente novamente.');
                confirmBtn.textContent = 'Sim, Ativar Agora';
                confirmBtn.disabled = false;
                closeModal();
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Ocorreu um erro de comunicação. Verifique sua conexão.');
            confirmBtn.textContent = 'Sim, Ativar Agora';
            confirmBtn.disabled = false;
            closeModal();
        });
    });
});
</script>

<?php require '../footer.php'; ?>