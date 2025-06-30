<?php
include 'config.php';
$erro = '';
$page_title = 'Login - PetSync';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!$email || !$senha) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        // PASSO 1: Busca o usuário APENAS pelo e-mail, independente do status.
        $stmt = $mysqli->prepare('SELECT id, nome, email, senha, is_admin, is_active FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        // PASSO 2: Verifica se um usuário com aquele e-mail foi encontrado.
        if ($u) {
            // PASSO 3: Se o usuário existe, AGORA sim verificamos a senha.
            if (password_verify($senha, $u['senha'])) {
                // A senha está correta! Agora verificamos o status da conta.
                
                // PASSO 4: A conta está ativa?
                if ($u['is_active']) {
                    // SUCESSO! Cria a sessão e redireciona.
                    $_SESSION['usuario'] = [ 
                        'id' => $u['id'], 
                        'nome' => $u['nome'],
                        'is_admin' => $u['is_admin']
                    ];
                    header('Location: index.php');
                    exit;
                } else {
                    // ERRO ESPECÍFICO: A conta existe e a senha está correta, mas está inativa.
                    $erro = 'Sua conta está desativada. Entre em contato com o petshop.';
                }
            } else {
                // ERRO GENÉRICO: O usuário existe, mas a senha está errada.
                $erro = 'E-mail ou senha inválidos.';
            }
        } else {
            // ERRO GENÉRICO: Nenhum usuário com este e-mail foi encontrado.
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}

require 'header.php';
?>

<?php
if ($erro):
?>
<div id="toast-notification" class="bg-red-500 show">
    <?php echo htmlspecialchars($erro); ?>
</div>
<?php endif; ?>


<section class="hero-pattern py-12">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold text-petGray mb-4">Acesse sua Conta</h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">Entre para acessar sua conta e ver seus agendamentos.</p>
    </div>
</section>

<section class="py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-petBlue p-6">
                <h2 class="text-2xl font-bold text-white text-center">Login</h2>
            </div>
            <div class="p-8">
                <form action="login.php" method="POST" class="space-y-6">
                  <div>
                    <label for="email" class="block text-petGray font-medium mb-1">Email</label>
                    <input id="email" name="email" type="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input focus:outline-none" placeholder="seu@email.com" required>
                  </div>
                  <div>
                    <label for="senha" class="block text-petGray font-medium mb-1">Senha</label>
                    <input id="senha" name="senha" type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input focus:outline-none" placeholder="********" required>
                  </div>
                  <button type="submit" class="w-full bg-petOrange hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-transform hover:-translate-y-1">Entrar</button>
                </form>
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Não tem uma conta? <a href="cadastro.php" class="text-petBlue hover:underline font-semibold">Cadastre-se</a></p>
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
        setTimeout(() => toast.classList.remove('show'), 5000);
    }
});
</script>

<?php require 'footer.php'; ?>