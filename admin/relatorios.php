<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Relatórios';

// --- CONSULTAS PARA OS RELATÓRIOS ---

// Relatório de Usuários
$total_usuarios = $mysqli->query("SELECT COUNT(id) as total FROM usuarios WHERE is_active = 1")->fetch_assoc()["total"];
$ultimos_usuarios = $mysqli->query("SELECT nome, email, data_cadastro FROM usuarios ORDER BY data_cadastro DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$clientes_ativos = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_admin = 0 AND is_active = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$colaboradores_ativos = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_colaborador = 1 AND is_active = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$admins_ativos = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_admin = 1 AND is_active = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

// Relatório de Pets
$total_pets = $mysqli->query("SELECT COUNT(id) as total FROM pets WHERE data_cadastro IS NOT NULL")->fetch_assoc()["total"];
$pets_por_especie = $mysqli->query("SELECT especie, COUNT(id) as total FROM pets GROUP BY especie ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);

// Relatório de Produtos
$total_produtos = $mysqli->query("SELECT COUNT(id) as total FROM produtos WHERE ativo = 1")->fetch_assoc()["total"];
$produtos_mais_vendidos = $mysqli->query("SELECT p.nome, SUM(pi.quantidade) as total_vendido FROM pedido_itens pi JOIN produtos p ON pi.produto_id = p.id GROUP BY p.nome ORDER BY total_vendido DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$produtos_baixo_estoque = $mysqli->query("SELECT nome, estoque FROM produtos WHERE estoque <= 10 ORDER BY estoque ASC")->fetch_all(MYSQLI_ASSOC);

// Relatório de Agendamentos
$total_agendamentos = $mysqli->query("SELECT COUNT(id) as total FROM agendamentos WHERE status = 'Concluído'")->fetch_assoc()["total"];
$agendamentos_por_status = $mysqli->query("SELECT status, COUNT(id) as total FROM agendamentos GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$agendamentos_por_servico = $mysqli->query("SELECT servico, COUNT(id) as total FROM agendamentos GROUP BY servico ORDER BY total DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Relatório de Pedidos
$total_pedidos = $mysqli->query("SELECT COUNT(id) as total FROM pedidos WHERE status = 'Concluído'")->fetch_assoc()["total"];
$pedidos_por_status = $mysqli->query("SELECT status, COUNT(id) as total FROM pedidos GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$pedidos_por_forma_pagamento = $mysqli->query("SELECT forma_pagamento, COUNT(id) as total FROM pedidos GROUP BY forma_pagamento")->fetch_all(MYSQLI_ASSOC);

require '../header.php';
?>

<div class="container mx-auto px-4 py-12">
    <h1 class="text-4xl font-bold text-petGray mb-8">Relatórios Gerenciais</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Seção de Resumo Geral -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-lg text-center">
            <h3 class="text-xl font-semibold text-petGray">Resumo Geral do Sistema</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="p-4 bg-gray-100 rounded-lg">
                    <p class="text-2xl font-bold text-petBlue"><?= $total_usuarios ?></p>
                    <p class="text-sm text-gray-600">Total de Usuários</p>
                </div>
                <div class="p-4 bg-gray-100 rounded-lg">
                    <p class="text-2xl font-bold text-petBlue"><?= $total_pets ?></p>
                    <p class="text-sm text-gray-600">Total de Pets Cadastrados</p>
                </div>
                <div class="p-4 bg-gray-100 rounded-lg">
                    <p class="text-2xl font-bold text-petBlue"><?= $total_produtos ?></p>
                    <p class="text-sm text-gray-600">Total de Produtos</p>
                </div>
                <div class="p-4 bg-gray-100 rounded-lg">
                    <p class="text-2xl font-bold text-petBlue"><?= $total_agendamentos ?></p>
                    <p class="text-sm text-gray-600">Total de Agendamentos Concluídos</p>
                </div>
                <div class="p-4 bg-gray-100 rounded-lg">
                    <p class="text-2xl font-bold text-petBlue"><?= $total_pedidos ?></p>
                    <p class="text-sm text-gray-600">Total de Pedidos Concluídos</p>
                </div>
            </div>
        </div>

        <!-- Relatório de Usuários -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Últimos 10 Usuários Cadastrados</h2>
                <a href="exportar.php?relatorio=ultimos_usuarios" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Nome</th><th class="p-2">E-mail</th><th class="p-2">Data</th></tr></thead>
                    <tbody>
                        <?php foreach($ultimos_usuarios as $usuario): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($usuario['nome']) ?></td><td class="p-2"><?= htmlspecialchars($usuario['email']) ?></td><td class="p-2"><?= date('d/m/Y', strtotime($usuario['data_cadastro'])) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Clientes Ativos</h2>
                 <a href="exportar.php?relatorio=clientes_ativos" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-y-auto max-h-96">
                <table class="w-full text-left text-sm">
                     <thead><tr class="border-b"><th class="p-2">Nome</th><th class="p-2">E-mail</th></tr></thead>
                     <tbody>
                        <?php foreach($clientes_ativos as $cliente): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($cliente['nome']) ?></td><td class="p-2"><?= htmlspecialchars($cliente['email']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Colaboradores Ativos</h2>
                 <a href="exportar.php?relatorio=colaboradores_ativos" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-y-auto max-h-96">
                <table class="w-full text-left text-sm">
                     <thead><tr class="border-b"><th class="p-2">Nome</th><th class="p-2">E-mail</th></tr></thead>
                     <tbody>
                        <?php foreach($colaboradores_ativos as $colaborador): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($colaborador['nome']) ?></td><td class="p-2"><?= htmlspecialchars($colaborador['email']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Administradores Ativos</h2>
                 <a href="exportar.php?relatorio=admins_ativos" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-y-auto max-h-96">
                <table class="w-full text-left text-sm">
                     <thead><tr class="border-b"><th class="p-2">Nome</th><th class="p-2">E-mail</th></tr></thead>
                     <tbody>
                        <?php foreach($admins_ativos as $admin): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($admin['nome']) ?></td><td class="p-2"><?= htmlspecialchars($admin['email']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Relatório de Pets -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Pets por Espécie</h2>
                <a href="exportar.php?relatorio=pets_por_especie" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Espécie</th><th class="p-2">Total</th></tr></thead>
                    <tbody>
                        <?php foreach($pets_por_especie as $pet_especie): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($pet_especie['especie']) ?></td><td class="p-2"><?= htmlspecialchars($pet_especie['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Relatório de Produtos -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Produtos Mais Vendidos</h2>
                <a href="exportar.php?relatorio=produtos_mais_vendidos" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Produto</th><th class="p-2">Total Vendido</th></tr></thead>
                    <tbody>
                        <?php foreach($produtos_mais_vendidos as $produto): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($produto['nome']) ?></td><td class="p-2"><?= htmlspecialchars($produto['total_vendido']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Produtos com Baixo Estoque (<= 10)</h2>
                <a href="exportar.php?relatorio=produtos_baixo_estoque" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Produto</th><th class="p-2">Estoque</th></tr></thead>
                    <tbody>
                        <?php foreach($produtos_baixo_estoque as $produto): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($produto['nome']) ?></td><td class="p-2"><?= htmlspecialchars($produto['estoque']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Relatório de Agendamentos -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Agendamentos por Status</h2>
                <a href="exportar.php?relatorio=agendamentos_por_status" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Status</th><th class="p-2">Total</th></tr></thead>
                    <tbody>
                        <?php foreach($agendamentos_por_status as $agendamento_status): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($agendamento_status['status']) ?></td><td class="p-2"><?= htmlspecialchars($agendamento_status['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Agendamentos por Serviço</h2>
                <a href="exportar.php?relatorio=agendamentos_por_servico" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Serviço</th><th class="p-2">Total</th></tr></thead>
                    <tbody>
                        <?php foreach($agendamentos_por_servico as $agendamento_servico): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($agendamento_servico['servico']) ?></td><td class="p-2"><?= htmlspecialchars($agendamento_servico['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Relatório de Pedidos -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Pedidos por Status</h2>
                <a href="exportar.php?relatorio=pedidos_por_status" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Status</th><th class="p-2">Total</th></tr></thead>
                    <tbody>
                        <?php foreach($pedidos_por_status as $pedido_status): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($pedido_status['status']) ?></td><td class="p-2"><?= htmlspecialchars($pedido_status['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Pedidos por Forma de Pagamento</h2>
                <a href="exportar.php?relatorio=pedidos_por_forma_pagamento" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Forma de Pagamento</th><th class="p-2">Total</th></tr></thead>
                    <tbody>
                        <?php foreach($pedidos_por_forma_pagamento as $forma_pagamento): ?>
                        <tr class="border-b"><td class="p-2"><?= htmlspecialchars($forma_pagamento['forma_pagamento']) ?></td><td class="p-2"><?= htmlspecialchars($forma_pagamento['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require '../footer.php'; ?>

