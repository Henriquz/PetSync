document.addEventListener('DOMContentLoaded', function() {
    // Função para verificar o status de login e atualizar a navbar
    function updateNavbarLoginStatus() {
    const currentUser = JSON.parse(localStorage.getItem('petsync_current_user'));

    // Elementos da Navbar Desktop
    const desktopNav = document.querySelector('nav .hidden.md\\:flex');
    const loginLinkDesktop = desktopNav ? desktopNav.querySelector('a[href="login.html"]') : null;
    const userInfoDesktopPlaceholder = document.getElementById('user-info-desktop');

    // Elementos da Navbar Mobile
    const mobileMenu = document.getElementById('mobile-menu');
    const loginLinkMobile = mobileMenu ? mobileMenu.querySelector('a[href="login.html"]') : null;
    const userInfoMobilePlaceholder = document.getElementById('user-info-mobile');

    if (currentUser) {
        // Usuário está logado
        const userName = currentUser.name.split(' ')[0];

        // Atualiza Desktop Navbar
        if (loginLinkDesktop) {
            loginLinkDesktop.style.display = 'none';
        }
        if (userInfoDesktopPlaceholder) {
            userInfoDesktopPlaceholder.innerHTML = `
                <span class="text-petGray font-medium">Olá, ${userName}!</span>
            `;
            userInfoDesktopPlaceholder.style.display = 'flex';
            userInfoDesktopPlaceholder.classList.add('items-center');
        }

        // Atualiza Mobile Navbar
        if (loginLinkMobile) {
            loginLinkMobile.style.display = 'none';
        }
        if (userInfoMobilePlaceholder) {
            userInfoMobilePlaceholder.innerHTML = `
                <span class="block py-2 text-petGray font-medium">Olá, ${userName}!</span>
            `;
            userInfoMobilePlaceholder.style.display = 'block';
        }

    } else {
        // Usuário não está logado
        if (loginLinkDesktop) {
            loginLinkDesktop.style.display = 'inline-block';
        }
        if (userInfoDesktopPlaceholder) {
            userInfoDesktopPlaceholder.style.display = 'none';
        }
        if (loginLinkMobile) {
            loginLinkMobile.style.display = 'block';
        }
        if (userInfoMobilePlaceholder) {
            userInfoMobilePlaceholder.style.display = 'none';
        }
    }
}
    // Verifica se a função logout está disponível (de auth.js)
    if (typeof logout === 'function') {
        // Chama a função para atualizar a navbar assim que o DOM estiver pronto
        updateNavbarLoginStatus();
    } else {
        // Tenta novamente após um pequeno atraso, caso auth.js carregue depois
        setTimeout(() => {
             if (typeof logout === 'function') {
                 updateNavbarLoginStatus();
             } else {
                 console.error("Função logout() não encontrada. Certifique-se de que auth.js foi carregado antes de navbar-update.js.");
             }
        }, 50); // Atraso pequeno
    }
});

