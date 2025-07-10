<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Gerenciar Usuários';
$erro = $ok = '';
$super_admin_email = 'admin@admin.com'; 

// --- LÓGICA DE INATIVAR/REATIVAR ---
if (isset($_GET['toggle_status_id'])) {
    $id_para_alterar = intval($_GET['toggle_status_id']);
    $novo_status = intval($_GET['status']);

    if ($id_para_alterar == $_SESSION['usuario']['id']) {
        $_SESSION['erro_msg'] = "Você não pode alterar o status da sua própria conta!";
    } else {
        $stmt_check = $mysqli->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmt_check->bind_param('i', $id_para_alterar);
        $stmt_check->execute();
        $user_to_toggle = $stmt_check->get_result()->fetch_assoc();
        if ($user_to_toggle && $user_to_toggle['email'] === $super_admin_email) {
            $_SESSION['erro_msg'] = "Não é permitido inativar o administrador principal.";
        } else {
            $stmt = $mysqli->prepare("UPDATE usuarios SET is_active = ? WHERE id = ?");
            $stmt->bind_param('ii', $novo_status, $id_para_alterar);
            $stmt->execute();
            $_SESSION['ok_msg'] = "Status do usuário alterado com sucesso!";
        }
    }
    header("Location: usuarios.php");
    exit;
}

// --- LÓGICA PARA ADICIONAR OU EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_colaborador = isset($_POST['is_colaborador']) ? 1 : 0;
    
    if ($id) { // Edição
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $stmt_check = $mysqli->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmt_check->bind_param('i', $id);
        $stmt_check->execute();
        $user_to_edit = $stmt_check->get_result()->fetch_assoc();
        if ($user_to_edit['email'] === $super_admin_email && (!$is_admin || !$is_active)) {
            $erro = "Não é permitido remover o status de administrador ou inativar a conta principal.";
        }
    } else { // Adição
        $is_active = 1; 
    }

    if(empty($erro)) {
        if (!empty($senha)) $hash = password_hash($senha, PASSWORD_DEFAULT);

        if ($id) { // Edição
            if (!empty($senha)) {
                $stmt = $mysqli->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, is_admin = ?, is_colaborador = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param('sssiiii', $nome, $email, $hash, $is_admin, $is_colaborador, $is_active, $id);
            } else {
                $stmt = $mysqli->prepare("UPDATE usuarios SET nome = ?, email = ?, is_admin = ?, is_colaborador = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param('ssiiii', $nome, $email, $is_admin, $is_colaborador, $is_active, $id);
            }
            $ok = "Usuário atualizado com sucesso!";
        } else { // Adição
            if (empty($senha)) {
                $erro = "O campo senha é obrigatório para novos usuários.";
            } else {
                $stmt = $mysqli->prepare("INSERT INTO usuarios (nome, email, senha, is_admin, is_colaborador, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssiii', $nome, $email, $hash, $is_admin, $is_colaborador, $is_active);
                $ok = "Usuário cadastrado com sucesso!";
            }
        }
        if (empty($erro) && isset($stmt)) {
            if(!$stmt->execute()) { 
                $erro = "Erro: " . $stmt->error; 
            } else {
                $_SESSION['ok_msg'] = $ok;
                $redirect_url = "usuarios.php?";
                if (isset($_GET['busca'])) $redirect_url .= "&busca=" . urlencode($_GET['busca']);
                if (isset($_GET['filtro_tipo'])) $redirect_url .= "&filtro_tipo=" . urlencode($_GET['filtro_tipo']);
                if (isset($_GET['filtro_status'])) $redirect_url .= "&filtro_status=" . urlencode($_GET['filtro_status']);
                header("Location: " . $redirect_url);
                exit;
            }
        }
    }
    // Se houver erro no POST, ele será exibido no formulário abaixo
    $_SESSION['erro_msg'] = $erro;
}


// --- LÓGICA DE EXIBIÇÃO ---
if (isset($_SESSION['ok_msg'])) { $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if (isset($_SESSION['erro_msg'])) { $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }

$acao = $_GET['acao'] ?? null;
$usuario_para_editar = null;
$exibir_formulario = ($acao === 'adicionar' || $acao === 'editar');

if ($acao === 'editar' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $usuario_para_editar = $stmt->get_result()->fetch_assoc();
    if (!$usuario_para_editar) $exibir_formulario = false;
}

// --- LÓGICA DE PAGINAÇÃO E FILTROS MÚLTIPLOS ---
$busca = trim($_GET['busca'] ?? '');
$filtro_tipo = $_GET['filtro_tipo'] ?? '';
$filtro_status = $_GET['filtro_status'] ?? '';

$limit = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $limit;

$base_sql = "FROM usuarios";
$where_clauses = [];
$params_sql = [];
$types = '';

if ($busca) {
    $where_clauses[] = "(nome LIKE ? OR email LIKE ?)";
    $search_term = "%" . $busca . "%";
    $params_sql[] = $search_term;
    $params_sql[] = $search_term;
    $types .= 'ss';
}
if ($filtro_tipo !== "") {
    if ($filtro_tipo === 'admin') {
        $where_clauses[] = "is_admin = 1";
    } elseif ($filtro_tipo === 'colaborador') {
        $where_clauses[] = "is_colaborador = 1";
    } elseif ($filtro_tipo === 'cliente') {
        $where_clauses[] = "is_admin = 0 AND is_colaborador = 0";
    }
}
if ($filtro_status !== '') {
    $where_clauses[] = "is_active = ?";
    $params_sql[] = $filtro_status;
    $types .= 'i';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
}

$count_sql = "SELECT COUNT(id) as total " . $base_sql . $where_sql;
$stmt_total = $mysqli->prepare($count_sql);
if (!empty($types)) {
    $stmt_total->bind_param($types, ...$params_sql);
}
$stmt_total->execute();
$total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limit);

$sql = "SELECT * " . $base_sql . $where_sql . " ORDER BY nome ASC LIMIT ? OFFSET ?";
$params_sql[] = $limit;
$params_sql[] = $offset;
$types .= 'ii';

$stmt = $mysqli->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params_sql);
}
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require '../header.php';
?>

<style>
    #toast-notification-container > div { animation: fadeInOut 5s forwards; }
    @keyframes fadeInOut { 0%, 100% { opacity: 0; transform: translateY(-20px); } 10%, 90% { opacity: 1; transform: translateY(0); } }
    #confirmation-modal { transition: opacity 0.3s ease; }
</style>

<div id="toast-notification-container" class="fixed top-24 right-5 z-[100] w-full max-w-sm">
    <?php if ($ok): ?><div class="bg-green-500 text-white p-4 rounded-lg shadow-lg mb-2"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="bg-red-500 text-white p-4 rounded-lg shadow-lg mb-2"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-petGray">Gerenciar Usuários</h1>
        <a href="usuarios.php?acao=adicionar" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg mt-4 md:mt-0">
            Adicionar Novo Usuário
        </a>
    </div>

    <div id="form-container" class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg mb-12 <?php if (!$exibir_formulario) echo 'hidden'; ?>">
        <h2 class="text-2xl font-semibold text-petBlue mb-6"><?= $usuario_para_editar ? 'Editar Usuário' : 'Adicionar Novo Usuário' ?></h2>
        
        <?php
        $form_id = $usuario_para_editar['id'] ?? '';
        $form_nome = $_POST['nome'] ?? $usuario_para_editar['nome'] ?? '';
        $form_email = $_POST['email'] ?? $usuario_para_editar['email'] ?? '';
        $form_is_admin = isset($_POST["is_admin"]) ? true : ($usuario_para_editar["is_admin"] ?? false);
        $form_is_colaborador = isset($_POST["is_colaborador"]) ? true : ($usuario_para_editar["is_colaborador"] ?? false);
        $form_is_active = isset($_POST['is_active']) ? true : ($usuario_para_editar['is_active'] ?? true);
        $is_super_admin_form = ($form_email === $super_admin_email);
        ?>

        <form action="usuarios.php<?= $form_id ? '?acao=editar&id='.$form_id : '?acao=adicionar' ?>" method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= $form_id ?>">
            <div>
                <label class="block text-petGray font-medium">Nome Completo</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($form_nome) ?>" class="w-full mt-1 p-2 border rounded-md form-input" required>
            </div>
            <div>
                <label class="block text-petGray font-medium">E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($form_email) ?>" class="w-full mt-1 p-2 border rounded-md form-input" required <?= $is_super_admin_form ? 'readonly' : '' ?>>
            </div>
            <div>
                <label class="block text-petGray font-medium">Senha</label>
                <input type="password" name="senha" class="w-full mt-1 p-2 border rounded-md form-input" placeholder="<?= $usuario_para_editar ? 'Deixe em branco para não alterar' : 'Senha obrigatória' ?>">
            </div>
            <div class="flex space-x-6">
                <div class="flex items-center">
                    <input type="checkbox" name="is_admin" id="is_admin_checkbox" class="h-4 w-4 rounded" <?= $form_is_admin ? 'checked' : '' ?> <?= $is_super_admin_form ? 'onclick="return false;"' : '' ?>>
                    <label for="is_admin_checkbox" class="ml-2 text-petGray font-medium">É Administrador?</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_colaborador" id="is_colaborador_checkbox" class="h-4 w-4 rounded" <?= $form_is_colaborador ? 'checked' : '' ?> <?= $is_super_admin_form ? 'onclick="return false;"' : '' ?>>
                    <label for="is_colaborador_checkbox" class="ml-2 text-petGray font-medium">É Colaborador?</label>
                </div>
                <?php if ($usuario_para_editar && !$is_super_admin_form): ?>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" class="h-4 w-4 rounded" <?= $form_is_active ? 'checked' : '' ?>>
                    <label for="is_active" class="ml-2 text-petGray font-medium">Está Ativo?</label>
                </div>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <a href="usuarios.php" class="text-gray-600 mr-4">Cancelar</a>
                <button type="submit" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-5 rounded-lg">Salvar Usuário</button>
            </div>
        </form>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center">
             <h2 class="text-2xl font-semibold text-petBlue">Usuários Cadastrados</h2>
             <button id="toggle-filters-btn" class="bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-md hover:bg-gray-300 mt-4 md:mt-0 flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L10 12.414l-5.707-5.707A1 1 0 014 6V3z" clip-rule="evenodd" /></svg>
                <span>Filtros e Busca</span>
            </button>
        </div>
        
        <?php $filtros_ativos = !empty($busca) || !empty($filtro_tipo) || !empty($filtro_status); ?>
        <div id="filters-container" class="mt-4 mb-6 border-b pb-6 <?php if (!$filtros_ativos) echo 'hidden'; ?>">
            <form action="usuarios.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="busca" class="block text-sm font-medium text-gray-700">Buscar por nome ou e-mail</label>
                    <input type="text" name="busca" id="busca" placeholder="Digite aqui..." value="<?= htmlspecialchars($busca) ?>" class="mt-1 w-full p-2 border rounded-md form-input">
                </div>
                <div>
                    <label for="filtro_tipo" class="block text-sm font-medium text-gray-700">Tipo de Usuário</label>
                    <select name="filtro_tipo" id="filtro_tipo" class="mt-1 w-full p-2 border rounded-md form-input bg-white">
                        <option value="" <?= $filtro_tipo === '' ? 'selected' : '' ?>>Todos os Tipos</option>
                        <option value="admin" <?= $filtro_tipo === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="colaborador" <?= $filtro_tipo === 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                        <option value="cliente" <?= $filtro_tipo === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    </select>
                </div>
                <div>
                    <label for="filtro_status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="filtro_status" id="filtro_status" class="mt-1 w-full p-2 border rounded-md form-input bg-white">
                        <option value="" <?= $filtro_status === '' ? 'selected' : '' ?>>Todos os Status</option>
                        <option value="1" <?= $filtro_status === '1' ? 'selected' : '' ?>>Ativos</option>
                        <option value="0" <?= $filtro_status === '0' ? 'selected' : '' ?>>Inativos</option>
                    </select>
                </div>
                <div class="md:col-start-4 flex justify-end space-x-2">
                    <a href="usuarios.php" class="bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-md hover:bg-gray-400">Limpar</a>
                    <button type="submit" class="bg-petBlue text-white font-semibold py-2 px-4 rounded-md hover:bg-blue-700">Filtrar</button>
                </div>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b"><th class="p-2">Nome</th><th class="p-2">E-mail</th><th class="p-2">Tipo</th><th class="p-2">Status</th><th class="p-2">Ações</th></tr>
                </thead>
                <tbody>
                    <?php if (count($usuarios) > 0): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr class="border-b hover:bg-gray-50 <?= $usuario['is_active'] ? '' : 'opacity-50 bg-red-50' ?>">
                            <td class="p-2 font-medium"><?= htmlspecialchars($usuario['nome']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($usuario['email']) ?></td>
                            <td class="p-2"><span class="text-xs font-semibold px-2 py-1 rounded-full <?php 
                                if ($usuario["is_admin"]) echo "bg-petBlue text-white";
                                elseif ($usuario["is_colaborador"]) echo "bg-petOrange text-white";
                                else echo "bg-gray-200 text-gray-700";
                            ?>"><?php 
                                if ($usuario["is_admin"]) echo "Admin";
                                elseif ($usuario["is_colaborador"]) echo "Colaborador";
                                else echo "Cliente";
                            ?></span></td>
                            <td class="p-2"><span class="text-xs font-semibold px-2 py-1 rounded-full <?= $usuario['is_active'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' ?>"><?= $usuario['is_active'] ? 'Ativo' : 'Inativo' ?></span></td>
                            <td class="p-2">
                                <?php if ($usuario['email'] === $super_admin_email): ?>
                                    <span class="text-gray-400 italic">Não editável</span>
                                <?php else: ?>
                                    <a href="usuarios.php?acao=editar&id=<?= $usuario['id'] ?>" class="text-petBlue hover:underline">Editar</a>
                                    <?php if ($usuario['id'] != $_SESSION['usuario']['id']): ?>
                                        <?php if ($usuario['is_active']): ?>
                                            <a href="usuarios.php?toggle_status_id=<?= $usuario['id'] ?>&status=0" class="text-red-500 hover:underline ml-4 confirmation-link" data-message="Tem certeza que deseja INATIVAR este usuário? Ele não poderá mais acessar o sistema.">Inativar</a>
                                        <?php else: ?>
                                             <a href="usuarios.php?toggle_status_id=<?= $usuario['id'] ?>&status=1" class="text-green-500 hover:underline ml-4 confirmation-link" data-message="Tem certeza que deseja REATIVAR este usuário? Ele voltará a ter acesso ao sistema.">Reativar</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center p-4 text-gray-500">Nenhum usuário encontrado para os filtros aplicados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-8 flex justify-center space-x-2">
            <?php if ($total_paginas > 1): ?>
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?= $i ?>&busca=<?= htmlspecialchars($busca) ?>&filtro_tipo=<?= htmlspecialchars($filtro_tipo) ?>&filtro_status=<?= htmlspecialchars($filtro_status) ?>"
                       class="px-4 py-2 rounded-md transition-colors <?= ($i == $pagina_atual) ? 'bg-petBlue text-white' : 'bg-gray-200 text-gray-700 hover:bg-petBlue hover:text-white' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="confirmation-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-2xl p-6 md:p-8 max-w-md w-full">
        <h3 id="modal-title" class="text-2xl font-bold text-petGray mb-4">Confirmação Necessária</h3>
        <p id="modal-text" class="text-gray-600 mb-6"></p>
        <div class="flex justify-end space-x-4">
            <button id="modal-cancel-btn" class="px-6 py-2 rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 font-semibold">Cancelar</button>
            <a id="modal-confirm-link" class="px-6 py-2 rounded-md text-white font-semibold">Confirmar</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // MODIFICADO: Removido o script do toast antigo
    
    const toggleAddFormBtn = document.querySelector('a[href="usuarios.php?acao=adicionar"]');
    const formContainer = document.getElementById('form-container');
    const cancelFormLink = document.querySelector('#form-container a[href="usuarios.php"]');
    if (toggleAddFormBtn && formContainer.classList.contains('hidden')) {
        toggleAddFormBtn.addEventListener('click', (e) => {
            e.preventDefault();
            formContainer.classList.remove('hidden');
            window.scrollTo(0, formContainer.offsetTop - 100);
        });
    }
    if (cancelFormLink) {
        cancelFormLink.addEventListener('click', (e) => { e.preventDefault(); formContainer.classList.add('hidden'); });
    }

    const toggleFiltersBtn = document.getElementById('toggle-filters-btn');
    const filtersContainer = document.getElementById('filters-container');
    if (toggleFiltersBtn && filtersContainer) {
        toggleFiltersBtn.addEventListener('click', () => {
            filtersContainer.classList.toggle('hidden');
        });
    }

    const modal = document.getElementById('confirmation-modal');
    if (modal) {
        const modalTitle = document.getElementById('modal-title');
        const modalText = document.getElementById('modal-text');
        const confirmLink = document.getElementById('modal-confirm-link');
        const cancelBtn = document.getElementById('modal-cancel-btn');
        const closeModal = () => modal.classList.add('hidden');
        
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });

        document.querySelectorAll('.confirmation-link').forEach(link => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                modalTitle.textContent = 'Confirmação Necessária';
                modalText.textContent = link.dataset.message;
                confirmLink.href = link.href;
                confirmLink.className = 'px-6 py-2 rounded-md text-white font-semibold bg-red-500 hover:bg-red-600';
                modal.classList.remove('hidden');
            });
        });

        const isAdminCheckbox = document.getElementById('is_admin_checkbox');
        if (isAdminCheckbox) {
            isAdminCheckbox.addEventListener('change', (event) => {
                if (event.target.checked) {
                    event.preventDefault();
                    event.target.checked = false;
                    modalTitle.textContent = 'Atenção!';
                    modalText.textContent = 'Você está prestes a tornar este usuário um Administrador. Ele terá acesso a todas as configurações gerais da plataforma. Confirma que este é um colaborador do Petshop e que você deseja conceder esse acesso?';
                    confirmLink.href = '#'; 
                    confirmLink.className = 'px-6 py-2 rounded-md text-white font-semibold bg-petOrange hover:bg-orange-600';
                    modal.classList.remove('hidden');
                    
                    const newConfirmClickHandler = () => {
                        isAdminCheckbox.checked = true;
                        closeModal();
                        confirmLink.removeEventListener('click', newConfirmClickHandler);
                    };
                    confirmLink.addEventListener('click', newConfirmClickHandler);
                    
                    const newCancelClickHandler = () => {
                        isAdminCheckbox.checked = false;
                        closeModal();
                        cancelBtn.removeEventListener('click', newCancelClickHandler);
                    };
                    cancelBtn.addEventListener('click', newCancelClickHandler, { once: true });
                }
            });
        }
    }
});
</script>

<?php require '../footer.php'; ?>