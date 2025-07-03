<?php
// ======================================================================
// PetSync - Página de Agendamento v17.0 (Versão Completa e Funcional)
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

// 2. DEFINIÇÕES E BUSCA DE DADOS INICIAIS
// ----------------------------------------------------------------------
$page_title = 'Novo Agendamento - PetSync';
$usuario_logado = $_SESSION['usuario'];
$id_usuario_logado = $usuario_logado['id'];

// Garante que os dados do PHP sejam sempre arrays para evitar erros no JS
$query_servicos = "SELECT nome, duracao_minutos FROM servicos WHERE ativo = 1 ORDER BY nome ASC";
$result_servicos = $mysqli->query($query_servicos);
$lista_servicos = $result_servicos ? $result_servicos->fetch_all(MYSQLI_ASSOC) : [];

$result_configs = $mysqli->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('permitir_delivery', 'permitir_cliente_leva_e_busca')");
$opcoes_agendamento = [];
while ($row = $result_configs->fetch_assoc()) {
    $opcoes_agendamento[$row['chave']] = (bool)$row['valor'];
}
$permitir_cliente_leva = $opcoes_agendamento['permitir_cliente_leva_e_busca'] ?? false;
$permitir_delivery = $opcoes_agendamento['permitir_delivery'] ?? false;

$horarios_trabalho_result = $mysqli->query("SELECT dia_semana FROM horarios_atendimento WHERE ativo = 1");
$dias_trabalho = $horarios_trabalho_result ? array_column($horarios_trabalho_result->fetch_all(MYSQLI_ASSOC), 'dia_semana') : [];

$stmt_pets = $mysqli->prepare("SELECT id, nome, raca, especie FROM pets WHERE dono_id = ? ORDER BY nome ASC");
$stmt_pets->bind_param("i", $id_usuario_logado);
$stmt_pets->execute();
$pets_cliente = $stmt_pets->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
$stmt_pets->close();

$enderecos_cliente = [];
if ($permitir_delivery) {
    $stmt_enderecos = $mysqli->prepare("SELECT id, rua, numero, complemento, bairro, cidade, estado, cep FROM enderecos WHERE usuario_id = ? ORDER BY id ASC");
    $stmt_enderecos->bind_param("i", $id_usuario_logado);
    $stmt_enderecos->execute();
    $enderecos_cliente = $stmt_enderecos->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmt_enderecos->close();
}
$cliente_selecionado = $mysqli->query("SELECT nome FROM usuarios WHERE id = $id_usuario_logado")->fetch_assoc();


// 3. PROCESSAMENTO DO FORMULÁRIO (LÓGICA PHP COMPLETA)
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_agendamento'])) {
    $pet_id = $_POST['pet_id'] ?? null;
    $servicos_array = $_POST['servicos'] ?? [];
    $data_agendamento_str = $_POST['data_agendamento'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $tipo_entrega = $_POST['tipo_entrega'] ?? null;
    $endereco_id = ($tipo_entrega === 'delivery') ? ($_POST['endereco_id'] ?? null) : null;
    $error_step = 0;
    $erro = '';

    if (empty($erro) && $pet_id === 'novo_pet') {
        $pet_nome = trim($_POST['novo_pet_nome']);
        $pet_nascimento = !empty($_POST['novo_pet_nascimento']) ? $_POST['novo_pet_nascimento'] : null;
        $pet_especie = $_POST['novo_pet_especie'] ?? '';
        if ($pet_especie === 'Outro(a)') { $pet_especie = trim($_POST['outra_especie'] ?? ''); }
        $pet_raca = $_POST['novo_pet_raca'] ?? '';
        if ($pet_raca === 'Outro(a)') { $pet_raca = trim($_POST['outra_raca'] ?? ''); }
        if(empty($pet_raca) && isset($_POST['novo_pet_especie']) && $_POST['novo_pet_especie'] === 'Outro(a)'){ $pet_raca = 'N/A'; }
        if (!empty($pet_nome) && !empty($pet_especie) && !empty($pet_raca)) {
            $stmt_pet = $mysqli->prepare("INSERT INTO pets (dono_id, nome, especie, raca, data_nascimento) VALUES (?, ?, ?, ?, ?)");
            $stmt_pet->bind_param("issss", $id_usuario_logado, $pet_nome, $pet_especie, $pet_raca, $pet_nascimento);
            if ($stmt_pet->execute()) { $pet_id = $mysqli->insert_id; }
            else { $erro = "Ocorreu um erro ao cadastrar seu novo pet."; }
            $stmt_pet->close();
        } else {
            $erro = "Nome, espécie e raça são obrigatórios para o novo pet.";
            $error_step = 0;
        }
    }

    if (empty($erro) && $tipo_entrega === 'delivery' && $endereco_id === 'novo_endereco') {
        if (!empty(trim($_POST['novo_endereco_rua']))) {
            $stmt_end = $mysqli->prepare("INSERT INTO enderecos (usuario_id, rua, numero, complemento, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_end->bind_param("isssssss", $id_usuario_logado, $_POST['novo_endereco_rua'], $_POST['novo_endereco_numero'], $_POST['novo_endereco_complemento'], $_POST['novo_endereco_bairro'], $_POST['novo_endereco_cidade'], $_POST['novo_endereco_estado'], $_POST['novo_endereco_cep']);
            if($stmt_end->execute()) { $endereco_id = $mysqli->insert_id; }
            else { $erro = "Ocorreu um erro ao cadastrar seu novo endereço."; }
            $stmt_end->close();
        } else {
             $erro = "A rua do novo endereço é obrigatória para delivery.";
             $error_step = 2;
        }
    }

    $servico_str = implode(', ', $servicos_array);
    if (empty($erro)) {
        if (!$pet_id || $pet_id === 'novo_pet') { $erro = "Pet não foi selecionado ou cadastrado corretamente."; $error_step = 0; }
        elseif (empty($servico_str)) { $erro = "Nenhum serviço foi selecionado."; $error_step = 1; }
        elseif (!$tipo_entrega) { $erro = "O tipo de entrega não foi selecionado."; $error_step = 2; }
        elseif ($tipo_entrega === 'delivery' && (!$endereco_id || $endereco_id === 'novo_endereco')) { $erro = "Selecione ou cadastre um endereço para a entrega."; $error_step = 2; }
        elseif (!$data_agendamento_str) { $erro = "A data e o horário não foram selecionados."; $error_step = 3; }
    }

    if (empty($erro)) {
        $stmt = $mysqli->prepare("INSERT INTO agendamentos (usuario_id, pet_id, servico, data_agendamento, observacoes, tipo_entrega, endereco_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssi", $id_usuario_logado, $pet_id, $servico_str, $data_agendamento_str, $observacoes, $tipo_entrega, $endereco_id);

        if ($stmt->execute()) {
            $_SESSION['ok_msg'] = "Agendamento solicitado! Você pode acompanhar o status na página 'Meus Agendamentos'.";
            header("Location: meus_agendamentos.php");
            exit;
        } else {
            $_SESSION['erro_msg'] = "Ocorreu um erro ao salvar seu agendamento. Tente novamente.";
            $_SESSION['form_data'] = $_POST;
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

$erro_msg_php = $_SESSION['erro_msg'] ?? '';
$form_data_php = $_SESSION['form_data'] ?? null;
$error_step_php = $_SESSION['error_step'] ?? null;
unset($_SESSION['erro_msg'], $_SESSION['form_data'], $_SESSION['error_step']);

require 'header.php';
?>

<div id="toast-notification-container" class="fixed top-20 right-5 z-[100] space-y-2"></div>

<main class="py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto" x-data="bookingForm(bookingPageData)">

            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold text-petGray mb-4">Agendar <span class="text-petOrange">Visita</span></h1>
            </div>

            <div class="flex items-center justify-between mb-12 space-x-2 text-xs sm:text-sm">
                <template x-for="(step, index) in steps" :key="index">
                    <div class="flex-1">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold"
                                 :class="{'!bg-petBlue !text-white': currentStep >= index, 'bg-gray-200 text-petGray': currentStep < index}">
                                <span x-show="currentStep <= index" x-text="index + 1"></span>
                                <span x-show="currentStep > index">✔</span>
                            </div>
                            <span class="mt-2 text-petGray" :class="{'!text-petBlue !font-medium': currentStep == index}" x-text="step"></span>
                        </div>
                        <div x-show="index < steps.length - 1" class="relative flex-1 h-1 mt-[-24px] mx-auto w-[calc(100%-2.5rem)]">
                            <div class="absolute inset-0" :class="{'bg-petBlue': currentStep > index, 'bg-gray-200': currentStep <= index}"></div>
                        </div>
                    </div>
                </template>
            </div>

            <form id="booking-form" action="agendamento.php" method="POST" @submit.prevent="submitForm">
                <input type="hidden" name="data_agendamento" :value="selectedDateTime">
                <input type="hidden" name="pet_id" x-model="selectedPetId">
                <input type="hidden" name="confirmar_agendamento" value="1">
                <input type="hidden" name="tipo_entrega" x-model="tipoEntrega">
                <textarea name="observacoes" x-model="observacoes" class="hidden"></textarea>

                <div x-show="currentStep === 0">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <h2 class="text-2xl font-bold text-petGray mb-6">Passo 1: Informações do Pet</h2>
                        <div class="p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg mb-6">
                            <p><span class="font-semibold">Cliente:</span> <span x-text="clienteNome"></span></p>
                        </div>

                        <label class="block text-petGray font-medium mb-2">Selecione o Pet <span class="text-red-500">*</span></label>
                        <div x-show="pets.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                            <template x-for="pet in pets" :key="pet.id">
                                <div @click="selectPet(pet.id)" class="pet-card border-2 rounded-lg p-4 text-center cursor-pointer transition-all"
                                     :class="selectedPetId == pet.id ? 'border-petBlue bg-blue-50 shadow-md' : 'border-gray-200 hover:border-petBlue'">
                                    <div class="text-3xl mb-2">🐾</div>
                                    <p class="font-semibold text-petGray" x-text="pet.nome"></p>
                                    <p class="text-sm text-gray-500" x-text="pet.raca || pet.especie"></p>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="selectPet('novo_pet')" class="text-sm text-petBlue hover:underline font-semibold">
                            <span x-text="pets.length > 0 ? '... ou cadastrar outro pet' : 'Cadastre seu primeiro pet para começar'"></span>
                        </button>

                        <div x-show="selectedPetId === 'novo_pet'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 border-t pt-6 mt-4">
                             <h3 class="md:col-span-2 text-lg font-semibold text-petOrange">Dados do Novo Pet</h3>
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
                                <select id="novo_pet_especie_select" name="novo_pet_especie" x-model="novoPet.especie" class="w-full mt-1 p-2 border rounded-md form-input">
                                    <option value="">-- Selecione --</option>
                                    <template x-for="especie in Object.keys(racasPorEspecie)" :key="especie">
                                        <option :value="especie" x-text="especie"></option>
                                    </template>
                                </select>
                             </div>
                              <div x-show="novoPet.especie === 'Outro(a)'">
                                <label class="block text-sm font-medium text-petGray">Qual espécie?</label>
                                <input type="text" name="outra_especie" class="w-full mt-1 p-2 border rounded-md form-input">
                             </div>
                             <div>
                                <label for="novo_pet_raca_select" class="block text-sm font-medium text-petGray">Raça<span class="text-red-500">*</span></label>
                                <select id="novo_pet_raca_select" name="novo_pet_raca" x-model="novoPet.raca" class="w-full mt-1 p-2 border rounded-md form-input" :disabled="!novoPet.especie || racasPorEspecie[novoPet.especie].length === 0">
                                    <option value="">-- Selecione --</option>
                                    <template x-if="novoPet.especie && racasPorEspecie[novoPet.especie]">
                                        <template x-for="raca in racasPorEspecie[novoPet.especie]" :key="raca">
                                            <option :value="raca" x-text="raca"></option>
                                        </template>
                                    </template>
                                </select>
                             </div>
                             <div x-show="novoPet.raca === 'Outro(a)'">
                                <label class="block text-sm font-medium text-petGray">Qual raça?</label>
                                <input type="text" name="outra_raca" class="w-full mt-1 p-2 border rounded-md form-input">
                             </div>
                        </div>
                    </div>
                </div>

                <div x-show="currentStep === 1">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <h2 class="text-2xl font-bold text-petGray mb-6">Passo 2: Selecione os Serviços</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="servico in allServices" :key="servico.nome">
                                <label class="flex items-center border rounded-lg p-4 cursor-pointer transition-all" :class="servicosSelecionados[servico.nome] ? 'border-petBlue bg-blue-50 shadow-md' : 'border-gray-200 hover:border-petBlue'">
                                    <input type="checkbox" name="servicos[]" :value="servico.nome" x-model="servicosSelecionados[servico.nome]" @change="clearSelectedDate" class="form-checkbox h-5 w-5 text-petBlue focus:ring-petBlue">
                                    <div class="ml-3">
                                        <span class="text-petGray font-medium" x-text="servico.nome"></span>
                                        <span class="text-xs text-gray-500 block" x-text="`Duração: ${servico.duracao_minutos} min`"></span>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-show="currentStep === 2">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <h2 class="text-2xl font-bold text-petGray mb-6">Passo 3: Como será a entrega e retirada?</h2>
                        <div class="space-y-4">
                            <label x-show="permiteLoja" class="flex items-center border rounded-lg p-4 cursor-pointer" :class="{'selected': tipoEntrega === 'loja'}">
                                <input type="radio" value="loja" x-model="tipoEntrega" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue">
                                <span class="ml-3 text-petGray text-lg">Vou levar e buscar na loja</span>
                            </label>
                            <label x-show="permiteDelivery" class="flex items-center border rounded-lg p-4 cursor-pointer" :class="{'selected': tipoEntrega === 'delivery'}">
                                <input type="radio" value="delivery" x-model="tipoEntrega" class="form-radio h-5 w-5 text-petBlue focus:ring-petBlue">
                                <span class="ml-3 text-petGray text-lg">Quero que busquem e entreguem em casa</span>
                            </label>
                        </div>
                        <div x-show="tipoEntrega === 'delivery'" class="mt-6 space-y-4">
                            <label for="endereco_id" class="block text-petGray font-medium">Selecione o endereço:</label>
                            <select name="endereco_id" id="endereco_id" x-model="enderecoId" class="w-full p-2 border rounded-md bg-white form-input">
                                <option value="">-- Seus endereços cadastrados --</option>
                                <template x-for="endereco in allEnderecos" :key="endereco.id">
                                    <option :value="endereco.id" x-text="`${endereco.rua}, ${endereco.numero}`"></option>
                                </template>
                                <option value="novo_endereco">** Cadastrar Novo Endereço **</option>
                            </select>
                            <div x-show="enderecoId === 'novo_endereco'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-6 mt-4">
                                <h3 class="md:col-span-2 text-lg font-semibold text-petOrange" x-text="allEnderecos.length > 0 ? 'Dados do Novo Endereço' : 'Cadastre seu primeiro endereço'"></h3>
                                <div>
                                    <label for="novo_endereco_cep" class="block text-sm font-medium text-petGray">CEP</label>
                                    <input type="text" id="novo_endereco_cep" name="novo_endereco_cep" placeholder="Digite o CEP" @blur="buscarCep($event.target.value)" class="w-full mt-1 p-2 border rounded-md form-input">
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
                    </div>
                </div>

                <div x-show="currentStep === 3" x-init="getNoOfDays(); selectDate(new Date().getDate())">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <h2 class="text-2xl font-bold text-petGray mb-6">Passo 4: Escolha a Data e Horário</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-lg font-semibold text-petGray mb-4">Selecione uma data</h3>
                                <div class="calendar bg-white border border-gray-300 rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-4">
                                        <button type="button" @click="prevMonth" class="p-2 rounded-full hover:bg-gray-100">
                                            <svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                        </button>
                                        <h4 class="text-petGray font-semibold text-lg" x-text="`${monthNames[month]} ${year}`"></h4>
                                        <button type="button" @click="nextMonth" class="p-2 rounded-full hover:bg-gray-100">
                                            <svg class="w-5 h-5 text-petGray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-gray-500 text-sm font-medium">
                                        <template x-for="day in days" :key="day"><div x-text="day"></div></template>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 mt-2">
                                        <template x-for="blankday in blankdays"><div></div></template>
                                        <template x-for="date in no_of_days" :key="date">
                                            <div @click="selectDate(date)" x-text="date"
                                                 :class="{
                                                     'bg-petBlue text-white': selectedDate && selectedDate.getDate() == date && selectedDate.getMonth() == month,
                                                     'hover:bg-gray-200': isAvailable(date),
                                                     'text-gray-400 cursor-not-allowed': !isAvailable(date),
                                                     'cursor-pointer': isAvailable(date)
                                                 }"
                                                 class="p-2 text-center rounded-full">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-petGray mb-4">Selecione um horário</h3>
                                <div x-show="isLoadingTimes" class="text-center p-4">
                                    <svg class="animate-spin h-6 w-6 text-petBlue mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <p class="text-sm text-gray-500 mt-2">Buscando horários...</p>
                                </div>
                                <div x-show="!isLoadingTimes && !selectedDate" class="text-center p-4 bg-gray-50 rounded-md">
                                    <p class="text-sm text-gray-600">Por favor, selecione uma data no calendário para ver os horários disponíveis.</p>
                                </div>
                                <div x-show="!isLoadingTimes && selectedDate && timeSlots.length === 0" class="text-center p-4 bg-gray-50 rounded-md">
                                    <p class="text-sm text-gray-600">Nenhum horário disponível para este dia. Tente outra data.</p>
                                </div>
                                <div x-show="!isLoadingTimes && selectedDate && timeSlots.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <template x-for="timeSlot in timeSlots" :key="timeSlot.time">
                                        <button type="button" @click="selectTime(timeSlot.time)"
                                                :disabled="!timeSlot.available"
                                                :class="{'bg-petBlue text-white': selectedTime === timeSlot.time, 'bg-gray-200 text-gray-400 cursor-not-allowed': !timeSlot.available, 'hover:bg-blue-100': timeSlot.available}"
                                                class="time-slot py-2 px-3 border border-gray-300 rounded-md text-petGray transition"
                                                x-text="timeSlot.time">
                                        </button>
                                    </template>
                                </div>
                                <div class="mt-6">
                                    <h3 class="text-lg font-semibold text-petGray mb-4">Observações</h3>
                                    <textarea x-model.lazy="observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md form-input" placeholder="Alergias, comportamento, etc..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="currentStep === 4">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <h2 class="text-2xl font-bold text-petGray mb-6">Passo 5: Confirme seu Agendamento</h2>
                        <div class="bg-petLightGray p-6 rounded-lg space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b pb-4">
                                <div>
                                    <p class="font-medium text-petGray">Cliente:</p>
                                    <p class="font-semibold text-lg" x-text="summary.ownerName"></p>
                                </div>
                                <div>
                                    <p class="font-medium text-petGray">Pet:</p>
                                    <p class="font-semibold text-lg" x-text="summary.petName"></p>
                                </div>
                            </div>
                            <div class="pt-2 border-b pb-4">
                                <p class="font-medium text-petGray">Serviços:</p>
                                <p class="font-semibold text-lg" x-text="summary.services"></p>
                            </div>
                            <div class="pt-2 border-b pb-4">
                                <p class="font-medium text-petGray">Entrega/Retirada:</p>
                                <p class="font-semibold text-lg" x-text="summary.delivery"></p>
                            </div>
                            <div class="pt-2 border-b pb-4">
                                <p class="font-medium text-petGray">Data e Hora:</p>
                                <p class="font-semibold text-lg" x-text="summary.datetime"></p>
                            </div>
                            <div class="pt-2">
                                <p class="font-medium text-petGray">Observações:</p>
                                <p class="text-gray-600 italic" x-text="summary.notes"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex" :class="currentStep > 0 ? 'justify-between' : 'justify-end'">

                    <button type="button" x-show="currentStep > 0 && currentStep < steps.length - 1" @click="navigate('prev')" class="bg-gray-200 text-petGray px-6 py-3 rounded-md font-medium hover:bg-gray-300">Voltar</button>

                    <button type="button" x-show="currentStep < steps.length - 1" @click="navigate('next')" class="bg-petBlue text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700">Próximo</button>

                    <button type="submit" x-show="currentStep === steps.length - 1" class="bg-petOrange text-white px-6 py-3 rounded-md font-medium hover:bg-orange-700">Confirmar Agendamento</button>
                    
                </div>
            </form>

        </div>
    </div>
</main>

<script>
    const bookingPageData = {
        pets: <?= json_encode($pets_cliente, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>,
        servicos: <?= json_encode($lista_servicos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>,
        enderecos: <?= json_encode($enderecos_cliente, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>,
        diasTrabalho: <?= json_encode($dias_trabalho, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>,
        formData: <?= json_encode($form_data_php, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>,
        errorStep: <?= json_encode($error_step_php) ?>,
        clienteNome: '<?= htmlspecialchars($cliente_selecionado['nome'] ?? 'Cliente', ENT_QUOTES) ?>',
        permiteLoja: <?= $permitir_cliente_leva ? 'true' : 'false' ?>,
        permiteDelivery: <?= $permitir_delivery ? 'true' : 'false' ?>,
        erroPHP: '<?= addslashes($erro_msg_php) ?>'
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>

<script>
    function bookingForm(data) {
        return {
            // --- ESTADO DO COMPONENTE ---
            currentStep: 0,
            steps: ['Pet', 'Serviços', 'Entrega', 'Horário', 'Confirmar'],
            pets: data.pets,
            allServices: data.servicos,
            allEnderecos: data.enderecos,
            clienteNome: data.clienteNome,
            permiteLoja: data.permiteLoja,
            permiteDelivery: data.permiteDelivery,
            diasTrabalho: data.diasTrabalho.map(d => parseInt(d)),
            summary: { ownerName: '', petName: '', services: '', delivery: '', datetime: '', notes: '' },
            
            // --- MODELS DO FORMULÁRIO ---
            selectedPetId: '',
            servicosSelecionados: {},
            tipoEntrega: '',
            enderecoId: '',
            observacoes: '',
            
            // --- ESTADO DO NOVO PET ---
            racasPorEspecie: {'Cão':['SRD (Vira-lata)','Shih Tzu','Yorkshire','Poodle','Lhasa Apso','Buldogue Francês','Golden Retriever','Labrador','Outro(a)'],'Gato':['SRD (Vira-lata)','Siamês','Persa','Angorá','Sphynx','Maine Coon','Outro(a)'],'Outro(a)':[]},
            novoPet: { especie: '', raca: '' },

            // --- CALENDÁRIO E HORÁRIOS ---
            month: new Date().getMonth(),
            year: new Date().getFullYear(),
            monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            days: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
            blankdays: [],
            no_of_days: [],
            selectedDate: null,
            selectedTime: null,
            timeSlots: [],
            isLoadingTimes: false,
            
            // --- GETTERS (PROPRIEDADES COMPUTADAS) ---
            get totalDuration() {
                return this.allServices.reduce((total, service) => {
                    return this.servicosSelecionados[service.nome] ? total + parseInt(service.duracao_minutos) : total;
                }, 0);
            },
            get selectedDateTime() {
                if (!this.selectedDate || !this.selectedTime) return '';
                const y = this.selectedDate.getFullYear();
                const m = String(this.selectedDate.getMonth() + 1).padStart(2, '0');
                const d = String(this.selectedDate.getDate()).padStart(2, '0');
                return `${y}-${m}-${d} ${this.selectedTime}:00`;
            },
            get isLastStep() {
                return this.currentStep === this.steps.length - 1;
            },
            get isNotLastStep() {
                return this.currentStep < this.steps.length - 1;
            },
            
            // --- MÉTODOS ---
            init() {
                this.tipoEntrega = this.permiteLoja ? 'loja' : (this.permiteDelivery ? 'delivery' : '');
                if (this.pets.length === 1) this.selectedPetId = this.pets[0].id;
                this.allServices.forEach(s => { this.servicosSelecionados[s.nome] = false; });
                
                if (data.erroPHP) this.showToast(data.erroPHP);
                if (data.errorStep !== null) this.currentStep = data.errorStep;
                if (data.formData) this.restoreFormState(data.formData);

                // Inicializa o calendário se estiver no passo 3
                if (this.currentStep === 3) {
                    this.getNoOfDays();
                    // Seleciona a data atual por padrão se for um dia disponível
                    const today = new Date().getDate();
                    if (this.isAvailable(today)) {
                        this.selectDate(today);
                    }
                }
            },
            
            navigate(direction) {
                if (direction === 'next') {
                    if (!this.validateStep()) return;
                }
                
                const newStep = this.currentStep + (direction === 'next' ? 1 : -1);
                this.currentStep = Math.max(0, Math.min(this.steps.length - 1, newStep));

                // Se avançar para o passo 3, inicializa o calendário e seleciona a data atual
                if (this.currentStep === 3) {
                    this.getNoOfDays();
                    const today = new Date().getDate();
                    if (this.isAvailable(today)) {
                        this.selectDate(today);
                    }
                }

                if (this.isLastStep) {
                    this.populateSummary();
                }
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            
            selectPet(id) {
                this.selectedPetId = id;
                if (id === 'novo_pet') this.novoPet = { especie: '', raca: '' };
            },

            clearSelectedDate() {
                this.selectedDate = null;
                this.selectedTime = null;
                this.timeSlots = [];
            },

            getNoOfDays() {
                let d = new Date(this.year, this.month + 1, 0);
                this.no_of_days = Array.from({length: d.getDate()}, (_, i) => i + 1);
                let f = new Date(this.year, this.month, 1);
                this.blankdays = Array.from({length: f.getDay()}, (_, i) => i + 1);
            },

            prevMonth() {
                if (this.month === 0) { this.month = 11; this.year--; } 
                else { this.month--; }
                this.getNoOfDays();
            },

            nextMonth() {
                if (this.month === 11) { this.month = 0; this.year++; } 
                else { this.month++; }
                this.getNoOfDays();
            },
            
            isAvailable(date) {
                const d = new Date(this.year, this.month, date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return this.diasTrabalho.includes(d.getDay()) && d >= today;
            },

            async selectDate(date) {
                if (!this.isAvailable(date)) return;
                if (this.totalDuration === 0) {
                    this.showToast('Selecione pelo menos um serviço antes de escolher a data.');
                    return;
                }
                this.selectedDate = new Date(this.year, this.month, date);
                this.selectedTime = null;
                this.timeSlots = [];
                this.isLoadingTimes = true;
                
                const dateStr = `${this.year}-${String(this.month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                try {
                    const response = await fetch(`ajax_get_horarios.php?date=${dateStr}&duracao=${this.totalDuration}`);
                    const result = await response.json();
                    if (result.disponiveis) { this.timeSlots = result.disponiveis; }
                    else if (result.erro) { this.showToast(result.erro); }
                } catch (e) {
                    this.showToast('Erro de comunicação ao buscar horários.');
                } finally {
                    this.isLoadingTimes = false;
                }
            },

            selectTime(time) { this.selectedTime = time; },
            
            validateStep() {
                let message = '';
                switch (this.currentStep) {
                    case 0:
                        if (!this.selectedPetId) {
                            message = 'Você precisa selecionar um pet ou cadastrar um novo.';
                        } else if (this.selectedPetId === 'novo_pet') {
                            const novoPetNome = document.getElementById('novo_pet_nome').value.trim();
                            const novoPetEspecie = document.getElementById('novo_pet_especie_select').value;
                            const novoPetRaca = document.getElementById('novo_pet_raca_select').value;

                            if (!novoPetNome) {
                                message = 'O nome do novo pet é obrigatório.';
                            } else if (!novoPetEspecie || (novoPetEspecie === 'Outro(a)' && !document.querySelector('input[name="outra_especie"]').value.trim())) {
                                message = 'A espécie do novo pet é obrigatória.';
                            } else if (!novoPetRaca || (novoPetRaca === 'Outro(a)' && !document.querySelector('input[name="outra_raca"]').value.trim())) {
                                message = 'A raça do novo pet é obrigatória.';
                            }
                        }
                        break;
                    case 1:
                        if (this.totalDuration === 0) {
                            message = 'Selecione pelo menos um serviço.';
                        }
                        break;
                    case 2:
                        if (!this.tipoEntrega) {
                            message = 'Selecione o tipo de entrega/retirada.';
                        } else if (this.tipoEntrega === 'delivery') {
                            if(!this.enderecoId) {
                                message = 'Selecione um endereço para o delivery.';
                            } else if (this.enderecoId === 'novo_endereco') {
                                const novoEnderecoRua = document.getElementById('novo_endereco_rua').value.trim();
                                if (!novoEnderecoRua) {
                                    message = 'A rua do novo endereço é obrigatória para delivery.';
                                }
                            }
                        }
                        break;
                    case 3:
                        if (!this.selectedDate || !this.selectedTime) {
                            message = 'Selecione uma data e um horário.';
                        }
                        break;
                }
                if (message) {
                    this.showToast(message);
                    return false;
                }
                return true;
            },

            populateSummary() {
                this.summary.ownerName = this.clienteNome;
                if (this.selectedPetId === 'novo_pet') {
                    this.summary.petName = `${document.getElementById('novo_pet_nome').value} (Novo)`;
                } else {
                    const pet = this.pets.find(p => p.id == this.selectedPetId);
                    this.summary.petName = pet ? pet.nome : 'N/D';
                }
                this.summary.services = Object.keys(this.servicosSelecionados).filter(s => this.servicosSelecionados[s]).join(', ') || 'Nenhum';
                if (this.tipoEntrega === 'loja') {
                    this.summary.delivery = 'Cliente leva e busca na loja';
                } else {
                    if (this.enderecoId && this.enderecoId !== 'novo_endereco') {
                        const end = this.allEnderecos.find(e => e.id == this.enderecoId);
                        this.summary.delivery = end ? `Delivery: ${end.rua}, ${end.numero}` : 'Endereço selecionado';
                    } else {
                        this.summary.delivery = `Delivery: ${document.getElementById('novo_endereco_rua').value} (Novo Endereço)`;
                    }
                }
                if (this.selectedDate && this.selectedTime) {
                    const dateOpts = { day: '2-digit', month: 'long', year: 'numeric' };
                    this.summary.datetime = this.selectedDate.toLocaleDateString('pt-BR', dateOpts) + ' às ' + this.selectedTime;
                } else {
                    this.summary.datetime = 'Não definido';
                }
                this.summary.notes = this.observacoes || 'Nenhuma.';
            },
            
            async buscarCep(cep) {
                const cepLimpo = cep.replace(/\D/g, '');
                if (cepLimpo.length !== 8) return;
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                    const data = await response.json();
                    if (data.erro) {
                        this.showToast('CEP não encontrado.');
                    } else {
                        document.getElementById('novo_endereco_rua').value = data.logradouro || '';
                        document.getElementById('novo_endereco_bairro').value = data.bairro || '';
                        document.getElementById('novo_endereco_cidade').value = data.localidade || '';
                        document.getElementById('novo_endereco_estado').value = data.uf || '';
                        document.getElementById('novo_endereco_numero').focus();
                    }
                } catch (e) {
                    this.showToast('Erro ao buscar CEP.');
                }
            },

            showToast(message, type = 'error') {
                const container = document.getElementById('toast-notification-container');
                if (!container) return;
                const toast = document.createElement('div');
                toast.className = (type === 'error' ? 'bg-red-500' : 'bg-green-500') + ' text-white p-4 rounded-lg shadow-lg';
                toast.textContent = message;
                container.appendChild(toast);
                setTimeout(() => toast.remove(), 5000);
            },
            
            submitForm() {
                if (this.validateStep()) {
                    document.getElementById('booking-form').submit();
                }
            },

            restoreFormState(formData) {
                this.selectedPetId = formData.pet_id || '';
                this.tipoEntrega = formData.tipo_entrega || '';
                this.enderecoId = formData.endereco_id || '';
                this.observacoes = formData.observacoes || '';
                if (formData.servicos && Array.isArray(formData.servicos)) {
                    formData.servicos.forEach(s => {
                        if (this.servicosSelecionados.hasOwnProperty(s)) {
                            this.servicosSelecionados[s] = true;
                        }
                    });
                }
            }
        };
    }
</script>
<?php require 'footer.php'; ?>

