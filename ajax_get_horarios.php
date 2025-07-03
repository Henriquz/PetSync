<?php
// ======================================================================
// PetSync - AJAX para buscar horários disponíveis (v2 - Corrigido)
// ======================================================================

header('Content-Type: application/json');
include 'config.php';

// --- VALIDAÇÃO DOS DADOS DE ENTRADA ---
$date_str = $_GET['date'] ?? null;
// MODIFICADO: Agora vamos usar a duração total enviada pelo Javascript
$duracao_total_servicos = isset($_GET['duracao']) ? (int)$_GET['duracao'] : 0;

if (!$date_str || $duracao_total_servicos <= 0) {
    echo json_encode(['erro' => 'Dados insuficientes (data ou duração dos serviços não fornecidos).']);
    exit;
}

try {
    $date = new DateTime($date_str);
    $dia_semana = (int)$date->format('w'); // 0 (Domingo) a 6 (Sábado)
} catch (Exception $e) {
    echo json_encode(['erro' => 'Formato de data inválido.']);
    exit;
}

// --- 1. BUSCAR REGRA DE HORÁRIO PARA O DIA DA SEMANA ---
$stmt_horario = $mysqli->prepare("SELECT hora_inicio, hora_fim, pausa_inicio, pausa_fim, capacidade_por_slot FROM horarios_atendimento WHERE dia_semana = ? AND ativo = 1");
$stmt_horario->bind_param("i", $dia_semana);
$stmt_horario->execute();
$horario_regra = $stmt_horario->get_result()->fetch_assoc();
$stmt_horario->close();

if (!$horario_regra) {
    echo json_encode(['disponiveis' => [], 'trabalha' => false]); // Dia não trabalhado
    exit;
}

// --- 2. BUSCAR AGENDAMENTOS EXISTENTES E SUAS DURAÇÕES PARA O DIA ---
// Esta query foi otimizada para buscar a duração de múltiplos serviços de um agendamento
$stmt_agendamentos = $mysqli->prepare(
    "SELECT 
        a.data_agendamento,
        (SELECT SUM(s.duracao_minutos) FROM servicos s WHERE FIND_IN_SET(s.nome, REPLACE(a.servico, ', ', ','))) as duracao_total
     FROM agendamentos a
     WHERE DATE(a.data_agendamento) = ? AND a.status != 'Cancelado'"
);
$date_sql = $date->format('Y-m-d');
$stmt_agendamentos->bind_param("s", $date_sql);
$stmt_agendamentos->execute();
$agendamentos_existentes_raw = $stmt_agendamentos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_agendamentos->close();


// --- 3. CALCULAR SLOTS DISPONÍVEIS ---
$horarios_disponiveis = [];
$inicio_dia = new DateTime($date->format('Y-m-d') . ' ' . $horario_regra['hora_inicio']);
$fim_dia = new DateTime($date->format('Y-m-d') . ' ' . $horario_regra['hora_fim']);
$pausa_inicio = $horario_regra['pausa_inicio'] ? new DateTime($date->format('Y-m-d') . ' ' . $horario_regra['pausa_inicio']) : null;
$pausa_fim = $horario_regra['pausa_fim'] ? new DateTime($date->format('Y-m-d') . ' ' . $horario_regra['pausa_fim']) : null;
$capacidade_total = (int)$horario_regra['capacidade_por_slot'];
$intervalo_slot = new DateInterval('PT15M'); // Verifica a disponibilidade a cada 15 minutos

$periodo_total = new DatePeriod($inicio_dia, $intervalo_slot, $fim_dia);

foreach ($periodo_total as $horario_potencial) {
    // Pula horários no passado
    if ($horario_potencial < new DateTime()) {
        continue;
    }
    
    $fim_potencial = clone $horario_potencial;
    $fim_potencial->add(new DateInterval('PT' . $duracao_total_servicos . 'M'));

    // Verifica se o slot está dentro do horário de trabalho
    if ($fim_potencial > $fim_dia) {
        continue; // O serviço terminaria depois do expediente
    }

    // Verifica se o slot colide com a pausa
    if ($pausa_inicio && $pausa_fim) {
        if (($horario_potencial < $pausa_fim && $fim_potencial > $pausa_inicio)) {
            continue; // O serviço colide com o horário de almoço/pausa
        }
    }

    // Verifica a capacidade (quantos agendamentos estão acontecendo simultaneamente)
    $agendamentos_simultaneos = 0;
    foreach ($agendamentos_existentes_raw as $ag) {
        if (empty($ag['data_agendamento']) || empty($ag['duracao_total'])) continue;

        $ag_inicio = new DateTime($ag['data_agendamento']);
        $ag_fim = clone $ag_inicio;
        $ag_fim->add(new DateInterval('PT' . (int)$ag['duracao_total'] . 'M'));

        // Verifica se há sobreposição de horários
        if ($horario_potencial < $ag_fim && $fim_potencial > $ag_inicio) {
            $agendamentos_simultaneos++;
        }
    }

    if ($agendamentos_simultaneos < $capacidade_total) {
        $horarios_disponiveis[] = [
            'time' => $horario_potencial->format('H:i'),
            'available' => true
        ];
    }
}

echo json_encode(['disponiveis' => $horarios_disponiveis, 'trabalha' => true]);
?>