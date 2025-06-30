<?php
include 'config.php';
$erro = $ok = '';
$page_title = 'Cadastro - PetSync';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = preg_replace('/\D/', '', ($_POST['telefone'] ?? ''));
    $senha = trim($_POST['senha'] ?? '');
    $senhaConf = trim($_POST['senha_conf'] ?? '');

    if (!$nome || !$email || !$senha) {
        $erro = 'Preencha nome, e-mail e senha.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $senhaConf) {
        $erro = 'As senhas não coincidem.';
    } else {
        $chk = $mysqli->prepare('SELECT id FROM usuarios WHERE email = ?');
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $ins = $mysqli->prepare('INSERT INTO usuarios (nome, email, telefone, senha) VALUES (?, ?, ?, ?)');
            $ins->bind_param('ssss', $nome, $email, $telefone, $hash);
            if ($ins->execute()) {
                $ok = 'Cadastro realizado com sucesso! Redirecionando...';
            } else {
                $erro = 'Ocorreu um erro ao salvar seu cadastro.';
            }
        }
    }
}

require 'header.php';
?>

<?php
$toast_message = '';
$toast_class = '';

if ($ok) {
    $toast_message = $ok;
    $toast_class = 'bg-green-500';
} elseif ($erro) {
    $toast_message = $erro;
    $toast_class = 'bg-red-500';
}

if ($toast_message):
?>
<div id="toast-notification" class="<?php echo $toast_class; ?>">
    <?php echo htmlspecialchars($toast_message); ?>
</div>
<?php endif; ?>


<section class="hero-pattern py-12">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold text-petGray mb-4">Crie sua Conta</h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">Cadastre-se para agendar serviços e ter acesso a ofertas exclusivas.</p>
    </div>
</section>

<section class="py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-petOrange p-6">
                <h2 class="text-2xl font-bold text-white text-center">Cadastro</h2>
            </div>
            <div class="p-8">
                <form id="register-form" action="cadastro.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-petGray font-medium mb-1">Nome Completo</label>
                        <input name="nome" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input" placeholder="Seu nome" required>
                    </div>
                    <div>
                        <label class="block text-petGray font-medium mb-1">Email</label>
                        <input name="email" type="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input" placeholder="seu@email.com" required>
                    </div>
                    <div>
                        <label class="block text-petGray font-medium mb-1">Telefone</label>
                        <input name="telefone" id="telefone" type="tel" maxlength="15" class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input" placeholder="(33) 99999-9999">
                    </div>
                    <div>
                        <label class="block text-petGray font-medium mb-1">Senha</label>
                        <input name="senha" type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div>
                        <label class="block text-petGray font-medium mb-1">Confirmar Senha</label>
                        <input name="senha_conf" type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input" placeholder="Repita a senha" required>
                    </div>
                    <button type="submit" class="w-full bg-petOrange hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-transform hover:-translate-y-1">Criar Conta</button>
                </form>
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Já tem uma conta? <a href="login.php" class="text-petBlue hover:underline font-semibold">Entrar</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toast-notification');
    if (toast) {
        setTimeout(() => toast.classList.add('show'), 100);

        if (toast.classList.contains('bg-green-500')) {
            setTimeout(() => window.location.href = 'login.php', 3000);
        } else {
            setTimeout(() => toast.classList.remove('show'), 5000);
        }
    }
});
</script>

<?php require 'footer.php'; ?>