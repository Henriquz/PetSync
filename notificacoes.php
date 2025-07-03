<?php
// ======================================================================
// PetSync - Página de Histórico de Notificações v5.0 (Modal de Imagem)
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
$page_title = 'Histórico de Notificações - PetSync';

// 2. MARCAR NOTIFICAÇÕES COMO LIDAS AO VISITAR A PÁGINA
// ----------------------------------------------------------------------
$mysqli->query("UPDATE notificacoes SET lida = 1 WHERE usuario_id = $id_usuario_logado AND lida = 0");

// 3. LÓGICA DE FILTROS E PAGINAÇÃO
// ----------------------------------------------------------------------
$filtro_tipo = $_GET['tipo'] ?? 'todos';
$filtro_palavra = trim($_GET['q'] ?? '');
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

$filtro_ativo = ($filtro_tipo !== 'todos' || !empty($filtro_palavra) || !empty($filtro_data_inicio) || !empty($filtro_data_fim));

$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$where_conditions = ["usuario_id = ?"];
$bind_params = [$id_usuario_logado];
$bind_types = "i";

if ($filtro_tipo !== 'todos' && in_array($filtro_tipo, ['alerta', 'automatica'])) {
    $where_conditions[] = "tipo = ?";
    $bind_params[] = $filtro_tipo;
    $bind_types .= "s";
}
if (!empty($filtro_palavra)) {
    $where_conditions[] = "mensagem LIKE ?";
    $bind_params[] = "%" . $filtro_palavra . "%";
    $bind_types .= "s";
}
if (!empty($filtro_data_inicio)) {
    $where_conditions[] = "DATE(data_criacao) >= ?";
    $bind_params[] = $filtro_data_inicio;
    $bind_types .= "s";
}
if (!empty($filtro_data_fim)) {
    $where_conditions[] = "DATE(data_criacao) <= ?";
    $bind_params[] = $filtro_data_fim;
    $bind_types .= "s";
}

$where_sql = implode(" AND ", $where_conditions);

$count_query = "SELECT COUNT(*) FROM notificacoes WHERE $where_sql";
$stmt_count = $mysqli->prepare($count_query);
$stmt_count->bind_param($bind_types, ...$bind_params);
$stmt_count->execute();
$total_notificacoes = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$total_paginas = ceil($total_notificacoes / $itens_por_pagina);
if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// 4. BUSCA DAS NOTIFICAÇÕES PARA A PÁGINA ATUAL
// ----------------------------------------------------------------------
$query = "SELECT id, mensagem, link, data_criacao, lida, tipo, imagem_url 
          FROM notificacoes 
          WHERE $where_sql
          ORDER BY data_criacao DESC 
          LIMIT ? OFFSET ?";
          
$bind_types .= "ii";
$bind_params[] = $itens_por_pagina;
$bind_params[] = $offset;

$stmt = $mysqli->prepare($query);
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$notificacoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require 'header.php';
?>
<style>
    #filter-panel {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-in-out, padding 0.5s ease-in-out, margin 0.5s ease-in-out;
        padding-top: 0;
        padding-bottom: 0;
        margin-top: 0;
    }
    #filter-panel.open {
        max-height: 500px;
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
        margin-top: 1.5rem;
    }
    #filter-arrow {
        transition: transform 0.3s ease;
    }
</style>

<main class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-petGray">Histórico de Notificações</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm">
             <button id="filter-toggle-btn" class="w-full flex justify-between items-center p-4 font-semibold text-lg text-petGray text-left">
                <span>Filtros e Busca</span>
                <svg id="filter-arrow" class="w-6 h-6 transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div id="filter-panel" class="border-t border-gray-200 px-4">
                <form method="GET" action="notificacoes.php">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="q" class="text-sm font-medium text-gray-700">Buscar por palavra:</label>
                            <input type="search" name="q" id="q" value="<?= htmlspecialchars($filtro_palavra) ?>" placeholder="Ex: concluído, cancelado..." class="mt-1 w-full form-input rounded-md border-gray-300 shadow-sm focus:border-petBlue focus:ring-petBlue">
                        </div>
                        <div>
                            <label for="tipo" class="text-sm font-medium text-gray-700">Tipo de notificação:</label>
                            <select name="tipo" id="tipo" class="mt-1 w-full form-select rounded-md border-gray-300 shadow-sm focus:border-petBlue focus:ring-petBlue">
                                <option value="todos" <?= $filtro_tipo === 'todos' ? 'selected' : '' ?>>Todos os Tipos</option>
                                <option value="alertas" <?= $filtro_tipo === 'alertas' ? 'selected' : '' ?>>Alertas do Pet Shop</option>
                                <option value="automaticas" <?= $filtro_tipo === 'automaticas' ? 'selected' : '' ?>>Notificações Automáticas</option>
                            </select>
                        </div>
                        <div>
                            <label for="data_inicio" class="text-sm font-medium text-gray-700">De:</label>
                            <input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($filtro_data_inicio) ?>" class="mt-1 w-full form-input rounded-md border-gray-300 shadow-sm focus:border-petBlue focus:ring-petBlue">
                        </div>
                        <div>
                            <label for="data_fim" class="text-sm font-medium text-gray-700">Até:</label>
                            <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($filtro_data_fim) ?>" class="mt-1 w-full form-input rounded-md border-gray-300 shadow-sm focus:border-petBlue focus:ring-petBlue">
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-4 border-t border-gray-100 pt-4">
                        <a href="notificacoes.php" class="text-sm font-medium text-gray-600 hover:text-petBlue">Limpar Filtros</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-petBlue hover:bg-blue-800">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" /></svg>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="notification-history-list" class="mt-8 space-y-4">
            <?php if (empty($notificacoes)): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="mt-2 text-lg font-medium">Nenhuma notificação encontrada</h3>
                    <p class="mt-1 text-sm">Tente ajustar os filtros ou aguarde novos comunicados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notificacoes as $notif): ?>
                    <?php
                        $is_alert = $notif['tipo'] === 'alerta';
                        $has_image = !empty($notif['imagem_url']);
                        $container_class = $is_alert ? 'bg-orange-50' : 'bg-white';
                    ?>
                    <div class="rounded-lg shadow-md overflow-hidden flex <?= $container_class ?>">
                        <div class="w-2 flex-shrink-0 <?= $is_alert ? 'bg-petOrange' : 'bg-petBlue' ?>"></div>
                        <div class="p-4 sm:p-5 flex-1">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 pt-1">
                                    <?php if ($is_alert): ?>
                                        <svg class="w-6 h-6 text-petOrange" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                                    <?php else: ?>
                                        <svg class="w-6 h-6 text-petBlue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-petGray"><?= nl2br(htmlspecialchars($notif['mensagem'])) ?></p>
                                    
                                    <?php if($has_image): ?>
                                    <div class="mt-3">
                                        <button class="view-image-btn text-sm inline-flex items-center gap-2 font-semibold text-petBlue hover:underline" data-img-src="/petsync/<?= htmlspecialchars($notif['imagem_url']) ?>">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 00.75-.75v-3.69l-2.72-2.72a.75.75 0 00-1.06 0L11.5 12.25l-1.72-1.72a.75.75 0 00-1.06 0l-2.97 2.97zM12 7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" /></svg>
                                            Ver Imagem
                                        </button>
                                    </div>
                                    <?php endif; ?>

                                    <time class="text-xs text-gray-500 mt-2 block">
                                        <?= (new DateTime($notif['data_criacao']))->format('d/m/Y \à\s H:i') ?>
                                    </time>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if($total_paginas > 1): ?>
        <nav class="mt-8 flex items-center justify-between border-t border-gray-200 px-4 sm:px-0 pt-4">
             <?php
                $query_params = array_filter([
                    'tipo' => $filtro_tipo,
                    'q' => $filtro_palavra,
                    'data_inicio' => $filtro_data_inicio,
                    'data_fim' => $filtro_data_fim,
                ]);
            ?>
            <div class="-mt-px flex w-0 flex-1">
                <a href="?<?= http_build_query(array_merge($query_params, ['pagina' => $pagina_atual - 1])) ?>" class="<?= $pagina_atual <= 1 ? 'pointer-events-none opacity-50' : '' ?> inline-flex items-center pr-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                    <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                    Anterior
                </a>
            </div>
            <div class="hidden md:-mt-px md:flex">
                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?<?= http_build_query(array_merge($query_params, ['pagina' => $i])) ?>" class="<?= $i == $pagina_atual ? 'border-petBlue text-petBlue' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> inline-flex items-center border-t-2 px-4 pt-4 text-sm font-medium"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <div class="-mt-px flex w-0 flex-1 justify-end">
                <a href="?<?= http_build_query(array_merge($query_params, ['pagina' => $pagina_atual + 1])) ?>" class="<?= $pagina_atual >= $total_paginas ? 'pointer-events-none opacity-50' : '' ?> inline-flex items-center pl-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Próxima
                    <svg class="ml-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </a>
            </div>
        </nav>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterToggleButton = document.getElementById('filter-toggle-btn');
    const filterPanel = document.getElementById('filter-panel');
    const filterArrow = document.getElementById('filter-arrow');
    
    if (filterToggleButton) {
        const urlParams = new URLSearchParams(window.location.search);
        const isFilterActive = (urlParams.has('q') && urlParams.get('q') !== '') || 
                               (urlParams.has('tipo') && urlParams.get('tipo') !== 'todos') ||
                               (urlParams.has('data_inicio') && urlParams.get('data_inicio') !== '') ||
                               (urlParams.has('data_fim') && urlParams.get('data_fim') !== '');

        if (isFilterActive) {
             filterPanel.classList.add('open');
             filterArrow.style.transform = 'rotate(180deg)';
        }

        filterToggleButton.addEventListener('click', () => {
            filterPanel.classList.toggle('open');
            filterArrow.style.transform = filterPanel.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
        });
    }
});
</script>

<?php
require 'footer.php';
?>