<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Configurações';
$ok = $erro = '';

// Lista de todas as chaves de checkbox para validação e salvamento
$chaves_servicos = [
    'servico_banho_tosa_ativo', 'servico_veterinaria_ativo', 'servico_pet_shop_ativo', 'servico_hospedagem_ativo',
    'servico_adestramento_ativo', 'servico_day_care_ativo', 'servico_pet_taxi_ativo', 'servico_vacinacao_ativo',
    'servico_consultoria_nutricional_ativo', 'servico_sessao_fotos_ativo', 'servico_fisioterapia_ativo',
    'servico_pet_sitter_ativo', 'servico_outros_ativo'
];
$outros_checkboxes = ['exibir_secao_produtos', 'telefone_1_is_whatsapp', 'telefone_2_is_whatsapp'];
$todas_chaves_cb = array_merge($chaves_servicos, $outros_checkboxes);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servicos_selecionados_count = 0;
    foreach ($chaves_servicos as $chave) {
        if (!empty($_POST[$chave])) {
            $servicos_selecionados_count++;
        }
    }

    if ($servicos_selecionados_count > 4) {
        $erro = 'Erro: Você pode selecionar no máximo 4 serviços para exibir na página inicial.';
    } else {
        foreach ($todas_chaves_cb as $cb) {
            if (!isset($_POST[$cb])) {
                $_POST[$cb] = '0';
            }
        }

        foreach ($_POST as $chave => $valor) {
            $stmt_check = $mysqli->prepare("SELECT chave FROM configuracoes WHERE chave = ?");
            $stmt_check->bind_param('s', $chave);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows > 0) {
                $stmt_update = $mysqli->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
                $stmt_update->bind_param('ss', $valor, $chave);
                $stmt_update->execute();
            } else {
                if ($valor !== '0' && $valor !== '') {
                    $stmt_insert = $mysqli->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?)");
                    $stmt_insert->bind_param('ss', $chave, $valor);
                    $stmt_insert->execute();
                }
            }
        }
        $ok = 'Configurações salvas com sucesso!';
        header("Location: configuracoes.php?ok_msg=" . urlencode($ok));
        exit;
    }
}

if (isset($_GET['ok_msg'])) {
    $ok = $_GET['ok_msg'];
}

$configuracoes = [];
$result_config = $mysqli->query("SELECT chave, valor FROM configuracoes");
while ($row = $result_config->fetch_assoc()) {
    $configuracoes[$row['chave']] = $row['valor'];
}

require '../header.php';
?>
<div id="toast-container" class="fixed top-5 right-5 z-50"></div>

<div class="container mx-auto px-4 py-12">
    <h1 class="text-4xl font-bold text-petGray mb-8">Configurações do Site</h1>
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <form action="configuracoes.php" method="POST" class="space-y-6">
            
            <h2 class="text-2xl font-semibold text-petBlue border-b pb-2 mb-4">Contato e Horários</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="space-y-2">
                    <label for="telefone_1" class="block text-petGray font-medium">Telefone 1</label>
                    <input type="text" name="telefone_1" id="telefone_1" value="<?= htmlspecialchars($configuracoes['telefone_1'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input" placeholder="(xx) x xxxx-xxxx" oninput="maskPhone(event)" maxlength="17">
                    <div class="flex items-center mt-2">
                        <input type="checkbox" id="telefone_1_is_whatsapp" name="telefone_1_is_whatsapp" value="1" class="h-4 w-4 text-green-600 rounded focus:ring-green-500" <?= !empty($configuracoes['telefone_1_is_whatsapp']) ? 'checked' : '' ?>>
                        <label for="telefone_1_is_whatsapp" class="ml-2 text-sm text-petGray">É WhatsApp?</label>
                    </div>
                </div>
                <div class="space-y-2">
                    <label for="telefone_2" class="block text-petGray font-medium">Telefone 2</label>
                    <input type="text" name="telefone_2" id="telefone_2" value="<?= htmlspecialchars($configuracoes['telefone_2'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input" placeholder="(xx) x xxxx-xxxx" oninput="maskPhone(event)" maxlength="17">
                     <div class="flex items-center mt-2">
                        <input type="checkbox" id="telefone_2_is_whatsapp" name="telefone_2_is_whatsapp" value="1" class="h-4 w-4 text-green-600 rounded focus:ring-green-500" <?= !empty($configuracoes['telefone_2_is_whatsapp']) ? 'checked' : '' ?>>
                        <label for="telefone_2_is_whatsapp" class="ml-2 text-sm text-petGray">É WhatsApp?</label>
                    </div>
                </div>
                <div>
                    <label for="email_contato" class="block text-petGray font-medium">E-mail</label>
                    <input type="email" name="email_contato" id="email_contato" value="<?= htmlspecialchars($configuracoes['email_contato'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input">
                </div>
                 <div>
                    <label for="horario_semana" class="block text-petGray font-medium">Horário (Seg-Sex)</label>
                    <input type="text" name="horario_semana" id="horario_semana" value="<?= htmlspecialchars($configuracoes['horario_semana'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input">
                </div>
                <div>
                    <label for="horario_sabado" class="block text-petGray font-medium">Horário (Sábado)</label>
                    <input type="text" name="horario_sabado" id="horario_sabado" value="<?= htmlspecialchars($configuracoes['horario_sabado'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input">
                </div>
            </div>

            <h2 class="text-2xl font-semibold text-petBlue border-b pb-2 mt-8 mb-4">Gerenciar Seções da Página</h2>
            <div class="space-y-4">
                <div class="p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center">
                    <input
                      type="checkbox"
                      name="exibir_secao_produtos"
                      id="exibir_secao_produtos"
                      value="1"
                      class="h-5 w-5 text-petOrange rounded focus:ring-orange-500"
                      <?= !empty($configuracoes['exibir_secao_produtos']) ? 'checked' : '' ?>
                    >
                    <label
                      for="exibir_secao_produtos"
                      class="ml-3 text-petGray font-medium"
                    >
                      Exibir a seção "Produtos Populares"?
                    </label>
                  </div>
                  <p class="mt-2 pl-8 text-sm text-gray-500">
                    (Confira somente quem comercializa produtos)
                  </p>
                </div>


            <h2 class="text-2xl font-semibold text-petBlue border-b pb-2 mt-8 mb-4">Gerenciar Serviços</h2>
            <p class="text-petGray mb-4">Escolha no <span class="font-bold text-petOrange">máximo 4 serviços</span> para destacar na página inicial.</p>
            <div id="services-checkbox-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                 <?php
                    $todos_servicos_admin = [
                        ['chave' => 'servico_banho_tosa_ativo', 'titulo' => 'Banho e Tosa'], ['chave' => 'servico_veterinaria_ativo', 'titulo' => 'Veterinária'],
                        ['chave' => 'servico_pet_shop_ativo', 'titulo' => 'Pet Shop'], ['chave' => 'servico_hospedagem_ativo', 'titulo' => 'Hospedagem'],
                        ['chave' => 'servico_adestramento_ativo', 'titulo' => 'Adestramento'], ['chave' => 'servico_day_care_ativo', 'titulo' => 'Day Care (Creche)'],
                        ['chave' => 'servico_pet_taxi_ativo', 'titulo' => 'Pet Táxi'], ['chave' => 'servico_vacinacao_ativo', 'titulo' => 'Vacinação'],
                        ['chave' => 'servico_consultoria_nutricional_ativo', 'titulo' => 'Consultoria Nutricional'], ['chave' => 'servico_sessao_fotos_ativo', 'titulo' => 'Sessão de Fotos'],
                        ['chave' => 'servico_fisioterapia_ativo', 'titulo' => 'Fisioterapia'], ['chave' => 'servico_pet_sitter_ativo', 'titulo' => 'Pet Sitter'],
                        ['chave' => 'servico_outros_ativo', 'titulo' => 'Outros Serviços'],
                    ];
                 ?>
                 <?php foreach ($todos_servicos_admin as $servico): ?>
                 <div class="flex items-center p-3 bg-gray-50 rounded-lg border">
                    <input type="checkbox" name="<?= $servico['chave'] ?>" id="<?= $servico['chave'] ?>" value="1" class="service-checkbox h-5 w-5 text-petBlue rounded focus:ring-blue-500" <?= !empty($configuracoes[$servico['chave']]) ? 'checked' : '' ?>>
                    <label for="<?= $servico['chave'] ?>" class="ml-3 text-petGray font-medium"><?= $servico['titulo'] ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h2 class="text-2xl font-semibold text-petBlue border-b pb-2 mt-8 mb-4">Localização</h2>
            <div>
                <label for="endereco" class="block text-petGray font-medium">Endereço</label>
                <input type="text" name="endereco" id="endereco" value="<?= htmlspecialchars($configuracoes['endereco'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input">
            </div>
            <div>
                <label for="mapa_url" class="block text-petGray font-medium">Código do Google Maps (iframe)</label>
                <textarea name="mapa_url" id="mapa_url" rows="5" class="w-full mt-1 p-2 border rounded-md form-input font-mono text-sm"><?= htmlspecialchars($configuracoes['mapa_url'] ?? '') ?></textarea>
            </div>
            <h2 class="text-2xl font-semibold text-petBlue border-b pb-2 mt-8 mb-4">Seção "Sobre"</h2>
            <div>
                <label for="sobre_titulo" class="block text-petGray font-medium">Título</label>
                <input type="text" name="sobre_titulo" id="sobre_titulo" value="<?= htmlspecialchars($configuracoes['sobre_titulo'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input">
            </div>
            <div>
                <label for="sobre_texto_1" class="block text-petGray font-medium">Primeiro Parágrafo</label>
                <textarea name="sobre_texto_1" id="sobre_texto_1" rows="4" class="w-full mt-1 p-2 border rounded-md form-input"><?= htmlspecialchars($configuracoes['sobre_texto_1'] ?? '') ?></textarea>
            </div>
            <div>
                <label for="sobre_texto_2" class="block text-petGray font-medium">Segundo Parágrafo</label>
                <textarea name="sobre_texto_2" id="sobre_texto_2" rows="4" class="w-full mt-1 p-2 border rounded-md form-input"><?= htmlspecialchars($configuracoes['sobre_texto_2'] ?? '') ?></textarea>
            </div>
            <h3 class="text-xl font-semibold text-petGray pt-4">Estatísticas da Seção "Sobre"</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div>
                    <label class="text-sm">Stat <?= $i ?> (Número)</label>
                    <input type="text" name="stat_<?= $i ?>_num" value="<?= htmlspecialchars($configuracoes["stat_{$i}_num"] ?? '') ?>" class="w-full p-2 border rounded-md">
                    <label class="text-sm">Stat <?= $i ?> (Descrição)</label>
                    <input type="text" name="stat_<?= $i ?>_desc" value="<?= htmlspecialchars($configuracoes["stat_{$i}_desc"] ?? '') ?>" class="w-full p-2 border rounded-md">
                </div>
                <?php endfor; ?>
            </div>
            <div class="text-right mt-8">
                <button type="submit" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg">
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- NOVO SISTEMA DE NOTIFICAÇÃO (TOAST) ---
    const toastContainer = document.getElementById('toast-container');

    function showToast(message, isError = false) {
        if (!toastContainer) return;

        const toast = document.createElement('div');
        const icon_success = `<svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        const icon_error = `<svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        
        toast.className = `flex items-center p-4 mb-4 text-white rounded-lg shadow-lg transform transition-all duration-300 ease-in-out ${isError ? 'bg-red-500' : 'bg-green-500'}`;
        toast.innerHTML = `${isError ? icon_error : icon_success} ${message}`;

        toastContainer.appendChild(toast);

        // Animação de entrada
        setTimeout(() => toast.style.transform = 'translateX(0)', 10);

        // Remover o toast após 5 segundos
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    // Exibir toasts vindos do PHP (após salvar)
    <?php if ($ok): ?>
        showToast('<?= addslashes(htmlspecialchars($ok)) ?>');
    <?php endif; ?>
    <?php if ($erro): ?>
        showToast('<?= addslashes(htmlspecialchars($erro)) ?>', true);
    <?php endif; ?>

    // --- LÓGICA PARA LIMITAR SERVIÇOS ---
    const checkboxes = document.querySelectorAll('.service-checkbox');
    const maxAllowed = 4;
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const checkedCount = document.querySelectorAll('.service-checkbox:checked').length;
            if (checkedCount > maxAllowed) {
                // Usa o novo sistema de toast para o aviso
                showToast(`Você pode selecionar no máximo ${maxAllowed} serviços.`, true);
                checkbox.checked = false;
            }
        });
    });
});

// --- NOVA FUNÇÃO PARA MÁSCARA DE TELEFONE ---
function maskPhone(event) {
    const input = event.target;
    let value = input.value.replace(/\D/g, ''); // Remove tudo que não é dígito
    value = value.substring(0, 11); // Limita a 11 dígitos

    if (value.length > 10) {
        // Formato para celular: (xx) x xxxx-xxxx
        value = value.replace(/^(\d{2})(\d)(\d{4})(\d{4}).*/, '($1) $2 $3-$4');
    } else if (value.length > 6) {
        // Formato para telefone fixo: (xx) xxxx-xxxx
        value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
    } else if (value.length > 2) {
        value = value.replace(/^(\d{2})(\d*)/, '($1) $2');
    } else if (value.length > 0) {
        value = value.replace(/^(\d*)/, '($1');
    }
    
    input.value = value;
}
</script>

<?php require '../footer.php'; ?>