<?php
// ======================================================================
// PetSync - Painel de Gerenciamento vFinal (Design Sutil e Profissional)
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include '../config.php'; // A função criar_notificacao() deve estar em config.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$page_title = 'Gerenciar Agendamentos - PetSync';
$ok = '';
$erro = '';

// 2. PROCESSAMENTO DE AÇÕES (POST)
// ----------------------------------------------------------------------
$current_params = "?status=" . urlencode($_GET['status'] ?? 'all')
                . "&data_filtro=" . urlencode($_GET['data_filtro'] ?? 'all')
                . "&mostrar_cancelados=" . urlencode($_GET['mostrar_cancelados'] ?? '0');
$redirect_url = htmlspecialchars($_SERVER['PHP_SELF']) . $current_params;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento_id = $_POST['agendamento_id'] ?? null;

    if ($agendamento_id && is_numeric($agendamento_id)) {
        
        // Antes de qualquer ação, pega as informações para a notificação
        $info_stmt = $mysqli->prepare("SELECT a.usuario_id, p.nome as pet_nome FROM agendamentos a LEFT JOIN pets p ON a.pet_id = p.id WHERE a.id = ?");
        $info_stmt->bind_param("i", $agendamento_id);
        $info_stmt->execute();
        $ag_info = $info_stmt->get_result()->fetch_assoc();
        $info_stmt->close();

        if (isset($_POST['iniciar_atendimento'])) {
            $stmt = $mysqli->prepare("UPDATE agendamentos SET status = 'Em Andamento' WHERE id = ? AND (status = 'Pendente' OR status = 'Confirmado')");
            $stmt->bind_param("i", $agendamento_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['ok_msg'] = "Atendimento iniciado com sucesso.";
                if ($ag_info) {
                    $msg = "O atendimento para " . htmlspecialchars($ag_info['pet_nome']) . " foi iniciado!";
                    criar_notificacao($mysqli, $ag_info['usuario_id'], $msg, 'meus_agendamentos.php');
                }
            } else { $_SESSION['erro_msg'] = "Erro ao iniciar atendimento."; }
            $stmt->close();
        }
        elseif (isset($_POST['concluir_agendamento'])) {
            $obs_admin = trim($_POST['observacoes_admin'] ?? '');
            $stmt = $mysqli->prepare("UPDATE agendamentos SET status = 'Concluído', observacoes_admin = ? WHERE id = ? AND status = 'Em Andamento'");
            $stmt->bind_param("si", $obs_admin, $agendamento_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['ok_msg'] = "Agendamento concluído com sucesso.";
                 if ($ag_info) {
                    $msg = "Oba! O atendimento para " . htmlspecialchars($ag_info['pet_nome']) . " foi concluído e seu pet já pode ser retirado!";
                    criar_notificacao($mysqli, $ag_info['usuario_id'], $msg, 'meus_agendamentos.php');
                }
            } else { $_SESSION['erro_msg'] = "Erro ao concluir o agendamento."; }
            $stmt->close();
        }
        elseif (isset($_POST['cancelar_agendamento'])) {
            $motivo_cancelamento = trim($_POST['motivo_cancelamento'] ?? '');
            
            $stmt_check = $mysqli->prepare("SELECT status FROM agendamentos WHERE id = ?");
            $stmt_check->bind_param("i", $agendamento_id);
            $stmt_check->execute();
            $status_atual = $stmt_check->get_result()->fetch_assoc()['status'] ?? null;
            $stmt_check->close();

            if (empty($motivo_cancelamento)) { // A validação principal agora é no JS, mas mantemos uma no back-end por segurança
                $_SESSION['erro_msg'] = "O motivo do cancelamento é obrigatório.";
            } else {
                $stmt = $mysqli->prepare("UPDATE agendamentos SET status = 'Cancelado', observacoes_admin = ? WHERE id = ? AND status IN ('Pendente', 'Confirmado', 'Em Andamento')");
                $stmt->bind_param("si", $motivo_cancelamento, $agendamento_id);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $_SESSION['ok_msg'] = "Agendamento cancelado com sucesso.";
                    if ($ag_info) {
                        $msg = "Atenção: o agendamento para " . htmlspecialchars($ag_info['pet_nome']) . " foi cancelado.";
                        if(!empty($motivo_cancelamento)) $msg .= " Motivo: " . $motivo_cancelamento;
                        criar_notificacao($mysqli, $ag_info['usuario_id'], $msg, 'meus_agendamentos.php');
                    }
                } else { $_SESSION['erro_msg'] = "Erro ao cancelar o agendamento."; }
                $stmt->close();
            }
        }
    } else {
        $_SESSION['erro_msg'] = "ID de agendamento inválido.";
    }
    header("Location: $redirect_url");
    exit;
}

// 3. LÓGICA DE PAGINAÇÃO E FILTRO
$itens_por_pagina = 9;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$status_filtro = isset($_GET['status']) && in_array($_GET['status'], ['Pendente', 'Confirmado', 'Em Andamento', 'Concluído', 'Cancelado']) ? $_GET['status'] : 'all';
$data_filtro = isset($_GET['data_filtro']) && in_array($_GET['data_filtro'], ['hoje', 'amanha', 'semana']) ? $_GET['data_filtro'] : 'all';
$mostrar_cancelados = isset($_GET['mostrar_cancelados']) && $_GET['mostrar_cancelados'] == '1';

$where_conditions = [];
$bind_params_types = "";
$bind_params_values = [];

if ($status_filtro !== 'all') {
    $where_conditions[] = "a.status = ?";
    $bind_params_types .= "s";
    $bind_params_values[] = $status_filtro;
}
if ($data_filtro !== 'all') {
    switch($data_filtro) {
        case 'hoje': $where_conditions[] = "DATE(a.data_agendamento) = CURDATE()"; break;
        case 'amanha': $where_conditions[] = "DATE(a.data_agendamento) = CURDATE() + INTERVAL 1 DAY"; break;
        case 'semana': $where_conditions[] = "YEARWEEK(a.data_agendamento, 1) = YEARWEEK(CURDATE(), 1)"; break;
    }
}
if (!$mostrar_cancelados && $status_filtro !== 'Cancelado') {
    $where_conditions[] = "a.status != 'Cancelado'";
}

$where_sql = empty($where_conditions) ? "1" : implode(" AND ", $where_conditions);

$count_query = "SELECT COUNT(*) FROM agendamentos a WHERE $where_sql";
$stmt_count = $mysqli->prepare($count_query);
if (!empty($bind_params_values)) $stmt_count->bind_param($bind_params_types, ...$bind_params_values);
$stmt_count->execute();
$total_agendamentos = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$total_paginas = ceil($total_agendamentos / $itens_por_pagina);
if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// 4. BUSCA DE DADOS PARA EXIBIÇÃO
if (isset($_SESSION['ok_msg'])) { $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if (isset($_SESSION['erro_msg'])) { $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }

$query = "SELECT a.*, p.nome as pet_nome, u.nome as cliente_nome, u.telefone as cliente_telefone, u.email as cliente_email, e.rua, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep
          FROM agendamentos a 
          LEFT JOIN pets p ON a.pet_id = p.id 
          LEFT JOIN usuarios u ON a.usuario_id = u.id
          LEFT JOIN enderecos e ON a.endereco_id = e.id
          WHERE $where_sql
          ORDER BY
                CASE WHEN a.status IN ('Pendente', 'Confirmado', 'Em Andamento') AND a.data_agendamento >= NOW() THEN 1 ELSE 2 END ASC,
                CASE WHEN a.status IN ('Pendente', 'Confirmado', 'Em Andamento') AND a.data_agendamento >= NOW() THEN a.data_agendamento END ASC,
                a.data_agendamento DESC
          LIMIT ? OFFSET ?";

$bind_params_types_query = $bind_params_types . "ii";
$bind_params_values_query = array_merge($bind_params_values, [$itens_por_pagina, $offset]);

$stmt = $mysqli->prepare($query);
$stmt->bind_param($bind_params_types_query, ...$bind_params_values_query);
$stmt->execute();
$agendamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function formatarData($data) {
    try {
        $datetime = new DateTime($data, new DateTimeZone('America/Sao_Paulo'));
        $agora = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $hoje = $agora->format('Y-m-d');
        $amanha = (new DateTime('tomorrow', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        
        $data_formatada = $datetime->format('Y-m-d');

        if ($data_formatada === $hoje) {
            return 'Hoje às ' . $datetime->format('H:i');
        } elseif ($data_formatada === $amanha) {
            return 'Amanhã às ' . $datetime->format('H:i');
        } else {
            return $datetime->format('d/m/Y \à\s H:i');
        }
    } catch (Exception $e) { return 'Data inválida'; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="icon" href="../data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐾</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { 
            theme: { 
                extend: { 
                    colors: { 
                        petOrange: '#FF7A00', 
                        petBlue: '#0078C8', 
                        petGray: '#4A5568', 
                        petLightGray: '#f8fafc' 
                    }
                } 
            } 
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f0f2f5; 
            display: flex;
            flex-direction: column;
        }
        main { flex: 1; }
        
        #toast-notification-container > div { animation: fadeInOut 5s forwards; }
        @keyframes fadeInOut { 0%, 100% { opacity: 0; transform: translateY(-20px); } 10%, 90% { opacity: 1; transform: translateY(0); } }
        
        #filter-panel { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out; }
        
        .appointment-card { transition: all 0.2s ease-in-out; background-color: #ffffff; border: 1px solid #e2e8f0; height: 100%; }
        .appointment-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); border-color: #cbd5e1; }
        
        .details-arrow { transition: transform 0.2s ease-in-out; }
        .details-toggle-btn.open .details-arrow { transform: rotate(180deg); }
        .details-toggle-btn { transition: all 0.2s ease; border-radius: 50%; }
        .details-toggle-btn:hover { background-color: #f0f2f5; transform: none; }
        
        .pagination-container { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        .page-item { transition: all 0.2s ease-in-out; border-radius: 0.375rem; border: 1px solid transparent; }
        .page-item:hover { background-color: #f0f2f5; border-color: #cbd5e1; }
        .page-item.active { background-color: #0078C8; border-color: #0078C8; color: white; transform: none; box-shadow: none; }
        .page-item:not(.active):not(.disabled):hover { background-color: #e2e8f0; border-color: #0078C8; }
        .page-item.disabled { opacity: 0.6; cursor: not-allowed; pointer-events: none; }
        
        .modal-overlay { transition: opacity 0.2s ease; backdrop-filter: blur(2px); }
        
        .action-btn { transition: all 0.2s ease; }
        .action-btn:hover { opacity: 0.8; }

        .filter-btn-submit { transition: all 0.2s ease-in-out; }
        .filter-btn-submit:hover { opacity: 0.8; }
        
        .filter-status-btn, .filter-date-btn {
            display: flex; align-items: center; justify-content: center;
            padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem;
            background-color: #ffffff; color: #6b7280; font-size: 0.875rem; font-weight: 500;
            transition: all 0.2s ease-in-out; cursor: pointer; text-align: center;
        }
        .filter-status-btn:hover, .filter-date-btn:hover { border-color: #cbd5e1; background-color: #f8fafc; }
        .filter-status-btn.active, .filter-date-btn.active { border-color: #0078C8; background-color: #0078C8; color: white; }
        
        .filter-status-btn[data-status="Pendente"].active { background-color: #f59e0b; border-color: #f59e0b; }
        .filter-status-btn[data-status="Confirmado"].active { background-color: #0ea5e9; border-color: #0ea5e9; }
        .filter-status-btn[data-status="Em Andamento"].active { background-color: #f97316; border-color: #f97316; }
        .filter-status-btn[data-status="Concluído"].active { background-color: #10b981; border-color: #10b981; }
        .filter-status-btn[data-status="Cancelado"].active { background-color: #ef4444; border-color: #ef4444; }
        
        .toggle-bg { transition: background-color 0.2s ease-in-out; }
        .toggle-bg:after {
            content: ''; position: absolute; top: 2px; left: 2px;
            background-color: white; border-radius: 9999px;
            height: 1.25rem; /* 20px */
            width: 1.25rem; /* 20px */
            transition: transform 0.2s ease-in-out;
        }
        input:checked + .toggle-bg { background-color: #0078C8; border-color: #0078C8; }
        input:checked + .toggle-bg:after { transform: translateX(1.125rem); /* 18px */ }
    </style>
</head>
<body class="bg-petLightGray">
    <?php include '../header.php'; ?>
    
    <div id="toast-notification-container" class="fixed top-20 right-5 z-[100]">
        <?php if ($ok): ?><div class="bg-green-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    </div>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h1 class="text-3xl md:text-4xl font-bold text-petGray">Gerenciar Agendamentos</h1>
            <a href="../agendamento_admin.php" class="mt-4 md:mt-0 inline-flex items-center px-5 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-petBlue hover:bg-blue-800 transition">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                Fazer Novo Agendamento
            </a>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm mb-8">
            <button id="filter-toggle-btn" class="w-full flex justify-between items-center font-semibold text-lg text-petGray">
                <span>Filtros e Opções</span>
                <svg id="filter-arrow" class="w-6 h-6 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            
            <div id="filter-panel">
                 <form method="GET" action="" class="border-t border-gray-200 mt-4 pt-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Status</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button type="button" class="filter-status-btn <?= $status_filtro === 'all' ? 'active' : '' ?>" data-status="all">Todos</button>
                                <button type="button" class="filter-status-btn <?= $status_filtro === 'Pendente' ? 'active' : '' ?>" data-status="Pendente">Pendente</button>
                                <button type="button" class="filter-status-btn <?= $status_filtro === 'Confirmado' ? 'active' : '' ?>" data-status="Confirmado">Confirmado</button>
                                <button type="button" class="filter-status-btn <?= $status_filtro === 'Em Andamento' ? 'active' : '' ?>" data-status="Em Andamento">Em Andamento</button>
                                <button type="button" class="filter-status-btn <?= $status_filtro === 'Concluído' ? 'active' : '' ?>" data-status="Concluído">Concluído</button>
                                <button type="button" class="filter-status-btn <?= $status_filtro === 'Cancelado' ? 'active' : '' ?>" data-status="Cancelado">Cancelado</button>
                            </div>
                            <input type="hidden" id="status" name="status" value="<?= htmlspecialchars($status_filtro) ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Data</label>
                            <div class="grid grid-cols-2 sm:grid-cols-2 gap-2">
                                <button type="button" class="filter-date-btn <?= $data_filtro === 'all' ? 'active' : '' ?>" data-date="all">Qualquer Data</button>
                                <button type="button" class="filter-date-btn <?= $data_filtro === 'hoje' ? 'active' : '' ?>" data-date="hoje">Hoje</button>
                                <button type="button" class="filter-date-btn <?= $data_filtro === 'amanha' ? 'active' : '' ?>" data-date="amanha">Amanhã</button>
                                <button type="button" class="filter-date-btn <?= $data_filtro === 'semana' ? 'active' : '' ?>" data-date="semana">Esta Semana</button>
                            </div>
                            <input type="hidden" id="data_filtro" name="data_filtro" value="<?= htmlspecialchars($data_filtro) ?>">
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-5 border-t border-gray-200">
                         <label for="show-canceled-filter" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="show-canceled-filter" name="mostrar_cancelados" value="1" class="sr-only peer" <?= $mostrar_cancelados ? 'checked' : '' ?> onchange="this.form.submit()">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-petBlue/50 toggle-bg border border-gray-300"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Exibir cancelados</span>
                        </label>
                        <button type="submit" class="filter-btn-submit inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-petOrange hover:bg-orange-600">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            Aplicar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="appointments-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
            <?php if (empty($agendamentos)): ?>
                <div class="col-span-1 lg:col-span-3 bg-white p-8 rounded-lg shadow text-center text-petGray">
                    <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="mt-2 text-lg font-medium">Nenhum agendamento encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Tente ajustar os filtros ou adicione um novo agendamento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($agendamentos as $agendamento): ?>
                    <?php
                        $status_classes = 'bg-gray-100 text-gray-800';
                        if ($agendamento['status'] === 'Pendente') $status_classes = 'bg-yellow-100 text-yellow-800';
                        if ($agendamento['status'] === 'Confirmado') $status_classes = 'bg-sky-100 text-sky-800';
                        if ($agendamento['status'] === 'Em Andamento') $status_classes = 'bg-orange-100 text-orange-800';
                        if ($agendamento['status'] === 'Concluído') $status_classes = 'bg-green-100 text-green-800';
                        if ($agendamento['status'] === 'Cancelado') $status_classes = 'bg-red-100 text-red-800';
                    ?>
                    <div class="appointment-card flex flex-col bg-white rounded-xl shadow-md overflow-hidden transition hover:shadow-xl" data-id="<?= $agendamento['id'] ?>">
                        <div class="p-5 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $status_classes ?>">
                                    <?= htmlspecialchars($agendamento['status']) ?>
                                </span>
                                <button type="button" class="details-toggle-btn p-1 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600" data-target="#details-<?= $agendamento['id'] ?>">
                                    <span class="sr-only">Ver Detalhes</span>
                                    <svg class="w-5 h-5 details-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>
                            <div class="mt-4">
                                <h2 class="text-xl font-bold text-petGray truncate" title="<?= htmlspecialchars($agendamento['pet_nome'] ?? '') ?>"><?= htmlspecialchars($agendamento['pet_nome'] ?? 'Pet Excluído') ?></h2>
                                <p class="text-sm text-gray-500 font-medium truncate" title="<?= htmlspecialchars($agendamento['cliente_nome'] ?? '') ?>">Cliente: <span class="font-semibold text-gray-700"><?= htmlspecialchars($agendamento['cliente_nome'] ?? 'Excluído') ?></span></p>
                            </div>
                             <div class="mt-4 border-t border-gray-100 pt-4">
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18M-4.5 12h22.5" /></svg>
                                    <span class="text-sm font-semibold"><?= formatarData($agendamento['data_agendamento']) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div id="details-<?= $agendamento['id'] ?>" class="collapsible-details hidden">
                            <div class="border-t border-gray-200 px-5 py-4 space-y-4 bg-gray-50">
                                <dl class="space-y-4">
                                    <div class="text-sm"><dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>Serviços:</dt><dd class="text-petGray font-semibold pl-6"><?= htmlspecialchars($agendamento['servico']) ?></dd></div>
                                    <?php if (!empty($agendamento['rua'])): ?>
                                    <div class="text-sm"><dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V14.25m-17.25 4.5v-1.875a3.375 3.375 0 013.375-3.375h9.75a3.375 3.375 0 013.375 3.375v1.875" /></svg>Endereço (Buscar/Entregar):</dt><dd class="text-petGray font-semibold pl-6"><?= htmlspecialchars($agendamento['rua'] . ', ' . $agendamento['numero']) ?><br><?= htmlspecialchars($agendamento['bairro'] . ' - ' . $agendamento['cidade'] . '/' . $agendamento['estado']) ?></dd></div>
                                    <?php else: ?>
                                    <div class="text-sm"><dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>Tipo de Atendimento:</dt><dd class="text-petGray font-semibold pl-6">Cliente leva e busca</dd></div>
                                    <?php endif; ?>
                                    <div class="text-sm"><dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>Telefone Cliente:</dt><dd class="text-petGray font-semibold pl-6"><?= htmlspecialchars($agendamento['cliente_telefone'] ?? 'Não informado') ?></dd></div>
                                    <div class="text-sm"><dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>E-mail Cliente:</dt><dd class="text-petGray font-semibold pl-6"><?= htmlspecialchars($agendamento['cliente_email'] ?? 'Não informado') ?></dd></div>
                                    <?php if (!empty($agendamento['observacoes'])): ?>
                                    <div class="text-sm"><dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>Obs. Cliente:</dt><dd class="text-gray-600 italic pl-6">"<?= htmlspecialchars($agendamento['observacoes']) ?>"</dd></div>
                                    <?php endif; ?>
                                    <?php if (!empty($agendamento['observacoes_admin'])): ?>
                                    <div class="text-sm"><dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2V6a2 2 0 012-2h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H17z"></path></svg>Obs. Internas:</dt><dd class="text-gray-600 italic pl-6">"<?= htmlspecialchars($agendamento['observacoes_admin']) ?>"</dd></div>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-100 px-5 py-3 bg-gray-50 flex flex-wrap gap-2 justify-end mt-auto">
                            <?php if ($agendamento['status'] === 'Pendente' || $agendamento['status'] === 'Confirmado'): ?>
                                <button type="button" class="action-btn start-btn inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">Iniciar</button>
                                <button type="button" class="action-btn cancel-btn inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                            <?php elseif ($agendamento['status'] === 'Em Andamento'): ?>
                                <button type="button" class="action-btn conclude-btn inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-petBlue hover:bg-blue-700">Concluir</button>
                                <button type="button" class="action-btn cancel-btn inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav class="pagination-container mt-12 flex items-center justify-between px-4 sm:px-0">
            <div class="-mt-px flex w-0 flex-1"><a href="?status=<?= $status_filtro ?>&data_filtro=<?= $data_filtro ?>&mostrar_cancelados=<?= $mostrar_cancelados ? '1' : '0' ?>&pagina=<?= $pagina_atual - 1 ?>" class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?> inline-flex items-center pr-1 pt-4 pb-4 text-sm font-medium text-gray-500 hover:text-gray-700"><svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>Anterior</a></div>
            <div class="hidden md:-mt-px md:flex"><?php for($i = 1; $i <= $total_paginas; $i++): ?><a href="?status=<?= $status_filtro ?>&data_filtro=<?= $data_filtro ?>&mostrar_cancelados=<?= $mostrar_cancelados ? '1' : '0' ?>&pagina=<?= $i ?>" class="page-item <?= $i == $pagina_atual ? 'active' : '' ?> inline-flex items-center px-4 pt-4 pb-4 text-sm font-medium text-gray-500 hover:text-gray-700"><?= $i ?></a><?php endfor; ?></div>
            <div class="-mt-px flex w-0 flex-1 justify-end"><a href="?status=<?= $status_filtro ?>&data_filtro=<?= $data_filtro ?>&mostrar_cancelados=<?= $mostrar_cancelados ? '1' : '0' ?>&pagina=<?= $pagina_atual + 1 ?>" class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?> inline-flex items-center pl-1 pt-4 pb-4 text-sm font-medium text-gray-500 hover:text-gray-700">Próxima<svg class="ml-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></a></div>
        </nav>
        <?php endif; ?>
    </main>
    
    <div id="start-modal" class="modal fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        <div class="flex items-center justify-center min-h-screen">
            <div class="relative bg-white w-full max-w-lg p-6 mx-4 rounded-lg shadow-xl z-10">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Iniciar Atendimento</h3>
                <div class="mt-2"><p class="text-sm text-gray-500">Deseja alterar o status para "Em Andamento"?</p></div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button type="button" class="confirm-action-btn w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700">Sim, Iniciar</button>
                    <button type="button" class="modal-close-btn mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50">Voltar</button>
                </div>
            </div>
        </div>
    </div>
    <form id="start-form" action="<?= $redirect_url ?>" method="POST" class="hidden"><input type="hidden" name="agendamento_id"><input type="hidden" name="iniciar_atendimento" value="1"></form>
    
    <div id="conclude-modal" class="modal fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        <div class="flex items-center justify-center min-h-screen">
            <form id="conclude-form" action="<?= $redirect_url ?>" method="POST" class="relative bg-white w-full max-w-lg p-6 mx-4 rounded-lg shadow-xl z-10">
                <input type="hidden" name="agendamento_id"><input type="hidden" name="concluir_agendamento" value="1">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Concluir Agendamento</h3>
                <div class="mt-4">
                    <label for="observacoes_admin" class="block text-sm font-medium text-gray-700">Observações Internas (Opcional)</label>
                    <textarea name="observacoes_admin" id="observacoes_admin" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-petBlue focus:border-petBlue"></textarea>
                    <p class="mt-2 text-sm text-gray-500">Estas observações são para controle interno.</p>
                </div>
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-petBlue text-base font-medium text-white hover:bg-blue-700">Concluir e Salvar</button>
                    <button type="button" class="modal-close-btn mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50">Voltar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="cancel-modal" class="modal fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        <div class="flex items-center justify-center min-h-screen">
             <form id="cancel-form" action="<?= $redirect_url ?>" method="POST" class="relative bg-white w-full max-w-lg p-6 mx-4 rounded-lg shadow-xl z-10">
                <input type="hidden" name="agendamento_id"><input type="hidden" name="cancelar_agendamento" value="1">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Cancelar Agendamento</h3>
                <div class="mt-4">
                    <label for="motivo_cancelamento" class="block text-sm font-medium text-gray-700">Motivo do Cancelamento</label>
                    <textarea name="motivo_cancelamento" id="motivo_cancelamento" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-petBlue focus:border-petBlue transition" placeholder="Ex: Cliente desmarcou."></textarea>
                    <p id="cancel-error-msg" class="text-red-600 text-sm mt-1 hidden">O motivo do cancelamento é obrigatório.</p>
                    <p class="mt-2 text-sm text-gray-500">Esta observação ficará registrada como "Obs. Interna".</p>
                </div>
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700">Confirmar Cancelamento</button>
                    <button type="button" class="modal-close-btn mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50">Voltar</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA GERAL DOS MODAIS E FILTROS ---
    const startModal = document.getElementById('start-modal');
    const concludeModal = document.getElementById('conclude-modal');
    const cancelModal = document.getElementById('cancel-modal');
    const startForm = document.getElementById('start-form');
    const openModal = (modal) => { if(modal) modal.classList.remove('hidden'); }
    const closeModal = (modal) => { if(modal) modal.classList.add('hidden'); }
    [startModal, concludeModal, cancelModal].forEach(modal => {
        if (!modal) return;
        modal.querySelector('.modal-overlay')?.addEventListener('click', () => closeModal(modal));
        modal.querySelectorAll('.modal-close-btn').forEach(btn => {
            btn.addEventListener('click', (e) => { e.preventDefault(); closeModal(modal); });
        });
    });
    startModal?.querySelector('.confirm-action-btn')?.addEventListener('click', () => startForm.submit());
    const filterToggleButton = document.getElementById('filter-toggle-btn');
    const filterPanel = document.getElementById('filter-panel');
    const filterArrow = document.getElementById('filter-arrow');
    if (filterToggleButton && filterPanel && filterArrow) {
        filterToggleButton.addEventListener('click', () => {
            if (filterPanel.style.maxHeight) {
                filterPanel.style.maxHeight = null;
                filterArrow.style.transform = 'rotate(0deg)';
            } else {
                filterPanel.style.maxHeight = (filterPanel.scrollHeight + 40) + "px";
                filterArrow.style.transform = 'rotate(180deg)';
            }
        });
    }
    const appointmentsList = document.getElementById('appointments-list');
    if (appointmentsList) {
        appointmentsList.addEventListener('click', (e) => {
            const card = e.target.closest('.appointment-card');
            if (!card) return;
            const agendamentoId = card.dataset.id;
            const toggleButton = e.target.closest('.details-toggle-btn');
            const actionBtn = e.target.closest('.action-btn');
            if (toggleButton) {
                e.preventDefault();
                document.querySelectorAll('.collapsible-details:not(.hidden)').forEach(openDetails => {
                    if (openDetails !== card.querySelector('.collapsible-details')) {
                        openDetails.classList.add('hidden');
                        openDetails.closest('.appointment-card').querySelector('.details-toggle-btn').classList.remove('open');
                    }
                });
                const detailsContent = card.querySelector(toggleButton.dataset.target);
                if (detailsContent) {
                    toggleButton.classList.toggle('open');
                    detailsContent.classList.toggle('hidden');
                }
            } else if (actionBtn) {
                e.preventDefault();
                if (actionBtn.classList.contains('start-btn')) {
                    if(startForm) startForm.querySelector('input[name="agendamento_id"]').value = agendamentoId;
                    openModal(startModal);
                } else if (actionBtn.classList.contains('conclude-btn')) {
                    const concludeForm = document.getElementById('conclude-form');
                    if(concludeForm) concludeForm.querySelector('input[name="agendamento_id"]').value = agendamentoId;
                    openModal(concludeModal);
                } else if (actionBtn.classList.contains('cancel-btn')) {
                    const cancelForm = document.getElementById('cancel-form');
                    if(cancelForm) cancelForm.querySelector('input[name="agendamento_id"]').value = agendamentoId;
                    openModal(cancelModal);
                }
            }
        });
    }
    document.querySelectorAll('.filter-status-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-status-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('status').value = btn.dataset.status;
        });
    });
    document.querySelectorAll('.filter-date-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-date-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('data_filtro').value = btn.dataset.date;
        });
    });

    // --- LÓGICA DE VALIDAÇÃO PERSONALIZADA PARA O MODAL DE CANCELAMENTO ---
    const cancelForm = document.getElementById('cancel-form');
    const cancelTextarea = document.getElementById('motivo_cancelamento');
    const cancelErrorMsg = document.getElementById('cancel-error-msg');

    if (cancelForm && cancelTextarea && cancelErrorMsg) {
        cancelForm.addEventListener('submit', function(e) {
            if (cancelTextarea.value.trim() === '') {
                e.preventDefault(); // Impede o envio
                cancelErrorMsg.classList.remove('hidden');
                cancelTextarea.classList.add('border-red-500');
                cancelTextarea.focus();
            } else {
                cancelErrorMsg.classList.add('hidden');
                cancelTextarea.classList.remove('border-red-500');
            }
        });
        cancelTextarea.addEventListener('input', function() {
            if (cancelTextarea.value.trim() !== '') {
                cancelErrorMsg.classList.add('hidden');
                cancelTextarea.classList.remove('border-red-500');
            }
        });
    }
});
</script>
<?php include '../footer.php'; ?>
</body>
</html>