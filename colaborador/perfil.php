<?php
include '../config.php';
include 'check_colaborador.php';
$page_title = 'Colaborador - Meu Perfil';

$colaborador_id = $_SESSION['usuario']['id'];
$colaborador_nome = $_SESSION['usuario']['nome'];

// Busca dados completos do colaborador
$stmt = $mysqli->prepare("SELECT nome, email, telefone FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $colaborador_id);
$stmt->execute();
$colaborador = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Busca estatísticas do colaborador
$stmt_stats = $mysqli->prepare("
    SELECT 
        COUNT(*) as total_agendamentos,
        SUM(CASE WHEN status = 'Concluído' THEN 1 ELSE 0 END) as concluidos,
        SUM(CASE WHEN status = 'Em Andamento' THEN 1 ELSE 0 END) as em_andamento
    FROM agendamentos 
    WHERE colaborador_id = ?
");
$stmt_stats->bind_param("i", $colaborador_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

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
                <h1 class="text-4xl font-bold text-petGray">Meu Perfil</h1>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Informações Pessoais -->
            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-petGray mb-6">Informações Pessoais</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                            <p class="text-lg text-petGray"><?= htmlspecialchars($colaborador['nome']) ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <p class="text-lg text-petGray"><?= htmlspecialchars($colaborador['email']) ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <p class="text-lg text-petGray"><?= htmlspecialchars($colaborador['telefone'] ?: 'Não informado') ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Conta</label>
                            <span class="bg-petBlue text-white px-3 py-1 rounded-full text-sm font-medium">
                                Colaborador
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t">
                        <p class="text-sm text-gray-600">
                            <strong>Nota:</strong> Para alterar suas informações pessoais, entre em contato com o administrador do sistema.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="space-y-6">
                
                <!-- Card de Estatísticas -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-petGray mb-4">Minhas Estatísticas</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total de Agendamentos</span>
                            <span class="text-2xl font-bold text-petBlue"><?= $stats['total_agendamentos'] ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Concluídos</span>
                            <span class="text-2xl font-bold text-green-600"><?= $stats['concluidos'] ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Em Andamento</span>
                            <span class="text-2xl font-bold text-orange-600"><?= $stats['em_andamento'] ?></span>
                        </div>
                        
                        <?php if ($stats['total_agendamentos'] > 0): ?>
                        <div class="pt-4 border-t">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Taxa de Conclusão</span>
                                <span class="text-lg font-semibold text-petGray">
                                    <?= round(($stats['concluidos'] / $stats['total_agendamentos']) * 100, 1) ?>%
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Ações Rápidas -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-petGray mb-4">Ações Rápidas</h3>
                    
                    <div class="space-y-3">
                        <a href="agendamentos.php" class="block w-full bg-petBlue hover:bg-blue-700 text-white text-center py-2 px-4 rounded-md transition-colors">
                            Ver Meus Agendamentos
                        </a>
                        
                        <a href="agenda.php" class="block w-full bg-petOrange hover:bg-orange-600 text-white text-center py-2 px-4 rounded-md transition-colors">
                            Agenda do Dia
                        </a>
                        
                        <a href="agendamentos.php?status=Em Andamento" class="block w-full bg-orange-500 hover:bg-orange-600 text-white text-center py-2 px-4 rounded-md transition-colors">
                            Em Andamento
                        </a>
                    </div>
                </div>
                
                <!-- Informações do Sistema -->
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-petGray mb-4">Informações</h3>
                    
                    <div class="space-y-3 text-sm text-gray-600">
                        <p><strong>Sistema:</strong> PetSync v1.0</p>
                        <p><strong>Último acesso:</strong> <?= date('d/m/Y H:i') ?></p>
                        <p><strong>Suporte:</strong> Entre em contato com o administrador para dúvidas ou problemas técnicos.</p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php require '../footer.php'; ?>

