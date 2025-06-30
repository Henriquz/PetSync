<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Relatórios';

// --- CONSULTAS PARA OS RELATÓRIOS (sem alterações) ---
$ultimos_usuarios = $mysqli->query("SELECT nome, email, data_cadastro FROM usuarios ORDER BY data_cadastro DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$clientes_ativos = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_admin = 0 AND is_active = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$admins_ativos = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_admin = 1 AND is_active = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$total_produtos = $mysqli->query("SELECT COUNT(id) as total FROM produtos")->fetch_assoc()['total'];

require '../header.php';
?>

<div class="container mx-auto px-4 py-12">
    <h1 class="text-4xl font-bold text-petGray mb-8">Relatórios Gerenciais</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-lg text-center">
            <h3 class="text-xl font-semibold text-petGray">Total de Produtos Cadastrados</h3>
            <p class="text-5xl font-bold text-petBlue mt-2"><?= $total_produtos ?></p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-petBlue">Últimos 10 Usuários</h2>
                <a href="exportar.php?relatorio=ultimos_usuarios" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar para Excel (CSV)</a>
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
                 <a href="exportar.php?relatorio=clientes_ativos" class="text-sm bg-green-600 text-white py-1 px-3 rounded-lg hover:bg-green-700">Exportar para Excel (CSV)</a>
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

    </div>
</div>

<?php require '../footer.php'; ?>