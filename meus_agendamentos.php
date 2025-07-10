<?php
// ======================================================================
// PetSync - Página "Meus Agendamentos" v5.1 (Design Sutil)
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$id_usuario_logado = $_SESSION['usuario']['id'];
$page_title = 'Meus Agendamentos - PetSync';
$ok = '';
$erro = '';

// 2. PROCESSAMENTO DE AÇÕES (CANCELAMENTO)
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_agendamento'])) {
    $agendamento_id = $_POST['agendamento_id'] ?? null;
    if ($agendamento_id && is_numeric($agendamento_id)) {
        // Apenas permite cancelar agendamentos Pendentes ou Confirmados
        $stmt = $mysqli->prepare("UPDATE agendamentos SET status = 'Cancelado' WHERE id = ? AND usuario_id = ? AND status IN ('Pendente', 'Confirmado')");
        $stmt->bind_param("ii", $agendamento_id, $id_usuario_logado);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $_SESSION['ok_msg'] = "Agendamento cancelado com sucesso.";
        } else {
            $_SESSION['erro_msg'] = "Não foi possível cancelar o agendamento (pode já ter sido iniciado).";
        }
        $stmt->close();
    } else {
        $_SESSION['erro_msg'] = "ID de agendamento inválido.";
    }
    $status_atual = isset($_GET['status']) ? $_GET['status'] : 'all';
    header("Location: meus_agendamentos.php?status=" . urlencode($status_atual));
    exit;
}

// 3. LÓGICA DE PAGINAÇÃO E FILTRO
// ----------------------------------------------------------------------
$itens_por_pagina = 9;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$status_filtro = isset($_GET['status']) && in_array($_GET['status'], ['Pendente', 'Confirmado', 'Em Andamento', 'Concluído', 'Cancelado']) ? $_GET['status'] : 'all';

$where_conditions = ["a.usuario_id = ?"];
$bind_params_types = "i";
$bind_params_values = [$id_usuario_logado];

if ($status_filtro !== 'all') {
    $where_conditions[] = "a.status = ?";
    $bind_params_types .= "s";
    $bind_params_values[] = $status_filtro;
}

$where_sql = implode(" AND ", $where_conditions);
$count_query = "SELECT COUNT(*) FROM agendamentos a WHERE $where_sql";
$stmt_count = $mysqli->prepare($count_query);
$stmt_count->bind_param($bind_params_types, ...$bind_params_values);
$stmt_count->execute();
$total_agendamentos = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();
$total_paginas = ceil($total_agendamentos / $itens_por_pagina);
if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// 4. BUSCA DE DADOS PARA EXIBIÇÃO
// ----------------------------------------------------------------------
if (isset($_SESSION['ok_msg'])) { $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if (isset($_SESSION['erro_msg'])) { $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }

// A query agora também busca os dados do endereço para exibição
$query = "SELECT a.*, p.nome as pet_nome, e.rua, e.numero, e.bairro, e.cidade, e.estado
          FROM agendamentos a 
          LEFT JOIN pets p ON a.pet_id = p.id 
          LEFT JOIN enderecos e ON a.endereco_id = e.id
          WHERE $where_sql
          ORDER BY
              CASE WHEN a.status IN ('Pendente', 'Confirmado', 'Em Andamento') AND a.data_agendamento >= NOW() THEN 1 ELSE 2 END ASC,
              CASE WHEN a.status IN ('Pendente', 'Confirmado', 'Em Andamento') AND a.data_agendamento >= NOW() THEN a.data_agendamento END ASC,
              a.data_agendamento DESC
          LIMIT ? OFFSET ?";
          
$bind_params_types .= "ii";
$bind_params_values[] = $itens_por_pagina;
$bind_params_values[] = $offset;

$stmt = $mysqli->prepare($query);
$stmt->bind_param($bind_params_types, ...$bind_params_values);
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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐾</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { 
            theme: { 
                extend: { 
                    colors: { 
                        petOrange: '#FF7A00', 
                        petBlue: '#0078C8', 
                        petGray: '#4A5568', 
                        petLightGray: '#f8fafc',
                        petPurple: '#8B5CF6',
                        petGreen: '#10B981',
                        petRed: '#EF4444',
                        petYellow: '#F59E0B'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-gentle': 'bounceGentle 0.6s ease-in-out',
                        'pulse-soft': 'pulseSoft 2s infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        bounceGentle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' }
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        }
                    }
                } 
            } 
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f0f2f5; /* Cor de fundo mais neutra */
            display: flex;
            flex-direction: column;
        }
        main { flex: 1; }
        
        #toast-notification-container > div { 
            animation: fadeInOut 5s forwards; 
        }
        
        @keyframes fadeInOut { 
            0%, 100% { opacity: 0; transform: translateY(-20px); } 
            10%, 90% { opacity: 1; transform: translateY(0); } 
        }
        
        /* Filtros Ajustados */
        .filter-container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .filter-btn {
            position: relative;
            transition: all 0.2s ease-in-out;
            border: 1px solid transparent;
        }
        
        .filter-btn:hover {
            background-color: #f0f2f5;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        
        .filter-btn.active {
            background-color: #0078C8; /* Cor original do botão ativo */
            color: white;
            font-weight: 600;
            border-color: #0078C8;
            transform: none; /* Remove a transformação extra */
            box-shadow: none; /* Remove a sombra extra */
        }
        
        .filter-btn.all.active { background-color: #667eea; border-color: #667eea; }
        .filter-btn.pendente.active { background-color: #f59e0b; border-color: #f59e0b; }
        .filter-btn.confirmado.active { background-color: #3b82f6; border-color: #3b82f6; }
        .filter-btn.andamento.active { background-color: #f97316; border-color: #f97316; }
        .filter-btn.concluido.active { background-color: #10b981; border-color: #10b981; }
        .filter-btn.cancelado.active { background-color: #ef4444; border-color: #ef4444; }
        
        .filter-btn:not(.active) {
            background-color: #f8fafc;
            color: #64748b;
            border-color: #e2e8f0;
        }
        
        /* Cards Ajustados */
        .appointment-card {
            transition: all 0.2s ease-in-out;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            min-height: auto; /* Remover altura mínima fixa */
            height: 100%; /* Garantir que cards na mesma linha tenham a mesma altura */
        }
        
        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }
        
        .appointment-card.has-actions {
            border-left: 4px solid #0078C8; /* Cor mais neutra */
        }
        
        .appointment-card.has-actions:hover {
            border-left-color: #0056b3;
        }
        
        /* Status badges ajustados */
        .status-badge {
            font-weight: 600;
            letter-spacing: 0.025em;
            text-transform: uppercase;
            font-size: 0.75rem;
            border: 1px solid;
        }
        
        /* Paginação Ajustada */
        .pagination-container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .page-item {
            transition: all 0.2s ease-in-out;
            border-radius: 0.375rem;
            border: 1px solid transparent;
        }
        
        .page-item:hover {
            background-color: #f0f2f5;
            border-color: #cbd5e1;
        }
        
        .page-item.active {
            background-color: #0078C8;
            border-color: #0078C8;
            color: white;
            transform: none;
            box-shadow: none;
        }
        
        .page-item:not(.active):not(.disabled):hover {
            background-color: #e2e8f0;
            border-color: #0078C8;
        }
        
        .page-item.disabled { 
            opacity: 0.6; 
            cursor: not-allowed; 
            pointer-events: none; 
        }
        
        /* Animações de entrada mais sutis */
        .animate-on-load {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        /* Detalhes expansíveis */
        .details-arrow { 
            transition: transform 0.2s ease-in-out; 
        }
        
        .details-toggle-btn.open .details-arrow { 
            transform: rotate(180deg); 
        }
        
        .details-toggle-btn {
            transition: all 0.2s ease;
            border-radius: 50%;
        }
        
        .details-toggle-btn:hover {
            background-color: #f0f2f5;
            transform: none;
        }
        
        /* Modal ajustado */
        .modal-overlay { 
            transition: opacity 0.2s ease;
            backdrop-filter: blur(2px); /* Menos blur */
        }
        
        .modal-content {
            animation: fadeIn 0.2s ease-out; /* Animação mais rápida */
        }
        
        /* Botões de ação ajustados */
        .cancel-btn {
            transition: all 0.2s ease;
        }
        
        .cancel-btn:hover {
            background-color: rgba(239, 68, 68, 0.05); /* Menos intenso */
            transform: none;
        }
    </style>
</head>
<body class="bg-petLightGray">
    <?php include 'header.php'; // Inclui o cabeçalho padrão ?>
    
    <div id="toast-notification-container" class="fixed top-20 right-5 z-[100]">
        <?php if ($ok): ?><div class="bg-green-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    </div>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-petGray">
                    Meus Agendamentos
                </h1>
                <p class="text-gray-600 mt-2 text-lg">Gerencie todos os seus agendamentos em um só lugar</p>
            </div>
            <a href="agendamento.php" class="mt-4 md:mt-0 inline-flex items-center px-5 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-petBlue hover:bg-blue-800 transition">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Fazer Novo Agendamento
            </a>
        </div>

        <!-- Filtros Ajustados -->
        <div class="filter-container rounded-lg p-4 mb-8">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-petGray mr-2">Filtrar por:</span>
                <a href="?status=all" class="filter-btn all px-4 py-2 text-sm rounded-full transition <?= $status_filtro === 'all' ? 'active' : '' ?>">Todos</a>
                <a href="?status=Pendente" class="filter-btn pendente px-4 py-2 text-sm rounded-full transition <?= $status_filtro === 'Pendente' ? 'active' : '' ?>">Pendentes</a>
                <a href="?status=Confirmado" class="filter-btn confirmado px-4 py-2 text-sm rounded-full transition <?= $status_filtro === 'Confirmado' ? 'active' : '' ?>">Confirmados</a>
                <a href="?status=Em Andamento" class="filter-btn andamento px-4 py-2 text-sm rounded-full transition <?= $status_filtro === 'Em Andamento' ? 'active' : '' ?>">Em Andamento</a>
                <a href="?status=Concluído" class="filter-btn concluido px-4 py-2 text-sm rounded-full transition <?= $status_filtro === 'Concluído' ? 'active' : '' ?>">Concluídos</a>
                <a href="?status=Cancelado" class="filter-btn cancelado px-4 py-2 text-sm rounded-full transition <?= $status_filtro === 'Cancelado' ? 'active' : '' ?>">Cancelados</a>
            </div>
        </div>

        <!-- Lista de Agendamentos -->
        <div id="appointments-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
            <?php if (empty($agendamentos)): ?>
                <div class="col-span-1 lg:col-span-3 bg-white p-8 rounded-lg shadow text-center text-petGray">
                    <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium">Nenhum agendamento encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Você ainda não possui agendamentos com este status.</p>
                </div>
            <?php else: ?>
                <?php foreach ($agendamentos as $index => $agendamento): ?>
                    <?php
                        $status_classes = 'bg-gray-100 text-gray-800';
                        $status_icon = '';
                        if ($agendamento['status'] === 'Pendente') {
                            $status_classes = 'bg-yellow-100 text-yellow-800';
                            $status_icon = '<svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                        }
                        if ($agendamento['status'] === 'Confirmado') {
                            $status_classes = 'bg-sky-100 text-sky-800';
                            $status_icon = '<svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                        }
                        if ($agendamento['status'] === 'Em Andamento') {
                            $status_classes = 'bg-orange-100 text-orange-800';
                            $status_icon = '<svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>';
                        }
                        if ($agendamento['status'] === 'Concluído') {
                            $status_classes = 'bg-green-100 text-green-800';
                            $status_icon = '<svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>';
                        }
                        if ($agendamento['status'] === 'Cancelado') {
                            $status_classes = 'bg-red-100 text-red-800';
                            $status_icon = '<svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                        }
                        
                        $has_actions = ($agendamento['status'] === 'Pendente' || $agendamento['status'] === 'Confirmado');
                    ?>
                    <div class="appointment-card flex flex-col bg-white rounded-xl shadow-md overflow-hidden transition hover:shadow-xl <?= $has_actions ? 'has-actions' : '' ?>" data-id="<?= $agendamento['id'] ?>" data-pet-name="<?= htmlspecialchars($agendamento['pet_nome'] ?? 'Pet Excluído') ?>">
                        <div class="p-5 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $status_classes ?>">
                                    <?= $status_icon ?>
                                    <?= htmlspecialchars($agendamento['status']) ?>
                                </span>
                                <button type="button" class="details-toggle-btn p-1 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600" data-target="#details-<?= $agendamento['id'] ?>">
                                    <span class="sr-only">Ver Detalhes</span>
                                    <svg class="w-5 h-5 details-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>
                            <div class="mt-4">
                                <h2 class="text-xl font-bold text-petGray truncate" title="<?= htmlspecialchars($agendamento['pet_nome'] ?? '') ?>"><?= htmlspecialchars($agendamento['pet_nome'] ?? 'Pet Excluído') ?></h2>
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
                                    <div class="text-sm">
                                        <dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>Serviços:</dt>
                                        <dd class="text-petGray font-semibold pl-6"><?= htmlspecialchars($agendamento['servico']) ?></dd>
                                    </div>
                                    <?php if (!empty($agendamento['rua'])): ?>
                                    <div class="text-sm">
                                        <dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V14.25m-17.25 4.5v-1.875a3.375 3.375 0 013.375-3.375h9.75a3.375 3.375 0 013.375 3.375v1.875" /></svg>Endereço:</dt>
                                        <dd class="text-petGray font-semibold pl-6"><?= htmlspecialchars($agendamento['rua'] . ', ' . $agendamento['numero']) ?></dd>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-sm">
                                        <dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>Tipo:</dt>
                                        <dd class="text-petGray font-semibold pl-6">Cliente leva e busca</dd>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($agendamento['observacoes'])): ?>
                                    <div class="text-sm">
                                        <dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>Suas Observações:</dt>
                                        <dd class="text-gray-600 italic pl-6">"<?= htmlspecialchars($agendamento['observacoes']) ?>"</dd>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($agendamento['observacoes_admin'])): ?>
                                    <div class="text-sm">
                                        <dt class="font-medium text-gray-500 flex items-center"><svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2V6a2 2 0 012-2h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H17z"></path></svg>Observações da Loja:</dt>
                                        <dd class="text-gray-600 italic pl-6">"<?= htmlspecialchars($agendamento['observacoes_admin']) ?>"</dd>
                                    </div>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>
                        
                        <?php if ($agendamento['status'] === 'Pendente' || $agendamento['status'] === 'Confirmado'): ?>
                        <div class="border-t border-gray-100 px-5 py-3 bg-gray-50 flex justify-end mt-auto">
                            <button type="button" class="cancel-btn text-sm font-medium text-red-600 hover:text-red-800">
                                Cancelar Agendamento
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Paginação Ajustada -->
        <?php if($total_paginas > 1): ?>
        <nav class="pagination-container mt-12 flex items-center justify-between px-4 sm:px-0">
            <div class="-mt-px flex w-0 flex-1">
                <a href="?status=<?= $status_filtro ?>&pagina=<?= $pagina_atual - 1 ?>" class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?> inline-flex items-center pr-1 pt-4 pb-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                    Anterior
                </a>
            </div>
            <div class="hidden md:-mt-px md:flex">
                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?status=<?= $status_filtro ?>&pagina=<?= $i ?>" class="page-item <?= $i == $pagina_atual ? 'active' : '' ?> inline-flex items-center px-4 pt-4 pb-4 text-sm font-medium text-gray-500 hover:text-gray-700"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <div class="-mt-px flex w-0 flex-1 justify-end">
                <a href="?status=<?= $status_filtro ?>&pagina=<?= $pagina_atual + 1 ?>" class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?> inline-flex items-center pl-1 pt-4 pb-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Próxima
                    <svg class="ml-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </a>
            </div>
        </nav>
        <?php endif; ?>
    </main>

    <!-- Modal Ajustado -->
    <div id="cancel-modal" class="modal fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
        <div class="flex items-center justify-center min-h-screen">
            <div class="relative bg-white w-full max-w-lg p-6 mx-4 rounded-lg shadow-xl z-10">
                <div class="flex items-start">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="ml-4 text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Cancelar Agendamento</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-text">
                                Tem certeza que deseja cancelar o agendamento para <strong id="modal-pet-name" class="text-petGray"></strong>?
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirm-cancel-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                        Sim, Cancelar
                    </button>
                    <button type="button" id="close-modal-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        Voltar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <form id="cancel-form" action="meus_agendamentos.php" method="POST" class="hidden">
        <input type="hidden" name="agendamento_id" id="agendamento_id_to_cancel">
        <input type="hidden" name="cancelar_agendamento" value="1">
    </form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const appointmentsList = document.getElementById('appointments-list');
    const cancelModal = document.getElementById('cancel-modal');
    
    if (appointmentsList) {
        appointmentsList.addEventListener('click', (e) => {
            // Lógica para expandir detalhes
            const toggleButton = e.target.closest('.details-toggle-btn');
            if (toggleButton) {
                const targetId = toggleButton.dataset.target;
                const detailsContent = document.querySelector(targetId);
                if (detailsContent) {
                    toggleButton.classList.toggle('open');
                    detailsContent.classList.toggle('hidden');
                    
                    // Animação suave para os detalhes
                    if (!detailsContent.classList.contains('hidden')) {
                        detailsContent.style.maxHeight = '0';
                        detailsContent.style.overflow = 'hidden';
                        detailsContent.style.transition = 'max-height 0.3s ease-out';
                        
                        setTimeout(() => {
                            detailsContent.style.maxHeight = detailsContent.scrollHeight + 'px';
                        }, 10);
                        
                        setTimeout(() => {
                            detailsContent.style.maxHeight = '';
                            detailsContent.style.overflow = '';
                        }, 300);
                    }
                }
            }
            
            // Lógica para o botão de cancelar
            const cancelButton = e.target.closest('.cancel-btn');
            if (cancelButton) {
                const card = e.target.closest('.appointment-card');
                const agendamentoId = card.dataset.id;
                const petName = card.dataset.petName;
                
                const agendamentoIdInput = document.getElementById('agendamento_id_to_cancel');
                const modalPetName = document.getElementById('modal-pet-name');
                
                agendamentoIdInput.value = agendamentoId;
                modalPetName.textContent = petName;
                
                // Animação de abertura do modal
                cancelModal.classList.remove('hidden');
                setTimeout(() => {
                    cancelModal.querySelector('.modal-overlay').style.opacity = '1';
                }, 10);
            }
        });
    }

    if(cancelModal) {
        const closeModalBtn = document.getElementById('close-modal-btn');
        const confirmCancelBtn = document.getElementById('confirm-cancel-btn');
        const cancelForm = document.getElementById('cancel-form');
        const overlay = cancelModal.querySelector('.modal-overlay');

        confirmCancelBtn.addEventListener('click', () => {
            // Animação de loading no botão
            confirmCancelBtn.innerHTML = `
                <svg class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cancelando...
            `;
            confirmCancelBtn.disabled = true;
            
            setTimeout(() => {
                cancelForm.submit();
            }, 500);
        });
        
        const closeModal = () => {
            overlay.style.opacity = '0';
            setTimeout(() => {
                cancelModal.classList.add('hidden');
                overlay.style.opacity = '';
            }, 300);
        };
        
        closeModalBtn.addEventListener('click', closeModal);
        overlay?.addEventListener('click', closeModal);
    }
    
    // Efeito de hover nos filtros
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = '';
            }
        });
        
        btn.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = '';
            }
        });
    });
    
    // Remover efeito de parallax suave no scroll
    // window.addEventListener('scroll', () => {
    //     const scrolled = window.pageYOffset;
    //     const parallax = document.querySelector('main');
    //     const speed = scrolled * 0.1;
        
    //     if (parallax) {
    //         parallax.style.transform = `translateY(${speed}px)`;
    //     }
    // });
});
</script>
<?php require 'footer.php'; ?>
</body>
</html>

