<?php
include '../config.php';
include 'check_cliente.php'; 
$page_title = 'Meu Perfil - PetSync';

$id_usuario_logado = $_SESSION['usuario']['id'];
$ok = '';
$erro = '';

// Lógica para adicionar Pet ou Endereço
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- AÇÃO DE ADICIONAR PET ---
    if (isset($_POST['add_pet'])) {
        $nome_pet = trim($_POST['nome_pet']);
        $especie = trim($_POST['especie']);
        $raca = trim($_POST['raca']);
        $data_nascimento = $_POST['data_nascimento'] ?: null;

        if (!empty($nome_pet)) {
            $stmt = $mysqli->prepare("INSERT INTO pets (dono_id, nome, especie, raca, data_nascimento) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $id_usuario_logado, $nome_pet, $especie, $raca, $data_nascimento);
            if ($stmt->execute()) {
                // NOVO: Usa a sessão para a mensagem de sucesso
                $_SESSION['ok_msg'] = "Pet cadastrado com sucesso!";
            } else {
                $_SESSION['erro_msg'] = "Erro ao cadastrar pet.";
            }
        } else {
            $_SESSION['erro_msg'] = "O nome do pet é obrigatório.";
        }
    }
    // --- AÇÃO DE ADICIONAR ENDEREÇO ---
    elseif (isset($_POST['add_endereco'])) {
        $rua = trim($_POST['rua']);
        $numero = trim($_POST['numero']);
        $cidade = trim($_POST['cidade']);
        $estado = trim($_POST['estado']);
        $cep = trim($_POST['cep']);
        $complemento = trim($_POST['complemento']);
        $bairro = trim($_POST['bairro']);

        if (!empty($rua) && !empty($numero) && !empty($cidade) && !empty($estado)) {
             $stmt = $mysqli->prepare("INSERT INTO enderecos (usuario_id, rua, numero, complemento, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
             $stmt->bind_param("isssssss", $id_usuario_logado, $rua, $numero, $complemento, $bairro, $cidade, $estado, $cep);
             if ($stmt->execute()) {
                // NOVO: Usa a sessão para a mensagem de sucesso
                $_SESSION['ok_msg'] = "Endereço cadastrado com sucesso!";
            } else {
                $_SESSION['erro_msg'] = "Erro ao cadastrar endereço.";
            }
        } else {
            $_SESSION['erro_msg'] = "Campos obrigatórios do endereço não foram preenchidos.";
        }
    }

    // NOVO: Redireciona para a mesma página para limpar o POST e evitar reenvio
    header("Location: perfil.php");
    exit;
}

// NOVO: Pega a mensagem da sessão e depois a apaga para ser exibida uma única vez
if (isset($_SESSION['ok_msg'])) {
    $ok = $_SESSION['ok_msg'];
    unset($_SESSION['ok_msg']);
}
if (isset($_SESSION['erro_msg'])) {
    $erro = $_SESSION['erro_msg'];
    unset($_SESSION['erro_msg']);
}

// Busca os dados do usuário, seus pets e endereços
$pets = $mysqli->query("SELECT * FROM pets WHERE dono_id = $id_usuario_logado ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$enderecos = $mysqli->query("SELECT * FROM enderecos WHERE usuario_id = $id_usuario_logado ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$usuario_info = $mysqli->query("SELECT nome, email FROM usuarios WHERE id = $id_usuario_logado")->fetch_assoc();

require '../header.php';
?>

<?php if ($ok): ?><div id="toast-notification" class="bg-green-500 show"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($erro): ?><div id="toast-notification" class="bg-red-500 show"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="bg-petLightGray min-h-full">
    <div class="container mx-auto px-4 py-12">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-petGray">Olá, <span class="text-petBlue"><?= htmlspecialchars(explode(' ', $usuario_info['nome'])[0]) ?></span>!</h1>
            <p class="text-lg text-gray-500">Bem-vindo(a) ao seu painel. Aqui você pode gerenciar seus dados.</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                
                <div class="bg-white p-8 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-semibold text-petGray mb-4">Meus Dados</h2>
                    <div class="space-y-2">
                        <div><strong>Nome Completo:</strong> <?= htmlspecialchars($usuario_info['nome']) ?></div>
                        <div><strong>E-mail:</strong> <?= htmlspecialchars($usuario_info['email']) ?></div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-lg shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-semibold text-petGray">Meus Endereços</h2>
                        <button id="toggle-endereco-form" class="bg-petOrange text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors text-sm">Adicionar Novo</button>
                    </div>
                    
                    <form id="endereco-form" action="perfil.php" method="POST" class="hidden space-y-4 border-b mb-6 pb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="text" name="cep" id="cep" placeholder="CEP" class="md:col-span-1 w-full p-2 border rounded-md" required>
                            <input type="text" name="rua" id="rua" placeholder="Rua / Avenida" class="md:col-span-2 w-full p-2 border rounded-md" required>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="text" name="numero" placeholder="Número" class="w-full p-2 border rounded-md" required>
                            <input type="text" name="bairro" id="bairro" placeholder="Bairro" class="w-full p-2 border rounded-md">
                            <input type="text" name="complemento" placeholder="Complemento (Opcional)" class="w-full p-2 border rounded-md">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="cidade" id="cidade" placeholder="Cidade" class="w-full p-2 border rounded-md" required>
                            <input type="text" name="estado" id="estado" placeholder="Estado (UF)" maxlength="2" class="w-full p-2 border rounded-md" required>
                        </div>
                        <button type="submit" name="add_endereco" class="w-full bg-petBlue text-white py-2 rounded-md hover:bg-blue-700 font-semibold">Salvar Endereço</button>
                    </form>

                    <div class="space-y-4">
                        <?php if(empty($enderecos)): ?>
                            <p class="text-gray-500 text-center py-4">Nenhum endereço cadastrado.</p>
                        <?php else: foreach($enderecos as $endereco): ?>
                            <div class="flex items-center p-3 border rounded-lg bg-gray-50">
                                <div class="text-petBlue mr-4"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg></div>
                                <div><?= htmlspecialchars($endereco['rua']) ?>, <?= htmlspecialchars($endereco['numero']) ?> - <?= htmlspecialchars($endereco['bairro']) ?>, <?= htmlspecialchars($endereco['cidade']) ?>/<?= htmlspecialchars($endereco['estado']) ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-lg shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-semibold text-petGray">Meus Pets</h2>
                        <button id="toggle-pet-form" class="bg-petOrange text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors text-sm">Adicionar Novo</button>
                    </div>

                    <form id="pet-form" action="perfil.php" method="POST" class="hidden space-y-4 border-b mb-6 pb-6">
                        <input type="text" name="nome_pet" placeholder="Nome do Pet" class="w-full p-2 border rounded-md" required>
                        <input type="text" name="especie" placeholder="Espécie (Ex: Cão, Gato)" class="w-full p-2 border rounded-md">
                        <input type="text" name="raca" placeholder="Raça" class="w-full p-2 border rounded-md">
                        <div>
                            <label class="text-sm text-gray-500">Data de Nascimento</label>
                            <input type="date" name="data_nascimento" class="w-full p-2 border rounded-md">
                        </div>
                        <button type="submit" name="add_pet" class="w-full bg-petBlue text-white py-2 rounded-md hover:bg-blue-700 font-semibold">Salvar Pet</button>
                    </form>

                    <div class="space-y-4">
                        <?php if(empty($pets)): ?>
                            <p class="text-gray-500 text-center py-4">Nenhum pet cadastrado.</p>
                        <?php else: foreach($pets as $pet): ?>
                            <div class="flex items-center p-3 border rounded-lg bg-gray-50">
                                <div class="text-petOrange mr-4"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path d="M11.25 4.5A6.75 6.75 0 004.5 11.25v.236c0 .43.236.822.617.994l5.223 2.612a2.25 2.25 0 002.32 0l5.223-2.612a1.125 1.125 0 00.617-.994v-.236A6.75 6.75 0 0011.25 4.5z" /><path fill-rule="evenodd" d="M6.035 16.118a1.125 1.125 0 00-1.002-1.933 11.21 11.21 0 00-2.02.433A2.25 2.25 0 00.75 16.852v.236a3.375 3.375 0 001.002 2.533 11.25 11.25 0 0014.496 0A3.375 3.375 0 0017.25 17.088v-.236a2.25 2.25 0 00-2.262-2.234 11.21 11.21 0 00-2.02-.433 1.125 1.125 0 00-1.002 1.933A12.723 12.723 0 0111.25 16.5a12.723 12.723 0 01-5.215-.382zM14.28 14.13a1.125 1.125 0 10-1.06-1.765 9.723 9.723 0 01-3.94 0 1.125 1.125 0 10-1.06 1.765 11.22 11.22 0 006.06 0z" clip-rule="evenodd" /></svg></div>
                                <div>
                                    <p class="font-bold text-petGray"><?= htmlspecialchars($pet['nome']) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($pet['raca'] ?? $pet['especie']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toast-notification');
    if (toast) { setTimeout(() => { toast.classList.remove('show'); }, 5000); }

    const togglePetBtn = document.getElementById('toggle-pet-form');
    const petForm = document.getElementById('pet-form');
    if(togglePetBtn && petForm) {
        togglePetBtn.addEventListener('click', () => petForm.classList.toggle('hidden'));
    }

    const toggleEnderecoBtn = document.getElementById('toggle-endereco-form');
    const enderecoForm = document.getElementById('endereco-form');
    if(toggleEnderecoBtn && enderecoForm) {
        toggleEnderecoBtn.addEventListener('click', () => enderecoForm.classList.toggle('hidden'));
    }

    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', (event) => {
            let value = event.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            event.target.value = value.slice(0, 9);
        });

        cepInput.addEventListener('blur', async (event) => {
            const cep = event.target.value.replace(/\D/g, '');
            if (cep.length === 8) {
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();
                    if (!data.erro) {
                        document.getElementById('rua').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        document.querySelector('input[name="numero"]').focus();
                    }
                } catch (error) { console.error("Erro ao buscar CEP:", error); }
            }
        });
    }
});
</script>

<?php require '../footer.php'; ?>