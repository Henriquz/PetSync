<?php
// ======================================================================
// PetSync - Configurações de Agendamento v2.2 (Design Final)
// ======================================================================
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) { header('Location: ../login.php?erro=acesso_negado'); exit; }

$ok = '';
$erro = '';

// --- PROCESSAMENTO DOS FORMULÁRIOS (LÓGICA INALTERADA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_servico'])) {
    $nome_servico = trim($_POST['nome_servico'] ?? '');
    $duracao_minutos = (int)($_POST['duracao_minutos'] ?? 60);
    if (!empty($nome_servico) && $duracao_minutos > 0) {
        $stmt = $mysqli->prepare("INSERT INTO servicos (nome, duracao_minutos) VALUES (?, ?)");
        $stmt->bind_param("si", $nome_servico, $duracao_minutos);
        if ($stmt->execute()) { $_SESSION['ok_msg'] = "Serviço adicionado com sucesso!"; } 
        else { $_SESSION['erro_msg'] = "Erro ao adicionar o serviço."; }
        $stmt->close();
    } else {
        $_SESSION['erro_msg'] = "Nome e duração (maior que zero) do serviço são obrigatórios.";
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_servico_id'])) {
    $servico_id = (int)$_POST['toggle_servico_id'];
    $novo_status = (int)$_POST['status_atual'] ? 0 : 1;
    $stmt = $mysqli->prepare("UPDATE servicos SET ativo = ? WHERE id = ?");
    $stmt->bind_param("ii", $novo_status, $servico_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['ok_msg'] = "Status do serviço alterado com sucesso!";
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_opcoes'])) {
    $permitir_delivery = isset($_POST['permitir_delivery']) ? '1' : '0';
    $permitir_cliente_leva = isset($_POST['permitir_cliente_leva']) ? '1' : '0';
    $stmt = $mysqli->prepare("INSERT INTO configuracoes (chave, valor) VALUES ('permitir_delivery', ?), ('permitir_cliente_leva_e_busca', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $stmt->bind_param("ss", $permitir_delivery, $permitir_cliente_leva);
    if ($stmt->execute()) { $_SESSION['ok_msg'] = "Opções de agendamento salvas com sucesso!"; }
    else { $_SESSION['erro_msg'] = "Erro ao salvar as opções."; }
    $stmt->close();
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_horarios'])) {
    $dias = $_POST['dias'] ?? [];
    $mysqli->query("UPDATE horarios_atendimento SET ativo = 0"); 
    foreach ($dias as $dia_semana => $dados) {
        $ativo = isset($dados['ativo']) ? 1 : 0;
        $hora_inicio = !empty($dados['inicio']) ? $dados['inicio'] : null;
        $hora_fim = !empty($dados['fim']) ? $dados['fim'] : null;
        $pausa_inicio = !empty($dados['pausa_inicio']) ? $dados['pausa_inicio'] : null;
        $pausa_fim = !empty($dados['pausa_fim']) ? $dados['pausa_fim'] : null;
        $capacidade = (int)($dados['capacidade'] ?? 1);
        $stmt = $mysqli->prepare("INSERT INTO horarios_atendimento (dia_semana, hora_inicio, hora_fim, pausa_inicio, pausa_fim, capacidade_por_slot, ativo) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE hora_inicio = VALUES(hora_inicio), hora_fim = VALUES(hora_fim), pausa_inicio = VALUES(pausa_inicio), pausa_fim = VALUES(pausa_fim), capacidade_por_slot = VALUES(capacidade_por_slot), ativo = VALUES(ativo)");
        $stmt->bind_param("issssii", $dia_semana, $hora_inicio, $hora_fim, $pausa_inicio, $pausa_fim, $capacidade, $ativo);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['ok_msg'] = "Grade de horários atualizada com sucesso!";
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// --- BUSCA DE DADOS E MENSAGENS PARA EXIBIR NA PÁGINA ---
if (isset($_SESSION['ok_msg'])) { $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if (isset($_SESSION['erro_msg'])) { $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }
$servicos_cadastrados = $mysqli->query("SELECT * FROM servicos ORDER BY nome ASC");
$configuracoes_raw = $mysqli->query("SELECT * FROM configuracoes WHERE chave IN ('permitir_delivery', 'permitir_cliente_leva_e_busca')");
$configuracoes = [];
while($row = $configuracoes_raw->fetch_assoc()) { $configuracoes[$row['chave']] = $row['valor']; }
$horarios_result = $mysqli->query("SELECT * FROM horarios_atendimento");
$horarios_semana = [];
while($row = $horarios_result->fetch_assoc()){ $horarios_semana[$row['dia_semana']] = $row; }
$dias_da_semana_map = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 0 => 'Domingo'];
$page_title = "Configurar Agendamentos";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - PetSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        /* Estilo e animação dos TOASTS (notificações) */
        #toast-notification-container > div { animation: fadeInOut 5s forwards; }
        @keyframes fadeInOut { 0%, 100% { opacity: 0; transform: translateY(-20px); } 10%, 90% { opacity: 1; transform: translateY(0); } }
        /* Estilo dos toggles */
        .toggle-bg:after {
            content: ''; position: absolute; top: 2px; left: 2px;
            background-color: white; border-radius: 9999px;
            height: 1.25rem; width: 1.25rem;
            transition: transform 0.2s ease-in-out;
        }
        input:checked + .toggle-bg { background-color: #0078C8; border-color: #0078C8; }
        input:checked + .toggle-bg:after { transform: translateX(1.125rem); }
        [x-cloak] { display: none !important; }
    </style>
     <script>
        tailwind.config = { theme: { extend: { colors: { petOrange: '#FF7A00', petBlue: '#0078C8', petGray: '#4A5568' } } } }
    </script>
</head>
<body class="bg-slate-50">
<?php require '../header.php'; ?>

<div id="toast-notification-container" class="fixed top-24 right-5 z-[100] w-full max-w-sm">
    <?php if ($ok): ?><div class="bg-green-500 text-white p-4 rounded-lg shadow-lg mb-2"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="bg-red-500 text-white p-4 rounded-lg shadow-lg mb-2"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
</div>

<main class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-5xl mx-auto">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-petGray">Configurações de Agendamento</h1>
            <p class="text-lg text-gray-500 mt-1">Ajuste os serviços, horários e regras para seus clientes agendarem online.</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8">
            <h2 class="text-2xl font-semibold text-petGray mb-4 flex items-center">
                <svg class="w-6 h-6 mr-3 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                Gerenciar Serviços
            </h2>
            <form action="config_agendamento.php" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 pb-6 border-b border-gray-200">
                <div class="sm:col-span-1">
                    <label for="nome_servico" class="text-sm font-medium text-gray-700">Nome do Serviço</label>
                    <input type="text" id="nome_servico" name="nome_servico" placeholder="Ex: Banho e Tosa" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-petBlue focus:ring focus:ring-petBlue focus:ring-opacity-50" required>
                </div>
                <div>
                    <label for="duracao_minutos" class="text-sm font-medium text-gray-700">Duração (minutos)</label>
                    <input type="number" id="duracao_minutos" name="duracao_minutos" placeholder="Ex: 60" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-petBlue focus:ring focus:ring-petBlue focus:ring-opacity-50" required min="15" step="15" value="60">
                </div>
                <div class="self-end">
                    <button type="submit" name="add_servico" class="w-full px-4 py-2 bg-petBlue text-white font-semibold rounded-md hover:bg-blue-700 transition-colors">Adicionar Serviço</button>
                </div>
            </form>
            <ul class="space-y-3">
                <?php mysqli_data_seek($servicos_cadastrados, 0); while($servico = $servicos_cadastrados->fetch_assoc()): ?>
                    <li class="flex justify-between items-center p-3 rounded-lg border <?= $servico['ativo'] ? 'bg-white' : 'bg-gray-100 opacity-60' ?>">
                        <div>
                            <p class="text-gray-800 font-medium"><?= htmlspecialchars($servico['nome']) ?></p>
                            <p class="text-sm text-gray-500">Duração: <?= htmlspecialchars($servico['duracao_minutos']) ?> min</p>
                        </div>
                        <form action="config_agendamento.php" method="POST">
                            <input type="hidden" name="toggle_servico_id" value="<?= $servico['id'] ?>">
                            <input type="hidden" name="status_atual" value="<?= $servico['ativo'] ?>">
                            <button type="submit" class="text-xs font-bold py-1 px-3 rounded-full text-white uppercase tracking-wider <?= $servico['ativo'] ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600' ?>">
                                <?= $servico['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </button>
                        </form>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8">
            <h2 class="text-2xl font-semibold text-petGray mb-4 flex items-center">
                <svg class="w-6 h-6 mr-3 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Opções de Atendimento
            </h2>
            <form action="config_agendamento.php" method="POST" class="space-y-4">
                <label for="permitir_cliente_leva" class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <div>
                        <p class="font-medium text-gray-800">Atendimento na Loja</p>
                        <p class="text-sm text-gray-500">Permitir que o cliente leve e busque o pet no seu estabelecimento.</p>
                    </div>
                    <div class="relative inline-flex items-center">
                        <input type="checkbox" id="permitir_cliente_leva" name="permitir_cliente_leva" class="sr-only peer" <?= ($configuracoes['permitir_cliente_leva_e_busca'] ?? 0) ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-petBlue/50 toggle-bg border border-gray-300"></div>
                    </div>
                </label>
                <label for="permitir_delivery" class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <div>
                        <p class="font-medium text-gray-800">Serviço de Delivery</p>
                        <p class="text-sm text-gray-500">Oferecer a opção de buscar e entregar o pet na casa do cliente.</p>
                    </div>
                    <div class="relative inline-flex items-center">
                        <input type="checkbox" id="permitir_delivery" name="permitir_delivery" class="sr-only peer" <?= ($configuracoes['permitir_delivery'] ?? 0) ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-petBlue/50 toggle-bg border border-gray-300"></div>
                    </div>
                </label>
                <div class="text-right pt-4 mt-4">
                    <button type="submit" name="salvar_opcoes" class="px-6 py-2 bg-petOrange text-white font-semibold rounded-md hover:bg-orange-600 transition-colors">Salvar Opções</button>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
             <h2 class="text-2xl font-semibold text-petGray mb-2 flex items-center">
                <svg class="w-6 h-6 mr-3 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Grade de Horários e Capacidade
            </h2>
            <p class="text-sm text-gray-500 mb-6">Selecione os dias de trabalho e defina os horários. O sistema usará a duração de cada serviço para criar os horários disponíveis para os clientes.</p>
            <form action="config_agendamento.php" method="POST">
                <div class="space-y-4">
                    <?php foreach ($dias_da_semana_map as $num_dia => $nome_dia): ?>
                        <?php
                            $horario_dia = $horarios_semana[$num_dia] ?? null;
                            $is_ativo = $horario_dia && $horario_dia['ativo'];
                        ?>
                        <div x-data="{ ativo: <?= $is_ativo ? 'true' : 'false' ?> }" x-cloak class="p-4 rounded-lg border transition-all duration-300" :class="ativo ? 'border-petBlue bg-white shadow-sm' : 'border-gray-200 bg-gray-50'">
                            <div class="flex items-center cursor-pointer" @click="ativo = !ativo">
                                <input type="checkbox" name="dias[<?= $num_dia ?>][ativo]" :checked="ativo" class="h-5 w-5 text-petBlue rounded-md focus:ring-0 focus:ring-offset-0 pointer-events-none">
                                <label class="ml-3 font-semibold text-lg text-petGray"><?= $nome_dia ?></label>
                                <span class="ml-auto text-sm font-bold" :class="ativo ? 'text-green-600' : 'text-gray-500'">
                                    <span x-show="ativo">Aberto</span>
                                    <span x-show="!ativo">Fechado</span>
                                </span>
                            </div>
                            <div x-show="ativo" x-transition class="pt-4 mt-4 border-t space-y-4" :class="ativo ? 'border-gray-200' : 'border-transparent'">
                                <h4 class="font-medium text-gray-600">Horário de Trabalho</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="inicio_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Abre às</label>
                                        <input type="time" name="dias[<?= $num_dia ?>][inicio]" id="inicio_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['hora_inicio'] ?? '09:00') ?>" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-petBlue focus:ring focus:ring-petBlue focus:ring-opacity-50">
                                    </div>
                                    <div>
                                        <label for="fim_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Fecha às</label>
                                        <input type="time" name="dias[<?= $num_dia ?>][fim]" id="fim_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['hora_fim'] ?? '18:00') ?>" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-petBlue focus:ring focus:ring-petBlue focus:ring-opacity-50">
                                    </div>
                                </div>
                                <h4 class="font-medium text-gray-600">Intervalo (opcional)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                     <div>
                                        <label for="pausa_inicio_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Início Pausa</label>
                                        <input type="time" name="dias[<?= $num_dia ?>][pausa_inicio]" id="pausa_inicio_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['pausa_inicio'] ?? '') ?>" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-petBlue focus:ring focus:ring-petBlue focus:ring-opacity-50">
                                    </div>
                                    <div>
                                        <label for="pausa_fim_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Fim Pausa</label>
                                        <input type="time" name="dias[<?= $num_dia ?>][pausa_fim]" id="pausa_fim_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['pausa_fim'] ?? '') ?>" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-petBlue focus:ring focus:ring-petBlue focus:ring-opacity-50">
                                    </div>
                                </div>
                                <h4 class="font-medium text-gray-600">Capacidade</h4>
                                <div>
                                    <label for="cap_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Atendimentos simultâneos (por horário)</label>
                                    <input type="number" name="dias[<?= $num_dia ?>][capacidade]" id="cap_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['capacidade_por_slot'] ?? 1) ?>" min="1" class="mt-1 block w-full sm:w-1/2 rounded-md border border-gray-300 shadow-sm focus:border-petBlue focus:ring focus:ring-petBlue focus:ring-opacity-50">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-right pt-4 mt-6">
                     <button type="submit" name="salvar_horarios" class="px-6 py-2 bg-petOrange text-white font-semibold rounded-md hover:bg-orange-600 transition-colors">Salvar Grade de Horários</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require '../footer.php'; ?>
</body>
</html>