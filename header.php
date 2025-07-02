<?php
// Lembre-se que o arquivo config.php (que inicia a sessão)
// deve ser incluído ANTES deste header nas suas páginas.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'PetSync - Seu Pet, Nossa Paixão'; ?></title>
    
    <link rel="icon" href="/petsync/data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐾</text></svg>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        petOrange: '#FF7A00',
                        petBlue: '#0078C8',
                        petGray: '#4A5568',
                        petLightGray: '#f7fafc'
                    }
                }
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .hero-pattern { background-color: #f7fafc; background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23e2e8f0' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
        .form-input:focus { border-color: #0078C8; box-shadow: 0 0 0 3px rgba(0, 120, 200, 0.2); }
        #toast-notification { position: fixed; top: 2rem; right: 2rem; z-index: 9999; padding: 1rem 1.5rem; border-radius: 0.5rem; color: white; font-weight: 500; box-shadow: 0 4px 6px rgba(0,0,0,0.1); opacity: 0; visibility: hidden; transform: translateY(-20px); transition: all 0.3s ease-in-out; }
        #toast-notification.show { opacity: 1; visibility: visible; transform: translateY(0); }
    </style>
</head>
<body class="bg-petLightGray flex flex-col min-h-screen">
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="/petsync/index.php" class="text-2xl font-bold text-petBlue flex items-center">
                    <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 9C9.10457 9 10 8.10457 10 7C10 5.89543 9.10457 5 8 5C6.89543 5 6 5.89543 6 7C6 8.10457 6.89543 9 8 9Z" fill="#FF7A00"></path><path d="M16 9C17.1046 9 18 8.10457 18 7C18 5.89543 17.1046 5 16 5C14.8954 5 14 5.89543 14 7C14 8.10457 14.8954 9 16 9Z" fill="#FF7A00"></path><path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#FF7A00"></path><path d="M18 14C19.1046 14 20 13.1046 20 12C20 10.8954 19.1046 10 18 10C16.8954 10 16 10.8954 16 12C16 13.1046 16.8954 14 18 14Z" fill="#FF7A00"></path><path d="M12 18C13.6569 18 15 16.6569 15 15C15 13.3431 13.6569 12 12 12C10.3431 12 9 13.3431 9 15C9 16.6569 10.3431 18 12 18Z" fill="#0078C8"></path></svg>
                    Pet<span class="text-petOrange">Sync</span>
                </a>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/petsync/index.php#services" class="text-petGray hover:text-petBlue font-medium">Serviços</a>
                    <a href="/petsync/index.php#products" class="text-petGray hover:text-petBlue font-medium">Produtos</a>
                    <a href="/petsync/index.php#about" class="text-petGray hover:text-petBlue font-medium">Sobre</a>
                    <a href="/petsync/index.php#contact" class="text-petGray hover:text-petBlue font-medium">Contato</a>

                    <?php if (isset($_SESSION['usuario'])): 
                        $nomeCompleto = $_SESSION['usuario']['nome'];
                        $primeiroNome = explode(' ', $nomeCompleto)[0];
                    ?>
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 text-petGray hover:text-petBlue font-medium focus:outline-none">
                                <span>Olá, <?php echo htmlspecialchars($primeiroNome); ?></span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </button>
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-20">
    <a href="/petsync/cliente/perfil.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Meu Perfil</a>
    
    <?php if (isset($_SESSION['usuario']['is_admin']) && $_SESSION['usuario']['is_admin']): ?>
        <a href="/petsync/admin/gerencia_agendamentos.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Gerenciar Agendamentos</a>
    <?php else: ?>
        <a href="/petsync/meus_agendamentos.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Meus Agendamentos</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['usuario']['is_admin']) && $_SESSION['usuario']['is_admin']): ?>
        <a href="/petsync/admin/index.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Painel do Administrador</a>
    <?php endif; ?>
    <a href="/petsync/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-petLightGray">Sair</a>
</div>
                        </div>
                    <?php else: ?>
                        <a href="/petsync/login.php" class="inline-block px-4 py-1.5 bg-petOrange text-white text-sm font-semibold rounded-full shadow-md hover:bg-petBlue hover:shadow-lg transition-colors duration-300 ease-in-out">Entrar</a>
                    <?php endif; ?>
                </div>
                
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-petGray focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </div>
            
            <div id="mobile-menu" class="md:hidden hidden pt-4 pb-2">
    <div class="border-t my-2"></div>
    <?php if (isset($_SESSION['usuario'])): ?>
        <a href="/petsync/cliente/perfil.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Meu Perfil</a>
        
        <?php if (isset($_SESSION['usuario']['is_admin']) && $_SESSION['usuario']['is_admin']): ?>
            <a href="/petsync/admin/gerencia_agendamentos.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Gerenciar Agendamentos</a>
        <?php else: ?>
            <a href="/petsync/meus_agendamentos.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Meus Agendamentos</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['usuario']['is_admin']) && $_SESSION['usuario']['is_admin']): ?>
            <a href="/petsync/admin/index.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Painel do Administrador</a>
        <?php endif; ?>
        <a href="/petsync/logout.php" class="block py-2 px-4 text-red-600 hover:bg-petLightGray rounded-md">Sair</a>
    <?php else: ?>
        <?php endif; ?>
</div>
        </div>
    </nav>
    <main class="flex-grow">