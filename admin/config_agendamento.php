<?php
// ======================================================================
// PetSync - Configurações de Agendamento v2.0 (Horários e Duração)
// ======================================================================
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) { header('Location: ../login.php?erro=acesso_negado'); exit; }

$ok = '';
$erro = '';

// --- PROCESSAMENTO DOS FORMULÁRIOS ---

// Lógica para ADICIONAR um novo serviço (com duração)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_servico'])) {
    $nome_servico = trim($_POST['nome_servico'] ?? '');
    $duracao_minutos = (int)($_POST['duracao_minutos'] ?? 60);
    if (!empty($nome_servico) && $duracao_minutos > 0) {
        $stmt = $mysqli->prepare("INSERT INTO servicos (nome, duracao_minutos) VALUES (?, ?)");
        $stmt->bind_param("si", $nome_servico, $duracao_minutos);
        if ($stmt->execute()) { $ok = "Serviço adicionado com sucesso!"; } 
        else { $erro = "Erro ao adicionar o serviço."; }
        $stmt->close();
    } else {
        $erro = "Nome e duração (maior que zero) do serviço são obrigatórios.";
    }
}

// Lógica para ATIVAR/DESATIVAR um serviço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_servico_id'])) {
    $servico_id = (int)$_POST['toggle_servico_id'];
    $novo_status = (int)$_POST['status_atual'] ? 0 : 1;
    $stmt = $mysqli->prepare("UPDATE servicos SET ativo = ? WHERE id = ?");
    $stmt->bind_param("ii", $novo_status, $servico_id);
    $stmt->execute();
    $stmt->close();
    $ok = "Status do serviço alterado com sucesso!";
}

// Lógica para SALVAR as opções de entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_opcoes'])) {
    $permitir_delivery = isset($_POST['permitir_delivery']) ? '1' : '0';
    $permitir_cliente_leva = isset($_POST['permitir_cliente_leva']) ? '1' : '0';
    $stmt = $mysqli->prepare("INSERT INTO configuracoes (chave, valor) VALUES ('permitir_delivery', ?), ('permitir_cliente_leva_e_busca', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $stmt->bind_param("ss", $permitir_delivery, $permitir_cliente_leva);
    if ($stmt->execute()) { $ok = "Opções de agendamento salvas com sucesso!"; }
    else { $erro = "Erro ao salvar as opções."; }
    $stmt->close();
}

// Lógica para salvar os HORÁRIOS, PAUSAS E CAPACIDADE
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
    $ok = "Grade de horários atualizada com sucesso!";
}

// --- BUSCA DE DADOS PARA EXIBIR NA PÁGINA ---
$servicos_cadastrados = $mysqli->query("SELECT * FROM servicos ORDER BY nome ASC");
$configuracoes_raw = $mysqli->query("SELECT * FROM configuracoes WHERE chave IN ('permitir_delivery', 'permitir_cliente_leva_e_busca')");
$configuracoes = [];
while($row = $configuracoes_raw->fetch_assoc()) { $configuracoes[$row['chave']] = $row['valor']; }
$horarios_result = $mysqli->query("SELECT * FROM horarios_atendimento");
$horarios_semana = [];
while($row = $horarios_result->fetch_assoc()){ $horarios_semana[$row['dia_semana']] = $row; }
$dias_da_semana_map = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 0 => 'Domingo'];

$page_title = "Configurar Agendamentos";
require '../header.php';
?>
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<main class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-petGray mb-6">Configurações de Agendamento</h1>
        <?php if ($ok): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($ok) ?></p></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($erro) ?></p></div><?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-semibold text-petGray mb-4">Gerenciar Serviços</h2>
            <form action="config_agendamento.php" method="POST" class="flex flex-col sm:flex-row gap-4 mb-6 pb-6 border-b">
                <input type="text" name="nome_servico" placeholder="Nome do novo serviço" class="flex-grow form-input rounded-md border-gray-300 shadow-sm focus:border-petBlue focus:ring-petBlue" required>
                <input type="number" name="duracao_minutos" placeholder="Duração (minutos)" class="form-input rounded-md border-gray-300 shadow-sm focus:border-petBlue focus:ring-petBlue" required min="15">
                <button type="submit" name="add_servico" class="px-4 py-2 bg-petBlue text-white font-semibold rounded-md hover:bg-blue-700 w-full sm:w-auto">Adicionar</button>
            </form>
            <ul class="space-y-2">
                <?php mysqli_data_seek($servicos_cadastrados, 0); while($servico = $servicos_cadastrados->fetch_assoc()): ?>
                    <li class="flex justify-between items-center p-2 rounded-md <?= $servico['ativo'] ? 'bg-green-50' : 'bg-gray-100' ?>">
                        <div>
                            <p class="text-gray-800 font-medium"><?= htmlspecialchars($servico['nome']) ?></p>
                            <p class="text-xs text-gray-500">Duração: <?= htmlspecialchars($servico['duracao_minutos']) ?> min</p>
                        </div>
                        <form action="config_agendamento.php" method="POST">
                            <input type="hidden" name="toggle_servico_id" value="<?= $servico['id'] ?>">
                            <input type="hidden" name="status_atual" value="<?= $servico['ativo'] ?>">
                            <button type="submit" class="text-xs font-bold py-1 px-3 rounded-full text-white <?= $servico['ativo'] ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600' ?>">
                                <?= $servico['ativo'] ? 'ATIVO' : 'INATIVO' ?>
                            </button>
                        </form>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-semibold text-petGray mb-4">Opções de Entrega e Retirada</h2>
            <form action="config_agendamento.php" method="POST" class="space-y-4">
                <div class="relative flex items-start">
                    <div class="flex items-center h-5"><input id="permitir_cliente_leva" name="permitir_cliente_leva" type="checkbox" class="focus:ring-petBlue h-4 w-4 text-petBlue border-gray-300 rounded" <?= ($configuracoes['permitir_cliente_leva_e_busca'] ?? 0) ? 'checked' : '' ?>></div>
                    <div class="ml-3 text-sm"><label for="permitir_cliente_leva" class="font-medium text-gray-700">Permitir que o cliente leve e busque o pet na loja</label></div>
                </div>
                <div class="relative flex items-start">
                    <div class="flex items-center h-5"><input id="permitir_delivery" name="permitir_delivery" type="checkbox" class="focus:ring-petBlue h-4 w-4 text-petBlue border-gray-300 rounded" <?= ($configuracoes['permitir_delivery'] ?? 0) ? 'checked' : '' ?>></div>
                    <div class="ml-3 text-sm"><label for="permitir_delivery" class="font-medium text-gray-700">Oferecer serviço de busca e entrega (Delivery)</label></div>
                </div>
                <div class="text-right pt-4 border-t mt-4">
                    <button type="submit" name="salvar_opcoes" class="px-6 py-2 bg-petOrange text-white font-semibold rounded-md hover:bg-orange-600">Salvar Opções</button>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-semibold text-petGray mb-4">Grade de Horários e Capacidade</h2>
            <p class="text-sm text-gray-500 mb-4">Defina seus horários de trabalho, pausas para almoço e quantos pets podem ser atendidos no mesmo horário. Deixe os campos de pausa em branco se não houver intervalo.</p>
            <form action="config_agendamento.php" method="POST">
                <div class="space-y-4">
                    <?php foreach ($dias_da_semana_map as $num_dia => $nome_dia): ?>
                        <?php
                            $horario_dia = $horarios_semana[$num_dia] ?? null;
                            $is_ativo = $horario_dia && $horario_dia['ativo'];
                        ?>
                        <div x-data="{ ativo: <?= $is_ativo ? 'true' : 'false' ?> }" class="p-4 rounded-md border" :class="ativo ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'">
                            <div class="flex items-center">
                                <input type="checkbox" name="dias[<?= $num_dia ?>][ativo]" id="dia_<?= $num_dia ?>" class="h-5 w-5 text-petBlue rounded focus:ring-petBlue" x-model="ativo">
                                <label for="dia_<?= $num_dia ?>" class="ml-3 font-semibold text-lg text-petGray"><?= $nome_dia ?></label>
                            </div>
                            <div x-show="ativo" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-2 pl-8">
                                <div>
                                    <label for="inicio_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Abre às</label>
                                    <input type="time" name="dias[<?= $num_dia ?>][inicio]" id="inicio_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['hora_inicio'] ?? '09:00') ?>" class="mt-1 w-full form-input rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label for="fim_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Fecha às</label>
                                    <input type="time" name="dias[<?= $num_dia ?>][fim]" id="fim_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['hora_fim'] ?? '18:00') ?>" class="mt-1 w-full form-input rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label for="pausa_inicio_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Início Pausa</label>
                                    <input type="time" name="dias[<?= $num_dia ?>][pausa_inicio]" id="pausa_inicio_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['pausa_inicio'] ?? '') ?>" class="mt-1 w-full form-input rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label for="pausa_fim_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Fim Pausa</label>
                                    <input type="time" name="dias[<?= $num_dia ?>][pausa_fim]" id="pausa_fim_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['pausa_fim'] ?? '') ?>" class="mt-1 w-full form-input rounded-md border-gray-300">
                                </div>
                                <div class="sm:col-span-2 lg:col-span-4">
                                    <label for="cap_<?= $num_dia ?>" class="text-xs font-medium text-gray-700">Capacidade de Atendimentos Simultâneos (por horário)</label>
                                    <input type="number" name="dias[<?= $num_dia ?>][capacidade]" id="cap_<?= $num_dia ?>" value="<?= htmlspecialchars($horario_dia['capacidade_por_slot'] ?? 1) ?>" min="1" class="mt-1 w-full form-input rounded-md border-gray-300">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-right pt-4 border-t mt-6">
                     <button type="submit" name="salvar_horarios" class="px-6 py-2 bg-petOrange text-white font-semibold rounded-md hover:bg-orange-600">Salvar Grade de Horários</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php 
require '../footer.php'; 
?>