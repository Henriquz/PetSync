<?php
session_start();
include 'config.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!$email || !$senha) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $mysqli->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        if ($u && password_verify($senha, $u['senha'])) {
            $_SESSION['usuario'] = [
                'id'    => $u['id'],
                'nome'  => $u['nome'],
                'email' => $u['email']
            ];
            header('Location: index.php');   // ou dashboard.php
            exit;
        }
        $erro = 'Credenciais inválidas.';
    }
}
?>
<!DOCTYPE html>
<!-- favicon -->
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐾</text></svg>">
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetSync</title>
    <!-- Referência ao Tailwind CSS e configuração (mantendo o original) -->
    <script src="./saved_resource"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        petOrange: '#FF7A00',
                        petBlue: '#0078C8',
                        petGray: '#4A5568',
                        petLightGray: '#E2E8F0'
                    }
                }
            }
        }
    </script>
    <!-- Referência à fonte Poppins (mantendo o original) -->
    <link href="./css2" rel="stylesheet">
    <!-- Estilos específicos da página de login -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .hero-pattern {
            background-color: #f7fafc;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23e2e8f0' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .form-input:focus {
            border-color: #0078C8;
            box-shadow: 0 0 0 3px rgba(0, 120, 200, 0.2);
        }
        .auth-card {
            transition: all 0.3s ease;
        }
        .btn-primary {
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
    </style>
    <!-- Referência a um CSS original (mantendo o original) -->
    <link href="./3ea6e67a3e74024cf5f100ea6a2851bf.css" rel="stylesheet">
</head>
<body class="bg-white">
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="index.html" class="text-2xl font-bold text-petBlue flex items-center">
                        <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 9C9.10457 9 10 8.10457 10 7C10 5.89543 9.10457 5 8 5C6.89543 5 6 5.89543 6 7C6 8.10457 6.89543 9 8 9Z" fill="#FF7A00"></path>
                            <path d="M16 9C17.1046 9 18 8.10457 18 7C18 5.89543 17.1046 5 16 5C14.8954 5 14 5.89543 14 7C14 8.10457 14.8954 9 16 9Z" fill="#FF7A00"></path>
                            <path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#FF7A00"></path>
                            <path d="M18 14C19.1046 14 20 13.1046 20 12C20 10.8954 19.1046 10 18 10C16.8954 10 16 10.8954 16 12C16 13.1046 16.8954 14 18 14Z" fill="#FF7A00"></path>
                            <path d="M12 18C13.6569 18 15 16.6569 15 15C15 13.3431 13.6569 12 12 12C10.3431 12 9 13.3431 9 15C9 16.6569 10.3431 18 12 18Z" fill="#0078C8"></path>
                        </svg>
                        Pet<span class="text-petOrange">Sync</span>
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-petGray focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden hidden mt-3 pb-3">
                <a href="index.html" class="block py-2 text-petGray hover:text-petBlue font-medium">Início</a>
                <a href="index.html#servicos" class="block py-2 text-petGray hover:text-petBlue font-medium">Serviços</a>
                <a href="produtos.html" class="block py-2 text-petGray hover:text-petBlue font-medium">Produtos</a>
                <a href="galeria.html" class="block py-2 text-petGray hover:text-petBlue font-medium">Galeria</a>
                <a href="index.html#sobre" class="block py-2 text-petGray hover:text-petBlue font-medium">Sobre</a>
                <a href="index.html#contato" class="block py-2 text-petGray hover:text-petBlue font-medium">Contato</a>
                <a href="login.html" class="block py-2 text-petBlue font-medium">Entrar</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-pattern py-12">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-petGray mb-4">Acesse sua Conta</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Entre com suas credenciais para acessar sua conta e aproveitar todos os recursos exclusivos para clientes PetSync.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden auth-card">
                    <div class="bg-petBlue p-6">
                        <h2 class="text-2xl font-bold text-white text-center">Login</h2>
                    </div>
                    <div class="p-6">
                        <div id="login-alert" class="mb-4 p-3 rounded-lg text-sm hidden"></div>
                        
                        <?php if ($erro): ?>
                          <div class="bg-red-100 text-red-700 px-3 py-2 rounded mb-4"><?= $erro ?></div>
                        <?php endif; ?>

                        <form action="login.php" method="POST" class="space-y-4" id="login-form">
                          <!-- EMAIL -->
                          <div class="mb-4">
                            <label class="block text-petGray font-medium mb-1">Email</label>
                            <input name="email" type="email"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input
                                          focus:outline-none focus:border-petBlue"
                                   placeholder="seu@email.com" required>
                          </div>

                          <!-- SENHA -->
                          <div class="mb-6">
                            <label class="block text-petGray font-medium mb-1">Senha</label>
                            <input name="senha" type="password"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input
                                          focus:outline-none focus:border-petBlue"
                                   placeholder="********" required>
                          </div>

                          <!-- BOTÃO -->
                          <button type="submit"
                                  class="w-full bg-petOrange hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg transition">
                            Entrar
                          </button>
                        </form>




                        
                        <div class="mt-6 text-center">
                            <p class="text-gray-600">
                                Não tem uma conta? <a href="cadastro.html" class="text-petBlue hover:underline">Cadastre-se</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <a href="index.html" class="inline-flex items-center text-petGray hover:text-petBlue">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar para a página inicial
                    </a>
                </div>
            </div>
        </div>
    </section>

    <main class="flex-grow">  <!-- Adicionar esta tag com a classe -->
        <section class="hero-pattern py-12">...</section>
        <section class="py-12">...</section> <!-- Você ainda pode ajustar o padding aqui (ex: pt-12) se necessário para o espaçamento interno -->
    </main> <!-- Fechar a tag main -->

    <!-- Footer -->
    <footer class="bg-petGray text-white py-2">
  <div class="container mx-auto px-4 flex items-center justify-between">

    <!-- Logo alinhada à esquerda -->
    <div class="flex items-center">
      <svg class="w-6 h-6 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8 9C9.10457 9 10 8.10457 10 7C10 5.89543 9.10457 5 8 5C6.89543 5 6 5.89543 6 7C6 8.10457 6.89543 9 8 9Z" fill="#FF7A00"/>
        <path d="M16 9C17.1046 9 18 8.10457 18 7C18 5.89543 17.1046 5 16 5C14.8954 5 14 5.89543 14 7C14 8.10457 14.8954 9 16 9Z" fill="#FF7A00"/>
        <path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#FF7A00"/>
        <path d="M18 14C19.1046 14 20 13.1046 20 12C20 10.8954 19.1046 10 18 10C16.8954 10 16 10.8954 16 12C16 13.1046 16.8954 14 18 14Z" fill="#FF7A00"/>
        <path d="M12 18C13.6569 18 15 16.6569 15 15C15 13.3431 13.6569 12 12 12C10.3431 12 9 13.3431 9 15C9 16.6569 10.3431 18 12 18Z" fill="#0078C8"/>
      </svg>
      <span class="text-lg font-bold text-white">
        Pet<span class="text-petOrange">Sync</span>
      </span>
    </div>

    <!-- Texto centralizado -->
    <div class="flex-1 text-center">
      <p class="text-gray-400 text-xs">
        © 2025 Direitos reservados à equipe PetSync.
      </p>
    </div>

    <!-- Redes sociais dentro do footer -->
    <div class="flex space-x-2">

      <!-- Instagram -->
      <a href="https://www.instagram.com/henriquea1ves_/" target="_blank" rel="noopener"
   class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center hover:bg-petOrange transition">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="w-4 h-4">
    <path fill="currentColor" d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9
      114.9-51.3 114.9-114.9S287.7 141 224.1 141zm0 
      189.6c-41.2 0-74.7-33.5-74.7-74.7s33.5-74.7 
      74.7-74.7 74.7 33.5 74.7 74.7-33.5 74.7-74.7
      74.7zm146.4-194.3c0 14.9-12 
      26.9-26.9 26.9s-26.9-12-26.9-26.9 12-26.9 
      26.9-26.9 26.9 12 26.9 26.9zm76.1 
      27.2c-1.7-35.7-9.9-67.3-36.2-93.5s-57.8-34.5-93.5-36.2C293.7 
      32 256 32 224 32s-69.7 0-92.9 1.8c-35.7 
      1.7-67.3 9.9-93.5 36.2S3.8 127.8 
      2.1 163.5C.3 186.7 0 224 0 
      256s.3 69.7 1.8 92.9c1.7 35.7 
      9.9 67.3 36.2 93.5s57.8 34.5 
      93.5 36.2c23.2 1.6 60.9 1.8 
      92.9 1.8s69.7-.3 92.9-1.8c35.7-1.7 
      67.3-9.9 93.5-36.2s34.5-57.8 
      36.2-93.5c1.6-23.2 1.8-60.9 
      1.8-92.9s-.2-69.7-1.8-92.9zm-48.6 
      228c-7.8 19.6-22.9 34.7-42.6 
      42.6-29.5 11.7-99.5 9-132.9 
      9s-103.4 2.6-132.9-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.9s-2.6-103.4 
      9-132.9c7.8-19.6 22.9-34.7 42.6-42.6 
      29.5-11.7 99.5-9 132.9-9s103.4-2.6 
      132.9 9c19.6 7.8 34.7 22.9 42.6 42.6 
      11.7 29.5 9 99.5 9 132.9s2.7 
      103.4-9 132.9z"/>
  </svg>
</a>
    </div>
  </div>
</footer>

    <!-- Scripts -->
  <script src="scripts/navegacao.js"></script>
    <script>
    document.getElementById('mobile-menu-button')
      ?.addEventListener('click', () =>
           document.getElementById('mobile-menu')?.classList.toggle('hidden'));
    </script>
</body>
</html>
