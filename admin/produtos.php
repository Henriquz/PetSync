<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Produtos';
$erro = $ok = '';

// Lógica de DELEÇÃO
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $mysqli->prepare("SELECT imagem FROM produtos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && !empty($result['imagem']) && file_exists("../Imagens/produtos/" . $result['imagem'])) {
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
    // MODIFICADO: Coleta de dados de estoque e ativo
    $estoque = (int)($_POST['estoque'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $imagem_atual = $_POST['imagem_atual'] ?? '';
    $imagem_nome = $imagem_atual;

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $dir_upload = "../Imagens/produtos/";
        if (!is_dir($dir_upload)) mkdir($dir_upload, 0755, true);
        
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem_nome = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['imagem']['tmp_name'], $dir_upload . $imagem_nome);
    }
    
    if ($id) { // Edição
        // MODIFICADO: Query de Edição atualizada com estoque e ativo
        $stmt = $mysqli->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, estoque = ?, ativo = ?, imagem = ? WHERE id = ?");
        $stmt->bind_param('ssdiisi', $nome, $descricao, $preco, $estoque, $ativo, $imagem_nome, $id);
        $ok = "Produto atualizado com sucesso!";
    } else { // Adição
        // MODIFICADO: Query de Adição atualizada com estoque e ativo
        $stmt = $mysqli->prepare("INSERT INTO produtos (nome, descricao, preco, estoque, ativo, imagem) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdiss', $nome, $descricao, $preco, $estoque, $ativo, $imagem_nome);
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
<style>
    #toast-notification {
        position: fixed; bottom: 20px; right: 20px;
        padding: 1rem 1.5rem; color: white; border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transform: translateX(calc(100% + 20px));
        transition: transform 0.5s ease-in-out;
        z-index: 100;
    }
    #toast-notification.show { transform: translateX(0); }
</style>

<?php if ($ok): ?><div id="toast-notification" class="bg-green-500 show"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($erro): ?><div id="toast-notification" class="bg-red-500 show"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="container mx-auto px-4 py-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-petGray">Gerenciar Produtos</h1>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg mb-12">
        <h2 class="text-2xl font-semibold text-petBlue mb-6"><?= $produto_para_editar ? 'Editar Produto' : 'Adicionar Novo Produto' ?></h2>
        <form action="produtos.php" method="POST" enctype="multipart/form-data" class="space-y-6">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-petGray font-medium">Preço (ex: 89.90)</label>
                    <input type="number" step="0.01" name="preco" value="<?= htmlspecialchars($produto_para_editar['preco'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input" required>
                </div>
                <div>
                    <label class="block text-petGray font-medium">Quantidade em Estoque</label>
                    <input type="number" name="estoque" min="0" value="<?= htmlspecialchars($produto_para_editar['estoque'] ?? '0') ?>" class="w-full mt-1 p-2 border rounded-md form-input" required>
                </div>
            </div>

            <div>
                <label class="block text-petGray font-medium">Imagem</label>
                <input type="file" name="imagem" accept="image/*" class="w-full mt-1 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-petBlue hover:file:bg-blue-100"/>
                <?php if (!empty($produto_para_editar['imagem'])): ?>
                    <p class="text-sm text-gray-500 mt-2">Imagem atual: <?= htmlspecialchars($produto_para_editar['imagem']) ?>. Envie um novo arquivo para substituir.</p>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="ativo" id="ativo" value="1" class="h-4 w-4 rounded border-gray-300 text-petBlue focus:ring-petBlue" <?php if (!isset($produto_para_editar) || !empty($produto_para_editar['ativo'])) echo 'checked'; ?>>
                <label for="ativo" class="ml-2 block text-sm text-petGray">Produto ativo (visível na loja)</label>
            </div>

            <div class="text-right pt-4">
                <?php if ($produto_para_editar): ?>
                    <a href="produtos.php" class="text-gray-600 hover:text-gray-800 font-medium mr-4">Cancelar Edição</a>
                <?php endif; ?>
                <button type="submit" class="bg-petOrange hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg shadow-md hover:shadow-lg transition-all">Salvar Produto</button>
            </div>
        </form>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold text-petBlue mb-6">Produtos Cadastrados</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b">
                        <th class="p-3">Imagem</th>
                        <th class="p-3">Nome</th>
                        <th class="p-3">Preço</th>
                        <th class="p-3 text-center">Estoque</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($produtos)): ?>
                        <tr><td colspan="6" class="p-4 text-center text-gray-500">Nenhum produto cadastrado.</td></tr>
                    <?php else: ?>
                    <?php foreach ($produtos as $produto): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">
                            <img src="../Imagens/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="w-16 h-16 object-cover rounded-md shadow-sm">
                        </td>
                        <td class="p-3 font-medium text-petGray"><?= htmlspecialchars($produto['nome']) ?></td>
                        <td class="p-3">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                        <td class="p-3 text-center font-semibold"><?= $produto['estoque'] ?></td>
                        <td class="p-3 text-center">
                            <?php if ($produto['ativo']): ?>
                                <span class="px-3 py-1 text-xs font-bold text-green-800 bg-green-100 rounded-full">Ativo</span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-bold text-red-800 bg-red-100 rounded-full">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <a href="produtos.php?edit_id=<?= $produto['id'] ?>#edit-form" class="text-petBlue hover:underline font-semibold">Editar</a>
                            <a href="produtos.php?delete_id=<?= $produto['id'] ?>" class="text-red-500 hover:underline ml-4 font-semibold" onclick="return confirm('Tem certeza que deseja deletar este produto?');">Deletar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toast-notification');
    if (toast) {
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
});
</script>

<?php require '../footer.php'; ?>