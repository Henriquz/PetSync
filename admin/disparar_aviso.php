<?php
include '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['is_admin'])) { exit('Acesso negado.'); }

$ok = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinatario_id = $_POST['destinatario'] ?? null;
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (empty($destinatario_id) || empty($mensagem)) {
        $erro = "Por favor, selecione um destinatário e escreva uma mensagem.";
    } else {
        if ($destinatario_id === 'todos') {
            // Enviar para todos os clientes
            $result = $mysqli->query("SELECT id FROM usuarios WHERE is_admin = 0");
            $clientes = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($clientes as $cliente) {
                criar_notificacao($mysqli, $cliente['id'], $mensagem);
            }
            $ok = "Aviso enviado para todos os clientes!";
        } else {
            // Enviar para um cliente específico
            criar_notificacao($mysqli, $destinatario_id, $mensagem);
            $ok = "Aviso enviado com sucesso!";
        }
    }
}

$clientes = $mysqli->query("SELECT id, nome FROM usuarios WHERE is_admin = 0 ORDER BY nome ASC");
$page_title = "Disparar Avisos";
require 'header.php'; // Ou o seu header do admin
?>
<main class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-petGray mb-6">Disparar Avisos e Notificações</h1>
    
    <?php if ($ok): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($ok) ?></p></div><?php endif; ?>
    <?php if ($erro): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= htmlspecialchars($erro) ?></p></div><?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <form action="disparar_aviso.php" method="POST">
            <div class="mb-4">
                <label for="destinatario" class="block text-sm font-medium text-gray-700">Enviar Para:</label>
                <select name="destinatario" id="destinatario" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-petBlue focus:border-petBlue sm:text-sm rounded-md" required>
                    <option value="">-- Selecione --</option>
                    <option value="todos">*** TODOS OS CLIENTES ***</option>
                    <?php while($cliente = $clientes->fetch_assoc()): ?>
                        <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="mensagem" class="block text-sm font-medium text-gray-700">Mensagem:</label>
                <textarea name="mensagem" id="mensagem" rows="5" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-petBlue focus:border-petBlue" required></textarea>
            </div>
            <div class="text-right">
                <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-petBlue hover:bg-blue-800">
                    Enviar Notificação
                </button>
            </div>
        </form>
    </div>
</main>
<?php require 'footer.php'; // Ou o seu footer do admin ?>