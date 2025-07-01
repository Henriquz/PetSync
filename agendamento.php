<?php
// ======================================================================
// PetSync - Página de Agendamento v12.8 (Padrão Cliente com Melhorias)
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Apenas usuários logados (não-admins) podem acessar esta página.
if (!isset($_SESSION['usuario']) || !empty($_SESSION['usuario']['is_admin'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 2. DEFINIÇÕES INICIAIS
// ----------------------------------------------------------------------
$page_title = 'Novo Agendamento - PetSync';
$usuario_logado = $_SESSION['usuario'];
$id_usuario_logado = $usuario_logado['id'];
$ok = '';
$erro = '';
$form_data_json = 'null';
$error_step_json = 'null';

// 3. PROCESSAMENTO DO FORMULÁRIO
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_agendamento'])) {
    $pet_id = $_POST['pet_id'] ?? null;
    $servicos_array = $_POST['servicos'] ?? [];
    $data_agendamento_str = $_POST['data_agendamento'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $tipo_entrega = $_POST['tipo_entrega'] ?? null;
    $endereco_id = ($tipo_entrega === 'delivery') ? ($_POST['endereco_id'] ?? null) : null;
    $error_step = 0; // Passo padrão do erro

    // Lógica de cadastro de PET
    if (empty($erro) && $pet_id === 'novo_pet') {
        $pet_nome = trim($_POST['novo_pet_nome']);
        $pet_nascimento = !empty($_POST['novo_pet_nascimento']) ? $_POST['novo_pet_nascimento'] : null;
        $pet_especie = $_POST['novo_pet_especie'] ?? '';
        if ($pet_especie === 'Outro(a)') { $pet_especie = trim($_POST['outra_especie'] ?? ''); }
        $pet_raca = $_POST['novo_pet_raca'] ?? '';
        if ($pet_raca === 'Outro(a)') { $pet_raca = trim($_POST['outra_raca'] ?? ''); }
        
        if(empty($pet_raca) && isset($_POST['novo_pet_especie']) && $_POST['novo_pet_especie'] === 'Outro(a)'){
            $pet_raca = 'N/A';
        }

        if (!empty($pet_nome) && !empty($pet_especie) && !empty($pet_raca)) {
            $stmt_pet = $mysqli->prepare("INSERT INTO pets (dono_id, nome, especie, raca, data_nascimento) VALUES (?, ?, ?, ?, ?)");
            $stmt_pet->bind_param("issss", $id_usuario_logado, $pet_nome, $pet_especie, $pet_raca, $pet_nascimento);
            if ($stmt_pet->execute()) { 
                $pet_id = $mysqli->insert_id; 
            } else { 
                $erro = "Ocorreu um erro ao cadastrar seu novo pet."; 
            }
            $stmt_pet->close();
        } else {
            $erro = "Nome, espécie e raça são obrigatórios para o novo pet.";
            $error_step = 0; // Erro no passo do Pet
        }
    }
    
    // Cadastro de novo Endereço
    if (empty($erro) && $tipo_entrega === 'delivery' && $endereco_id === 'novo_endereco') {
        if (!empty(trim($_POST['novo_endereco_rua']))) {
            $stmt_end = $mysqli->prepare("INSERT INTO enderecos (usuario_id, rua, numero, complemento, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_end->bind_param("isssssss", $id_usuario_logado, $_POST['novo_endereco_rua'], $_POST['novo_endereco_numero'], $_POST['novo_endereco_complemento'], $_POST['novo_endereco_bairro'], $_POST['novo_endereco_cidade'], $_POST['novo_endereco_estado'], $_POST['novo_endereco_cep']);
            if($stmt_end->execute()) {
                $endereco_id = $mysqli->insert_id;
            } else {
                $erro = "Ocorreu um erro ao cadastrar seu novo endereço.";
            }
            $stmt_end->close();
        } else {
             $erro = "A rua do novo endereço é obrigatória para delivery.";
             $error_step = 2; // Erro no passo de Entrega
        }
    }

    // Validação final e definição do passo do erro
    $servicos = implode(', ', $servicos_array);
    if (empty($erro)) {
        if (!$pet_id || $pet_id === 'novo_pet') { $erro = "Pet não foi selecionado ou cadastrado corretamente."; $error_step = 0; }
        elseif (empty($servicos)) { $erro = "Nenhum serviço foi selecionado."; $error_step = 1; }
        elseif (!$tipo_entrega) { $erro = "O tipo de entrega não foi selecionado."; $error_step = 2; }
        elseif ($tipo_entrega === 'delivery' && (!$endereco_id || $endereco_id === 'novo_endereco')) { $erro = "Selecione ou cadastre um endereço para a entrega."; $error_step = 2; }
        elseif (!$data_agendamento_str) { $erro = "A data e o horário não foram selecionados."; $error_step = 3; }
    }

    // Se não houver erros, salva no banco
    if (empty($erro)) {
        $stmt = $mysqli->prepare("INSERT INTO agendamentos (usuario_id, pet_id, servico, data_agendamento, observacoes, tipo_entrega, endereco_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissisi", $id_usuario_logado, $pet_id, $servicos, $data_agendamento_str, $observacoes, $tipo_entrega, $endereco_id);
        
        if ($stmt->execute()) {
            $_SESSION['ok_msg'] = ($tipo_entrega === 'delivery') 
                ? "Agendamento solicitado! Buscaremos seu pet no endereço selecionado." 
                : "Agendamento solicitado! Assim que o serviço for concluído, avisaremos para você buscar seu pet.";
        } else {
            $_SESSION['erro_msg'] = "Ocorreu um erro ao salvar seu agendamento. Tente novamente.";
            $_SESSION['form_data'] = $_POST;
            $_SESSION['error_step'] = 4; // Erro no passo de confirmação
        }
        $stmt->close();
    } else {
        $_SESSION['erro_msg'] = $erro;
        $_SESSION['form_data'] = $_POST;
        $_SESSION['error_step'] = $error_step;
    }
    
    header("Location: agendamento.php");
    exit;
}

// 4. LÓGICA PARA EXIBIÇÃO DA PÁGINA
// ----------------------------------------------------------------------
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

$horarios_disponiveis = $mysqli->query("SELECT TIME_FORMAT(horario, '%H:%i') as horario_formatado FROM horarios_disponiveis ORDER BY horario ASC")->fetch_all(MYSQLI_ASSOC);
$horarios_json = json_encode(array_column($horarios_disponiveis, 'horario_formatado'));
$cliente_selecionado = $mysqli->query("SELECT * FROM usuarios WHERE id = $id_usuario_logado")->fetch_assoc();
$pets_cliente = $mysqli->query("SELECT * FROM pets WHERE dono_id = $id_usuario_logado ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$enderecos_cliente = $mysqli->query("SELECT * FROM enderecos WHERE usuario_id = $id_usuario_logado ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
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
        body { font-family: 'Poppins', sans-serif; background-color: #f7fafc; }
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
<body>
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3"><div class="flex justify-between items-center"><a href="/petsync/index.php" class="text-2xl font-bold text-petBlue flex items-center"><svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 9C9.10457 9 10 8.10457 10 7C10 5.89543 9.10457 5 8 5C6.89543 5 6 5.89543 6 7C6 8.10457 6.89543 9 8 9Z" fill="#FF7A00"></path><path d="M16 9C17.1046 9 18 8.10457 18 7C18 5.89543 17.1046 5 16 5C14.8954 5 14 5.89543 14 7C14 8.10457 14.8954 9 16 9Z" fill="#FF7A00"></path><path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#FF7A00"></path><path d="M18 14C19.1046 14 20 13.1046 20 12C20 10.8954 19.1046 10 18 10C16.8954 10 16 10.8954 16 12C16 13.1046 16.8954 14 18 14Z" fill="#FF7A00"></path><path d="M12 18C13.6569 18 15 16.6569 15 15C15 13.3431 13.6569 12 12 12C10.3431 12 9 13.3431 9 15C9 16.6569 10.3431 18 12 18Z" fill="#0078C8"></path></svg>Pet<span class="text-petOrange">Sync</span></a><a href="/petsync/index.php" class="bg-petOrange hover:bg-orange-700 text-white font-medium py-2 px-5 rounded-lg text-center transition duration-300">Voltar Ao Início</a></div></div>
    </nav>
    
    <div id="toast-notification-container" class="fixed top-5 right-5 z-[100]">
        <?php if ($ok): ?><div class="bg-green-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    </div>

    <?php if (empty($ok)): ?>
        <main id="booking-form-container" class="py-12">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-12"><h1 class="text-4xl md:text-5xl font-bold text-petGray mb-4">Agendar <span class="text-petOrange">Visita</span></h1></div>
                    <div class="flex items-center justify-between mb-12 space-x-2 text-xs sm:text-sm">
                        <?php $steps_texts = ['Pet', 'Serviços', 'Entrega', 'Horário', 'Confirmar']; ?>
                        <?php foreach($steps_texts as $i => $text): ?>
                            <div class="flex flex-col items-center text-center w-1/<?= count($steps_texts) ?>"><div class="w-10 h-10 bg-gray-200 text-petGray rounded-full flex items-center justify-center font-bold step-indicator" id="step-indicator-<?= $i ?>"><?= $i+1 ?></div><span class="mt-2 text-petGray" id="step-text-<?= $i ?>"><?= $text ?></span></div>
                            <?php if($i < count($steps_texts) - 1): ?><div class="flex-1 h-1 bg-gray-200" id="progress-bar-<?= $i ?>"></div><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <form id="booking-form" action="agendamento.php" method="POST" novalidate>
                        <input type="hidden" name="data_agendamento" id="data_agendamento_hidden">
                        <input type="hidden" name="pet_id" id="selected_pet_id" value="<?= empty($pets_cliente) ? 'novo_pet' : '' ?>">
                        
                        <div id="steps-container">
                            <div class="step-content bg-white p-8 rounded-lg shadow-md" id="step0">
                                <h2 class="text-2xl font-bold text-petGray mb-6">Passo 1: Informações do Pet</h2>
                                <div class="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg mb-6">
                                    <p><span class="font-semibold">Cliente:</span> <?= htmlspecialchars($cliente_selecionado['nome'] ?? '') ?></p>
                                </div>
                                <label class="block text-petGray font-medium mb-2">Selecione o Pet <span class="text-red-500">*</span></label>
                                <?php if (!empty($pets_cliente)): ?>
                                <div id="pet-card-list" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                                    <?php foreach($pets_cliente as $pet): ?>
                                    <div class="pet-card border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer transition-all" data-pet-id="<?= $pet['id'] ?>" data-pet-info="<?= htmlspecialchars($pet['nome']) ?> (<?= htmlspecialchars($pet['raca'] ?? $pet['especie']) ?>)">
                                        <div class="text-3xl mb-2">🐾</div>
                                        <p class="font-semibold text-petGray"><?= htmlspecialchars($pet['nome']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($pet['raca'] ?? $pet['especie']) ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="toggle-pet-form-btn" class="text-sm text-petBlue hover:underline font-semibold">... ou cadastrar outro pet</button>
                                <?php endif; ?>
                                
                                <div id="novo-pet-form" class="<?= empty($pets_cliente) ? '' : 'hidden' ?> grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 border-t pt-6 mt-4">
                                     <h3 class="md:col-span-2 text-lg font-semibold text-petOrange"><?= empty($pets_cliente) ? 'Cadastre seu primeiro pet para começar' : 'Dados do Novo Pet' ?></h3>
                                     <div>
                                        <label for="novo_pet_nome" class="block text-sm font-medium text-petGray">Nome do Pet <span class="text-red-500">*</span></label>
                                        <input type="text" name="novo_pet_nome" id="novo_pet_nome" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                     <div>
                                        <label for="novo_pet_nascimento" class="block text-sm font-medium text-petGray">Data de Nascimento</label>
                                        <input type="date" name="novo_pet_nascimento" id="novo_pet_nascimento" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                     <div>
                                        <label for="novo_pet_especie_select" class="block text-sm font-medium text-petGray">Espécie<span class="text-red-500">*</span></label>
                                        <select id="novo_pet_especie_select" name="novo_pet_especie" class="w-full mt-1 p-2 border rounded-md form-input"></select>
                                     </div>
                                      <div id="outra_especie_div" class="hidden">
                                        <label class="block text-sm font-medium text-petGray">Qual espécie?</label>
                                        <input type="text" id="outra_especie_input" name="outra_especie" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                     <div>
                                        <label for="novo_pet_raca_select" class="block text-sm font-medium text-petGray">Raça<span class="text-red-500">*</span></label>
                                        <select id="novo_pet_raca_select" name="novo_pet_raca" class="w-full mt-1 p-2 border rounded-md form-input"></select>
                                     </div>
                                     <div id="outra_raca_div" class="hidden">
                                        <label class="block text-sm font-medium text-petGray">Qual raça?</label>
                                        <input type="text" id="outra_raca_input" name="outra_raca" class="w-full mt-1 p-2 border rounded-md form-input">
                                     </div>
                                </div>
                                <div class="mt-8 flex justify-end"><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                            </div>
                            
                            <div class="step-content hidden" id="step1">
                                <div class="bg-white p-8 rounded-lg shadow-md">
                                    <h2 class="text-2xl font-bold text-petGray mb-6">Passo 2: Selecione os Serviços</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="services-list">
                                        <label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Banho e Tosa" data-label="Banho e Tosa" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Banho e Tosa</span></label>
                                        <label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Consulta Veterinária" data-label="Consulta Veterinária" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Consulta Veterinária</span></label>
                                        <label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Vacinação" data-label="Vacinação" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Vacinação</span></label>
                                        <label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Hospedagem" data-label="Hospedagem" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Hospedagem</span></label>
                                    </div>
                                    <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                                </div>
                            </div>
                            
                            <div class="step-content hidden" id="step2">
                                <div class="bg-white p-8 rounded-lg shadow-md">
                                    <h2 class="text-2xl font-bold text-petGray mb-6">Passo 3: Como será a entrega e retirada?</h2>
                                    <div class="space-y-4">
                                        <label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="radio" name="tipo_entrega" value="loja" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue" checked><span class="ml-3 text-petGray text-lg">Vou levar e buscar na loja</span></label>
                                        <label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="radio" name="tipo_entrega" value="delivery" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray text-lg">Quero que busquem e entreguem em casa</span></label>
                                    </div>
                                    <div id="endereco-delivery-section" class="hidden mt-6 space-y-4">
                                        <label for="endereco_id" class="block text-petGray font-medium">Selecione o endereço:</label>
                                        <?php if(!empty($enderecos_cliente)): ?>
                                        <select name="endereco_id" id="endereco_id" class="w-full p-2 border rounded-md bg-white form-input">
                                            <option value="">-- Seus endereços cadastrados --</option>
                                            <?php foreach($enderecos_cliente as $endereco): ?>
                                            <option value="<?= $endereco['id'] ?>" data-endereco-info="<?= htmlspecialchars($endereco['rua']) . ', ' . htmlspecialchars($endereco['numero']) ?>"><?= htmlspecialchars($endereco['rua']) . ', ' . htmlspecialchars($endereco['numero']) ?></option>
                                            <?php endforeach; ?>
                                            <option value="novo_endereco">** Cadastrar Novo Endereço **</option>
                                        </select>
                                        <?php else: ?>
                                        <input type="hidden" name="endereco_id" id="endereco_id" value="novo_endereco">
                                        <?php endif; ?>
                                        <div id="novo-endereco-form" class="<?= empty($enderecos_cliente) ? '' : 'hidden' ?> grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-6 mt-4">
                                            <h3 class="md:col-span-2 text-lg font-semibold text-petOrange"><?= empty($enderecos_cliente) ? 'Cadastre seu primeiro endereço' : 'Dados do Novo Endereço' ?></h3>
                                            <div>
                                                <label for="novo_endereco_cep" class="block text-sm font-medium text-petGray">CEP</label>
                                                <input type="text" id="novo_endereco_cep" name="novo_endereco_cep" placeholder="Digite o CEP" class="w-full mt-1 p-2 border rounded-md form-input">
                                            </div>
                                            <div></div>
                                            <div class="md:col-span-2">
                                                <label for="novo_endereco_rua" class="block text-sm font-medium text-petGray">Rua / Avenida <span class="text-red-500">*</span></label>
                                                <input type="text" id="novo_endereco_rua" name="novo_endereco_rua" placeholder="Rua / Avenida" class="w-full mt-1 p-2 border rounded-md form-input">
                                            </div>
                                            <div>
                                                <label for="novo_endereco_numero" class="block text-sm font-medium text-petGray">Número</label>
                                                <input type="text" id="novo_endereco_numero" name="novo_endereco_numero" placeholder="Número" class="w-full mt-1 p-2 border rounded-md form-input">
                                            </div>
                                            <div>
                                                <label for="novo_endereco_bairro" class="block text-sm font-medium text-petGray">Bairro</label>
                                                <input type="text" id="novo_endereco_bairro" name="novo_endereco_bairro" placeholder="Bairro" class="w-full mt-1 p-2 border rounded-md form-input">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label for="novo_endereco_complemento" class="block text-sm font-medium text-petGray">Complemento</label>
                                                <input type="text" id="novo_endereco_complemento" name="novo_endereco_complemento" placeholder="Apto, Bloco (Opcional)" class="w-full mt-1 p-2 border rounded-md form-input">
                                            </div>
                                            <div>
                                                <label for="novo_endereco_cidade" class="block text-sm font-medium text-petGray">Cidade</label>
                                                <input type="text" id="novo_endereco_cidade" name="novo_endereco_cidade" placeholder="Cidade" class="w-full mt-1 p-2 border rounded-md form-input">
                                            </div>
                                            <div>
                                                <label for="novo_endereco_estado" class="block text-sm font-medium text-petGray">Estado</label>
                                                <input type="text" id="novo_endereco_estado" name="novo_endereco_estado" placeholder="UF" maxlength="2" class="w-full mt-1 p-2 border rounded-md form-input">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                                </div>
                            </div>
                            
                            <div class="step-content hidden" id="step3">
                                <div class="bg-white p-8 rounded-lg shadow-md">
                                    <h2 class="text-2xl font-bold text-petGray mb-6">Passo 4: Escolha a Data e Horário</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div><h3 class="text-lg font-semibold text-petGray mb-4">Selecione uma data</h3><div class="calendar bg-white border border-gray-300 rounded-lg p-4"><div class="flex justify-between items-center mb-4"><button type="button" id="prev-month" class="p-2 rounded-full hover:bg-gray-100"><svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button><h4 id="current-month" class="text-petGray font-semibold text-lg"></h4><button type="button" id="next-month" class="p-2 rounded-full hover:bg-gray-100"><svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button></div><div class="grid grid-cols-7 gap-1 text-center text-gray-500 text-sm font-medium"><div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div></div><div id="calendar-days" class="grid grid-cols-7 gap-1 mt-2"></div></div></div>
                                        <div><h3 class="text-lg font-semibold text-petGray mb-4">Selecione um horário</h3><div id="time-slots" class="grid grid-cols-2 sm:grid-cols-3 gap-3"></div><div class="mt-6"><h3 class="text-lg font-semibold text-petGray mb-4">Observações</h3><textarea name="observacoes" id="observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md form-input" placeholder="Alguma informação adicional? Ex: Alergias, comportamento..."></textarea></div></div>
                                    </div>
                                    <div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div>
                                </div>
                            </div>
                            
                            <div class="step-content hidden" id="step4">
                                <div class="bg-white p-8 rounded-lg shadow-md">
                                    <h2 class="text-2xl font-bold text-petGray mb-6">Passo 5: Confirme seu Agendamento</h2>
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
                        </div>
                    </form>
                </div>
            </div>
        </main>
    <?php else: ?>
        <main class="py-12"><div class="container mx-auto px-4 py-16 text-center"><div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg"><div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6"><svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></div><h2 class="text-3xl font-bold text-petGray mb-4">Agendamento Solicitado!</h2><p class="text-petGray text-lg mb-6"><?= htmlspecialchars($ok) ?></p><p class="text-gray-500 mb-8">Obrigado por escolher a PetSync! Redirecionando para a página inicial em 5 segundos...</p></div></div></main>
        <script> setTimeout(() => { window.location.href = '/petsync/index.php'; }, 5000); </script>
    <?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const preservedData = <?= $form_data_json ?>;
    const errorStep = <?= $error_step_json ?>;

    if (!document.getElementById('booking-form-container') && !preservedData) return;

    // --- ELEMENTOS GLOBAIS ---
    const stepElements = document.querySelectorAll('.step-content');
    const navButtons = document.querySelectorAll('.nav-btn');
    let currentStep = 0;

    // --- ELEMENTOS DO FORMULÁRIO ---
    const ownerName = <?= json_encode($cliente_selecionado['nome'] ?? '') ?>;
    const selectedPetIdInput = document.getElementById('selected_pet_id');
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
    const enderecoIdSelect = document.getElementById('endereco_id');
    const novoEnderecoForm = document.getElementById('novo-endereco-form');
    const novoEnderecoCepInput = document.getElementById('novo_endereco_cep');
    const novoEnderecoRuaInput = document.getElementById('novo_endereco_rua');
    const novoEnderecoNumeroInput = document.getElementById('novo_endereco_numero');
    const novoEnderecoBairroInput = document.getElementById('novo_endereco_bairro');
    const novoEnderecoCidadeInput = document.getElementById('novo_endereco_cidade');
    const novoEnderecoEstadoInput = document.getElementById('novo_endereco_estado');
    const novoEnderecoComplementoInput = document.getElementById('novo_endereco_complemento');

    const dataAgendamentoHidden = document.getElementById('data_agendamento_hidden');
    const observacoesInput = document.getElementById('observacoes');
    
    let selectedDate = null, selectedTime = null;
    const availableTimes = <?= $horarios_json ?>;

    const petData = {
        'Cão': ['SRD (Vira-lata)', 'Shih Tzu', 'Yorkshire', 'Poodle', 'Lhasa Apso', 'Buldogue Francês', 'Golden Retriever', 'Labrador', 'Outro(a)'],
        'Gato': ['SRD (Vira-lata)', 'Siamês', 'Persa', 'Angorá', 'Sphynx', 'Maine Coon', 'Outro(a)'],
        'Outro(a)': []
    };

    // --- FUNÇÕES UTILITÁRIAS ---
    const showToast = (message, type = 'erro') => {
        const container = document.getElementById('toast-notification-container');
        const toast = document.createElement('div');
        const bgColor = type === 'erro' ? 'bg-red-500' : 'bg-green-500';
        toast.className = `${bgColor} text-white p-4 rounded-lg shadow-lg mb-2`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    };

    const buscarCep = async (cep) => {
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

    // --- FUNÇÕES DE LÓGICA DO FORMULÁRIO ---
    const showStep = (stepIndex) => {
        currentStep = stepIndex;
        stepElements.forEach((el, index) => el.classList.toggle('hidden', index !== currentStep));
        const indicatorCount = document.querySelectorAll('.step-indicator').length;
        for (let i = 0; i < indicatorCount; i++) {
            const indicator = document.getElementById(`step-indicator-${i}`);
            const text = document.getElementById(`step-text-${i}`);
            const bar = document.getElementById(`progress-bar-${i}`);
            indicator.classList.remove('bg-petBlue', 'text-white', 'bg-gray-200');
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

    const populateSummary = () => {
        dataAgendamentoHidden.value = `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')} ${selectedTime}:00`;
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        document.getElementById('summary-datetime').textContent = selectedDate.toLocaleDateString('pt-BR', dateOptions) + ' às ' + selectedTime;
        document.getElementById('summary-owner-name').textContent = ownerName;
        
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
        let deliveryText = "Vou levar e buscar na loja.";
        if(deliveryType === 'delivery') {
            if(enderecoIdSelect && enderecoIdSelect.value !== 'novo_endereco') {
                deliveryText = `Buscar em: ${enderecoIdSelect.options[enderecoIdSelect.selectedIndex].text}`;
            } else {
                deliveryText = `Buscar em: ${novoEnderecoRuaInput.value.trim()} (Novo Endereço)`;
            }
        }
        document.getElementById('summary-delivery').textContent = deliveryText;
        document.getElementById('summary-notes').textContent = observacoesInput.value.trim() || 'Nenhuma.';
    };

    const validateStep = () => {
        if (currentStep === 0) {
            if (!selectedPetIdInput.value || (selectedPetIdInput.value === 'novo_pet' && !novoPetNomeInput.value.trim())) {
                showToast('Por favor, selecione um pet ou cadastre um novo.'); return false;
            }
            if (selectedPetIdInput.value === 'novo_pet') {
                if (!especieSelect.value) { showToast('A espécie do novo pet é obrigatória.'); return false; }
                if (especieSelect.value === 'Outro(a)' && !outraEspecieInput.value.trim()) { showToast('Por favor, especifique a espécie.'); return false; }
                if (especieSelect.value !== 'Outro(a)' && !racaSelect.value) { showToast('A raça do novo pet é obrigatória.'); return false; }
                if (racaSelect.value === 'Outro(a)' && !outraRacaInput.value.trim()) { showToast('Por favor, especifique a raça.'); return false; }
            }
        } else if (currentStep === 1) {
            if (document.querySelectorAll('input[name="servicos[]"]:checked').length === 0) {
                showToast('Selecione pelo menos um serviço.'); return false;
            }
        } else if (currentStep === 2) {
            const deliveryType = document.querySelector('input[name="tipo_entrega"]:checked').value;
            if (deliveryType === 'delivery') {
                if ((!enderecoIdSelect || !enderecoIdSelect.value) && !novoEnderecoRuaInput.value.trim()) {
                     showToast('Para entrega, selecione ou cadastre um endereço com rua preenchida.'); return false;
                }
                if(enderecoIdSelect && enderecoIdSelect.value === 'novo_endereco' && !novoEnderecoRuaInput.value.trim()) {
                     showToast('Para entrega, cadastre um novo endereço com rua preenchida.'); return false;
                }
            }
        } else if (currentStep === 3) {
            if (!selectedDate || !selectedTime) {
                showToast('Por favor, selecione uma data e um horário.'); return false;
            }
            populateSummary();
        }
        return true;
    };
    
    const restoreFormState = (data) => {
        // Pet
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
        // Serviços
        if (data.servicos && typeof data.servicos === 'string') {
            const services = data.servicos.split(', ');
            services.forEach(v => {
                const cb = document.querySelector(`input[name="servicos[]"][value="${v}"]`);
                if (cb) { cb.checked = true; cb.closest('.service-option').classList.add('selected'); }
            });
        }
        // Entrega
        if (data.tipo_entrega) {
            const radio = document.querySelector(`input[name="tipo_entrega"][value="${data.tipo_entrega}"]`);
            if (radio) {
                radio.checked = true;
                radio.closest('.service-option').classList.add('selected');
                if (data.tipo_entrega === 'delivery') enderecoSection.classList.remove('hidden');
            }
        }
        // Endereço
        if (data.endereco_id) {
            if(enderecoIdSelect) enderecoIdSelect.value = data.endereco_id;
            if (data.endereco_id === 'novo_endereco') {
                if(enderecoIdSelect) enderecoIdSelect.dispatchEvent(new Event('change'));
                novoEnderecoCepInput.value = data.novo_endereco_cep || '';
                novoEnderecoRuaInput.value = data.novo_endereco_rua || '';
                novoEnderecoNumeroInput.value = data.novo_endereco_numero || '';
                novoEnderecoBairroInput.value = data.novo_endereco_bairro || '';
                novoEnderecoCidadeInput.value = data.novo_endereco_cidade || '';
                novoEnderecoEstadoInput.value = data.novo_endereco_estado || '';
                novoEnderecoComplementoInput.value = data.novo_endereco_complemento || '';
            }
        }
        // Data e Hora
        if (data.data_agendamento) {
            const [datePart, timePart] = data.data_agendamento.split(' ');
            selectedDate = new Date(datePart.replace(/-/g, '/') + ' 00:00:00');
            selectedTime = timePart.substring(0, 5);
            dataAgendamentoHidden.value = data.data_agendamento;
        }
        if (data.observacoes) observacoesInput.value = data.observacoes;

        if (errorStep !== null) showStep(errorStep);
    };

    // --- EVENT LISTENERS ---
    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            const direction = button.dataset.direction;
            if (direction === 'next') {
                if (validateStep()) showStep(currentStep + 1);
            } else {
                showStep(currentStep - 1);
            }
        });
    });

    const setActivePet = (petId) => {
        selectedPetIdInput.value = petId;
        novoPetForm.classList.toggle('hidden', petId !== 'novo_pet');
        document.querySelectorAll('.pet-card').forEach(card => card.classList.toggle('selected', card.dataset.petId === petId));
    };
    document.querySelectorAll('.pet-card').forEach(card => card.addEventListener('click', () => setActivePet(card.dataset.petId)));
    const togglePetFormBtn = document.getElementById('toggle-pet-form-btn');
    if (togglePetFormBtn) togglePetFormBtn.addEventListener('click', () => setActivePet('novo_pet'));

    document.querySelectorAll('#services-list .service-option').forEach(label => {
        label.addEventListener('click', () => setTimeout(() => label.classList.toggle('selected', label.querySelector('input').checked), 0));
    });

    document.querySelectorAll('input[name="tipo_entrega"]').forEach(radio => radio.addEventListener('change', (e) => {
        enderecoSection.classList.toggle('hidden', e.target.value !== 'delivery');
        document.querySelectorAll('input[name="tipo_entrega"]').forEach(r => r.closest('.service-option').classList.remove('selected'));
        e.target.closest('.service-option').classList.add('selected');
    }));
    if (enderecoIdSelect) enderecoIdSelect.addEventListener('change', () => novoEnderecoForm.classList.toggle('hidden', enderecoIdSelect.value !== 'novo_endereco'));

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
    if(novoEnderecoCepInput) novoEnderecoCepInput.addEventListener('blur', (e) => buscarCep(e.target.value));

    // --- CALENDÁRIO ---
    const calendarDaysEl = document.getElementById('calendar-days');
    const currentMonthEl = document.getElementById('current-month');
    const timeSlotsContainer = document.getElementById('time-slots');
    let calendarDate = new Date();
    const renderTimeSlots = () => { if(!timeSlotsContainer) return; timeSlotsContainer.innerHTML = ''; availableTimes.forEach(time => { const btn = document.createElement('button'); btn.type = 'button'; btn.className = `time-slot py-2 px-3 border border-gray-300 rounded-md text-petGray hover:border-petBlue ${selectedTime === time ? 'selected' : ''}`; btn.dataset.time = time; btn.textContent = time; btn.addEventListener('click', () => { selectedTime = time; renderTimeSlots(); }); timeSlotsContainer.appendChild(btn); }); };
    const renderCalendar = () => { if(!calendarDaysEl) return; calendarDate.setDate(1); const firstDayIndex = calendarDate.getDay(); const lastDay = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 0).getDate(); const prevLastDay = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), 0).getDate(); const nextDays = 7 - (new Date(calendarDate.getFullYear(), calendarDate.getMonth(), lastDay).getDay()) - 1; const months = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"]; currentMonthEl.innerHTML = `${months[calendarDate.getMonth()]} ${calendarDate.getFullYear()}`; let days = ""; for (let x = firstDayIndex; x > 0; x--) { days += `<div class="py-2 text-center text-gray-300">${prevLastDay - x + 1}</div>`; } const today = new Date(); today.setHours(0, 0, 0, 0); for (let i = 1; i <= lastDay; i++) { const dayDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), i); let classes = 'date-cell py-2 text-center rounded-md '; if (dayDate < today) { classes += 'text-gray-300 cursor-not-allowed'; } else { classes += 'cursor-pointer hover:bg-petBlue hover:text-white transition-transform duration-200'; if (selectedDate && dayDate.getTime() === selectedDate.getTime()) classes += ' selected'; } days += `<div class="${classes}" data-date="${dayDate.toISOString()}">${i}</div>`; } for (let j = 1; j <= nextDays; j++) { days += `<div class="py-2 text-center text-gray-300">${j}</div>`; } calendarDaysEl.innerHTML = days; document.querySelectorAll('#calendar-days div[data-date]').forEach(dayEl => { dayEl.addEventListener('click', (e) => { const clickedDate = new Date(e.target.dataset.date); if(clickedDate >= today) { selectedDate = clickedDate; selectedTime = null; renderCalendar(); renderTimeSlots(); }}); }); };
    document.getElementById('prev-month')?.addEventListener('click', () => { calendarDate.setMonth(calendarDate.getMonth() - 1); renderCalendar(); });
    document.getElementById('next-month')?.addEventListener('click', () => { calendarDate.setMonth(calendarDate.getMonth() + 1); renderCalendar(); });
    
    // --- INICIALIZAÇÃO ---
    if (preservedData) {
        restoreFormState(preservedData);
    } else {
        showStep(0);
        if (selectedPetIdInput.value === 'novo_pet') {
            setActivePet('novo_pet');
        }
    }
    renderCalendar();
    renderTimeSlots();
});
</script>
</body>
</html>