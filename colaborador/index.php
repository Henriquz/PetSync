<?php
include '../config.php';
include 'check_colaborador.php';
$page_title = 'Colaborador - Dashboard';

// Busca estatísticas do colaborador
$colaborador_id = $_SESSION['usuario']['id'];
$colaborador_nome = $_SESSION['usuario']['nome'];

// Agendamentos de hoje
$hoje = date('Y-m-d');
$stmt_hoje = $mysqli->prepare("
    SELECT COUNT(*) as total 
    FROM agendamentos 
    WHERE DATE(data_agendamento) = ? 
    AND status IN ('Pendente', 'Confirmado', 'Em Andamento')
");
$stmt_hoje->bind_param("s", $hoje);
$stmt_hoje->execute();
$agendamentos_hoje = $stmt_hoje->get_result()->fetch_assoc()['total'];
$stmt_hoje->close();

// Meus agendamentos em andamento
$stmt_meus = $mysqli->prepare("
    SELECT COUNT(*) as total 
    FROM agendamentos 
    WHERE colaborador_id = ? 
    AND status = 'Em Andamento'
");
$stmt_meus->bind_param("i", $colaborador_id);
$stmt_meus->execute();
$meus_em_andamento = $stmt_meus->get_result()->fetch_assoc()['total'];
$stmt_meus->close();

// Total concluído pelo colaborador este mês
$primeiro_dia_mes = date('Y-m-01');
$stmt_concluidos = $mysqli->prepare("
    SELECT COUNT(*) as total 
    FROM agendamentos 
    WHERE colaborador_id = ? 
    AND status = 'Concluído'
    AND DATE(data_agendamento) >= ?
");
$stmt_concluidos->bind_param("is", $colaborador_id, $primeiro_dia_mes);
$stmt_concluidos->execute();
$concluidos_mes = $stmt_concluidos->get_result()->fetch_assoc()['total'];
$stmt_concluidos->close();

require '../header.php';
?>

<div class="bg-petLightGray min-h-full">
    <div class="container mx-auto px-4 py-12">
        <div class="flex items-center gap-4 mb-8 border-b-4 border-petBlue pb-2">
            <h1 class="text-4xl font-bold text-petGray">Painel do Colaborador</h1>
            <div class="bg-petBlue text-white px-3 py-1 rounded-full text-sm font-medium">
                <?= htmlspecialchars($colaborador_nome) ?>
            </div>
        </div>
        
        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-petGray"><?= $agendamentos_hoje ?></p>
                        <p class="text-sm text-gray-600">Agendamentos Hoje</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-petGray"><?= $meus_em_andamento ?></p>
                        <p class="text-sm text-gray-600">Em Andamento</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-petGray"><?= $concluidos_mes ?></p>
                        <p class="text-sm text-gray-600">Concluídos Este Mês</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu de Ações -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-blue-100 text-petBlue rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-petBlue mb-2">Meus Agendamentos</h2>
                <p class="text-petGray text-sm flex-grow">Visualize e gerencie os agendamentos atribuídos a você.</p>
                <div class="mt-auto pt-4">
                    <a href="agendamentos.php" class="bg-petBlue hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Agendamentos</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-orange-100 text-petOrange rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-petOrange mb-2">Agenda do Dia</h2>
                <p class="text-petGray text-sm flex-grow">Veja todos os agendamentos programados para hoje.</p>
                <div class="mt-auto pt-4">
                    <a href="agenda.php" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Agenda</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col text-center hover:shadow-xl transition-shadow duration-300 h-full">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-green-600 mb-2">Meu Perfil</h2>
                <p class="text-petGray text-sm flex-grow">Visualize e edite suas informações pessoais.</p>
                <div class="mt-auto pt-4">
                    <a href="perfil.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Ver Perfil</a>
                </div>
            </div>
            
        </div>
        
        <!-- Ações Rápidas -->
        <div class="mt-8 bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold text-petGray mb-4">Ações Rápidas</h3>
            <div class="flex flex-wrap gap-4">
                <button onclick="iniciarProximoAgendamento()" class="bg-petBlue hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Iniciar Próximo Agendamento
                </button>
                <button onclick="marcarComoConcluido()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Marcar como Concluído
                </button>
                <a href="agendamentos.php?status=Em Andamento" class="bg-petOrange hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Ver Em Andamento
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function iniciarProximoAgendamento() {
    // Redireciona para a página de agendamentos com filtro para pendentes
    window.location.href = 'agendamentos.php?status=Pendente';
}

function marcarComoConcluido() {
    // Redireciona para a página de agendamentos com filtro para em andamento
    window.location.href = 'agendamentos.php?status=Em Andamento';
}
</script>

<?php require '../footer.php'; ?>

