<?php
// ======================================================================
// PetSync - Página "Meus Agendamentos" v3.2 (Layout de Grid Refinado)
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
        $stmt = $mysqli->prepare("UPDATE agendamentos SET status = 'Cancelado' WHERE id = ? AND usuario_id = ? AND status = 'Pendente'");
        $stmt->bind_param("ii", $agendamento_id, $id_usuario_logado);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $_SESSION['ok_msg'] = "Agendamento cancelado com sucesso.";
        } else {
            $_SESSION['erro_msg'] = "Não foi possível cancelar o agendamento.";
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
$status_filtro = isset($_GET['status']) && in_array($_GET['status'], ['Pendente', 'Concluído', 'Cancelado']) ? $_GET['status'] : 'all';
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
$query = "SELECT a.*, p.nome as pet_nome 
          FROM agendamentos a 
          LEFT JOIN pets p ON a.pet_id = p.id 
          WHERE $where_sql
          ORDER BY
             CASE WHEN a.status = 'Pendente' AND a.data_agendamento >= NOW() THEN 1 ELSE 2 END ASC,
             CASE WHEN a.status = 'Pendente' AND a.data_agendamento >= NOW() THEN a.data_agendamento END ASC,
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
        $datetime = new DateTime($data);
        return $datetime->format('d/m/Y \à\s H:i');
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
        tailwind.config = { theme: { extend: { colors: { petOrange: '#FF7A00', petBlue: '#0078C8', petGray: '#4A5568', petLightGray: '#f7fafc', sky: { 100: '#e0f2fe', 700: '#0369a1'}, amber: { 100: '#fef3c7', 700: '#b45309'} } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f7fafc; }
        #toast-notification-container > div { animation: fadeInOut 5s forwards; }
        @keyframes fadeInOut { 0%, 100% { opacity: 0; transform: translateY(-20px); } 10%, 90% { opacity: 1; transform: translateY(0); } }
        .modal-overlay { transition: opacity 0.3s ease; }
        .filter-btn.active, .page-item.active {
            background-color: #0078C8; color: white; font-weight: 600; border-color: #0078C8;
        }
        .page-item.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .details-arrow { transition: transform 0.3s ease; }
        .details-toggle-btn.open .details-arrow { transform: rotate(180deg); }
    </style>
</head>
<body>
    <nav class="bg-white shadow-md sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex justify-between items-center">
                <a href="/petsync/index.php" class="text-2xl font-bold text-petBlue flex items-center">
                    <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 9C9.10457 9 10 8.10457 10 7C10 5.89543 9.10457 5 8 5C6.89543 5 6 5.89543 6 7C6 8.10457 6.89543 9 8 9Z" fill="#FF7A00"></path><path d="M16 9C17.1046 9 18 8.10457 18 7C18 5.89543 17.1046 5 16 5C14.8954 5 14 5.89543 14 7C14 8.10457 14.8954 9 16 9Z" fill="#FF7A00"></path><path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#FF7A00"></path><path d="M18 14C19.1046 14 20 13.1046 20 12C20 10.8954 19.1046 10 18 10C16.8954 10 16 10.8954 16 12C16 13.1046 16.8954 14 18 14Z" fill="#FF7A00"></path><path d="M12 18C13.6569 18 15 16.6569 15 15C15 13.3431 13.6569 12 12 12C10.3431 12 9 13.3431 9 15C9 16.6569 10.3431 18 12 18Z" fill="#0078C8"></path></svg>
                    Pet<span class="text-petOrange">Sync</span>
                </a>
                <a href="/petsync/index.php" class="bg-petOrange hover:bg-orange-700 text-white font-medium py-2 px-5 rounded-lg text-center transition duration-300">Voltar Ao Início</a>
            </div>
        </div>
    </nav>
    
    <div id="toast-notification-container" class="fixed top-5 right-5 z-[100]">
        <?php if ($ok): ?><div class="bg-green-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    </div>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-petGray">Meus Agendamentos</h1>
            <a href="agendamento.php" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-petBlue hover:bg-blue-700">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                Fazer Novo Agendamento
            </a>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm mb-8">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-petGray mr-2">Filtrar por:</span>
                <a href="?status=all" class="filter-btn px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 transition <?= $status_filtro === 'all' ? 'active' : '' ?>">Todos</a>
                <a href="?status=Pendente" class="filter-btn px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 transition <?= $status_filtro === 'Pendente' ? 'active' : '' ?>">Pendentes</a>
                <a href="?status=Concluído" class="filter-btn px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 transition <?= $status_filtro === 'Concluído' ? 'active' : '' ?>">Concluídos</a>
                <a href="?status=Cancelado" class="filter-btn px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 transition <?= $status_filtro === 'Cancelado' ? 'active' : '' ?>">Cancelados</a>
            </div>
        </div>

        <div id="appointments-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
            <?php if (empty($agendamentos)): ?>
                <div class="col-span-1 lg:col-span-3 bg-white p-8 rounded-lg shadow text-center text-petGray">
                     <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="mt-2 text-lg font-medium">Nenhum agendamento encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Não há agendamentos com os filtros selecionados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($agendamentos as $agendamento): ?>
                    <?php
                        $status_cor = 'bg-gray-400';
                        if ($agendamento['status'] === 'Pendente') $status_cor = 'bg-zinc-600   ';
                        if ($agendamento['status'] === 'Em Andamento') $status_cor = 'bg-amber-500';
                        if ($agendamento['status'] === 'Concluído') $status_cor = 'bg-green-600';
                        if ($agendamento['status'] === 'Cancelado') $status_cor = 'bg-red-500';
                        
                        $date_highlight_class = '';
                        $date_highlight_text = '';
                        if ($agendamento['status'] === 'Pendente') {
                            try {
                                $appointment_date = new DateTime($agendamento['data_agendamento']);
                                $today = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
                                $appointment_date_day = (clone $appointment_date)->setTime(0,0,0);
                                $today_day = (clone $today)->setTime(0,0,0);

                                if ($appointment_date_day < $today_day) {
                                    $date_highlight_class = 'bg-slate-200 text-slate-700';
                                    $date_highlight_text = 'Já passou';
                                } elseif ($appointment_date_day == $today_day) {
                                    $date_highlight_class = 'bg-red-100 text-red-700';
                                    $date_highlight_text = 'É Hoje!';
                                } else {
                                    $interval = $today_day->diff($appointment_date_day);
                                    $days_diff = $interval->days;
                                    if ($days_diff == 1) {
                                        $date_highlight_class = 'bg-amber-100 text-amber-700';
                                        $date_highlight_text = 'Amanhã';
                                    } elseif ($days_diff <= 7) {
                                        $date_highlight_class = 'bg-amber-100 text-amber-700';
                                        $date_highlight_text = 'Próximo';
                                    } else {
                                        $date_highlight_class = 'bg-sky-100 text-sky-700';
                                        $date_highlight_text = 'Futuro';
                                    }
                                }
                            } catch (Exception $e) {}
                        }
                    ?>
                    <div class="appointment-card flex flex-col bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300">
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between flex-grow">
                            <div class="flex items-center flex-grow">
                                <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-petBlue text-white font-bold text-xl">
                                    <?= htmlspecialchars(strtoupper(substr($agendamento['pet_nome'] ?? 'P', 0, 1))) ?>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-xl font-bold text-petGray"><?= htmlspecialchars($agendamento['pet_nome'] ?? 'Pet não encontrado') ?></h2>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <p class="text-sm text-gray-500 flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <?= formatarData($agendamento['data_agendamento']) ?>
                                        </p>
                                        <?php if (!empty($date_highlight_text)): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $date_highlight_class ?>">
                                            <?= $date_highlight_text ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                             <div class="flex-shrink-0 mt-4 sm:mt-0 flex flex-row-reverse sm:flex-row items-center gap-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold text-white <?= $status_cor ?>">
                                    <?= htmlspecialchars($agendamento['status']) ?>
                                </span>
                                <button type="button" class="details-toggle-btn p-2 rounded-full hover:bg-gray-100 text-gray-500" data-target="#details-<?= $agendamento['id'] ?>">
                                    <span class="sr-only">Ver Detalhes</span>
                                    <svg class="w-5 h-5 details-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>
                        </div>
                        
                        <div id="details-<?= $agendamento['id'] ?>" class="collapsible-details hidden">
                            <div class="border-t border-gray-200 p-5 space-y-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-petOrange flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12l2.879-2.879M12 12L9.121 9.121m0 0L5 5m7 7l-7 7"></path></svg>
                                    <div class="ml-3">
                                        <dt class="text-sm font-medium text-gray-500">Serviços</dt>
                                        <dd class="text-sm text-petGray font-semibold"><?= htmlspecialchars($agendamento['servico']) ?></dd>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-petOrange flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    <div class="ml-3">
                                        <dt class="text-sm font-medium text-gray-500">Entrega/Retirada</dt>
                                        <dd class="text-sm text-petGray font-semibold"><?= $agendamento['tipo_entrega'] === 'delivery' ? 'Buscar e entregar em casa' : 'Cliente leva e busca' ?></dd>
                                    </div>
                                </div>
                                <?php if (!empty($agendamento['observacoes'])): ?>
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-petOrange flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                    <div class="ml-3">
                                        <dt class="text-sm font-medium text-gray-500">Observações</dt>
                                        <dd class="mt-1 text-sm text-gray-600 italic">"<?= htmlspecialchars($agendamento['observacoes']) ?>"</dd>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($agendamento['status'] === 'Pendente'): ?>
                            <div class="border-t border-gray-200 px-5 py-3 bg-gray-50 text-right">
                                <button type="button" class="cancel-btn inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700" data-id="<?= $agendamento['id'] ?>" data-pet-name="<?= htmlspecialchars($agendamento['pet_nome']) ?>">
                                    Cancelar Agendamento
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav class="mt-12 flex items-center justify-between border-t border-gray-200 px-4 sm:px-0">
            <div class="-mt-px flex w-0 flex-1">
                <a href="?status=<?= $status_filtro ?>&pagina=<?= $pagina_atual - 1 ?>" class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?> inline-flex items-center border-t-2 border-transparent pr-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                    <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                    Anterior
                </a>
            </div>
            <div class="hidden md:-mt-px md:flex">
                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?status=<?= $status_filtro ?>&pagina=<?= $i ?>" class="page-item <?= $i == $pagina_atual ? 'active' : '' ?> inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <div class="-mt-px flex w-0 flex-1 justify-end">
                <a href="?status=<?= $status_filtro ?>&pagina=<?= $pagina_atual + 1 ?>" class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?> inline-flex items-center border-t-2 border-transparent pl-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                    Próxima
                    <svg class="ml-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </a>
            </div>
        </nav>
        <?php endif; ?>
    </main>

    <div id="cancel-modal" class="fixed inset-0 z-50 hidden">
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
                                Tem certeza que deseja cancelar o agendamento para <strong id="modal-pet-name" class="text-petGray"></strong>? Esta ação não pode ser desfeita.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirm-cancel-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirmar Cancelamento
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
    if (appointmentsList) {
        appointmentsList.addEventListener('click', (e) => {
            const toggleButton = e.target.closest('.details-toggle-btn');
            if (toggleButton) {
                const targetId = toggleButton.dataset.target;
                const detailsContent = document.querySelector(targetId);
                
                if (detailsContent) {
                    toggleButton.classList.toggle('open');
                    detailsContent.classList.toggle('hidden');
                }
            }
        });
    }

    const cancelModal = document.getElementById('cancel-modal');
    if(cancelModal) {
        const closeModalBtn = document.getElementById('close-modal-btn');
        const confirmCancelBtn = document.getElementById('confirm-cancel-btn');
        const cancelForm = document.getElementById('cancel-form');
        const agendamentoIdInput = document.getElementById('agendamento_id_to_cancel');
        const modalPetName = document.getElementById('modal-pet-name');
        
        const openCancelModal = (id, petName) => {
            agendamentoIdInput.value = id;
            modalPetName.textContent = petName;
            cancelModal.classList.remove('hidden');
        };

        const closeCancelModal = () => {
            cancelModal.classList.add('hidden');
        };

        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', () => {
                openCancelModal(button.dataset.id, button.dataset.petName);
            });
        });

        confirmCancelBtn.addEventListener('click', () => cancelForm.submit());
        closeModalBtn.addEventListener('click', closeCancelModal);
        cancelModal.querySelector('.modal-overlay')?.addEventListener('click', closeCancelModal);
    }
});
</script>
</body>
</html>