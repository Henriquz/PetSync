<?php
include '../config.php';
include 'check_admin.php';
$page_title = 'Admin - Configurações';
$ok = $erro = '';

// --- LÓGICA ATUALIZADA PARA SALVAR E REDIRECIONAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $stmt_insert = $mysqli->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?)");
            $stmt_insert->bind_param('ss', $chave, $valor);
            $stmt_insert->execute();
        }
    }
    $ok = 'Configurações salvas com sucesso!';

    // ADICIONADO: Redireciona para a mesma página com a mensagem de sucesso na URL
    header("Location: configuracoes.php?ok_msg=" . urlencode($ok));
    exit;
}

// ADICIONADO: Pega a mensagem de sucesso da URL para exibir o toast
if (isset($_GET['ok_msg'])) {
    $ok = $_GET['ok_msg'];
}

// Lógica para buscar as configurações do banco (sem alterações)
$configuracoes = [];
$result_config = $mysqli->query("SELECT chave, valor FROM configuracoes");
while ($row = $result_config->fetch_assoc()) {
    $configuracoes[$row['chave']] = $row['valor'];
}

require '../header.php';
?>
<?php if ($ok): ?>
<div id="toast-notification" class="bg-green-500 show">
    <?php echo htmlspecialchars($ok); ?>
</div>
<?php endif; ?>

<div class="container mx-auto px-4 py-12">
    <h1 class="text-4xl font-bold text-petGray mb-8">Configurações do Site</h1>
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <form action="configuracoes.php" method="POST" class="space-y-6">
            
            <h2 class="text-2xl font-semibold text-petBlue border-b pb-2 mb-4">Contato e Horários</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="telefone_1" class="block text-petGray font-medium">Telefone 1</label>
                    <input type="text" name="telefone_1" id="telefone_1" value="<?= htmlspecialchars($configuracoes['telefone_1'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input">
                </div>
                 <div>
                    <label for="telefone_2" class="block text-petGray font-medium">Telefone 2 (WhatsApp)</label>
                    <input type="text" name="telefone_2" id="telefone_2" value="<?= htmlspecialchars($configuracoes['telefone_2'] ?? '') ?>" class="w-full mt-1 p-2 border rounded-md form-input">
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
    const toast = document.getElementById('toast-notification');
    if (toast) {
        setTimeout(() => { 
            toast.style.opacity = '0';
            // Opcional: remover o elemento depois da transição
            setTimeout(() => { toast.remove(); }, 500);
        }, 5000);
    }
});
</script>

<?php require '../footer.php'; ?>