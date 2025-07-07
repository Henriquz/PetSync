<?php
// Lembre-se que o arquivo config.php (que inicia a sessão)
// deve ser incluído ANTES deste header nas suas páginas.
if (session_status() === PHP_SESSION_NONE) { session_start(); } // Garante que a sessão está ativa

// --- NOVA LÓGICA ---
// Verifica se a configuração para exibir a loja está ativa.
// Usamos isset() para garantir que não haverá erro se a variável $configuracoes não existir.
$exibir_loja = isset($configuracoes['exibir_secao_produtos']) && $configuracoes['exibir_secao_produtos'];
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
        .form-input:focus { border-color: #0078C8; box-shadow: 0 0 0 3px rgba(0, 120, 200, 0.2); }
        
        .notification-count {
            transform: scale(0.9) translate(50%, -50%);
            transform-origin: top right;
        }
        .notification-item { position: relative; padding-right: 2.5rem; }
        .dismiss-btn { position: absolute; top: 50%; right: 0.75rem; transform: translateY(-50%); width: 1.5rem; height: 1.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; opacity: 0.5; transition: all 0.2s; }
        .notification-item:hover .dismiss-btn { opacity: 1; }
        .dismiss-btn:hover { background-color: #e5e7eb; color: #ef4444; }

        .image-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000;
            opacity: 0; visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .image-modal.open { opacity: 1; visibility: visible; }
        .image-modal-content { position: relative; max-width: 90%; max-height: 90%; }
        .image-modal-content img { display: block; max-width: 100%; max-height: 90vh; border-radius: 0.5rem; }
        .image-modal-close {
            position: absolute; top: -1rem; right: -1rem;
            width: 2.5rem; height: 2.5rem;
            background-color: white; color: #374151;
            border-radius: 9999px;
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
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
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="/petsync/index.php#services" class="text-petGray hover:text-petBlue font-medium">Serviços</a>
                    <?php if ($exibir_loja): // AJUSTE 1: Menu Desktop ?>
                        <a href="/petsync/loja.php" class="text-petGray hover:text-petBlue font-medium">Loja</a>
                    <?php endif; ?>
                    <a href="/petsync/galeria.php" class="text-petGray hover:text-petBlue font-medium">Galeria</a>
                    <a href="/petsync/index.php#about" class="text-petGray hover:text-petBlue font-medium">Sobre</a>
                    <a href="/petsync/index.php#contact" class="text-petGray hover:text-petBlue font-medium">Contato</a>

                    <?php if (isset($_SESSION['usuario'])): 
                        $nomeCompleto = $_SESSION['usuario']['nome'];
                        $primeiroNome = explode(' ', $nomeCompleto)[0];
                    ?>
                        <?php if (empty($_SESSION['usuario']['is_admin'])): // Apenas para clientes ?>
                        
                        <?php 
                            $itens_no_carrinho = isset($_SESSION['carrinho']) ? count($_SESSION['carrinho']) : 0;
                        ?>
                        <?php if ($exibir_loja): // Mostra o carrinho apenas se a loja estiver ativa ?>
                        <a href="/petsync/carrinho.php" id="cart-icon-container" class="relative <?php if($itens_no_carrinho == 0) echo 'hidden'; ?>">
                            <svg class="w-6 h-6 text-petGray hover:text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span id="cart-count" class="absolute top-0 right-0 notification-count w-5 h-5 bg-petOrange text-white text-xs font-bold rounded-full flex items-center justify-center">
                                <?= $itens_no_carrinho ?>
                            </span>
                        </a>
                        <?php endif; ?>

                        <div class="relative">
                            <div id="notification-bell-container" class="relative cursor-pointer">
                                <svg class="w-6 h-6 text-petGray hover:text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <span id="notification-count" class="hidden absolute top-0 right-0 notification-count w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center"></span>
                            </div>
                            
                            <div id="notification-dropdown" class="hidden absolute top-full right-0 mt-4 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-20">
                                <div class="px-4 py-2 border-b"><h3 class="text-sm font-semibold text-petGray">Notificações</h3></div>
                                <div id="notification-list" class="max-h-80 overflow-y-auto"><p class="text-center text-sm text-gray-500 py-4">Carregando...</p></div>
                                <div class="px-4 py-2 border-t bg-gray-50 flex justify-between items-center">
                                    <button id="clear-read-btn" class="text-xs text-petBlue hover:underline font-semibold">Limpar lidas</button>
                                    <a href="/petsync/notificacoes.php" class="text-xs text-white bg-petBlue hover:bg-blue-800 font-semibold py-1 px-3 rounded-full transition-colors">Ver Todas</a>
                                </div>
                            </div>

                        </div>
                        <?php endif; ?>

                        <?php if (!empty($_SESSION['usuario']['is_admin'])): // Apenas para admins ?>
                        <div class="relative">
                            <div id="admin-notification-bell-container" class="relative cursor-pointer">
                                <svg class="w-6 h-6 text-petGray hover:text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <span id="admin-notification-count" class="hidden absolute top-0 right-0 notification-count w-5 h-5 bg-petBlue text-white text-xs font-bold rounded-full flex items-center justify-center"></span>
                            </div>
                            <div id="admin-notification-dropdown" class="hidden absolute top-full right-0 mt-4 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-20">
                                <div class="px-4 py-2 border-b"><h3 class="text-sm font-semibold text-petGray">Novos Agendamentos</h3></div>
                                <div id="admin-notification-list" class="max-h-80 overflow-y-auto"><p class="text-center text-sm text-gray-500 py-4">Carregando...</p></div>
                                <div class="px-4 py-2 border-t bg-gray-50 flex justify-between items-center">
                                    <button id="admin-clear-read-btn" class="text-xs text-petBlue hover:underline font-semibold">Limpar lidas</button>
                                    <a href="/petsync/admin/gerencia_agendamentos.php" class="text-xs text-white bg-petBlue hover:bg-blue-800 font-semibold py-1 px-3 rounded-full transition-colors">Todos os agendamentos</a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 text-petGray hover:text-petBlue font-medium focus:outline-none">
                                <span>Olá, <?php echo htmlspecialchars($primeiroNome); ?></span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </button>
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-20">
                                <a href="/petsync/cliente/perfil.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Meu Perfil</a>
                                <?php if (isset($_SESSION["usuario"]["is_admin"]) && $_SESSION["usuario"]["is_admin"]): ?>
                                    <a href="/petsync/admin/index.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Painel do Administrador</a>
                                    <a href="/petsync/admin/gerencia_agendamentos.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Gerenciar Agendamentos</a>
                                    <?php if ($exibir_loja): ?>
                                    <a href="/petsync/admin/gerenciar_pedidos.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Gerenciar Pedidos</a>
                                    <?php endif; ?>
                                <?php elseif (isset($_SESSION["usuario"]["is_colaborador"]) && $_SESSION["usuario"]["is_colaborador"]): ?>
                                    <a href="/petsync/colaborador/index.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Painel do Colaborador</a>
                                    <a href="/petsync/colaborador/agendamentos.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Meus Agendamentos</a>
                                    <a href="/petsync/colaborador/agenda.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Agenda do Dia</a>
                                    <a href="/petsync/colaborador/perfil.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Meu Perfil</a>
                                <?php else: ?>
                                    <a href="/petsync/meus_agendamentos.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Meus Agendamentos</a>
                                    <?php if ($exibir_loja): ?>
                                    <a href="/petsync/meus_pedidos.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Meus Pedidos</a>
                                    <?php endif; ?>
                                    <a href="/petsync/notificacoes.php" class="block px-4 py-2 text-sm text-petGray hover:bg-petLightGray">Minhas Notificações</a>
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
                        <a href="/petsync/admin/index.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Painel do Administrador</a>
                        <a href="/petsync/admin/gerencia_agendamentos.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Gerenciar Agendamentos</a>
                        <?php if ($exibir_loja): ?>
                        <a href="/petsync/admin/gerenciar_pedidos.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Gerenciar Pedidos</a>
                        <?php endif; ?>
                    <?php elseif (isset($_SESSION["usuario"]["is_colaborador"]) && $_SESSION["usuario"]["is_colaborador"]): ?>
                        <a href="/petsync/colaborador/index.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Painel do Colaborador</a>
                        <a href="/petsync/colaborador/agendamentos.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Meus Agendamentos</a>
                        <a href="/petsync/colaborador/agenda.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Agenda do Dia</a>
                        <a href="/petsync/colaborador/perfil.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Meu Perfil</a>
                    <?php else: // AJUSTE 2: Menu Mobile Cliente Logado ?>
                        <?php if ($exibir_loja): ?>
                            <a href="/petsync/loja.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Loja</a>
                            <a href="/petsync/carrinho.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Meu Carrinho</a>
                        <?php endif; ?>
                        <a href="/petsync/meus_agendamentos.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Meus Agendamentos</a>
                        <?php if ($exibir_loja): ?>
                        <a href="/petsync/meus_pedidos.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Meus Pedidos</a>
                        <?php endif; ?>
                        <a href="/petsync/notificacoes.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Minhas Notificações</a>
                    <?php endif; ?>
                    <a href="/petsync/logout.php" class="block py-2 px-4 text-red-600 hover:bg-petLightGray rounded-md">Sair</a>
                <?php else: // AJUSTE 3: Menu Mobile Deslogado ?>
                    <a href="/petsync/index.php#services" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Serviços</a>
                    <?php if ($exibir_loja): ?>
                    <a href="/petsync/loja.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Loja</a>
                    <?php endif; ?>
                    <a href="/petsync/galeria.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Galeria</a>
                    <a href="/petsync/login.php" class="block py-2 px-4 text-petGray hover:bg-petLightGray rounded-md">Entrar</a>
                <?php endif; ?>
             </div>
        </div>
    </nav>
    <main class="flex-grow">