<?php
// ======================================================================
// PetSync - Página de Agendamento v11.1 (Corrigida e Refatorada)
// ======================================================================

// 1. CONFIGURAÇÃO E SEGURANÇA
// ----------------------------------------------------------------------
include 'config.php';

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

// 3. PROCESSAMENTO DO FORMULÁRIO (QUANDO O PASSO FINAL É CONFIRMADO)
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_agendamento'])) {
    $pet_id = $_POST['pet_id'] ?? null;
    $servicos = isset($_POST['servicos']) ? implode(', ', $_POST['servicos']) : '';
    $data_agendamento_str = $_POST['data_agendamento'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $tipo_entrega = $_POST['tipo_entrega'] ?? null;
    
    // Se a entrega for 'loja', o endereco_id é NULL. Caso contrário, pega o valor do POST.
    $endereco_id = ($tipo_entrega === 'delivery') ? ($_POST['endereco_id'] ?? null) : null;

    // Cadastra novo Pet, se aplicável
    if ($pet_id === 'novo_pet' && !empty(trim($_POST['novo_pet_nome']))) {
        $novo_pet_nome = trim($_POST['novo_pet_nome']);
        $novo_pet_especie = trim($_POST['novo_pet_especie']);
        $novo_pet_raca = trim($_POST['novo_pet_raca']);
        $novo_pet_nascimento = !empty($_POST['novo_pet_nascimento']) ? $_POST['novo_pet_nascimento'] : null;
        $stmt_pet = $mysqli->prepare("INSERT INTO pets (dono_id, nome, especie, raca, data_nascimento) VALUES (?, ?, ?, ?, ?)");
        $stmt_pet->bind_param("issss", $id_usuario_logado, $novo_pet_nome, $novo_pet_especie, $novo_pet_raca, $novo_pet_nascimento);
        if ($stmt_pet->execute()) {
            $pet_id = $mysqli->insert_id;
        } else {
            $erro = "Ocorreu um erro ao cadastrar seu novo pet.";
        }
        $stmt_pet->close();
    }
    
    // Cadastra novo Endereço, se aplicável
    if ($endereco_id === 'novo_endereco' && !empty(trim($_POST['novo_endereco_rua']))) {
        $stmt_end = $mysqli->prepare("INSERT INTO enderecos (usuario_id, rua, numero, complemento, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_end->bind_param(
            "isssssss", 
            $id_usuario_logado, 
            $_POST['novo_endereco_rua'], 
            $_POST['novo_endereco_numero'], 
            $_POST['novo_endereco_complemento'], 
            $_POST['novo_endereco_bairro'], 
            $_POST['novo_endereco_cidade'], 
            $_POST['novo_endereco_estado'], 
            $_POST['novo_endereco_cep']
        );
        if($stmt_end->execute()) {
            $endereco_id = $mysqli->insert_id;
        } else {
            $erro = "Ocorreu um erro ao cadastrar seu novo endereço.";
        }
        $stmt_end->close();
    }

    // Validação final dos dados
    if (!$pet_id || $pet_id === 'novo_pet' || empty($servicos) || !$data_agendamento_str || !$tipo_entrega) {
        if(empty($erro)) $erro = "Todos os campos obrigatórios devem ser preenchidos.";
    } elseif ($tipo_entrega === 'delivery' && (!$endereco_id || $endereco_id === 'novo_endereco')) {
        if(empty($erro)) $erro = "Por favor, selecione ou cadastre um endereço para a entrega.";
    }

    // Salva o agendamento no banco se não houver erros
    if (empty($erro)) {
        // CORREÇÃO CRÍTICA: O tipo do parâmetro para endereco_id deve ser `i` (integer), mas pode ser nulo.
        // A string de bind_param estava incorreta. Corrigido para "iissisi".
        $stmt = $mysqli->prepare("INSERT INTO agendamentos (usuario_id, pet_id, servico, data_agendamento, observacoes, tipo_entrega, endereco_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissisi", $id_usuario_logado, $pet_id, $servicos, $data_agendamento_str, $observacoes, $tipo_entrega, $endereco_id);
        
        if ($stmt->execute()) {
            $_SESSION['ok_msg'] = ($tipo_entrega === 'delivery') 
                ? "Agendamento solicitado! Buscaremos seu pet no endereço selecionado." 
                : "Agendamento solicitado! Assim que o serviço for concluído, avisaremos para você buscar seu pet.";
        } else {
            // Para depuração: $erro = "Erro no agendamento: " . $stmt->error;
            $_SESSION['erro_msg'] = "Ocorreu um erro ao salvar seu agendamento. Tente novamente.";
        }
        $stmt->close();
    } else {
        $_SESSION['erro_msg'] = $erro;
    }
    
    header("Location: agendamento.php");
    exit;
}

// 4. LÓGICA PARA EXIBIÇÃO DA PÁGINA
// ----------------------------------------------------------------------
if (isset($_SESSION['ok_msg'])) { $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if (isset($_SESSION['erro_msg'])) { $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }

// Busca dados para preencher o formulário
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
        .validation-error { border-color: #ef4444; }
        .validation-message { color: #ef4444; font-size: 0.875rem; }
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
                            <div class="step-content bg-white p-8 rounded-lg shadow-md" id="step0"><h2 class="text-2xl font-bold text-petGray mb-6">Passo 1: Informações do Pet</h2><div class="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg mb-6"><p><span class="font-semibold">Cliente:</span> <?= htmlspecialchars($cliente_selecionado['nome'] ?? '') ?></p></div><label class="block text-petGray font-medium mb-2">Selecione o Pet <span class="text-red-500">*</span></label><div id="pet_validation_error" class="text-red-500 text-sm mb-2 hidden"></div><?php if (!empty($pets_cliente)): ?><div id="pet-card-list" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-4"><?php foreach($pets_cliente as $pet): ?><div class="pet-card border-2 border-gray-200 rounded-lg p-4 text-center cursor-pointer transition-all" data-pet-id="<?= $pet['id'] ?>" data-pet-info="<?= htmlspecialchars($pet['nome']) ?> (<?= htmlspecialchars($pet['raca'] ?? $pet['especie']) ?>)"><div class="text-3xl mb-2">🐾</div><p class="font-semibold text-petGray"><?= htmlspecialchars($pet['nome']) ?></p><p class="text-sm text-gray-500"><?= htmlspecialchars($pet['raca'] ?? $pet['especie']) ?></p></div><?php endforeach; ?></div><button type="button" id="toggle-pet-form-btn" class="text-sm text-petBlue hover:underline font-semibold">... ou cadastrar outro pet</button><?php endif; ?><div id="novo-pet-form" class="<?= empty($pets_cliente) ? '' : 'hidden' ?> grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 border-t pt-6 mt-4"><h3 class="md:col-span-2 text-lg font-semibold text-petOrange"><?= empty($pets_cliente) ? 'Cadastre seu primeiro pet para começar' : 'Dados do Novo Pet' ?></h3><div><label for="novo_pet_nome" class="block text-sm font-medium text-petGray">Nome do Pet <span class="text-red-500">*</span></label><input type="text" name="novo_pet_nome" id="novo_pet_nome" class="w-full mt-1 p-2 border rounded-md form-input"></div><div><label for="novo_pet_especie" class="block text-sm font-medium text-petGray">Espécie</label><input type="text" name="novo_pet_especie" id="novo_pet_especie" class="w-full mt-1 p-2 border rounded-md form-input" placeholder="Ex: Cão, Gato"></div><div><label for="novo_pet_raca" class="block text-sm font-medium text-petGray">Raça</label><input type="text" name="novo_pet_raca" id="novo_pet_raca" class="w-full mt-1 p-2 border rounded-md form-input"></div><div><label for="novo_pet_nascimento" class="block text-sm font-medium text-petGray">Data de Nascimento</label><input type="date" name="novo_pet_nascimento" id="novo_pet_nascimento" class="w-full mt-1 p-2 border rounded-md form-input"></div></div><div class="mt-8 flex justify-end"><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div></div>
                            
                            <div class="step-content hidden" id="step1"><div class="bg-white p-8 rounded-lg shadow-md"><h2 class="text-2xl font-bold text-petGray mb-6">Passo 2: Selecione os Serviços</h2><div id="service_validation_error" class="text-red-500 text-sm mb-2 hidden"></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="services-list"><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Banho e Tosa" data-label="Banho e Tosa" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Banho e Tosa</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Consulta Veterinária" data-label="Consulta Veterinária" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Consulta Veterinária</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Vacinação" data-label="Vacinação" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Vacinação</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="checkbox" name="servicos[]" value="Hospedagem" data-label="Hospedagem" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray">Hospedagem</span></label></div><div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div></div></div>
                            
                            <div class="step-content hidden" id="step2"><div class="bg-white p-8 rounded-lg shadow-md"><h2 class="text-2xl font-bold text-petGray mb-6">Passo 3: Como será a entrega e retirada?</h2><div class="space-y-4"><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="radio" name="tipo_entrega" value="loja" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue" checked><span class="ml-3 text-petGray text-lg">Vou levar e buscar na loja</span></label><label class="flex items-center border rounded-lg p-4 cursor-pointer hover:border-petBlue transition-all service-option"><input type="radio" name="tipo_entrega" value="delivery" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue"><span class="ml-3 text-petGray text-lg">Quero que busquem e entreguem em casa</span></label></div><div id="endereco-delivery-section" class="hidden mt-6 space-y-4"><label for="endereco_id" class="block text-petGray font-medium">Selecione o endereço:</label><div id="address_validation_error" class="text-red-500 text-sm mb-2 hidden"></div><?php if(!empty($enderecos_cliente)): ?><select name="endereco_id" id="endereco_id" class="w-full p-2 border rounded-md bg-white form-input"><option value="">-- Seus endereços cadastrados --</option><?php foreach($enderecos_cliente as $endereco): ?><option value="<?= $endereco['id'] ?>" data-endereco-info="<?= htmlspecialchars($endereco['rua']) . ', ' . htmlspecialchars($endereco['numero']) ?>"><?= htmlspecialchars($endereco['rua']) . ', ' . htmlspecialchars($endereco['numero']) ?></option><?php endforeach; ?><option value="novo_endereco">** Cadastrar Novo Endereço **</option></select><?php else: ?><input type="hidden" name="endereco_id" id="endereco_id" value="novo_endereco"><?php endif; ?><div id="novo-endereco-form" class="<?= empty($enderecos_cliente) ? '' : 'hidden' ?> grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-6 mt-4"><h3 class="md:col-span-2 text-lg font-semibold text-petOrange"><?= empty($enderecos_cliente) ? 'Cadastre seu primeiro endereço' : 'Dados do Novo Endereço' ?></h3><div class="md:col-span-2"><input type="text" name="novo_endereco_rua" placeholder="Rua / Avenida" class="w-full p-2 border rounded-md form-input"></div><div><input type="text" name="novo_endereco_numero" placeholder="Número" class="w-full p-2 border rounded-md form-input"></div><div><input type="text" name="novo_endereco_bairro" placeholder="Bairro" class="w-full p-2 border rounded-md form-input"></div><div class="md:col-span-2"><input type="text" name="novo_endereco_complemento" placeholder="Complemento (Opcional)" class="w-full p-2 border rounded-md form-input"></div><div><input type="text" name="novo_endereco_cidade" placeholder="Cidade" class="w-full p-2 border rounded-md form-input"></div><div><input type="text" name="novo_endereco_estado" placeholder="Estado (UF)" maxlength="2" class="w-full p-2 border rounded-md form-input"></div><div><input type="text" name="novo_endereco_cep" placeholder="CEP" class="w-full p-2 border rounded-md form-input"></div></div></div><div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div></div></div>
                            
                            <div class="step-content hidden" id="step3"><div class="bg-white p-8 rounded-lg shadow-md"><h2 class="text-2xl font-bold text-petGray mb-6">Passo 4: Escolha a Data e Horário</h2><div id="datetime_validation_error" class="text-red-500 text-sm mb-2 hidden"></div><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><div><h3 class="text-lg font-semibold text-petGray mb-4">Selecione uma data</h3><div class="calendar bg-white border border-gray-300 rounded-lg p-4"><div class="flex justify-between items-center mb-4"><button type="button" id="prev-month" class="p-2 rounded-full hover:bg-gray-100"><svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button><h4 id="current-month" class="text-petGray font-semibold text-lg"></h4><button type="button" id="next-month" class="p-2 rounded-full hover:bg-gray-100"><svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button></div><div class="grid grid-cols-7 gap-1 text-center text-gray-500 text-sm font-medium"><div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div></div><div id="calendar-days" class="grid grid-cols-7 gap-1 mt-2"></div></div></div><div><h3 class="text-lg font-semibold text-petGray mb-4">Selecione um horário</h3><div id="time-slots" class="grid grid-cols-2 sm:grid-cols-3 gap-3"></div><div class="mt-6"><h3 class="text-lg font-semibold text-petGray mb-4">Observações</h3><textarea name="observacoes" id="observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md form-input" placeholder="Alguma informação adicional? Ex: Alergias, comportamento..."></textarea></div></div></div><div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="button" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 nav-btn" data-direction="next">Próximo</button></div></div></div>
                            
                            <div class="step-content hidden" id="step4"><div class="bg-white p-8 rounded-lg shadow-md"><h2 class="text-2xl font-bold text-petGray mb-6">Passo 5: Confirme seu Agendamento</h2><div class="bg-petLightGray p-6 rounded-lg space-y-4"><div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b pb-4"><div><p class="font-medium text-petGray">Cliente:</p><p id="summary-owner-name" class="font-semibold text-lg"></p></div><div><p class="font-medium text-petGray">Pet:</p><p id="summary-pet-name" class="font-semibold text-lg"></p></div></div><div class="pt-2 border-b pb-4"><p class="font-medium text-petGray">Serviços:</p><p id="summary-services" class="font-semibold text-lg"></p></div><div class="pt-2 border-b pb-4"><p class="font-medium text-petGray">Entrega/Retirada:</p><p id="summary-delivery" class="font-semibold text-lg"></p></div><div class="pt-2 border-b pb-4"><p class="font-medium text-petGray">Data e Hora:</p><p id="summary-datetime" class="font-semibold text-lg"></p></div><div class="pt-2"><p class="font-medium text-petGray">Observações:</p><p id="summary-notes" class="text-gray-600 italic"></p></div></div><div class="mt-8 flex justify-between"><button type="button" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300 nav-btn" data-direction="prev">Voltar</button><button type="submit" name="confirmar_agendamento" class="bg-petOrange text-white px-6 py-3 rounded-md font-medium hover:bg-orange-700">Confirmar Agendamento</button></div></div></div>
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
    // Esconde a notificação depois de 5 segundos
    const toastContainer = document.getElementById('toast-notification-container');
    if (toastContainer && toastContainer.children.length > 0) {
        setTimeout(() => { toastContainer.innerHTML = ''; }, 5000);
    }
    
    // Se o formulário não existe na página (ex: na tela de sucesso), interrompe o script.
    if (!document.getElementById('booking-form-container')) return;

    // --- VARIÁVEIS GLOBAIS DO FORMULÁRIO ---
    const stepElements = document.querySelectorAll('.step-content');
    const navButtons = document.querySelectorAll('.nav-btn');
    let currentStep = 0; // Começa sempre no passo 0 (índice do array)

    // --- VARIÁVEIS DO PASSO 1: PET ---
    const petCards = document.querySelectorAll('.pet-card');
    const togglePetFormBtn = document.getElementById('toggle-pet-form-btn');
    const novoPetForm = document.getElementById('novo-pet-form');
    const selectedPetIdInput = document.getElementById('selected_pet_id');
    const novoPetNomeInput = document.getElementById('novo_pet_nome');

    // --- VARIÁVEIS DO PASSO 3: ENDEREÇO ---
    const tipoEntregaRadios = document.querySelectorAll('input[name="tipo_entrega"]');
    const enderecoSection = document.getElementById('endereco-delivery-section');
    const enderecoIdSelect = document.getElementById('endereco_id');
    const novoEnderecoForm = document.getElementById('novo-endereco-form');
    const novoEnderecoRuaInput = document.querySelector('input[name="novo_endereco_rua"]');
    const hasExistingAddresses = <?= !empty($enderecos_cliente) ? 'true' : 'false' ?>;

    // --- VARIÁVEIS DO PASSO 4: CALENDÁRIO E HORÁRIO ---
    let selectedDate = null, selectedTime = null;
    let calendarDate = new Date();
    const calendarDaysEl = document.getElementById('calendar-days');
    const currentMonthEl = document.getElementById('current-month');
    const timeSlotsContainer = document.getElementById('time-slots');
    const availableTimes = <?= $horarios_json ?>;

    // --- VARIÁVEIS DO PASSO 5: RESUMO ---
    // CORREÇÃO: Passa o nome do cliente para o JS de forma segura.
    const ownerName = <?= json_encode($cliente_selecionado['nome'] ?? '') ?>;


    // ======================================================================
    // FUNÇÕES PRINCIPAIS
    // ======================================================================

    /**
     * Mostra o passo atual e atualiza os indicadores visuais.
     * @param {number} stepIndex - O índice do passo a ser exibido.
     */
    const showStep = (stepIndex) => {
        currentStep = stepIndex;
        stepElements.forEach((el, index) => el.classList.toggle('hidden', index !== currentStep));

        // Atualiza os indicadores de passo (bolinhas e barras)
        const indicatorCount = document.querySelectorAll('.step-indicator').length;
        for (let i = 0; i < indicatorCount; i++) {
            const indicator = document.getElementById(`step-indicator-${i}`);
            const text = document.getElementById(`step-text-${i}`);
            const bar = document.getElementById(`progress-bar-${i}`);

            indicator.classList.remove('bg-petBlue', 'text-white', 'bg-gray-200');
            text.classList.remove('text-petBlue', 'font-medium');

            if (i < currentStep) { // Passos concluídos
                indicator.classList.add('bg-petBlue', 'text-white');
                indicator.innerHTML = '✔';
            } else if (i === currentStep) { // Passo atual
                indicator.classList.add('bg-petBlue', 'text-white');
                indicator.textContent = i + 1;
                text.classList.add('text-petBlue', 'font-medium');
            } else { // Passos futuros
                indicator.classList.add('bg-gray-200', 'text-petGray');
                indicator.textContent = i + 1;
            }

            if (bar) {
                bar.classList.remove('bg-petBlue', 'bg-gray-200');
                bar.classList.add(i < currentStep ? 'bg-petBlue' : 'bg-gray-200');
            }
        }
    };

    /**
     * Valida o passo atual antes de permitir avançar.
     * @returns {boolean} - True se o passo for válido, false caso contrário.
     */
    const validateStep = () => {
        if (currentStep === 0) { // Validação do Pet
            if (!selectedPetIdInput.value || (selectedPetIdInput.value === 'novo_pet' && !novoPetNomeInput.value.trim())) {
                alert('Por favor, selecione um pet existente ou cadastre um novo preenchendo o nome.');
                return false;
            }
        } else if (currentStep === 1) { // Validação dos Serviços
            if (document.querySelectorAll('input[name="servicos[]"]:checked').length === 0) {
                alert('Selecione pelo menos um serviço.');
                return false;
            }
        } else if (currentStep === 2) { // Validação da Entrega
            const deliveryType = document.querySelector('input[name="tipo_entrega"]:checked').value;
            if (deliveryType === 'delivery') {
                if (!enderecoIdSelect.value || (enderecoIdSelect.value === 'novo_endereco' && !novoEnderecoRuaInput.value.trim())) {
                    alert('Por favor, selecione um endereço de entrega ou cadastre um novo preenchendo a rua.');
                    return false;
                }
            }
        } else if (currentStep === 3) { // Validação da Data e Horário
            if (!selectedDate || !selectedTime) {
                alert('Por favor, selecione uma data e um horário disponíveis.');
                return false;
            }
            // Se a data for válida, preenche o resumo para o próximo passo.
            populateSummary();
        }
        return true;
    };
    
    /**
     * Coleta todas as informações dos passos e preenche o resumo final.
     */
    const populateSummary = () => {
        // Data e Hora
        document.getElementById('data_agendamento_hidden').value = `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')} ${selectedTime}:00`;
        const dateOptions = { day: '2-digit', month: 'long', year: 'numeric' };
        document.getElementById('summary-datetime').textContent = selectedDate.toLocaleDateString('pt-BR', dateOptions) + ' às ' + selectedTime;

        // Cliente
        document.getElementById('summary-owner-name').textContent = ownerName;

        // Pet
        const isNewPet = selectedPetIdInput.value === 'novo_pet';
        document.getElementById('summary-pet-name').textContent = isNewPet 
            ? `${novoPetNomeInput.value.trim()} (Novo Cadastro)` 
            : document.querySelector('.pet-card.selected').dataset.petInfo;

        // Serviços
        document.getElementById('summary-services').textContent = Array.from(document.querySelectorAll('input[name="servicos[]"]:checked')).map(cb => cb.dataset.label).join(', ');

        // Entrega
        const deliveryType = document.querySelector('input[name="tipo_entrega"]:checked').value;
        let deliveryText = "Cliente irá levar e buscar na loja.";
        if(deliveryType === 'delivery') {
            const isNewAddress = enderecoIdSelect.value === 'novo_endereco';
            if (isNewAddress) {
                deliveryText = `Buscar em: ${novoEnderecoRuaInput.value.trim()} (Novo Endereço)`;
            } else {
                deliveryText = `Buscar em: ${enderecoIdSelect.options[enderecoIdSelect.selectedIndex].text}`;
            }
        }
        document.getElementById('summary-delivery').textContent = deliveryText;

        // Observações
        document.getElementById('summary-notes').textContent = document.getElementById('observacoes').value.trim() || 'Nenhuma.';
    };


    // ======================================================================
    // EVENT LISTENERS E INICIALIZAÇÃO
    // ======================================================================
    
    // --- Navegação (Botões Próximo/Voltar) ---
    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            const direction = button.dataset.direction;
            if (direction === 'next') {
                if (validateStep()) {
                    showStep(currentStep + 1);
                }
            } else { // direction === 'prev'
                showStep(currentStep - 1);
            }
        });
    });

    // --- Lógica do Passo 1: Pet ---
    const setActivePet = (petId) => {
        selectedPetIdInput.value = petId;
        novoPetNomeInput.required = (petId === 'novo_pet');
        
        petCards.forEach(card => card.classList.toggle('selected', card.dataset.petId === petId));
        novoPetForm.classList.toggle('hidden', petId !== 'novo_pet');
    };
    petCards.forEach(card => card.addEventListener('click', () => setActivePet(card.dataset.petId)));
    if (togglePetFormBtn) {
        togglePetFormBtn.addEventListener('click', () => setActivePet('novo_pet'));
    }
    // Garante que o formulário de novo pet apareça se for a única opção.
    if (selectedPetIdInput.value === 'novo_pet') {
        setActivePet('novo_pet');
    }

    // --- Lógica do Passo 2: Serviços ---
    document.querySelectorAll('#services-list .service-option').forEach(label => {
        label.addEventListener('click', (e) => {
            // Previne que o clique duplo no checkbox desfaça a seleção da borda
            setTimeout(() => {
                label.classList.toggle('selected', label.querySelector('input').checked);
            }, 0);
        });
    });

    // --- Lógica do Passo 3: Entrega e Endereço ---
    tipoEntregaRadios.forEach(radio => radio.addEventListener('change', (e) => {
        enderecoSection.classList.toggle('hidden', e.target.value !== 'delivery');
        document.querySelectorAll('input[name="tipo_entrega"]').forEach(r => {
             r.closest('.service-option').classList.remove('selected');
        });
        e.target.closest('.service-option').classList.add('selected');
    }));
    const handleEnderecoChoice = () => {
        const isNovo = enderecoIdSelect.value === 'novo_endereco';
        novoEnderecoForm.classList.toggle('hidden', !isNovo);
        novoEnderecoRuaInput.required = isNovo;
    };
    if (enderecoIdSelect) {
        enderecoIdSelect.addEventListener('change', handleEnderecoChoice);
        // Garante que o form de novo endereço apareça se for a única opção.
        if (enderecoIdSelect.value === 'novo_endereco') {
             handleEnderecoChoice();
        }
    }
    
    // --- Lógica do Passo 4: Calendário e Horários ---
    const renderTimeSlots = () => { if(!timeSlotsContainer) return; timeSlotsContainer.innerHTML = ''; availableTimes.forEach(time => { const btn = document.createElement('button'); btn.type = 'button'; btn.className = `time-slot py-2 px-3 border border-gray-300 rounded-md text-petGray hover:border-petBlue ${selectedTime === time ? 'selected' : ''}`; btn.dataset.time = time; btn.textContent = time; btn.addEventListener('click', () => { selectedTime = time; renderTimeSlots(); }); timeSlotsContainer.appendChild(btn); }); };
    const renderCalendar = () => { if(!calendarDaysEl) return; calendarDate.setDate(1); const firstDayIndex = calendarDate.getDay(); const lastDay = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 0).getDate(); const prevLastDay = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), 0).getDate(); const nextDays = 7 - (new Date(calendarDate.getFullYear(), calendarDate.getMonth(), lastDay).getDay()) - 1; const months = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"]; currentMonthEl.innerHTML = `${months[calendarDate.getMonth()]} ${calendarDate.getFullYear()}`; let days = ""; for (let x = firstDayIndex; x > 0; x--) { days += `<div class="py-2 text-center text-gray-300">${prevLastDay - x + 1}</div>`; } const today = new Date(); today.setHours(0, 0, 0, 0); for (let i = 1; i <= lastDay; i++) { const dayDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), i); let classes = 'date-cell py-2 text-center rounded-md '; if (dayDate < today) { classes += 'text-gray-300 cursor-not-allowed'; } else { classes += 'cursor-pointer hover:bg-petBlue hover:text-white transition-transform duration-200'; if (selectedDate && dayDate.getTime() === selectedDate.getTime()) classes += ' selected'; } days += `<div class="${classes}" data-date="${dayDate.toISOString()}">${i}</div>`; } for (let j = 1; j <= nextDays; j++) { days += `<div class="py-2 text-center text-gray-300">${j}</div>`; } calendarDaysEl.innerHTML = days; document.querySelectorAll('#calendar-days div[data-date]').forEach(dayEl => { dayEl.addEventListener('click', (e) => { const clickedDate = new Date(e.target.dataset.date); if(clickedDate >= today) { selectedDate = clickedDate; selectedTime = null; renderCalendar(); renderTimeSlots(); }}); }); };
    
    document.getElementById('prev-month')?.addEventListener('click', () => { calendarDate.setMonth(calendarDate.getMonth() - 1); renderCalendar(); });
    document.getElementById('next-month')?.addEventListener('click', () => { calendarDate.setMonth(calendarDate.getMonth() + 1); renderCalendar(); });
    
    // --- INICIALIZAÇÃO DO FORMULÁRIO ---
    showStep(currentStep); // Exibe o primeiro passo
    renderCalendar(); // Renderiza o calendário
    renderTimeSlots(); // Renderiza os horários (inicialmente vazios até selecionar data)
});
</script>
</body>
</html>