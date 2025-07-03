<?php
// ======================================================================
// PetSync - Disparo de Avisos v4.0 (Histórico Refinado)
// ======================================================================
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) { header('Location: ../login.php?erro=acesso_negado'); exit; }

$ok = '';
$erro = '';
$limite_chars = 255;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinatario_id = $_POST['destinatario'] ?? null;
    $mensagem = trim($_POST['mensagem'] ?? '');
    $imagem_url = null;

    if (empty($destinatario_id) || empty($mensagem)) {
        $erro = "Por favor, selecione um destinatário e escreva uma mensagem.";
    } elseif (mb_strlen($mensagem) > $limite_chars) {
        $erro = "A mensagem excede o limite de $limite_chars caracteres.";
    } else {
        // Lógica de Upload de Imagem
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['imagem']['type'];
            $file_size = $_FILES['imagem']['size'];

            if (!in_array($file_type, $allowed_types)) {
                $erro = "Formato de imagem inválido. Apenas JPG, PNG e GIF são permitidos.";
            } elseif ($file_size > 2097152) { // 2MB
                $erro = "O arquivo de imagem é muito grande. O limite é 2MB.";
            } else {
                $upload_dir = '../uploads/notificacoes/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                $file_ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('notif_', true) . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_path)) {
                    $imagem_url = 'uploads/notificacoes/' . $new_filename;
                } else {
                    $erro = "Ocorreu um erro ao salvar a imagem.";
                }
            }
        }

        // Se não houve erro no upload, continua para enviar a notificação
        if (empty($erro)) {
            if ($destinatario_id === 'todos') {
                $result = $mysqli->query("SELECT id FROM usuarios WHERE is_admin = 0");
                $clientes = $result->fetch_all(MYSQLI_ASSOC);
                foreach ($clientes as $cliente) {
                    criar_notificacao($mysqli, $cliente['id'], $mensagem, '', 'alerta', $imagem_url);
                }
                $ok = "Alerta enviado para todos os clientes!";
            } else {
                criar_notificacao($mysqli, $destinatario_id, $mensagem, '', 'alerta', $imagem_url);
                $ok = "Alerta enviado com sucesso!";
            }
        }
    }
}

$clientes = $mysqli->query("SELECT id, nome FROM usuarios WHERE is_admin = 0 ORDER BY nome ASC");
$page_title = "Disparar Alertas";

// LÓGICA OTIMIZADA PARA BUSCAR HISTÓRICO E DESTINATÁRIOS
$itens_por_pagina = 6;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$count_result = $mysqli->query("SELECT COUNT(DISTINCT mensagem, imagem_url) FROM notificacoes WHERE tipo = 'alerta'");
$total_alertas = $count_result->fetch_row()[0];
$total_paginas = ceil($total_alertas / $itens_por_pagina);
if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Ajuste na query para pegar um ID de referência estável para o grupo
$query_historico = "SELECT MIN(id) as id, mensagem, imagem_url, MAX(data_criacao) as ultima_data 
                    FROM notificacoes 
                    WHERE tipo = 'alerta' 
                    GROUP BY mensagem, imagem_url 
                    ORDER BY ultima_data DESC 
                    LIMIT ? OFFSET ?";
$stmt_historico = $mysqli->prepare($query_historico);
$stmt_historico->bind_param("ii", $itens_por_pagina, $offset);
$stmt_historico->execute();
$historico_alertas = $stmt_historico->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_historico->close();

$destinatarios_por_alerta = [];
if (!empty($historico_alertas)) {
    $mensagens = array_column($historico_alertas, 'mensagem');
    if(!empty($mensagens)){
        $placeholders = implode(',', array_fill(0, count($mensagens), '?'));
        $types = str_repeat('s', count($mensagens));

        $query_destinatarios = "
            SELECT n.mensagem, u.nome, n.lida 
            FROM notificacoes n 
            JOIN usuarios u ON n.usuario_id = u.id 
            WHERE n.mensagem IN ($placeholders) AND n.tipo = 'alerta'";
        $stmt_dest = $mysqli->prepare($query_destinatarios);
        $stmt_dest->bind_param($types, ...$mensagens);
        $stmt_dest->execute();
        $destinatarios_raw = $stmt_dest->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_dest->close();

        foreach ($destinatarios_raw as $dest) {
            $destinatarios_por_alerta[$dest['mensagem']][] = ['nome' => $dest['nome'], 'lida' => $dest['lida']];
        }
    }
}


require '../header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
    .ts-control { padding: 0.5rem 0.75rem !important; border-radius: 0.375rem !important; border: 1px solid #d1d5db !important; }
    #image-preview-container { width: 100%; height: 150px; border: 2px dashed #d1d5db; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; background-color: #f9fafb; position: relative; overflow: hidden; }
    #image-preview { max-width: 100%; max-height: 100%; object-fit: cover; }
    #image-preview-text { color: #6b7280; }
    .recipient-list { max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; }
    .recipient-list.open { max-height: 200px; overflow-y: auto; }
    .toggle-arrow { transition: transform 0.3s ease; }
</style>

<main class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-petGray mb-6">Disparar Alerta para Clientes</h1>
        
        <?php if ($ok): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($ok) ?></p></div><?php endif; ?>
        <?php if ($erro): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($erro) ?></p></div><?php endif; ?>

        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md">
            <form id="alert-form" action="disparar_aviso.php" method="POST" enctype="multipart/form-data">
                <div class="space-y-6">
                    <div>
                        <label for="destinatario" class="block text-sm font-medium text-gray-700 mb-1">Enviar Para:</label>
                        <select name="destinatario" id="destinatario" required>
                            <option value="">Selecione um cliente ou todos...</option>
                            <option value="todos">*** TODOS OS CLIENTES ***</option>
                            <?php mysqli_data_seek($clientes, 0); while($cliente = $clientes->fetch_assoc()): ?>
                                <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label for="mensagem" class="block text-sm font-medium text-gray-700">Mensagem do Alerta:</label>
                        <div class="relative mt-1">
                            <textarea name="mensagem" id="mensagem" rows="5" class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-petBlue focus:border-petBlue" required maxlength="<?= $limite_chars ?>"></textarea>
                            <div id="char-counter" class="absolute bottom-2 right-2 text-xs text-gray-400">0 / <?= $limite_chars ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="imagem" class="block text-sm font-medium text-gray-700">Anexar Imagem (Opcional):</label>
                        <input type="file" name="imagem" id="imagem" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-petBlue hover:file:bg-blue-100">
                        <div id="image-preview-container" class="mt-4">
                            <span id="image-preview-text">Pré-visualização da imagem</span>
                            <img id="image-preview" class="hidden" src="" alt="Preview da imagem">
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t pt-6 flex justify-end">
                    <button type="submit" id="submit-btn" class="inline-flex items-center px-6 py-2.5 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-petOrange hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-petOrange">
                        <span id="btn-text">Enviar Alerta</span>
                        <svg id="btn-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-12">
            <h2 class="text-2xl font-bold text-petGray mb-4 border-b pb-2">Histórico de Alertas Enviados</h2>
            <?php if (empty($historico_alertas)): ?>
                <p class="text-center text-gray-500 bg-white p-6 rounded-lg shadow-md">Nenhum alerta foi enviado ainda.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($historico_alertas as $alerta): ?>
                        <?php
                            $todos_destinatarios = $destinatarios_por_alerta[$alerta['mensagem']] ?? [];
                            $total_destinatarios_count = count($todos_destinatarios);
                            $destinatarios_que_viram = array_filter($todos_destinatarios, fn($d) => $d['lida']);
                            $viram_count = count($destinatarios_que_viram);
                        ?>
                        <div class="bg-white rounded-lg shadow-md flex flex-col">
                            <div class="p-4 flex-grow">
                                <p class="text-gray-800 text-sm mb-3"><?= nl2br(htmlspecialchars($alerta['mensagem'])) ?></p>
                                <?php if ($alerta['imagem_url']): ?>
                                    <button type="button" class="view-image-btn text-xs inline-flex items-center gap-2 font-semibold text-petBlue hover:underline" data-img-src="/petsync/<?= htmlspecialchars($alerta['imagem_url']) ?>">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 00.75-.75v-3.69l-2.72-2.72a.75.75 0 00-1.06 0L11.5 12.25l-1.72-1.72a.75.75 0 00-1.06 0l-2.97 2.97zM12 7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" /></svg>
                                        Ver Imagem
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="border-t bg-gray-50 px-4 py-3 text-xs text-gray-500">
                                Enviado em: <?= (new DateTime($alerta['ultima_data']))->format('d/m/Y \à\s H:i') ?>
                            </div>
                            <div class="border-t">
                                <button type="button" class="recipient-toggle-btn w-full flex justify-between items-center p-3 text-sm font-medium text-left text-gray-600 hover:bg-gray-100">
                                    <span>Destinatários (<?= $total_destinatarios_count ?>)</span>
                                    <svg class="w-5 h-5 toggle-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div class="recipient-list bg-gray-100 border-t border-gray-200">
                                    <div class="px-3 pt-2 pb-1 text-xs font-bold text-gray-600">
                                        <?= $viram_count ?> de <?= $total_destinatarios_count ?> cliente(s) visualizaram:
                                    </div>
                                    <ul class="divide-y divide-gray-200 px-3 py-2">
                                        <?php if(empty($destinatarios_que_viram)): ?>
                                            <li class="py-2 text-sm text-center text-gray-500">Ninguém visualizou ainda.</li>
                                        <?php else: ?>
                                            <?php foreach ($destinatarios_que_viram as $dest): ?>
                                                <li class="py-2 flex items-center justify-between text-sm">
                                                    <span class="text-gray-700"><?= htmlspecialchars($dest['nome']) ?></span>
                                                    <svg class="w-5 h-5 text-green-500" title="Visualizado" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.18l.879-1.528A1.65 1.65 0 013.25 6H16.75a1.65 1.65 0 011.707 1.882l.88 1.528a1.65 1.65 0 010 1.18l-.879 1.528A1.65 1.65 0 0116.75 14H3.25a1.65 1.65 0 01-1.707-1.882L.664 10.59zM17 10a7 7 0 11-14 0 7 7 0 0114 0z" clip-rule="evenodd" /></svg>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if($total_paginas > 1): ?>
                <nav class="mt-8 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:justify-end">
                        <a href="?pagina=<?= $pagina_atual - 1 ?>" class="<?= $pagina_atual <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Anterior</a>
                        <a href="?pagina=<?= $pagina_atual + 1 ?>" class="<?= $pagina_atual >= $total_paginas ? 'opacity-50 cursor-not-allowed' : '' ?> ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Próxima</a>
                    </div>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new TomSelect('#destinatario', { create: false, sortField: { field: "text", direction: "asc" } });

    const mensagemTextarea = document.getElementById('mensagem');
    const charCounter = document.getElementById('char-counter');
    const limite = <?= $limite_chars ?>;
    mensagemTextarea.addEventListener('input', () => {
        const count = mensagemTextarea.value.length;
        charCounter.textContent = `${count} / ${limite}`;
        charCounter.classList.toggle('text-red-500', count > limite);
    });

    const imageInput = document.getElementById('imagem');
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewText = document.getElementById('image-preview-text');
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) { const reader = new FileReader(); imagePreviewText.classList.add('hidden'); imagePreview.classList.remove('hidden'); reader.onload = (e) => { imagePreview.src = e.target.result; }; reader.readAsDataURL(file); } 
        else { imagePreviewText.classList.remove('hidden'); imagePreview.classList.add('hidden'); imagePreview.src = ""; }
    });

    const form = document.getElementById('alert-form');
    const submitBtn = document.getElementById('submit-btn');
    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.querySelector('#btn-text').classList.add('hidden');
        submitBtn.querySelector('#btn-spinner').classList.remove('hidden');
    });

    document.querySelectorAll('.recipient-toggle-btn').forEach(button => {
        button.addEventListener('click', () => {
            const list = button.nextElementSibling;
            const arrow = button.querySelector('.toggle-arrow');
            list.classList.toggle('open');
            arrow.style.transform = list.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
        });
    });
});
</script>

<?php 
require '../footer.php'; 
?>