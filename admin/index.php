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
                 <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.096 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Configurações do Site</h2>
                <p class="text-petGray text-sm flex-grow">Edite informações de contato, horários e endereço.</p>
                 <div class="mt-auto pt-4">
                    <a href="configuracoes.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Editar Configurações</a>
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