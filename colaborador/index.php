<?php
include '../config.php';
include 'check_colaborador.php';
$page_title = 'Colaborador - Dashboard';

// Busca estatísticas do colaborador (LÓGICA INALTERADA)
$colaborador_id = $_SESSION['usuario']['id'];
$colaborador_nome = $_SESSION['usuario']['nome'];
$primeiro_nome = explode(' ', $colaborador_nome)[0];

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

<div class="bg-slate-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 md:py-12">
        
        <div class="max-w-4xl mx-auto space-y-10">

            <div class="text-center">
                <h2 class="text-2xl font-semibold text-gray-600">Olá, <span class="text-petBlue font-bold"><?= htmlspecialchars($primeiro_nome) ?></span>!</h2>
                <h1 class="text-4xl md:text-5xl font-bold text-petGray mt-1">O que você precisa fazer hoje?</h1>
                <p class="text-gray-500 mt-2 max-w-2xl mx-auto">Este é seu painel para acessar agendamentos e ver seu progresso diário.</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                <h3 class="text-xl font-bold text-petGray mb-4">Seu Resumo</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    
                    <div class="bg-blue-50 p-4 rounded-lg flex items-center gap-4">
                        <div class="w-12 h-12 bg-petBlue text-white rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-petGray"><?= $agendamentos_hoje ?></p>
                            <p class="text-sm text-gray-600">Agendamentos Hoje</p>
                        </div>
                    </div>
                    
                    <div class="bg-orange-50 p-4 rounded-lg flex items-center gap-4">
                        <div class="w-12 h-12 bg-petOrange text-white rounded-lg flex items-center justify-center flex-shrink-0">
                             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-petGray"><?= $meus_em_andamento ?></p>
                            <p class="text-sm text-gray-600">Em Andamento</p>
                        </div>
                    </div>

                    <div class="bg-green-50 p-4 rounded-lg flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-600 text-white rounded-lg flex items-center justify-center flex-shrink-0">
                             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-petGray"><?= $concluidos_mes ?></p>
                            <p class="text-sm text-gray-600">Concluídos no Mês</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                 <h3 class="text-xl font-bold text-petGray mb-4">Suas Ferramentas</h3>
                 <div class="space-y-4">
                     
                    <a href="agendamentos.php" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-blue-100 hover:shadow-sm transition-all duration-200">
                        <div class="w-10 h-10 flex-shrink-0 bg-petBlue text-white rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-bold text-lg text-petGray">Gerenciar Agendamentos</h4>
                            <p class="text-sm text-gray-500">Acesse a lista de todos os serviços que foram atribuídos a você.</p>
                        </div>
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-petBlue transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>

                     <a href="agenda.php" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-orange-100 hover:shadow-sm transition-all duration-200">
                        <div class="w-10 h-10 flex-shrink-0 bg-petOrange text-white rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-bold text-lg text-petGray">Agenda do Dia</h4>
                            <p class="text-sm text-gray-500">Visualize em formato de linha do tempo os agendamentos de hoje.</p>
                        </div>
                         <svg class="w-6 h-6 text-gray-400 group-hover:text-petOrange transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>

                     <a href="perfil.php" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-green-100 hover:shadow-sm transition-all duration-200">
                        <div class="w-10 h-10 flex-shrink-0 bg-green-600 text-white rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-bold text-lg text-petGray">Meu Perfil</h4>
                            <p class="text-sm text-gray-500">Edite suas informações pessoais e altere sua senha.</p>
                        </div>
                         <svg class="w-6 h-6 text-gray-400 group-hover:text-green-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>

                 </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                <h3 class="text-xl font-bold text-petGray mb-4">Ações Rápidas</h3>
                <p class="text-sm text-gray-500 mb-4">Use estes atalhos para as tarefas mais comuns.</p>
                <div class="flex flex-wrap gap-4">
                    <button onclick="iniciarProximoAgendamento()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2 rounded-lg transition-colors shadow hover:shadow-md">
                        Iniciar Próximo Agendamento
                    </button>
                    <button onclick="marcarComoConcluido()" class="bg-gray-700 hover:bg-gray-800 text-white font-bold px-5 py-2 rounded-lg transition-colors shadow hover:shadow-md">
                        Ver Atendimentos em Andamento
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// A lógica Javascript permanece a mesma, apenas atualizei os comentários para maior clareza.
function iniciarProximoAgendamento() {
    // Redireciona para a página de agendamentos com filtro para pendentes, que são os próximos a serem iniciados.
    window.location.href = 'agendamentos.php?status=Pendente';
}

function marcarComoConcluido() {
    // Redireciona para a página de agendamentos com filtro para "Em Andamento", que são os que podem ser concluídos.
    window.location.href = 'agendamentos.php?status=Em Andamento';
}
</script>

<?php require '../footer.php'; ?>