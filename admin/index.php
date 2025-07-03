<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Dashboard';
require '../header.php';
?>

<div class="bg-petLightGray min-h-full">
    <div class="container mx-auto px-4 py-12">
        <h1 class="text-4xl font-bold text-petGray mb-8 border-b-4 border-petBlue pb-2">Painel do Administrador</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Gerenciar Agendamentos</h2>
                <p class="text-petGray text-sm flex-grow">Visualize, altere o status ou cancele os agendamentos dos clientes.</p>
                <div class="mt-auto pt-4">
                    <a href="gerencia_agendamentos.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Agendamentos</a>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Gerenciar Produtos</h2>
                <p class="text-petGray text-sm flex-grow">Adicione, edite ou remova produtos da sua loja.</p>
                <div class="mt-auto pt-4">
                    <a href="produtos.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ir para Produtos</a>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.125-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.125-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Gerenciar Usuários</h2>
                <p class="text-petGray text-sm flex-grow">Crie, edite ou remova contas de clientes e funcionários.</p>
                 <div class="mt-auto pt-4">
                    <a href="usuarios.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ir para Usuários</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Gerenciar Galeria</h2>
                <p class="text-petGray text-sm flex-grow">Adicione ou remova as fotos de pets da galeria pública.</p>
                 <div class="mt-auto pt-4">
                    <a href="gerenciar_galeria.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ir para Galeria</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V4a2 2 0 00-2-2H7a2 2 0 00-2 2v1.882l-2.438 2.438a2 2 0 00-.562 1.414V14a2 2 0 002 2h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 001.414 0L13 16.293a1 1 0 01.707-.293H15a2 2 0 002-2v-4.266a2 2 0 00-.562-1.414L14.118 5.882H11z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Disparar Notificações</h2>
                <p class="text-petGray text-sm flex-grow">Envie alertas e comunicados para um ou todos os clientes.</p>
                 <div class="mt-auto pt-4">
                    <a href="disparar_aviso.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Enviar Avisos</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300">
                 <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v-2m0 6V4" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Configurar Agendamentos</h2>
                <p class="text-petGray text-sm flex-grow">Defina serviços, opções de entrega e horários de atendimento.</p>
                 <div class="mt-auto pt-4">
                    <a href="config_agendamento.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Configurar</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300">
                <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto">
                   <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Relatórios</h2>
                <p class="text-petGray text-sm flex-grow">Visualize dados e métricas importantes do seu sistema.</p>
                 <div class="mt-auto pt-4">
                    <a href="relatorios.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Relatórios</a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require '../footer.php'; ?>