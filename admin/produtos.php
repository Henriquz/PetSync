<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Produtos';
$erro = $ok = '';

// Lógica de DELEÇÃO
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    // Opcional: deletar a imagem do servidor
    $stmt = $mysqli->prepare("SELECT imagem FROM produtos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && $result['imagem'] && file_exists("../Imagens/produtos/" . $result['imagem'])) {
        unlink("../Imagens/produtos/" . $result['imagem']);
    }

    $stmt = $mysqli->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header("Location: produtos.php");
    exit;
}

// Lógica para ADICIONAR ou EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $imagem_atual = $_POST['imagem_atual'] ?? '';
    $imagem_nome = $imagem_atual;

    // Lógica de Upload de Imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $dir_upload = "../Imagens/produtos/";
        if (!is_dir($dir_upload)) mkdir($dir_upload, 0755, true);
        
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem_nome = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['imagem']['tmp_name'], $dir_upload . $imagem_nome);
    }
    
    if ($id) { // Edição
        $stmt = $mysqli->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, imagem = ? WHERE id = ?");
        $stmt->bind_param('ssdsi', $nome, $descricao, $preco, $imagem_nome, $id);
        $ok = "Produto atualizado com sucesso!";
    } else { // Adição
        $stmt = $mysqli->prepare("INSERT INTO produtos (nome, descricao, preco, imagem) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssds', $nome, $descricao, $preco, $imagem_nome);
        $ok = "Produto cadastrado com sucesso!";
    }
    $stmt->execute();
}

// Lógica para carregar um produto para EDIÇÃO
$produto_para_editar = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $mysqli->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $produto_para_editar = $stmt->get_result()->fetch_assoc();
}


// Lógica para LISTAR todos os produtos
$produtos = $mysqli->query("SELECT * FROM produtos ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

require '../header.php';
?>

<?php if ($ok): ?><div id="toast-notification" class="bg-green-500 show"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($erro): ?><div id="toast-notification" class="bg-red-500 show"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="container mx-auto px-4 py-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-petGray">Gerenciar Produtos</h1>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg mb-12">
        <h2 class="text-2xl font-semibold text-petBlue mb-6"><?= $produto_para_editar ? 'Editar Produto' : 'Adicionar Novo Produto' ?></h2>
        <form action="produtos.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?= $produto_para_editar['id'] ?? '' ?>">
            <input type="hidden" name="imagem_atual" value="<?= $produto_para_editar['imagem'] ?? '' ?>">
            <div>
                <label class="block text-petGray font-medium">Nome do Produto</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($produto_para_editar['nome'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input" required>
            </div>
            <div>
                <label class="block text-petGray font-medium">Descrição</label>
                <textarea name="descricao" rows="3" class="w-full mt-1 p-2 border rounded-md form-input"><?= htmlspecialchars($produto_para_editar['descricao'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-petGray font-medium">Preço (ex: 89.90)</label>
                <input type="number" step="0.01" name="preco" value="<?= htmlspecialchars($produto_para_editar['preco'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input" required>
            </div>
            <div>
                <label class="block text-petGray font-medium">Imagem</label>
                <input type="file" name="imagem" class="w-full mt-1">
                <?php if (!empty($produto_para_editar['imagem'])): ?>
                    <p class="text-sm text-gray-500 mt-2">Imagem atual: <?= htmlspecialchars($produto_para_editar['imagem']) ?>. Envie um novo arquivo para substituir.</p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <?php if ($produto_para_editar): ?>
                    <a href="produtos.php" class="text-gray-600 mr-4">Cancelar Edição</a>
                <?php endif; ?>
                <button type="submit" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-5 rounded-lg">Salvar Produto</button>
            </div>
        </form>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold text-petBlue mb-6">Produtos Cadastrados</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b">
                        <th class="p-2">Imagem</th>
                        <th class="p-2">Nome</th>
                        <th class="p-2">Preço</th>
                        <th class="p-2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                    <tr class="border-b">
                        <td class="p-2">
                            <img src="../Imagens/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="w-16 h-16 object-cover rounded">
                        </td>
                        <td class="p-2 font-medium"><?= htmlspecialchars($produto['nome']) ?></td>
                        <td class="p-2">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                        <td class="p-2">
                            <a href="produtos.php?edit_id=<?= $produto['id'] ?>" class="text-petBlue hover:underline">Editar</a>
                            <a href="produtos.php?delete_id=<?= $produto['id'] ?>" class="text-red-500 hover:underline ml-4" onclick="return confirm('Tem certeza que deseja deletar este produto?');">Deletar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Script para esconder a notificação depois de um tempo
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toast-notification');
    if (toast) {
        setTimeout(() => { toast.classList.remove('show'); }, 5000);
    }
});
</script>

<?php require '../footer.php'; ?>