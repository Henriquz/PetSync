<?php
// ======================================================================
// PetSync - Gerenciamento da Galeria v2.3 (Correção de Foco)
// ======================================================================
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) { header('Location: ../login.php?erro=acesso_negado'); exit; }

$ok = '';
$erro = '';

// Lógica para DELETAR uma foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_foto_id'])) {
    $foto_id = (int)$_POST['delete_foto_id'];
    $stmt = $mysqli->prepare("SELECT url_imagem FROM galeria WHERE id = ?");
    $stmt->bind_param("i", $foto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $foto = $result->fetch_assoc();
        if (file_exists('../' . $foto['url_imagem'])) {
            unlink('../' . $foto['url_imagem']);
        }
        $delete_stmt = $mysqli->prepare("DELETE FROM galeria WHERE id = ?");
        $delete_stmt->bind_param("i", $foto_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $_SESSION['ok_msg'] = "Foto removida com sucesso!";
    }
    $stmt->close();
    header("Location: gerenciar_galeria.php");
    exit;
}

// Lógica para ENVIAR uma nova foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagem'])) {
    $nome_pet = trim($_POST['nome_pet'] ?? '');
    $legenda = trim($_POST['legenda'] ?? '');

    if ($_FILES['imagem']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['imagem']['type'], $allowed_types) || $_FILES['imagem']['size'] > 5242880) { // 5MB Limit
            $erro = "Arquivo inválido! Apenas imagens JPG, PNG ou GIF de até 5MB são permitidas.";
        } else {
            $upload_dir = '../uploads/galeria/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            $file_ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('galeria_', true) . '.' . $file_ext;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_dir . $new_filename)) {
                $imagem_url = 'uploads/galeria/' . $new_filename;
                $stmt = $mysqli->prepare("INSERT INTO galeria (url_imagem, nome_pet, legenda) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $imagem_url, $nome_pet, $legenda);
                if ($stmt->execute()) {
                    $_SESSION['ok_msg'] = "Foto adicionada à galeria com sucesso!";
                } else {
                    $erro = "Erro ao salvar informações no banco de dados.";
                }
                $stmt->close();
            } else {
                $erro = "Erro ao mover o arquivo para o servidor.";
            }
        }
    } else {
        $erro = "Nenhum arquivo de imagem foi selecionado.";
    }
    header("Location: gerenciar_galeria.php");
    exit;
}

if(isset($_SESSION['ok_msg'])){ $ok = $_SESSION['ok_msg']; unset($_SESSION['ok_msg']); }
if(isset($_SESSION['erro_msg'])){ $erro = $_SESSION['erro_msg']; unset($_SESSION['erro_msg']); }

// Paginação das fotos existentes
$itens_por_pagina = 8;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$total_fotos = $mysqli->query("SELECT COUNT(*) FROM galeria")->fetch_row()[0];
$total_paginas = ceil($total_fotos / $itens_por_pagina);
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$fotos = $mysqli->query("SELECT * FROM galeria ORDER BY data_upload DESC LIMIT $itens_por_pagina OFFSET $offset");
$page_title = "Gerenciar Galeria";
require '../header.php';
?>

<style>
    #drop-area {
        border: 3px dashed #cbd5e1;
        transition: border-color 0.3s, background-color 0.3s;
    }
    #drop-area.drag-over {
        border-color: #0078C8; /* petBlue */
        background-color: #eff6ff; /* blue-50 */
    }
    #image-preview {
        max-width: 100%;
        max-height: 200px;
        object-fit: contain;
    }
</style>

<main class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-petGray mb-6">Gerenciar Galeria</h1>

        <?php if ($ok): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($ok) ?></p></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($erro) ?></p></div><?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-semibold text-petGray mb-4">Adicionar Nova Foto</h2>
            <form id="upload-form" action="gerenciar_galeria.php" method="POST" enctype="multipart/form-data">
                <div id="drop-area" class="rounded-lg p-8 text-center cursor-pointer">
                    <div id="upload-prompt">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-4-4V7a4 4 0 014-4h.5a3.5 3.5 0 017 0H17a4 4 0 014 4v5a4 4 0 01-4 4H7z"></path></svg>
                        <p class="mt-2 text-sm text-gray-600">
                            <span class="font-semibold text-petBlue">Clique para selecionar</span> ou arraste e solte a imagem aqui
                        </p>
                        <p class="text-xs text-gray-500 mt-1">PNG, JPG ou GIF de até 5MB</p>
                    </div>
                    <div id="preview-area" class="hidden">
                        <img id="image-preview" src="#" alt="Preview da imagem" class="mx-auto rounded-md mb-4"/>
                        <p id="file-info" class="text-sm text-gray-800 font-medium"></p>
                    </div>
                    <input type="file" name="imagem" id="imagem" class="hidden" accept="image/png, image/jpeg, image/gif">
                </div>
                
                <div id="details-area" class="hidden mt-6 space-y-4">
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nome_pet" class="block text-sm font-medium text-gray-700">Nome do Pet (opcional)</label>
                            <textarea name="nome_pet" id="nome_pet" rows="1" class="mt-1 w-full form-input rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:border-petBlue focus:ring-petBlue"></textarea>
                        </div>
                        <div>
                            <label for="legenda" class="block text-sm font-medium text-gray-700">Legenda (opcional)</label>
                            <textarea name="legenda" id="legenda" rows="2" class="mt-1 w-full form-input rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:border-petBlue focus:ring-petBlue"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-4">
                        <button type="button" id="cancel-btn" class="px-6 py-2 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-petBlue hover:bg-blue-800">Enviar Foto</button>
                    </div>
                </div>
            </form>
        </div>

        <h2 class="text-2xl font-semibold text-petGray mb-4">Fotos na Galeria</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php if ($fotos->num_rows > 0): mysqli_data_seek($fotos, 0); ?>
                <?php while($foto = $fotos->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden group relative">
                        <img src="/petsync/<?= htmlspecialchars($foto['url_imagem']) ?>" alt="<?= htmlspecialchars($foto['legenda']) ?>" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <p class="font-bold text-petGray truncate"><?= htmlspecialchars($foto['nome_pet'] ?: 'Sem nome') ?></p>
                            <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($foto['legenda'] ?: 'Sem legenda') ?></p>
                        </div>
                        <div class="absolute top-2 right-2">
                            <form action="gerenciar_galeria.php" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar esta foto? Esta ação não pode ser desfeita.');">
                                <input type="hidden" name="delete_foto_id" value="<?= $foto['id'] ?>">
                                <button type="submit" class="bg-red-600 text-white rounded-full p-2 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="col-span-full text-center text-gray-500">Nenhuma foto na galeria ainda.</p>
            <?php endif; ?>
        </div>

        <?php if($total_paginas > 1): ?>
            <nav class="mt-8 flex justify-center">
                 <a href="?pagina=<?= $pagina_atual - 1 ?>" class="<?= $pagina_atual <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-700 bg-white hover:bg-gray-50">Anterior</a>
                 <a href="?pagina=<?= $pagina_atual + 1 ?>" class="<?= $pagina_atual >= $total_paginas ? 'opacity-50 cursor-not-allowed' : '' ?> -ml-px relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-white hover:bg-gray-50">Próxima</a>
            </nav>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('imagem');
    const uploadPrompt = document.getElementById('upload-prompt');
    const previewArea = document.getElementById('preview-area');
    const imagePreview = document.getElementById('image-preview');
    const fileInfo = document.getElementById('file-info');
    const detailsArea = document.getElementById('details-area');
    const cancelBtn = document.getElementById('cancel-btn');

    dropArea.addEventListener('click', () => fileInput.click());

    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropArea.classList.add('drag-over');
    });
    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('drag-over');
    });
    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
            handleFile(files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            handleFile(fileInput.files[0]);
        }
    });

    const handleFile = (file) => {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Erro: Formato de arquivo não permitido. Use JPG, PNG ou GIF.');
            resetUploader();
            return;
        }
        if (file.size > 5 * 1024 * 1024) { // 5MB
            alert('Erro: O arquivo é muito grande. O limite é 5MB.');
            resetUploader();
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            imagePreview.src = reader.result;
            uploadPrompt.classList.add('hidden');
            previewArea.classList.remove('hidden');
            detailsArea.classList.remove('hidden');
            fileInfo.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        }
        reader.readAsDataURL(file);
    };

    const resetUploader = () => {
        fileInput.value = '';
        uploadPrompt.classList.remove('hidden');
        previewArea.classList.add('hidden');
        detailsArea.classList.add('hidden');
        imagePreview.src = '#';
        fileInfo.textContent = '';
    }
    
    cancelBtn.addEventListener('click', resetUploader);
});
</script>

<?php 
require '../footer.php'; 
?>