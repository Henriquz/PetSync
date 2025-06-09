document.addEventListener('DOMContentLoaded', function() {
    // Configurar formulário de login
    setupLoginForm();
    
    // Configurar formulário de cadastro
    setupRegisterForm();
    
    // Adicionar notificação ao DOM
    addNotificationElement();
});

// Função para configurar o formulário de login
function setupLoginForm() {
    const loginForm = document.getElementById('login-form');
    if (!loginForm) return;

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Validar campos
        if (!email || !password) {
            showNotification('Por favor, preencha todos os campos.', 'error');
            return;
        }

        // ✅ Verificar se é o admin hardcoded
        if (email === "admin@petsync.com" && password === "admin123") {
            const adminUser = {
                id: 0,
                name: "Administrador",
                email: email,
                role: "admin"
            };
            localStorage.setItem('petsync_current_user', JSON.stringify(adminUser));
            showNotification('Login como administrador realizado com sucesso!', 'success');
            setTimeout(() => {
                window.location.href = 'admin.html';
            }, 1000);
            return;
        }

        // ✅ Login via Firebase
        firebase.auth().signInWithEmailAndPassword(email, password)
            .then(async (result) => {
                const user = result.user;
                const docRef = firebase.firestore().collection("usuarios").doc(user.uid);
                const doc = await docRef.get();

                if (doc.exists) {
                    const usuarioData = doc.data();
                    localStorage.setItem('petsync_current_user', JSON.stringify(usuarioData));
                    showNotification('Login realizado com sucesso!', 'success');

                    setTimeout(() => {
                        if (usuarioData.role === 'admin') {
                            window.location.href = 'admin.html';
                        } else {
                            window.location.href = 'perfil.html';
                        }
                    }, 1000);
                } else {
                    showNotification('Usuário não encontrado no Firestore.', 'error');
                    firebase.auth().signOut();
                }
            })
            .catch((error) => {
                console.error("Erro Firebase:", error);
                showNotification('Erro ao fazer login. Verifique suas credenciais.', 'error');
            });
    });
}



// Função para configurar o formulário de cadastro
function setupRegisterForm() {
    const registerForm = document.getElementById('register-form');
    if (!registerForm) return;
    
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone')?.value || '';
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        const terms = document.getElementById('terms')?.checked;
        
        // Validar campos
        if (!name || !email || !password || !confirmPassword) {
            showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
            return;
        }
        
        if (password !== confirmPassword) {
            showNotification('As senhas não coincidem.', 'error');
            return;
        }
        
        if (terms === false) {
            showNotification('Você precisa aceitar os termos de uso.', 'error');
            return;
        }
        
        // Buscar usuários existentes
        const users = JSON.parse(localStorage.getItem('petsync_users')) || [];
        
        // Verificar se o email já está em uso
        if (users.some(u => u.email === email)) {
            showNotification('Este email já está em uso.', 'error');
            return;
        }
        
        // Criar novo usuário
        const newUser = {
            id: users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1,
            name,
            email,
            phone,
            password,
            role: 'client', // Papel padrão é cliente
            createdAt: new Date().toISOString()
        };
        
        // Adicionar à lista de usuários
        users.push(newUser);
        localStorage.setItem('petsync_users', JSON.stringify(users));
        
        // Mostrar mensagem de sucesso
        showNotification('Cadastro realizado com sucesso! Redirecionando para o login...', 'success');
        
        // Redirecionar para login após 2 segundos
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    });
}

// Função para criar usuários padrão
function createDefaultUsers() {
    const defaultUsers = [
        {
            id: 1,
            name: 'Administrador',
            email: 'admin@petsync.com',
            phone: '(33) 99999-9999',
            password: 'admin123',
            role: 'admin',
            createdAt: new Date().toISOString()
        },
        {
            id: 2,
            name: 'Cliente Teste',
            email: 'cliente@petsync.com',
            phone: '(33) 88888-8888',
            password: 'cliente123',
            role: 'client',
            createdAt: new Date().toISOString()
        }
    ];
    
    localStorage.setItem('petsync_users', JSON.stringify(defaultUsers));
}

// Função para adicionar elemento de notificação ao DOM
function addNotificationElement() {
    // Verificar se já existe
    if (document.getElementById('notification')) return;
    
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.id = 'notification';
    notification.className = 'notification';
    notification.innerHTML = `
        <div class="flex items-center">
            <span id="notification-message"></span>
            <button id="close-notification" class="ml-3 text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    // Adicionar estilos
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            max-width: 300px;
        }
        .notification.show {
            transform: translateX(0);
        }
        .notification.success {
            background-color: #10B981;
            color: white;
        }
        .notification.error {
            background-color: #EF4444;
            color: white;
        }
    `;
    
    // Adicionar ao DOM
    document.head.appendChild(style);
    document.body.appendChild(notification);
    
    // Configurar botão de fechar
    const closeNotification = document.getElementById('close-notification');
    if (closeNotification) {
        closeNotification.addEventListener('click', function() {
            notification.classList.remove('show');
        });
    }
}

// Função para mostrar notificação
function showNotification(message, type) {
    // Verificar se o elemento de notificação existe
    let notification = document.getElementById('notification');
    
    // Se não existir, criar
    if (!notification) {
        addNotificationElement();
        notification = document.getElementById('notification');
    }
    
    const notificationMessage = document.getElementById('notification-message');
    
    if (notification && notificationMessage) {
        // Definir mensagem
        notificationMessage.textContent = message;
        
        // Definir tipo (success ou error)
        notification.className = `notification ${type}`;
        
        // Mostrar notificação
        notification.classList.add('show');
        
        // Esconder notificação após 5 segundos
        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    }
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return localStorage.getItem('petsync_current_user') !== null;
}

// Função para fazer logout
function logout() {
    localStorage.removeItem('petsync_current_user');
    showNotification('Logout realizado com sucesso!', 'success');
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1000);
}

// Função para verificar se o usuário é administrador
function isAdmin() {
    const currentUser = JSON.parse(localStorage.getItem('petsync_current_user'));
    return currentUser && currentUser.role === 'admin';
}

// Função para proteger rotas que requerem login
function requireLogin() {
    if (!isLoggedIn()) {
        showNotification('Você precisa estar logado para acessar esta página', 'error');
        setTimeout(() => {
            window.location.href = `login.html?redirect=${encodeURIComponent(window.location.pathname)}`;
        }, 2000);
        return false;
    }
    return true;
}

// Função para proteger rotas que requerem admin
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        showNotification('Acesso restrito a administradores', 'error');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 2000);
        return false;
    }
    return true;
}
