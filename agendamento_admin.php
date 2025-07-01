<?php
// ======================================================================
// PetSync - Página de Agendamento v12.8 (Validação de Cliente e Máscaras)
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 2. DEFINIÇÕES INICIAIS
// ----------------------------------------------------------------------
$page_title = 'Novo Agendamento (Admin) - PetSync';
$ok = '';
$erro = '';
$form_data_json = 'null';
$error_step_json = 'null';

// 3. PROCESSAMENTO DO FORMULÁRIO
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_agendamento'])) {
    // ... (código de recebimento de dados) ...
    $cliente_id = $_POST['cliente_id'] ?? null;
    $pet_id = $_POST['pet_id'] ?? null;
    $servicos_array = $_POST['servicos'] ?? [];
    $data_agendamento_str = $_POST['data_agendamento'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $tipo_entrega = $_POST['tipo_entrega'] ?? null;
    $endereco_id = ($tipo_entrega === 'delivery') ? ($_POST['endereco_id'] ?? null) : null;
    $error_step = 0;

    // <-- CORREÇÃO PHP: Validação de e-mail obrigatório no back-end -->
    if ($cliente_id === 'novo_cliente') {
        $novo_nome = trim($_POST['novo_cliente_nome']);
        $novo_email = trim($_POST['novo_cliente_email']);

        if (empty($novo_nome)) {
            $erro = "O nome do novo cliente é obrigatório.";
            $error_step = 0;
        } elseif (empty($novo_email)) {
            $erro = "O e-mail do novo cliente é obrigatório.";
            $error_step = 0;
        } elseif (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
            $erro = "O formato do e-mail do novo cliente é inválido.";
            $error_step = 0;
        } else {
            $is_admin = 0; 
            $senha_padrao = password_hash('petsynccliente', PASSWORD_DEFAULT);
            $stmt_user = $mysqli->prepare("INSERT INTO usuarios (nome, email, telefone, senha, is_admin) VALUES (?, ?, ?, ?, ?)");
            $stmt_user->bind_param("ssssi", $novo_nome, $novo_email, $_POST['novo_cliente_telefone'], $senha_padrao, $is_admin);
            if ($stmt_user->execute()) {
                $cliente_id = $mysqli->insert_id;
            } else {
                $erro = "Ocorreu um erro ao cadastrar o novo cliente: " . $stmt_user->error;
                $error_step = 0;
            }
            $stmt_user->close();
        }
    }

    // ... (O restante da lógica de processamento do formulário permanece o mesmo) ...
    // Lógica de cadastro de PET
    if (empty($erro) && $pet_id === 'novo_pet') {
        $pet_nome = trim($_POST['novo_pet_nome']);
        $pet_nascimento = $_POST['novo_pet_nascimento'];
        $pet_especie = $_POST['novo_pet_especie'] ?? '';
        if ($pet_especie === 'Outro(a)') { $pet_especie = trim($_POST['outra_especie'] ?? ''); }
        $pet_raca = $_POST['novo_pet_raca'] ?? '';
        if ($pet_raca === 'Outro(a)') { $pet_raca = trim($_POST['outra_raca'] ?? ''); }
        
        if(empty($pet_raca) && isset($_POST['novo_pet_especie']) && $_POST['novo_pet_especie'] === 'Outro(a)'){
            $pet_raca = 'N/A';
        }

        if (!empty($pet_nome) && !empty($pet_especie) && !empty($pet_raca)) {
            $stmt_pet = $mysqli->prepare("INSERT INTO pets (dono_id, nome, especie, raca, data_nascimento) VALUES (?, ?, ?, ?, ?)");
            $stmt_pet->bind_param("issss", $cliente_id, $pet_nome, $pet_especie, $pet_raca, $pet_nascimento);
            if ($stmt_pet->execute()) { $pet_id = $mysqli->insert_id; }
            else { $erro = "Ocorreu um erro ao cadastrar o novo pet."; }
            $stmt_pet->close();
        } else {
            $erro = "Nome, espécie e raça são obrigatórios para o novo pet.";
            $error_step = 1;
        }
    }
    
    // Cadastro de novo Endereço
    if (empty($erro) && $tipo_entrega === 'delivery' && $endereco_id === 'novo_endereco') {
        if (!empty(trim($_POST['novo_endereco_rua']))) {
            $stmt_end = $mysqli->prepare("INSERT INTO enderecos (usuario_id, rua, numero, complemento, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_end->bind_param("isssssss", $cliente_id, $_POST['novo_endereco_rua'], $_POST['novo_endereco_numero'], $_POST['novo_endereco_complemento'], $_POST['novo_endereco_bairro'], $_POST['novo_endereco_cidade'], $_POST['novo_endereco_estado'], $_POST['novo_endereco_cep']);
            if($stmt_end->execute()) {
                $endereco_id = $mysqli->insert_id;
            } else {
                $erro = "Ocorreu um erro ao cadastrar o novo endereço.";
            }
            $stmt_end->close();
        } else {
             $erro = "A rua do novo endereço é obrigatória para delivery.";
             $error_step = 3;
        }
    }

    // Validação final e definição do passo do erro
    $servicos = implode(', ', $servicos_array);
    if (empty($erro)) {
        if (!$cliente_id) { $erro = "Cliente não foi selecionado."; $error_step = 0; }
        elseif (!$pet_id) { $erro = "Pet não foi selecionado."; $error_step = 1; }
        elseif (empty($servicos)) { $erro = "Nenhum serviço foi selecionado."; $error_step = 2; }
        elseif (!$tipo_entrega) { $erro = "O tipo de entrega não foi selecionado."; $error_step = 3; }
        elseif ($tipo_entrega === 'delivery' && !$endereco_id) { $erro = "Selecione ou cadastre um endereço para a entrega."; $error_step = 3; }
        elseif (!$data_agendamento_str) { $erro = "A data e o horário não foram selecionados."; $error_step = 4; }
    }

    // Se não houver erros, salva no banco
    if (empty($erro)) {
        $stmt = $mysqli->prepare("INSERT INTO agendamentos (usuario_id, pet_id, servico, data_agendamento, observacoes, tipo_entrega, endereco_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissisi", $cliente_id, $pet_id, $servicos, $data_agendamento_str, $observacoes, $tipo_entrega, $endereco_id);
        if ($stmt->execute()) {
            $_SESSION['ok_msg'] = "Agendamento realizado com sucesso!";
        } else {
            $_SESSION['erro_msg'] = "Ocorreu um erro ao salvar o agendamento: " . $stmt->error;
            $_SESSION['form_data'] = $_POST;
            $_SESSION['error_step'] = 5;
        }
        $stmt->close();
    } else {
        $_SESSION['erro_msg'] = $erro;
        $_SESSION['form_data'] = $_POST;
        $_SESSION['error_step'] = $error_step;
    }
    
    header("Location: agendamento_admin.php");
    exit;
}

// 4. PREPARAÇÃO DOS DADOS PARA A PÁGINA
// ----------------------------------------------------------------------
// ... (código de preparação de dados para a view, incluindo a recuperação de estado) ...
if (isset($_SESSION['ok_msg'])) { $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if (isset($_SESSION['erro_msg'])) { $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }
if (isset($_SESSION['form_data'])) {
    $form_data_json = json_encode($_SESSION['form_data']);
    unset($_SESSION['form_data']);
}
if (isset($_SESSION['error_step'])) {
    $error_step_json = json_encode($_SESSION['error_step']);
    unset($_SESSION['error_step']);
}
$clientes = $mysqli->query("SELECT id, nome, email FROM usuarios WHERE is_admin = 0 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$horarios_disponiveis = $mysqli->query("SELECT TIME_FORMAT(horario, '%H:%i') as horario_formatado FROM horarios_disponiveis ORDER BY horario ASC")->fetch_all(MYSQLI_ASSOC);
$horarios_json = json_encode(array_column($horarios_disponiveis, 'horario_formatado'));
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
        tailwind.config = { theme: { extend: { colors: { petOrange: '#FF7A00', petBlue: '#0078C8', petGray: '#4A5568', petLightGray: '#f7fafc' } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .form-input:focus, .form-checkbox:focus, .form-radio:focus, select:focus { border-color: #0078C8; box-shadow: 0 0 0 2px rgba(0, 120, 200, 0.2); outline: none; }
        .service-option { transition: all 0.2s ease; }
        .service-option.selected { border-color: #0078C8; background-color: #eff6ff; }
        .date-cell.selected { background-color: #0078C8; color: white; font-weight: bold; transform: scale(1.1); }
        .time-slot.selected { background-color: #0078C8; color: white; border-color: #0078C8; }
        .pet-card.selected { border-color: #0078C8; box-shadow: 0 0 0 3px rgba(0, 120, 200, 0.4); background-color: #eff6ff; }
        #toast-notification-container > div { animation: fadeInOut 5s forwards; }
        @keyframes fadeInOut { 0%, 100% { opacity: 0; transform: translateY(-20px); } 10%, 90% { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-petLightGray">
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-2xl font-bold text-petBlue flex items-center">
                    <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 9C9.10457 9 10 8.10457 10 7C10 5.89543 9.10457 5 8 5C6.89543 5 6 5.89543 6 7C6 8.10457 6.89543 9 8 9Z" fill="#FF7A00"></path><path d="M16 9C17.1046 9 18 8.10457 18 7C18 5.89543 17.1046 5 16 5C14.8954 5 14 5.89543 14 7C14 8.10457 14.8954 9 16 9Z" fill="#FF7A00"></path><path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#FF7A00"></path><path d="M18 14C19.1046 14 20 13.1046 20 12C20 10.8954 19.1046 10 18 10C16.8954 10 16 10.8954 16 12C16 13.1046 16.8954 14 18 14Z" fill="#FF7A00"></path><path d="M12 18C13.6569 18 15 16.6569 15 15C15 13.3431 13.6569 12 12 12C10.3431 12 9 13.3431 9 15C9 16.6569 10.3431 18 12 18Z" fill="#0078C8"></path></svg>
                    Pet<span class="text-petOrange">Sync</span>
                </a>
                <a href="index.php" class="bg-petOrange hover:bg-orange-700 text-white font-medium py-2 px-5 rounded-lg text-center transition duration-300">Voltar Ao Início</a>
            </div>
        </div>
    </nav>
    <div id="toast-notification-container" class="fixed top-5 right-5 z-[100]">
        <?php if ($ok): ?><div class="bg-green-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    </div>
    
    <?php if (empty($ok)): ?>
        <main id="booking-form-container" class="py-12">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-12"><h1 class="text-4xl md:text-5xl font-bold text-petGray mb-4">Agendamento <span class="text-petOrange">Administrativo</span></h1></div>
                    <div class="flex items-center justify-between mb-12 space-x-2 text-xs sm:text-sm">
                        <?php $steps_texts = ['Cliente', 'Pet', 'Serviços', 'Entrega', 'Horário', 'Confirmar']; ?>
                        <?php foreach($steps_texts as $i => $text): ?>
                            <div class="flex flex-col items-center text-center w-1/<?= count($steps_texts) ?>"><div class="w-10 h-10 bg-gray-200 text-petGray rounded-full flex items-center justify-center font-bold step-indicator" id="step-indicator-<?= $i ?>"><?= $i+1 ?></div><span class="mt-2 text-petGray" id="step-text-<?= $i ?>"><?= $text ?></span></div>
                            <?php if($i < count($steps_texts) - 1): ?><div class="flex-1 h-1 bg-gray-200" id="progress-bar-<?= $i ?>"></div><?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <form id="booking-form" action="agendamento_admin.php" method="POST" novalidate>
                        <input type="hidden" name="data_agendamento" id="data_agendamento_hidden">
                        
                        <div id="steps-container">
                            <div class="step-content bg-white p-8 rounded-lg shadow-md" id="step0">
                                <h2 class="text-2xl font-bold text-petGray mb-6">Passo 1: Selecione o Cliente<span class="text-red-500"> *</span></h2>
                                <label for="cliente_id" class="block text-petGray font-medium mb-2">Cliente Cadastrado</label>
                                <select name="cliente_id" id="cliente_id" class="w-full p-2 border rounded-md bg-white form-input">
                                    <option value="">-- Selecione um cliente --</option>
                                    <?php foreach($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>" data-cliente-nome="<?= htmlspecialchars($cliente['nome']) ?>"><?= htmlspecialchars($cliente['nome']) ?> (<?= htmlspecialchars($cliente['email']) ?>)</option>
                                    <?php endforeach; ?>
                                    <option value="novo_cliente">** Cadastrar Novo Cliente **</option>
                                </select>
                                <div id="novo-cliente-form" class="hidden grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 border-t pt-6 mt-6">
                                    <h3 class="md:col-span-2 text-lg font-semibold text-petOrange">Dados do Novo Cliente</h3>
                                    <div>
                                        <label class="block text-sm font-medium text-petGray">Nome Completo<span class="text-red-500"> *</span></label>
                                        <input type="text" name="novo_cliente_nome" id="novo_cliente_nome" class="w-full mt-1 p-2 border rounded-md form-input">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-petGray">Email<span class="text-red-500"> *</span></label>
                                        <input type="email" name="novo_cliente_email" id="novo_cliente_email" class="w-full mt-1 p-2 border rounded-md form-input">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-petGray">Telefone</label>
                                        <input type="text" name="novo_cliente_telefone" id="novo_cliente_telefone" class="w-full mt-1 p-2 border rounded-md form-input" placeholder="(XX) X XXXX-XXXX">
                                    </div>
                                </div>
                                <div class="mt-8 flex justify-end"><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                            </div>

                            <div class="step-content hidden bg-white p-8 rounded-lg shadow-md" id="step1">
                                <h2 class="text-2xl font-bold text-petGray mb-6">Passo 2: Informações do Pet<span class="text-red-500"> *</span></h2>
                                <input type="hidden" name="pet_id" id="selected_pet_id">
                                <div id="pet-selection-area">
                                    <p id="pet-loader" class="text-gray-500 italic">Aguardando seleção do cliente...</p>
                                    <div id="pet-card-list" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-4"></div>
                                    <button type="button" id="toggle-pet-form-btn" class="hidden text-sm text-petBlue hover:underline font-semibold">... ou cadastrar outro pet</button>
                                </div>
                                <div id="novo-pet-form" class="hidden grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 border-t pt-6 mt-4">
                                     <h3 class="md:col-span-2 text-lg font-semibold text-petOrange">Dados do Novo Pet</h3>
                                     <div>
                                        <label class="block text-sm font-medium text-petGray">Nome do Pet<span class="text-red-500"> *</span></label>
                                        <input type="text" id="novo_pet_nome" name="novo_pet_nome" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                     <div>
                                        <label class="block text-sm font-medium text-petGray">Data de Nascimento</label>
                                        <input type="date" name="novo_pet_nascimento" id="novo_pet_nascimento" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                     <div>
                                        <label for="novo_pet_especie_select" class="block text-sm font-medium text-petGray">Espécie<span class="text-red-500"> *</span></label>
                                        <select id="novo_pet_especie_select" name="novo_pet_especie" class="w-full mt-1 p-2 border rounded-md form-input"></select>
                                     </div>
                                      <div id="outra_especie_div" class="hidden">
                                        <label class="block text-sm font-medium text-petGray">Qual espécie?</label>
                                        <input type="text" id="outra_especie_input" name="outra_especie" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                     <div>
                                        <label for="novo_pet_raca_select" class="block text-sm font-medium text-petGray">Raça<span class="text-red-500"> *</span></label>
                                        <select id="novo_pet_raca_select" name="novo_pet_raca" class="w-full mt-1 p-2 border rounded-md form-input"></select>
                                     </div>
                                     <div id="outra_raca_div" class="hidden">
                                        <label class="block text-sm font-medium text-petGray">Qual raça?</label>
                                        <input type="text" id="outra_raca_input" name="outra_raca" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                </div>
                                <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                            </div>
                            
                            <div class="step-content hidden bg-white p-8 rounded-lg shadow-md" id="step2">
                                <h2 class="text-2xl font-bold text-petGray mb-6">Passo 3: Selecione os Serviços<span class="text-red-500"> *</span></h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Banho e Tosa" data-label="Banho e Tosa" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Banho e Tosa</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Consulta Veterinária" data-label="Consulta Veterinária" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Consulta Veterinária</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Vacinação" data-label="Vacinação" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Vacinação</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Hospedagem" data-label="Hospedagem" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Hospedagem</span></label></div>
                                <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                            </div>

                             <div class="step-content hidden bg-white p-8 rounded-lg shadow-md" id="step3">
                                <h2 class="text-2xl font-bold text-petGray mb-6">Passo 4: Como será a entrega e retirada?<span class="text-red-500"> *</span></h2>
                                <div class="space-y-4"><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="radio" name="tipo_entrega" value="loja" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue" checked><span class="ml-3 text-petGray text-lg">Cliente levará e buscará na loja</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="radio" name="tipo_entrega" value="delivery" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray text-lg">Buscar e entregar em casa</span></label></div>
                                <div id="endereco-delivery-section" class="hidden mt-6 space-y-4">
                                    <label for="endereco_id" class="block text-petGray font-medium">Selecione o endereço:<span class="text-red-500"> *</span></label>
                                    <p id="address-loader" class="text-gray-500 italic hidden"></p>
                                    <select name="endereco_id" id="endereco_id" class="w-full p-2 border rounded-md bg-white form-input"></select>
                                    <div id="novo-endereco-form" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-6 mt-4">
                                        <h3 class="md:col-span-2 text-lg font-semibold text-petOrange">Dados do Novo Endereço</h3>
                                        <div>
                                            <label class="block text-sm font-medium text-petGray">CEP</label>
                                            <input type="text" id="novo_endereco_cep" name="novo_endereco_cep" class="w-full mt-1 p-2 border rounded-md form-input">
                                        </div>
                                        <div></div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-petGray">Rua / Avenida<span class="text-red-500"> *</span></label>
                                            <input type="text" id="novo_endereco_rua" name="novo_endereco_rua" class="w-full mt-1 p-2 border rounded-md form-input">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-petGray">Número</label>
                                            <input type="text" id="novo_endereco_numero" name="novo_endereco_numero" class="w-full mt-1 p-2 border rounded-md form-input">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-petGray">Bairro</label>
                                            <input type="text" id="novo_endereco_bairro" name="novo_endereco_bairro" class="w-full mt-1 p-2 border rounded-md form-input">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-petGray">Complemento</label>
                                            <input type="text" id="novo_endereco_complemento" name="novo_endereco_complemento" placeholder="Apto, Bloco, Casa" class="w-full mt-1 p-2 border rounded-md form-input">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-petGray">Cidade</label>
                                            <input type="text" id="novo_endereco_cidade" name="novo_endereco_cidade" class="w-full mt-1 p-2 border rounded-md form-input">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-petGray">Estado (UF)</label>
                                            <input type="text" id="novo_endereco_estado" name="novo_endereco_estado" maxlength="2" class="w-full mt-1 p-2 border rounded-md form-input">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                            </div>
                            
                            <div class="step-content hidden bg-white p-8 rounded-lg shadow-md" id="step4">
                                <h2 class="text-2xl font-bold text-petGray mb-6">Passo 5: Escolha a Data e Horário<span class="text-red-500"> *</span></h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div><h3 class="text-lg font-semibold text-petGray mb-4">Selecione uma data</h3><div class="calendar bg-white border border-gray-300 rounded-lg p-4"><div class="flex justify-between items-center mb-4"><button type="button" id="prev-month" class="p-2 rounded-full hover:bg-gray-100"><svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button><h4 id="current-month" class="text-petGray font-semibold text-lg"></h4><button type="button" id="next-month" class="p-2 rounded-full hover:bg-gray-100"><svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button></div><div class="grid grid-cols-7 gap-1 text-center text-gray-500 text-sm font-medium"><div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div></div><div id="calendar-days" class="grid grid-cols-7 gap-1 mt-2"></div></div></div>
                                    <div><h3 class="text-lg font-semibold text-petGray mb-4">Selecione um horário</h3><div id="time-slots" class="grid grid-cols-2 sm:grid-cols-3 gap-3"></div><div class="mt-6"><h3 class="text-lg font-semibold text-petGray mb-4">Observações</h3><textarea name="observacoes" id="observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md form-input" placeholder="Alguma informação adicional? Ex: Alergias, comportamento..."></textarea></div></div>
                                </div>
                                <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                            </div>

                             <div class="step-content hidden bg-white p-8 rounded-lg shadow-md" id="step5">
                                <h2 class="text-2xl font-bold text-petGray mb-6">Passo 6: Confirme o Agendamento</h2>
                                <div class="bg-petLightGray p-6 rounded-lg space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b pb-4"><div><p class="font-medium text-petGray">Cliente:</p><p id="summary-owner-name" class="font-semibold text-lg"></p></div><div><p class="font-medium text-petGray">Pet:</p><p id="summary-pet-name" class="font-semibold text-lg"></p></div></div>
                                    <div class="pt-2 border-b pb-4"><p class="font-medium text-petGray">Serviços:</p><p id="summary-services" class="font-semibold text-lg"></p></div>
                                    <div class="pt-2 border-b pb-4"><p class="font-medium text-petGray">Entrega/Retirada:</p><p id="summary-delivery" class="font-semibold text-lg"></p></div>
                                    <div class="pt-2 border-b pb-4"><p class="font-medium text-petGray">Data e Hora:</p><p id="summary-datetime" class="font-semibold text-lg"></p></div>
                                    <div class="pt-2"><p class="font-medium text-petGray">Observações:</p><p id="summary-notes" class="text-gray-600 italic"></p></div>
                                </div>
                                <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="submit" name="confirmar_agendamento" class="bg-petOrange text-white px-6 py-3 rounded-md font-medium hover:bg-orange-700">Confirmar Agendamento</button></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    <?php else: ?>
        <main class="py-12"><div class="container mx-auto px-4 py-16 text-center"><div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg"><div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6"><svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></div><h2 class="text-3xl font-bold text-petGray mb-4">Agendamento Realizado!</h2><p class="text-petGray text-lg mb-6"><?= htmlspecialchars($ok) ?></p><p class="text-gray-500 mb-8">Redirecionando para a página de agendamento em 5 segundos...</p></div></div></main>
        <script> setTimeout(() => { window.location.href = 'agendamento_admin.php'; }, 5000); </script>
    <?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const preservedData = <?= $form_data_json ?>;
    const errorStep = <?= $error_step_json ?>;

    // --- ELEMENTOS GLOBAIS ---
    const stepElements = document.querySelectorAll('.step-content');
    const navButtons = document.querySelectorAll('.nav-btn');
    let currentStep = 0;

    // --- ELEMENTOS DO FORMULÁRIO ---
    const clienteSelect = document.getElementById('cliente_id');
    const novoClienteForm = document.getElementById('novo-cliente-form');
    const novoClienteNomeInput = document.getElementById('novo_cliente_nome');
    // CORREÇÃO JS: Elementos para as novas validações
    const novoClienteEmailInput = document.getElementById('novo_cliente_email');
    const novoClienteTelefoneInput = document.getElementById('novo_cliente_telefone');

    const selectedPetIdInput = document.getElementById('selected_pet_id');
    const petLoader = document.getElementById('pet-loader');
    const petCardList = document.getElementById('pet-card-list');
    const togglePetBtn = document.getElementById('toggle-pet-form-btn');
    const novoPetForm = document.getElementById('novo-pet-form');
    const novoPetNomeInput = document.getElementById('novo_pet_nome');
    const novoPetNascimentoInput = document.getElementById('novo_pet_nascimento');
    const especieSelect = document.getElementById('novo_pet_especie_select');
    const outraEspecieDiv = document.getElementById('outra_especie_div');
    const outraEspecieInput = document.getElementById('outra_especie_input');
    const racaSelect = document.getElementById('novo_pet_raca_select');
    const outraRacaDiv = document.getElementById('outra_raca_div');
    const outraRacaInput = document.getElementById('outra_raca_input');
    const enderecoSection = document.getElementById('endereco-delivery-section');
    const enderecoSelect = document.getElementById('endereco_id');
    const novoEnderecoForm = document.getElementById('novo-endereco-form');
    const novoEnderecoCepInput = document.getElementById('novo_endereco_cep');
    const novoEnderecoRuaInput = document.getElementById('novo_endereco_rua');
    const novoEnderecoNumeroInput = document.getElementById('novo_endereco_numero');
    const novoEnderecoBairroInput = document.getElementById('novo_endereco_bairro');
    const novoEnderecoCidadeInput = document.getElementById('novo_endereco_cidade');
    const novoEnderecoEstadoInput = document.getElementById('novo_endereco_estado');
    const novoEnderecoComplementoInput = document.getElementById('novo_endereco_complemento');
    const addressLoader = document.getElementById('address-loader');
    const observacoesInput = document.getElementById('observacoes');
    const dataAgendamentoHidden = document.getElementById('data_agendamento_hidden');

    let selectedDate = null, selectedTime = null;
    const availableTimes = <?= $horarios_json ?>;

    const petData = {
        'Cão': ['SRD (Vira-lata)', 'Shih Tzu', 'Yorkshire', 'Poodle', 'Lhasa Apso', 'Buldogue Francês', 'Golden Retriever', 'Labrador', 'Outro(a)'],
        'Gato': ['SRD (Vira-lata)', 'Siamês', 'Persa', 'Angorá', 'Sphynx', 'Maine Coon', 'Outro(a)'],
        'Outro(a)': []
    };

    // --- FUNÇÕES UTILITÁRIAS ---
    const showToast = (message, type = 'erro') => { /* ... (função inalterada) ... */
        const container = document.getElementById('toast-notification-container');
        const toast = document.createElement('div');
        const bgColor = type === 'erro' ? 'bg-red-500' : 'bg-green-500';
        toast.className = `${bgColor} text-white p-4 rounded-lg shadow-lg mb-2`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    };

    const buscarCep = async (cep) => { /* ... (função inalterada) ... */
        const cepLimpo = cep.replace(/\D/g, '');
        if (cepLimpo.length !== 8) return;
        showToast('Buscando CEP...', 'ok');
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
            const data = await response.json();
            if (data.erro) {
                showToast('CEP não encontrado.');
            } else {
                novoEnderecoRuaInput.value = data.logradouro || '';
                novoEnderecoBairroInput.value = data.bairro || '';
                novoEnderecoCidadeInput.value = data.localidade || '';
                novoEnderecoEstadoInput.value = data.uf || '';
                novoEnderecoNumeroInput.focus();
            }
        } catch (error) {
            showToast('Erro ao buscar o CEP. Tente novamente.');
        }
    };

    // CORREÇÃO JS: Nova função para a máscara de telefone
    const applyPhoneMask = (e) => {
        let value = e.target.value.replace(/\D/g, '');
        value = value.substring(0, 11); // Limita a 11 dígitos
        let formattedValue = '';
        if (value.length > 0) formattedValue = `(${value.substring(0, 2)}`;
        if (value.length > 2) formattedValue = `(${value.substring(0, 2)}) ${value.substring(2, 3)}`;
        if (value.length > 3) formattedValue = `(${value.substring(0, 2)}) ${value.substring(2, 3)} ${value.substring(3, 7)}`;
        if (value.length > 7) formattedValue = `(${value.substring(0, 2)}) ${value.substring(2, 3)} ${value.substring(3, 7)}-${value.substring(7, 11)}`;
        e.target.value = formattedValue;
    };

    // --- FUNÇÕES PRINCIPAIS DO FLUXO (showStep, setActivePet, etc.) ---
    // ... (funções inalteradas) ...
    const showStep = (stepIndex) => {
        currentStep = stepIndex;
        stepElements.forEach((el, index) => el.classList.toggle('hidden', index !== currentStep));
        const indicatorCount = document.querySelectorAll('.step-indicator').length;
        for (let i = 0; i < indicatorCount; i++) {
            const indicator = document.getElementById(`step-indicator-${i}`);
            const text = document.getElementById(`step-text-${i}`);
            const bar = document.getElementById(`progress-bar-${i}`);
            indicator.classList.remove('bg-petBlue', 'text-white');
            text.classList.remove('text-petBlue', 'font-medium');
            if (i < currentStep) {
                indicator.classList.add('bg-petBlue', 'text-white');
                indicator.innerHTML = '✔';
            } else if (i === currentStep) {
                indicator.classList.add('bg-petBlue', 'text-white');
                indicator.textContent = i + 1;
                text.classList.add('text-petBlue', 'font-medium');
            } else {
                indicator.classList.add('bg-gray-200', 'text-petGray');
                indicator.textContent = i + 1;
            }
            if (bar) {
                bar.classList.remove('bg-petBlue', 'bg-gray-200');
                bar.classList.add(i < currentStep ? 'bg-petBlue' : 'bg-gray-200');
            }
        }
    };
    const setActivePet = (petId) => {
        selectedPetIdInput.value = petId;
        novoPetForm.classList.toggle('hidden', petId !== 'novo_pet');
        document.querySelectorAll('.pet-card').forEach(c => c.classList.toggle('selected', c.dataset.petId === petId));
    };
    const updatePetUI = (pets) => {
        petCardList.innerHTML = '';
        if (pets && pets.length > 0) {
            pets.forEach(pet => {
                const petInfo = `${pet.nome} (${pet.raca || pet.especie || 'N/A'})`;
                petCardList.innerHTML += `<div class="pet-card border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer" data-pet-id="${pet.id}" data-pet-info="${petInfo}"><div class="text-3xl mb-2">🐾</div><p class="font-semibold text-petGray">${pet.nome}</p><p class="text-sm text-gray-500">${pet.raca || pet.especie}</p></div>`;
            });
            togglePetBtn.classList.remove('hidden');
            petLoader.classList.add('hidden');
        } else {
            petLoader.textContent = 'Este cliente não possui pets. Cadastre um novo abaixo.';
            petLoader.classList.remove('hidden');
            setActivePet('novo_pet');
            togglePetBtn.classList.add('hidden');
        }
        document.querySelectorAll('.pet-card').forEach(card => card.addEventListener('click', () => setActivePet(card.dataset.petId)));
    };
    const updateAddressUI = (addresses) => {
        enderecoSelect.innerHTML = '<option value="">-- Selecione um endereço --</option>';
        enderecoSelect.innerHTML += '<option value="novo_endereco">** Cadastrar Novo Endereço **</option>';
        if (addresses && addresses.length > 0) {
            addresses.forEach(addr => {
                const option = new Option(`${addr.rua}, ${addr.numero}`, addr.id);
                enderecoSelect.add(option, enderecoSelect.options[1]);
            });
            addressLoader.classList.add('hidden');
            enderecoSelect.classList.remove('hidden');
            enderecoSelect.value = "";
            novoEnderecoForm.classList.add('hidden');
        } else {
            addressLoader.classList.add('hidden');
            enderecoSelect.classList.remove('hidden');
            enderecoSelect.value = 'novo_endereco'; 
            enderecoSelect.dispatchEvent(new Event('change')); 
        }
    };
    const fetchClientData = async (clientId) => {
        try {
            const response = await fetch(`ajax_get_client_data.php?cliente_id=${clientId}`);
            if (!response.ok) throw new Error(`Erro ${response.status}: ${response.statusText}`);
            const data = await response.json();
            return data;
        } catch (error) {
            showToast("Erro ao buscar dados do cliente.");
            console.error("Erro ao buscar dados do cliente:", error);
            return null; // Retorna nulo para indicar falha
        }
    };
    const populateSummary = () => {
        const clienteNome = (clienteSelect.value === 'novo_cliente') ? `${novoClienteNomeInput.value.trim()} (Novo)`: clienteSelect.options[clienteSelect.selectedIndex].dataset.clienteNome;
        document.getElementById('summary-owner-name').textContent = clienteNome;
        let petText = '';
        if (selectedPetIdInput.value === 'novo_pet') {
            const especie = especieSelect.value === 'Outro(a)' ? outraEspecieInput.value : especieSelect.value;
            let raca = racaSelect.value === 'Outro(a)' ? outraRacaInput.value : racaSelect.value;
            if(!raca) raca = "N/A";
            petText = `${novoPetNomeInput.value.trim()} (${especie} - ${raca}) (Novo)`;
        } else {
            const selectedCard = document.querySelector('.pet-card.selected');
            if(selectedCard) petText = selectedCard.dataset.petInfo;
        }
        document.getElementById('summary-pet-name').textContent = petText;
        document.getElementById('summary-services').textContent = Array.from(document.querySelectorAll('input[name="servicos[]"]:checked')).map(cb => cb.dataset.label).join(', ');
        const deliveryType = document.querySelector('input[name="tipo_entrega"]:checked').value;
        let deliveryText = "Cliente levará e buscará na loja.";
        if(deliveryType === 'delivery') {
            deliveryText = (enderecoSelect.value === 'novo_endereco') ? `Buscar em: ${novoEnderecoRuaInput.value.trim()} (Novo)`: `Buscar em: ${enderecoSelect.options[enderecoSelect.selectedIndex].text}`;
        }
        document.getElementById('summary-delivery').textContent = deliveryText;
        if(selectedDate && selectedTime){
            const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
            document.getElementById('summary-datetime').textContent = selectedDate.toLocaleDateString('pt-BR', dateOptions) + ' às ' + selectedTime;
        }
        document.getElementById('summary-notes').textContent = observacoesInput.value.trim() || 'Nenhuma.';
    };

    // --- NAVEGAÇÃO E VALIDAÇÃO ---
    navButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const direction = button.dataset.direction;
            if (direction === 'next') {
                let canProceed = true;
                switch(currentStep) {
                    case 0:
                        // CORREÇÃO JS: Adiciona validação de e-mail no front-end
                        if (!clienteSelect.value) {
                            showToast('Por favor, selecione um cliente.'); canProceed = false;
                        } else if (clienteSelect.value === 'novo_cliente') {
                            if (!novoClienteNomeInput.value.trim()) {
                                showToast('O nome do novo cliente é obrigatório.'); canProceed = false;
                            } else if (!novoClienteEmailInput.value.trim()) {
                                showToast('O e-mail do novo cliente é obrigatório.'); canProceed = false;
                            } else if (!/^\S+@\S+\.\S+$/.test(novoClienteEmailInput.value)) {
                                showToast('Por favor, insira um formato de e-mail válido.'); canProceed = false;
                            }
                        }
                        
                        if (canProceed) {
                             if (clienteSelect.value !== 'novo_cliente') {
                                button.disabled = true; button.textContent = 'Buscando...';
                                const clientData = await fetchClientData(clienteSelect.value);
                                if(clientData){
                                    updatePetUI(clientData.pets); 
                                    updateAddressUI(clientData.enderecos);
                                    showStep(currentStep + 1);
                                }
                                button.disabled = false; button.textContent = 'Próximo';
                             } else {
                                updatePetUI([]); 
                                updateAddressUI([]);
                                showStep(currentStep + 1);
                            }
                        }
                        return; // Sai da função de clique aqui
                        
                    case 1:
                        if (!selectedPetIdInput.value) {
                            showToast('Por favor, selecione ou cadastre um pet.'); canProceed = false;
                        } else if (selectedPetIdInput.value === 'novo_pet') {
                            if (!novoPetNomeInput.value.trim()) { showToast('O nome do novo pet é obrigatório.'); canProceed = false; }
                            if (!especieSelect.value) { showToast('A espécie do novo pet é obrigatória.'); canProceed = false; }
                            if (especieSelect.value === 'Outro(a)' && !outraEspecieInput.value.trim()) { showToast('Por favor, especifique a espécie.'); canProceed = false; }
                            if (especieSelect.value !== 'Outro(a)' && !racaSelect.value) { showToast('A raça do novo pet é obrigatória.'); canProceed = false; }
                            if (racaSelect.value === 'Outro(a)' && !outraRacaInput.value.trim()) { showToast('Por favor, especifique a raça.'); canProceed = false; }
                        }
                        break;
                    
                    // ... (validação dos outros passos inalterada) ...
                    case 2:
                        if (document.querySelectorAll('input[name="servicos[]"]:checked').length === 0) {
                            showToast('Selecione pelo menos um serviço.'); canProceed = false;
                        }
                        break;
                    case 3:
                        const deliveryType = document.querySelector('input[name="tipo_entrega"]:checked').value;
                        if (deliveryType === 'delivery' && (!enderecoSelect.value || (enderecoSelect.value === 'novo_endereco' && !novoEnderecoRuaInput.value.trim()))) {
                            showToast('Para entrega, selecione ou cadastre um endereço com rua preenchida.'); canProceed = false;
                        }
                        break;
                    case 4:
                        if (!selectedDate || !selectedTime) {
                            showToast('Por favor, selecione uma data e um horário.'); canProceed = false;
                        } else {
                            const year = selectedDate.getFullYear();
                            const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
                            const day = String(selectedDate.getDate()).padStart(2, '0');
                            const dateTimeString = `${year}-${month}-${day} ${selectedTime}:00`;
                            dataAgendamentoHidden.value = dateTimeString;
                            populateSummary();
                        }
                        break;
                }
                if (canProceed) showStep(currentStep + 1);
            } else {
                showStep(currentStep - 1);
            }
        });
    });

    // --- FUNÇÃO DE RESTAURAÇÃO DE ESTADO ---
    const restoreFormState = async (data) => { /* ... (função inalterada) ... */
        if (data.cliente_id) {
            clienteSelect.value = data.cliente_id;
            if (data.cliente_id === 'novo_cliente') {
                novoClienteForm.classList.remove('hidden');
                document.getElementById('novo_cliente_nome').value = data.novo_cliente_nome || '';
                document.getElementById('novo_cliente_email').value = data.novo_cliente_email || '';
                const telInput = document.getElementById('novo_cliente_telefone');
                telInput.value = data.novo_cliente_telefone || '';
            }
        }
        if (data.servicos && Array.isArray(data.servicos)) {
            data.servicos.forEach(v => {
                const cb = document.querySelector(`input[name="servicos[]"][value="${v}"]`);
                if (cb) { cb.checked = true; cb.closest('.service-option').classList.add('selected'); }
            });
        }
        if (data.tipo_entrega) {
            const radio = document.querySelector(`input[name="tipo_entrega"][value="${data.tipo_entrega}"]`);
            if (radio) {
                radio.checked = true;
                radio.closest('.service-option').classList.add('selected');
                if (data.tipo_entrega === 'delivery') enderecoSection.classList.remove('hidden');
            }
        }
        if (data.data_agendamento) {
            const [datePart, timePart] = data.data_agendamento.split(' ');
            selectedDate = new Date(datePart.replace(/-/g, '/') + ' 00:00:00'); // Safari friendly
            selectedTime = timePart.substring(0, 5);
            dataAgendamentoHidden.value = data.data_agendamento;
        }
        if (data.observacoes) observacoesInput.value = data.observacoes;

        const populateListsAndContinue = (clientData) => {
            if (clientData) {
                updatePetUI(clientData.pets);
                updateAddressUI(clientData.enderecos);
            } else {
                updatePetUI([]);
                updateAddressUI([]);
            }
            if (data.pet_id) {
                setActivePet(data.pet_id);
                if (data.pet_id === 'novo_pet') {
                    novoPetNomeInput.value = data.novo_pet_nome || '';
                    novoPetNascimentoInput.value = data.novo_pet_nascimento || '';
                    if (data.novo_pet_especie) {
                        especieSelect.value = data.novo_pet_especie;
                        especieSelect.dispatchEvent(new Event('change'));
                        if (data.novo_pet_especie === 'Outro(a)') outraEspecieInput.value = data.outra_especie || '';
                    }
                    if (data.novo_pet_raca) {
                        setTimeout(() => { 
                            racaSelect.value = data.novo_pet_raca;
                            racaSelect.dispatchEvent(new Event('change'));
                            if (data.novo_pet_raca === 'Outro(a)') outraRacaInput.value = data.outra_raca || '';
                        }, 100);
                    }
                }
            }
            if (data.endereco_id) {
                enderecoSelect.value = data.endereco_id;
                if (data.endereco_id === 'novo_endereco') {
                    enderecoSelect.dispatchEvent(new Event('change'));
                    novoEnderecoCepInput.value = data.novo_endereco_cep || '';
                    novoEnderecoRuaInput.value = data.novo_endereco_rua || '';
                    novoEnderecoNumeroInput.value = data.novo_endereco_numero || '';
                    novoEnderecoBairroInput.value = data.novo_endereco_bairro || '';
                    novoEnderecoCidadeInput.value = data.novo_endereco_cidade || '';
                    novoEnderecoEstadoInput.value = data.novo_endereco_estado || '';
                    novoEnderecoComplementoInput.value = data.novo_endereco_complemento || '';
                }
            }
            if (errorStep !== null) showStep(errorStep);
        };
        
        if (data.cliente_id && data.cliente_id !== 'novo_cliente') {
            const clientData = await fetchClientData(data.cliente_id);
            populateListsAndContinue(clientData);
        } else {
            populateListsAndContinue(null);
        }
    };

    // --- EVENT LISTENERS ---
    // ... (listeners de espécie/raça, CEP e outros inalterados) ...
    especieSelect.innerHTML = '<option value="">-- Selecione --</option>';
    Object.keys(petData).forEach(especie => especieSelect.add(new Option(especie, especie)));
    especieSelect.addEventListener('change', () => {
        const selectedEspecie = especieSelect.value;
        racaSelect.innerHTML = '<option value="">-- Selecione --</option>';
        racaSelect.dispatchEvent(new Event('change'));
        outraEspecieDiv.classList.toggle('hidden', selectedEspecie !== 'Outro(a)');
        if (selectedEspecie === 'Outro(a)') outraEspecieInput.focus();
        if (petData[selectedEspecie] && petData[selectedEspecie].length > 0) {
            racaSelect.disabled = false;
            petData[selectedEspecie].forEach(raca => racaSelect.add(new Option(raca, raca)));
        } else {
            racaSelect.disabled = true;
        }
    });
    racaSelect.addEventListener('change', () => {
        outraRacaDiv.classList.toggle('hidden', racaSelect.value !== 'Outro(a)');
        if (racaSelect.value === 'Outro(a)') outraRacaInput.focus();
    });
    novoEnderecoCepInput.addEventListener('blur', (e) => buscarCep(e.target.value));
    clienteSelect.addEventListener('change', () => novoClienteForm.classList.toggle('hidden', clienteSelect.value !== 'novo_cliente'));
    togglePetBtn.addEventListener('click', () => setActivePet('novo_pet'));
    document.querySelectorAll('.service-option input[type="checkbox"]').forEach(i => i.addEventListener('change', (e)=>e.target.closest('.service-option').classList.toggle('selected', e.target.checked)));
    document.querySelectorAll('input[name="tipo_entrega"]').forEach(r => r.addEventListener('change', (e) => {
        enderecoSection.classList.toggle('hidden', e.target.value !== 'delivery');
        document.querySelectorAll('input[name="tipo_entrega"]').forEach(i => i.closest('.service-option').classList.remove('selected'));
        e.target.closest('.service-option').classList.add('selected');
    }));
    enderecoSelect.addEventListener('change', () => novoEnderecoForm.classList.toggle('hidden', enderecoSelect.value !== 'novo_endereco'));

    // CORREÇÃO JS: Adiciona o listener para a máscara de telefone
    novoClienteTelefoneInput.addEventListener('input', applyPhoneMask);

    // --- INICIALIZAÇÃO ---
    // ... (lógica de inicialização com calendário e recuperação de estado inalterada) ...
    const calendarDaysEl = document.getElementById('calendar-days');
    const currentMonthEl = document.getElementById('current-month');
    const timeSlotsContainer = document.getElementById('time-slots');
    let calendarRefDate = new Date();
    const renderTimeSlots = () => { timeSlotsContainer.innerHTML = ''; availableTimes.forEach(time => { const btn = document.createElement('button'); btn.type = 'button'; btn.className = `time-slot py-2 px-3 border border-gray-300 rounded-md text-petGray hover:border-petBlue ${selectedTime === time ? 'selected' : ''}`; btn.dataset.time = time; btn.textContent = time; btn.addEventListener('click', () => { selectedTime = time; renderTimeSlots(); }); timeSlotsContainer.appendChild(btn); }); };
    const renderCalendar = () => { if(!calendarDaysEl) return; let dateForMonth = new Date(calendarRefDate.getTime()); dateForMonth.setDate(1); const firstDayIndex = dateForMonth.getDay(); const lastDay = new Date(dateForMonth.getFullYear(), dateForMonth.getMonth() + 1, 0).getDate(); const prevLastDay = new Date(dateForMonth.getFullYear(), dateForMonth.getMonth(), 0).getDate(); const nextDays = 7 - (new Date(dateForMonth.getFullYear(), dateForMonth.getMonth(), lastDay).getDay()) - 1; const months = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"]; currentMonthEl.innerHTML = `${months[dateForMonth.getMonth()]} ${dateForMonth.getFullYear()}`; let days = ""; for (let x = firstDayIndex; x > 0; x--) { days += `<div class="py-2 text-center text-gray-300">${prevLastDay - x + 1}</div>`; } const today = new Date(); today.setHours(0, 0, 0, 0); for (let i = 1; i <= lastDay; i++) { const dayDate = new Date(dateForMonth.getFullYear(), dateForMonth.getMonth(), i); let classes = 'date-cell py-2 text-center rounded-md '; if (dayDate < today) { classes += 'text-gray-300 cursor-not-allowed'; } else { classes += 'cursor-pointer hover:bg-petBlue hover:text-white transition-transform duration-200'; if (selectedDate && dayDate.getTime() === selectedDate.getTime()) classes += ' selected'; } days += `<div class="${classes}" data-date="${dayDate.toISOString()}">${i}</div>`; } for (let j = 1; j <= nextDays; j++) { days += `<div class="py-2 text-center text-gray-300">${j}</div>`; } calendarDaysEl.innerHTML = days; document.querySelectorAll('#calendar-days div[data-date]').forEach(dayEl => { dayEl.addEventListener('click', (e) => { const clickedDate = new Date(e.target.dataset.date); if(clickedDate >= today) { selectedDate = clickedDate; selectedTime = null; renderCalendar(); renderTimeSlots(); }}); }); };
    document.getElementById('prev-month')?.addEventListener('click', () => { calendarRefDate.setMonth(calendarRefDate.getMonth() - 1); renderCalendar(); });
    document.getElementById('next-month')?.addEventListener('click', () => { calendarRefDate.setMonth(calendarRefDate.getMonth() + 1); renderCalendar(); });

    if (preservedData) {
        restoreFormState(preservedData);
    } else {
        showStep(0);
    }
    renderCalendar();
    renderTimeSlots();
});
</script>
</body>
</html>