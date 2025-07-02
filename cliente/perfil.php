<?php
// ======================================================================
// PetSync - v5.0 (Lógica de Caminho Absoluto e Cópia de Imagem)
// ======================================================================

// 1. CONFIGURAÇÃO E FUNÇÕES
// ----------------------------------------------------------------------
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'check_cliente.php'; 

function resizeImage($source_path, $destination_path, $max_width, $max_height, $quality = 85) {
    list($width, $height, $type) = getimagesize($source_path);
    if (!$width || !$height) { return false; }
    $ratio = $width / $height;

    if ($max_width / $max_height > $ratio) {
        $new_width = $max_height * $ratio;
        $new_height = $max_height;
    } else {
        $new_height = $max_width / $ratio;
        $new_width = $max_width;
    }

    $src_img = null;
    switch ($type) {
        case IMAGETYPE_JPEG: $src_img = imagecreatefromjpeg($source_path); break;
        case IMAGETYPE_PNG: $src_img = imagecreatefrompng($source_path); break;
        case IMAGETYPE_GIF: $src_img = imagecreatefromgif($source_path); break;
        case IMAGETYPE_WEBP: $src_img = imagecreatefromwebp($source_path); break;
        default: return false;
    }
    if (!$src_img) return false;

    $dst_img = imagecreatetruecolor($new_width, $new_height);
    
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagecolortransparent($dst_img, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);
    }
    
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG: $success = imagejpeg($dst_img, $destination_path, $quality); break;
        case IMAGETYPE_PNG: $success = imagepng($dst_img, $destination_path, 9); break;
        case IMAGETYPE_GIF: $success = imagegif($dst_img, $destination_path); break;
        case IMAGETYPE_WEBP: $success = imagewebp($dst_img, $destination_path, $quality); break;
    }
    
    imagedestroy($src_img);
    imagedestroy($dst_img);
    return $success;
}

// 2. DEFINIÇÕES E LÓGICA DE BLOQUEIO
// ----------------------------------------------------------------------
$page_title = 'Meu Perfil - PetSync';
$id_usuario_logado = $_SESSION['usuario']['id'];
$ok = ''; $erro = '';

$stmt_check_user = $mysqli->prepare("SELECT id FROM agendamentos WHERE usuario_id = ? AND status IN ('Pendente', 'Confirmado') LIMIT 1");
$stmt_check_user->bind_param("i", $id_usuario_logado);
$stmt_check_user->execute();
$bloqueia_dados_pessoais = $stmt_check_user->get_result()->num_rows > 0;

$result_pets = $mysqli->query("SELECT DISTINCT pet_id FROM agendamentos WHERE usuario_id = $id_usuario_logado AND status IN ('Pendente', 'Confirmado') AND pet_id IS NOT NULL");
$pets_bloqueados_ids = array_column($result_pets->fetch_all(MYSQLI_ASSOC), 'pet_id');

$result_enderecos = $mysqli->query("SELECT DISTINCT endereco_id FROM agendamentos WHERE usuario_id = $id_usuario_logado AND status IN ('Pendente', 'Confirmado') AND endereco_id IS NOT NULL");
$enderecos_bloqueados_ids = array_column($result_enderecos->fetch_all(MYSQLI_ASSOC), 'endereco_id');

// 3. PROCESSAMENTO DO FORMULÁRIO (POST)
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_ok = true; 

    // --- DEFINIÇÃO DE CAMINHOS ABSOLUTOS ---
    $path_para_filesystem = $_SERVER['DOCUMENT_ROOT'] . '/petsync/Imagens/pets/';
    $target_dir_absolute = str_replace('/', DIRECTORY_SEPARATOR, $path_para_filesystem);

    if (isset($_POST['add_pet'])) {
        $nome_pet        = trim($_POST['nome_pet']);
        $especie         = trim($_POST['especie']);
        if ($especie === 'Outro(a)') { $especie = trim($_POST['outra_especie']); }
        $raca            = trim($_POST['raca']);
        if ($raca === 'Outro(a)') { $raca = trim($_POST['outra_raca']); }
        $data_nascimento = $_POST['data_nascimento'] ?: null;
        $foto_url        = null;

        // Se o usuário enviou uma foto, processa ela
        if (isset($_FILES['foto_pet']) && $_FILES['foto_pet']['error'] === 0) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext     = strtolower(pathinfo($_FILES['foto_pet']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $foto_url = uniqid('pet_') . '_' . time() . '.' . $ext;
                if (!resizeImage($_FILES['foto_pet']['tmp_name'], $target_dir_absolute . $foto_url, 800, 800)) {
                    $_SESSION['erro_msg'] = "Erro ao salvar imagem.";
                    $foto_url = null; $form_ok = false;
                }
            } else {
                $_SESSION['erro_msg'] = "Formato de arquivo não permitido.";
                $form_ok = false;
            }
        } 
        // Se não enviou, copia a imagem padrão para um novo arquivo único
        else {
            $default_source_file = '';
            switch (mb_strtolower($especie, 'UTF-8')) {
                case 'cão': case 'cachorro': $default_source_file = 'default_dog.png'; break;
                case 'gato': $default_source_file = 'default_cat.png'; break;
                default: $default_source_file = 'default_other.png'; break;
            }

            $source_path = $target_dir_absolute . $default_source_file;
            $new_filename = uniqid('pet_') . '_' . time() . '.png';
            $destination_path = $target_dir_absolute . $new_filename;

            if (file_exists($source_path) && is_readable($source_path) && copy($source_path, $destination_path)) {
                $foto_url = $new_filename;
            } else {
                $_SESSION['erro_msg'] = "Erro crítico: Imagem padrão não encontrada ou sem permissão para cópia.";
                $form_ok = false;
            }
        }

        if ($form_ok && !empty($nome_pet)) {
            $stmt = $mysqli->prepare("INSERT INTO pets (dono_id, nome, especie, raca, data_nascimento, foto_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $id_usuario_logado, $nome_pet, $especie, $raca, $data_nascimento, $foto_url);
            if ($stmt->execute()) {
                $_SESSION['ok_msg'] = "Pet adicionado com sucesso!";
            } else {
                $_SESSION['erro_msg'] = "Erro ao adicionar pet: " . $stmt->error;
            }
        } elseif (empty($nome_pet)) {
            $_SESSION['erro_msg'] = "O nome do pet é obrigatório.";
        }
    }
    elseif (isset($_POST['edit_pet'])) {
        if (in_array($_POST['pet_id'], $pets_bloqueados_ids)) {
            $_SESSION['erro_msg'] = "Este pet não pode ser editado pois está em um agendamento ativo.";
        } else {
            $pet_id          = $_POST['pet_id'];
            $nome_pet        = trim($_POST['nome_pet']);
            $especie         = $_POST['especie'];
            if ($especie === 'Outro(a)') { $especie = trim($_POST['outra_especie']); }
            $raca            = $_POST['raca'];
            if ($raca === 'Outro(a)') { $raca = trim($_POST['outra_raca']); }
            $data_nascimento = $_POST['data_nascimento'] ?: null;
            $foto_atual      = $_POST['foto_atual'];
            $foto_url        = $foto_atual;

            if (isset($_FILES['foto_pet']) && $_FILES['foto_pet']['error'] === 0) {
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext     = strtolower(pathinfo($_FILES['foto_pet']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $foto_url = uniqid('pet_') . '_' . time() . '.' . $ext;
                    if (resizeImage($_FILES['foto_pet']['tmp_name'], $target_dir_absolute . $foto_url, 800, 800)) {
                        if ($foto_atual && !in_array($foto_atual, ['default_dog.png', 'default_cat.png', 'default_other.png']) && file_exists($target_dir_absolute . $foto_atual)) {
                            @unlink($target_dir_absolute . $foto_atual);
                        }
                    } else {
                        $_SESSION['erro_msg'] = "Erro ao salvar nova imagem.";
                        $foto_url = $foto_atual; $form_ok = false;
                    }
                } else {
                     $_SESSION['erro_msg'] = "Formato de arquivo não permitido na edição.";
                     $form_ok = false;
                }
            } 
            
            if ($form_ok) {
                $stmt = $mysqli->prepare("UPDATE pets SET nome = ?, especie = ?, raca = ?, data_nascimento = ?, foto_url = ? WHERE id = ? AND dono_id = ?");
                $stmt->bind_param("sssssii", $nome_pet, $especie, $raca, $data_nascimento, $foto_url, $pet_id, $id_usuario_logado);
                if ($stmt->execute()) {
                    $_SESSION['ok_msg'] = "Pet atualizado com sucesso!";
                } else {
                    $_SESSION['erro_msg'] = "Erro ao atualizar pet: " . $stmt->error;
                }
            }
        }
    }
    elseif (isset($_POST['add_endereco'])) {
        $rua = trim($_POST['rua']); $numero = trim($_POST['numero']); $bairro = trim($_POST['bairro']);
        $complemento = trim($_POST['complemento']); $cidade = trim($_POST['cidade']); $estado = trim($_POST['estado']); $cep = trim($_POST['cep']);
        
        if (!empty($rua) && !empty($cidade)) {
            $stmt = $mysqli->prepare("INSERT INTO enderecos (usuario_id, rua, numero, complemento, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $id_usuario_logado, $rua, $numero, $complemento, $bairro, $cidade, $estado, $cep);
            if ($stmt->execute()) { $_SESSION['ok_msg'] = "Endereço adicionado com sucesso!"; } 
            else { $_SESSION['erro_msg'] = "Erro ao adicionar endereço: " . $stmt->error; }
        } else { $_SESSION['erro_msg'] = "Rua e Cidade são campos obrigatórios."; }
    }
    elseif (isset($_POST['edit_usuario'])) {
        if ($bloqueia_dados_pessoais) { $_SESSION['erro_msg'] = "Seus dados não podem ser editados pois você possui agendamentos ativos."; } 
        else {
            $nome = trim($_POST['nome']); $email = trim($_POST['email']); $telefone = trim($_POST['telefone']);
            if (!empty($nome) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $mysqli->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nome, $email, $telefone, $id_usuario_logado);
                if ($stmt->execute()) { $_SESSION['ok_msg'] = "Dados atualizados com sucesso!"; } 
                else { $_SESSION['erro_msg'] = "Erro ao atualizar dados: " . $stmt->error; }
            } else { $_SESSION['erro_msg'] = "Nome ou e-mail inválido."; }
        }
    }

    header("Location: perfil.php");
    exit;
}

// 4. PREPARAÇÃO PARA EXIBIÇÃO
// ----------------------------------------------------------------------
if (isset($_SESSION['ok_msg'])) { $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if (isset($_SESSION['erro_msg'])) { $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }

$usuario_info = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE id = $id_usuario_logado")->fetch_assoc();
$pets = $mysqli->query("SELECT * FROM pets WHERE dono_id = $id_usuario_logado ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$enderecos = $mysqli->query("SELECT * FROM enderecos WHERE usuario_id = $id_usuario_logado ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$path_url_imagens = '/petsync/Imagens/pets/';

require '../header.php';
?>
<style>
    .toast-item { animation: slideInRight 0.5s ease-out forwards, fadeOut 0.5s ease-in 4.5s forwards; }
    @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
    .modal { background-color: rgba(0, 0, 0, 0.5); transition: opacity 0.3s ease; }
    .modal-content { transform: scale(0.95); opacity: 0; transition: all 0.3s ease; }
    .modal.active .modal-content { transform: scale(1); opacity: 1; }
</style>

<div id="toast-container" class="fixed top-5 right-5 z-[100] w-full max-w-xs space-y-2">
    <?php if ($ok): ?><div class="toast-item bg-green-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="toast-item bg-red-500 text-white p-4 rounded-lg shadow-lg"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
</div>

<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Olá, <span class="text-blue-600"><?= htmlspecialchars(explode(' ', $usuario_info['nome'])[0]) ?></span>!</h1>
            <p class="text-lg text-gray-500">Bem-vindo(a) ao seu painel. Aqui você pode gerenciar seus dados.</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-semibold text-gray-700">Meus Dados</h2>
                        <button id="edit-usuario-btn" data-bloqueado="<?= $bloqueia_dados_pessoais ? 'true' : 'false' ?>" class="bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors text-sm">Editar</button>
                    </div>
                    <div class="space-y-3 text-gray-700">
                        <div><strong>Nome Completo:</strong> <span data-field="nome"><?= htmlspecialchars($usuario_info['nome']) ?></span></div>
                        <div><strong>E-mail:</strong> <span data-field="email"><?= htmlspecialchars($usuario_info['email']) ?></span></div>
                        <div><strong>Telefone:</strong> <span data-field="telefone"><?= htmlspecialchars($usuario_info['telefone'] ?? 'Não informado') ?></span></div>
                    </div>
                </div>
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-semibold text-gray-700">Meus Endereços</h2>
                        <button id="toggle-endereco-form" class="bg-orange-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors text-sm">Adicionar Novo</button>
                    </div>
                    <form id="add-endereco-form" action="perfil.php" method="POST" class="hidden p-4 my-4 border-t border-b space-y-4">
                        <h3 class="font-bold text-lg text-gray-600">Novo Endereço</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-1"><label class="text-sm font-medium text-gray-600">CEP</label><input type="text" id="add-endereco-cep" name="cep" class="w-full mt-1 p-2 border rounded-md"></div>
                            <div class="md:col-span-2"><label class="text-sm font-medium text-gray-600">Rua</label><input type="text" id="add-endereco-rua" name="rua" class="w-full mt-1 p-2 border rounded-md"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-sm font-medium text-gray-600">Número</label><input type="text" id="add-endereco-numero" name="numero" class="w-full mt-1 p-2 border rounded-md"></div>
                            <div><label class="text-sm font-medium text-gray-600">Bairro</label><input type="text" id="add-endereco-bairro" name="bairro" class="w-full mt-1 p-2 border rounded-md"></div>
                            <div><label class="text-sm font-medium text-gray-600">Complemento</label><input type="text" id="add-endereco-complemento" name="complemento" class="w-full mt-1 p-2 border rounded-md"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="text-sm font-medium text-gray-600">Cidade</label><input type="text" id="add-endereco-cidade" name="cidade" class="w-full mt-1 p-2 border rounded-md"></div>
                            <div><label class="text-sm font-medium text-gray-600">Estado</label><input type="text" id="add-endereco-estado" name="estado" maxlength="2" class="w-full mt-1 p-2 border rounded-md"></div>
                        </div>
                        <button type="submit" name="add_endereco" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 font-semibold">Salvar Endereço</button>
                    </form>
                    <div class="space-y-4">
                        <?php if(empty($enderecos)): ?> <p class="text-gray-500 text-center py-4">Nenhum endereço cadastrado.</p> <?php endif; ?>
                        <?php foreach($enderecos as $endereco): $is_blocked = in_array($endereco['id'], $enderecos_bloqueados_ids); ?>
                            <div class="flex justify-between items-center p-3 border rounded-lg <?= $is_blocked ? 'bg-yellow-50' : 'bg-gray-50' ?>">
                                <div class="flex-grow flex items-center text-gray-700">
                                    <?php if($is_blocked): ?><svg title="Bloqueado para edição" class="w-5 h-5 mr-2 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg><?php endif; ?>
                                    <p><?= htmlspecialchars($endereco['rua']) ?>, <?= htmlspecialchars($endereco['numero']) ?></p>
                                </div>
                                <button class="edit-endereco-btn text-blue-600 hover:text-blue-800 text-sm font-semibold ml-4 flex-shrink-0" 
                                        data-bloqueado="<?= $is_blocked ? 'true' : 'false' ?>" data-id="<?= $endereco['id'] ?>" data-rua="<?= htmlspecialchars($endereco['rua']) ?>" 
                                        data-numero="<?= htmlspecialchars($endereco['numero']) ?>" data-bairro="<?= htmlspecialchars($endereco['bairro']) ?>" 
                                        data-cidade="<?= htmlspecialchars($endereco['cidade']) ?>" data-estado="<?= htmlspecialchars($endereco['estado']) ?>" 
                                        data-cep="<?= htmlspecialchars($endereco['cep']) ?>" data-complemento="<?= htmlspecialchars($endereco['complemento']) ?>">
                                    Editar
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-semibold text-gray-700">Meus Pets</h2>
                        <button id="toggle-pet-form" class="bg-orange-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600 transition-colors text-sm">Adicionar Novo</button>
                    </div>
                    <form id="add-pet-form" action="perfil.php" method="POST" enctype="multipart/form-data" class="hidden p-4 my-4 border-t border-b space-y-4">
                        <h3 class="font-bold text-lg text-gray-600">Novo Pet</h3>
                        <div><label class="text-sm font-medium text-gray-600">Nome</label><input type="text" name="nome_pet" class="w-full mt-1 p-2 border rounded-md"></div>
                        <div><label class="text-sm font-medium text-gray-600">Espécie</label><select id="add-pet-especie" name="especie" class="w-full mt-1 p-2 border rounded-md"></select></div>
                        <div id="add-outra-especie-div" class="hidden"><label class="text-sm font-medium text-gray-600">Qual espécie?</label><input type="text" name="outra_especie" class="w-full mt-1 p-2 border rounded-md"></div>
                        <div><label class="text-sm font-medium text-gray-600">Raça</label><select id="add-pet-raca" name="raca" class="w-full mt-1 p-2 border rounded-md"></select></div>
                        <div id="add-outra-raca-div" class="hidden"><label class="text-sm font-medium text-gray-600">Qual raça?</label><input type="text" name="outra_raca" class="w-full mt-1 p-2 border rounded-md"></div>
                        <div><label class="text-sm font-medium text-gray-600">Data de Nascimento</label><input type="date" name="data_nascimento" class="w-full mt-1 p-2 border rounded-md"></div>
                        <div><label class="text-sm font-medium text-gray-600">Foto (Opcional)</label><input type="file" name="foto_pet" accept="image/*" class="w-full text-sm mt-1 border p-1 rounded-md"></div>
                        <button type="submit" name="add_pet" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 font-semibold">Salvar Pet</button>
                    </form>
                    <div class="space-y-4">
                        <?php if(empty($pets)): ?> <p class="text-gray-500 text-center py-4">Nenhum pet cadastrado.</p> <?php endif; ?>
                        <?php foreach($pets as $pet): 
                            $is_blocked = in_array($pet['id'], $pets_bloqueados_ids);
                            $image_src = $path_url_imagens . htmlspecialchars($pet['foto_url']) . '?v=' . time();
                        ?>
                            <div class="flex justify-between items-center p-3 border rounded-lg <?= $is_blocked ? 'bg-yellow-50' : 'bg-gray-50' ?>">
                                <div class="flex items-center text-gray-700">
                                    <img src="<?= $image_src ?>" alt="Foto de <?= htmlspecialchars($pet['nome']) ?>" class="h-12 w-12 rounded-full object-cover mr-3">
                                    <div><p class="font-bold"><?= htmlspecialchars($pet['nome']) ?></p><p class="text-sm text-gray-500"><?= htmlspecialchars($pet['raca'] ?: $pet['especie']) ?></p></div>
                                </div>
                                <button class="edit-pet-btn text-blue-600 hover:text-blue-800 text-sm font-semibold flex-shrink-0"
                                        data-bloqueado="<?= $is_blocked ? 'true' : 'false' ?>" data-id="<?= $pet['id'] ?>" data-nome="<?= htmlspecialchars($pet['nome']) ?>"
                                        data-especie="<?= htmlspecialchars($pet['especie']) ?>" data-raca="<?= htmlspecialchars($pet['raca']) ?>"
                                        data-nascimento="<?= htmlspecialchars($pet['data_nascimento']) ?>" data-foto_url="<?= htmlspecialchars($pet['foto_url']) ?>">
                                    Editar
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="edit-usuario-modal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="modal-bg absolute inset-0" onclick="this.closest('.modal').querySelector('.close-modal-btn').click()"></div>
    <div class="modal-content relative bg-gray-100 w-full max-w-lg rounded-xl shadow-lg mx-auto">
        <div class="bg-white p-4 rounded-t-xl flex justify-between items-center border-b">
            <h3 class="text-xl font-bold text-gray-700 flex items-center"><svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>Editar Dados Pessoais</h3>
            <button class="close-modal-btn text-gray-400 hover:text-gray-600 font-bold text-2xl leading-none">&times;</button>
        </div>
        <form action="perfil.php" method="POST" class="p-6 space-y-4">
            <div><label for="edit-usuario-nome" class="text-sm font-medium text-gray-600">Nome Completo</label><input type="text" name="nome" id="edit-usuario-nome" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
            <div><label for="edit-usuario-email" class="text-sm font-medium text-gray-600">E-mail</label><input type="email" name="email" id="edit-usuario-email" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
            <div><label for="edit-usuario-telefone" class="text-sm font-medium text-gray-600">Telefone</label><input type="tel" name="telefone" id="edit-usuario-telefone" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="(99) 99999-9999"></div>
            <div class="flex justify-end pt-4"><button type="button" class="close-modal-btn bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg mr-2 hover:bg-gray-300">Cancelar</button><button type="submit" name="edit_usuario" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">Salvar Alterações</button></div>
        </form>
    </div>
</div>

<div id="edit-endereco-modal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="modal-bg absolute inset-0" onclick="this.closest('.modal').querySelector('.close-modal-btn').click()"></div>
    <div class="modal-content relative bg-gray-100 w-full max-w-2xl rounded-xl shadow-lg mx-auto">
        <div class="bg-white p-4 rounded-t-xl flex justify-between items-center border-b">
            <h3 class="text-xl font-bold text-gray-700 flex items-center"><svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>Editar Endereço</h3>
            <button class="close-modal-btn text-gray-400 hover:text-gray-600 font-bold text-2xl leading-none">&times;</button>
        </div>
        <form action="perfil.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="endereco_id" id="edit-endereco-id">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1"><label class="text-sm font-medium text-gray-600">CEP</label><input type="text" id="edit-endereco-cep" name="cep" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
                <div class="md:col-span-2"><label class="text-sm font-medium text-gray-600">Rua</label><input type="text" id="edit-endereco-rua" name="rua" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="text-sm font-medium text-gray-600">Número</label><input type="text" id="edit-endereco-numero" name="numero" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="text-sm font-medium text-gray-600">Bairro</label><input type="text" id="edit-endereco-bairro" name="bairro" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="text-sm font-medium text-gray-600">Complemento</label><input type="text" id="edit-endereco-complemento" name="complemento" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-600">Cidade</label><input type="text" id="edit-endereco-cidade" name="cidade" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="text-sm font-medium text-gray-600">Estado</label><input type="text" id="edit-endereco-estado" name="estado" maxlength="2" class="w-full mt-1 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></div>
            </div>
            <div class="flex justify-end pt-4"><button type="button" class="close-modal-btn bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg mr-2 hover:bg-gray-300">Cancelar</button><button type="submit" name="edit_endereco" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">Salvar Alterações</button></div>
        </form>
    </div>
</div>

<div id="edit-pet-modal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="modal-bg absolute inset-0" onclick="this.closest('.modal').querySelector('.close-modal-btn').click()"></div>
    <div class="modal-content relative bg-gray-100 w-full max-w-2xl rounded-xl shadow-lg mx-auto">
        <div class="bg-white p-4 rounded-t-xl flex justify-between items-center border-b">
            <h3 class="text-xl font-bold text-gray-700 flex items-center"><svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z" /><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" /></svg>Editar Pet</h3>
            <button class="close-modal-btn text-gray-400 hover:text-gray-600 font-bold text-2xl leading-none">&times;</button>
        </div>
        <form action="perfil.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="pet_id" id="edit-pet-id"><input type="hidden" name="foto_atual" id="edit-pet-foto-atual">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-600">Nome</label><input type="text" name="nome_pet" id="edit-pet-nome" class="w-full mt-1 p-2 border rounded-md"></div>
                <div><label class="text-sm font-medium text-gray-600">Data de Nascimento</label><input type="date" name="data_nascimento" id="edit-pet-nascimento" class="w-full mt-1 p-2 border rounded-md"></div>
                <div><label class="text-sm font-medium text-gray-600">Espécie</label><select id="edit-pet-especie" name="especie" class="w-full mt-1 p-2 border rounded-md"></select></div>
                <div id="edit-outra-especie-div" class="hidden"><label class="text-sm font-medium text-gray-600">Qual espécie?</label><input type="text" id="edit-outra-especie-input" name="outra_especie" class="w-full mt-1 p-2 border rounded-md"></div>
                <div><label class="text-sm font-medium text-gray-600">Raça</label><select id="edit-pet-raca" name="raca" class="w-full mt-1 p-2 border rounded-md"></select></div>
                <div id="edit-outra-raca-div" class="hidden"><label class="text-sm font-medium text-gray-600">Qual raça?</label><input type="text" id="edit-outra-raca-input" name="outra_raca" class="w-full mt-1 p-2 border rounded-md"></div>
                <div class="md:col-span-2"><label class="text-sm font-medium text-gray-600">Alterar Foto (Opcional)</label><input type="file" name="foto_pet" accept="image/*" class="w-full text-sm mt-1 border p-1 rounded-md"></div>
            </div>
            <div class="flex justify-end pt-4"><button type="button" class="close-modal-btn bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg mr-2 hover:bg-gray-300">Cancelar</button><button type="submit" name="edit_pet" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">Salvar Alterações</button></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // INÍCIO DA CORREÇÃO: Remover toasts gerados pelo servidor após a animação
    const serverToasts = document.querySelectorAll('#toast-container .toast-item');
    if (serverToasts.length > 0) {
        setTimeout(() => {
            serverToasts.forEach(toast => toast.remove());
        }, 5000); // Corresponde à duração total da animação CSS (4.5s + 0.5s)
    }
    // FIM DA CORREÇÃO

    // FUNÇÕES GLOBAIS
    const showToast = (message, type = 'alert') => {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'toast-item p-4 rounded-lg shadow-lg';
        toast.textContent = message;
        switch (type) {
            case 'success': toast.classList.add('bg-green-500', 'text-white'); break;
            case 'info': toast.classList.add('bg-blue-500', 'text-white'); break;
            case 'alert': default: toast.classList.add('bg-yellow-400', 'text-yellow-800'); break;
        }
        container.prepend(toast);
        setTimeout(() => { if (toast) toast.remove(); }, 5000);
    };

    const openModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden');
            setTimeout(() => modal.classList.add('active'), 10);
        }
    };

    const closeModal = (modal) => {
        if (!modal) return; 
        modal.classList.remove('active');
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300); 
    };

    const handleEditClick = (event, openModalCallback) => {
        if (event.currentTarget.dataset.bloqueado === 'true') { showToast('Item bloqueado. Para editar, finalize ou cancele o agendamento associado.', 'alert'); } 
        else { openModalCallback(event.currentTarget.dataset); }
    };

    // SETUP DOS EVENTOS
    document.querySelectorAll('.modal').forEach(modal => {
        modal.querySelectorAll('.close-modal-btn').forEach(btn => btn.addEventListener('click', () => closeModal(modal)));
    });

    document.getElementById('toggle-pet-form')?.addEventListener('click', () => {
        document.getElementById('add-pet-form').classList.toggle('hidden');
    });

    document.getElementById('toggle-endereco-form')?.addEventListener('click', () => {
        document.getElementById('add-endereco-form').classList.toggle('hidden');
    });

    document.getElementById('edit-usuario-telefone')?.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, '').substring(0,11);
        v = v.replace(/^(\d{2})(\d)/g, '($1) $2');
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
        e.target.value = v;
    });

    // LÓGICA DE ESPÉCIE/RAÇA GENÉRICA
    const petData = { 'Cão': ['SRD (Vira-lata)', 'Shih Tzu', 'Yorkshire', 'Poodle', 'Golden Retriever', 'Outro(a)'], 'Gato': ['SRD (Vira-lata)', 'Siamês', 'Persa', 'Outro(a)'], 'Outro(a)': [] };
    const setupPetSelectors = (formPrefix) => {
        const especieSelect = document.getElementById(`${formPrefix}-pet-especie`);
        const racaSelect = document.getElementById(`${formPrefix}-pet-raca`);
        const outraEspecieDiv = document.getElementById(`${formPrefix}-outra-especie-div`);
        const outraRacaDiv = document.getElementById(`${formPrefix}-outra-raca-div`);
        if (!especieSelect) return;

        const populateSpecies = () => {
            especieSelect.innerHTML = '<option value="">-- Selecione --</option>';
            Object.keys(petData).forEach(e => especieSelect.add(new Option(e, e)));
        };
        especieSelect.addEventListener('change', () => {
            const especie = especieSelect.value;
            racaSelect.innerHTML = '<option value="">-- Selecione --</option>';
            racaSelect.disabled = true;
            if(outraEspecieDiv) outraEspecieDiv.classList.toggle('hidden', especie !== 'Outro(a)');
            if(outraRacaDiv) outraRacaDiv.classList.add('hidden');
            if (petData[especie] && petData[especie].length > 0) {
                racaSelect.disabled = false;
                petData[especie].forEach(r => racaSelect.add(new Option(r, r)));
            }
        });
        racaSelect.addEventListener('change', () => {
            if(outraRacaDiv) outraRacaDiv.classList.toggle('hidden', racaSelect.value !== 'Outro(a)');
        });
        populateSpecies();
    };
    setupPetSelectors('add');
    setupPetSelectors('edit');

    // LÓGICA DE CEP GENÉRICA
    const setupCepListener = (formPrefix) => {
        const cepInput = document.getElementById(`${formPrefix}-endereco-cep`);
        if (!cepInput) return;
        
        cepInput.addEventListener('blur', async (e) => {
            const cep = e.target.value.replace(/\D/g, '');
            if (cep.length !== 8) return;
            showToast('Buscando CEP...', 'info');
            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();
                const form = cepInput.closest('form');
                if (data.erro) { showToast('CEP não encontrado.', 'alert'); }
                else {
                    form.querySelector(`[name="rua"]`).value = data.logradouro || '';
                    form.querySelector(`[name="bairro"]`).value = data.bairro || '';
                    form.querySelector(`[name="cidade"]`).value = data.localidade || '';
                    form.querySelector(`[name="estado"]`).value = data.uf || '';
                    form.querySelector(`[name="numero"]`).focus();
                }
            } catch (error) { showToast('Erro ao buscar o CEP.', 'alert'); }
        });
    };
    setupCepListener('add');
    setupCepListener('edit');
    
    // EVENT LISTENERS PARA ABRIR MODAIS DE EDIÇÃO
    document.getElementById('edit-usuario-btn')?.addEventListener('click', (e) => {
        handleEditClick(e, (data) => {
            const modal = document.getElementById('edit-usuario-modal');
            modal.querySelector('#edit-usuario-nome').value = document.querySelector('span[data-field="nome"]').textContent;
            modal.querySelector('#edit-usuario-email').value = document.querySelector('span[data-field="email"]').textContent;
            const phoneSpan = document.querySelector('span[data-field="telefone"]');
            modal.querySelector('#edit-usuario-telefone').value = phoneSpan.textContent.includes('Não informado') ? '' : phoneSpan.textContent;
            openModal('edit-usuario-modal');
        });
    });

    document.querySelectorAll('.edit-endereco-btn').forEach(btn => btn.addEventListener('click', (e) => {
        handleEditClick(e, (data) => {
            const modal = document.getElementById('edit-endereco-modal');
            modal.querySelector('#edit-endereco-id').value = data.id;
            modal.querySelector('#edit-endereco-rua').value = data.rua;
            modal.querySelector('#edit-endereco-numero').value = data.numero;
            modal.querySelector('#edit-endereco-bairro').value = data.bairro;
            modal.querySelector('#edit-endereco-cidade').value = data.cidade;
            modal.querySelector('#edit-endereco-estado').value = data.estado;
            modal.querySelector('#edit-endereco-cep').value = data.cep;
            modal.querySelector('#edit-endereco-complemento').value = data.complemento;
            openModal('edit-endereco-modal');
        });
    }));

    document.querySelectorAll('.edit-pet-btn').forEach(btn => btn.addEventListener('click', (e) => {
        handleEditClick(e, (data) => {
            const modal = document.getElementById('edit-pet-modal');
            modal.querySelector('#edit-pet-id').value = data.id;
            modal.querySelector('#edit-pet-foto-atual').value = data.foto_url;
            modal.querySelector('#edit-pet-nome').value = data.nome;
            modal.querySelector('#edit-pet-nascimento').value = data.nascimento;
            
            const especieSelect = document.getElementById('edit-pet-especie');
            const racaSelect = document.getElementById('edit-pet-raca');
            const outraEspecieInput = document.getElementById('edit-outra-especie-input');
            const outraRacaInput = document.getElementById('edit-outra-raca-input');
            
            if (Object.keys(petData).includes(data.especie)) {
                especieSelect.value = data.especie;
            } else {
                especieSelect.value = 'Outro(a)';
                outraEspecieInput.value = data.especie;
            }
            especieSelect.dispatchEvent(new Event('change'));

            setTimeout(() => {
                const racasDisponiveis = Array.from(racaSelect.options).map(opt => opt.value);
                if (racasDisponiveis.includes(data.raca)) {
                    racaSelect.value = data.raca;
                } else if (data.raca) {
                    racaSelect.value = 'Outro(a)';
                    outraRacaInput.value = data.raca;
                }
                racaSelect.dispatchEvent(new Event('change'));
            }, 50);

            openModal('edit-pet-modal');
        });
    }));
});
</script>

<?php require '../footer.php'; ?>