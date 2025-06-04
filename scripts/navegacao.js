
document.addEventListener("DOMContentLoaded", function () {
    // Adiciona a opção Galeria ao menu
    const menuDesktop = document.querySelector(".hidden.md\\:flex.space-x-8");
    const menuMobile = document.getElementById("mobile-menu");

    if (menuDesktop) {
        const links = menuDesktop.querySelectorAll("a");
        let sobreLink = null;
        for (let i = 0; i < links.length; i++) {
            if (links[i].textContent.trim() === "Sobre") {
                sobreLink = links[i];
                break;
            }
        }
        const galeriaLink = document.createElement("a");
        galeriaLink.href = "galeria.html";
        galeriaLink.textContent = "Galeria";
        galeriaLink.className = "text-petGray hover:text-petBlue font-medium";
        if (sobreLink) {
            menuDesktop.insertBefore(galeriaLink, sobreLink);
        }
    }

    if (menuMobile) {
        const links = menuMobile.querySelectorAll("a");
        let sobreLink = null;
        for (let i = 0; i < links.length; i++) {
            if (links[i].textContent.trim() === "Sobre") {
                sobreLink = links[i];
                break;
            }
        }
        const galeriaLink = document.createElement("a");
        galeriaLink.href = "galeria.html";
        galeriaLink.textContent = "Galeria";
        galeriaLink.className = "block py-2 text-petGray hover:text-petBlue font-medium";
        if (sobreLink) {
            menuMobile.insertBefore(galeriaLink, sobreLink);
        }
    }

    // Configura navegação suave para links internos
    const configureLink = function (link, targetId) {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: "smooth" });
            }
        });
    };

    // Adiciona IDs às seções importantes
    const servicosSection = document.querySelector("h2");
    if (servicosSection && servicosSection.textContent.includes("Nossos Serviços")) {
        let section = servicosSection.closest("section");
        if (section) section.id = "servicos";
    }
    const produtosSection = document.querySelectorAll("h2")[1];
    if (produtosSection && produtosSection.textContent.includes("Produtos Populares")) {
        let section = produtosSection.closest("section");
        if (section) section.id = "produtos";
    }
    const sobreHeadings = document.querySelectorAll("h2, h3, h4");
    for (let i = 0; i < sobreHeadings.length; i++) {
        if (sobreHeadings[i].textContent.includes("Sobre a PetSync")) {
            let section = sobreHeadings[i].closest("section");
            if (section) section.id = "sobre";
            break;
        }
    }
    const contatoHeading = Array.from(
        document.querySelectorAll("h2, h3, h4")
    ).find((el) => el.textContent.trim().toLowerCase().includes("entre em contato"));
    if (contatoHeading) {
        const contatoSection = contatoHeading.closest("section");
        if (contatoSection) contatoSection.id = "contato";
    }

    // Configura links do menu (Apenas na página inicial para links internos)
    if (window.location.pathname.endsWith('/') || window.location.pathname.endsWith('index.html')) {
        document.querySelectorAll("nav a").forEach((link) => {
            const text = link.textContent.trim();
            if (text === "Serviços") {
                link.href = "#servicos";
                configureLink(link, "servicos");
            } else if (text === "Produtos") {
                // Na index, aponta para a seção. Em outras páginas, mantém o href original (produtos.html)
                link.href = "#produtos"; 
                configureLink(link, "produtos");
            } else if (text === "Contato") {
                link.href = "#contato";
                configureLink(link, "contato");
            } else if (text === "Sobre") {
                link.href = "#sobre";
                configureLink(link, "sobre");
            } else if (text === "Início") {
                link.href = "index.html";
            }
            // Links como Galeria, Carrinho, Perfil, etc., manterão seus hrefs originais
        });

        // Configura botões específicos da index.html
        document.querySelectorAll("a").forEach((link) => {
            if (link.textContent.includes("Nossos Serviços")) {
                link.href = "#servicos";
                configureLink(link, "servicos");
            } else if (link.textContent.includes("Ver Todos os Prod")) {
                link.href = "produtos.html"; // Garante que este botão sempre vá para produtos.html
            } else if (link.textContent.includes("Agendar Visita")) {
                link.href = "agendamento.html";
            }
        });
    } else {
        // Em outras páginas, garante que o link 'Produtos' vá para produtos.html
        document.querySelectorAll("nav a").forEach((link) => {
            const text = link.textContent.trim();
            if (text === "Produtos") {
                link.href = "produtos.html";
                // Remove qualquer listener de clique que possa ter sido adicionado erroneamente
                // (Embora a condição acima deva prevenir isso, é uma segurança extra)
                // link.removeEventListener('click', configureLink); // Não é simples remover listener anônimo, mas a condição if/else resolve
            }
        });
    }

    // Adiciona funcionalidade ao botão do menu mobile
    const mobileMenuButton = document.getElementById("mobile-menu-button");
    const mobileMenu = document.getElementById("mobile-menu");
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener("click", function () {
            mobileMenu.classList.toggle("hidden");
        });
    }

    // Atualizar menu de navegação e carrinho ao carregar a página
    updateNavigation();
    addCartCounter();

    // Remove o link "Início" do menu mobile especificamente na página galeria.html
    if (window.location.pathname.includes("galeria.html")) {
        const mobileMenuForRemoval = document.getElementById("mobile-menu");
        if (mobileMenuForRemoval) {
            // Tenta encontrar o link de "Início" de forma mais robusta
            const inicioLinks = mobileMenuForRemoval.querySelectorAll("a");
            inicioLinks.forEach(link => {
                // Verifica pelo texto e pelo href para ter mais certeza
                if (link.textContent.trim() === "Início" && (link.getAttribute("href") === "index.html" || link.getAttribute("href") === "./index.html")) {
                    link.remove();
                }
            });
        }
    }

    // Opcional: Chamar updates em eventos específicos (login/logout)
    // document.addEventListener("userLoggedIn", () => { updateNavigation(); addCartCounter(); });
    // document.addEventListener('userLoggedOut', () => { updateNavigation(); addCartCounter(); });

    // Remover ou comentar o setInterval para melhor performance
    /*
    setInterval(function() {
        updateNavigation();
        addCartCounter();
    }, 1000);
    */
});

// Função para verificar se o usuário está logado
function isLoggedIn() {
    try {
        return localStorage.getItem("petsync_current_user") !== null;
    } catch (e) {
        console.error("Erro ao acessar localStorage:", e);
        return false;
    }
}

// Função para fazer logout
function logout() {
    try {
        localStorage.removeItem("petsync_current_user");
        updateNavigation(); // Atualiza o menu imediatamente
        addCartCounter(); // Atualiza o carrinho imediatamente
        if (typeof showNotification === "function") {
            showNotification("Logout realizado com sucesso!", "success");
        }
        setTimeout(() => {
            window.location.href = "index.html";
        }, 1000);
    } catch (e) {
        console.error("Erro durante o logout:", e);
        if (typeof showNotification === "function") {
            showNotification("Erro ao fazer logout.", "error");
        }
    }
}

// Função para verificar se o usuário é administrador
function isAdmin() {
    try {
        const currentUser = JSON.parse(localStorage.getItem("petsync_current_user"));
        return currentUser && currentUser.role === "admin";
    } catch (e) {
        console.error("Erro ao verificar status de admin:", e);
        return false;
    }
}

// Função para atualizar a navegação baseada no status de login
function updateNavigation() {
    const isUserLoggedIn = isLoggedIn();
    const currentUser = isUserLoggedIn ? JSON.parse(localStorage.getItem("petsync_current_user") || '{}') : null;
    const userName = currentUser && currentUser.name ? currentUser.name.split(' ')[0] : 'Usuário'; // Get first name or default
    const desktopNav = document.querySelector(".hidden.md\\:flex.space-x-6, .hidden.md\\:flex.space-x-8");
    const mobileNav = document.getElementById("mobile-menu");

    // --- Atualização Menu Desktop ---
    if (desktopNav) {
        const existingLinksDesktop = desktopNav.querySelectorAll(
            'a[href*="login.html"], a[href*="perfil.html"], a.login-btn-marker'
        );
        existingLinksDesktop.forEach((link) => link.remove());

        const loginLink = document.createElement("a");
        loginLink.classList.add("login-btn-marker");

        if (isUserLoggedIn) {
            loginLink.href = "perfil.html";
            loginLink.textContent = `Olá, ${userName}`;
            loginLink.className = "text-petGray hover:text-petBlue font-medium login-btn-marker";
        } else {
            loginLink.href = "login.html";
            loginLink.textContent = "Entrar";
            loginLink.className =
                "inline-block px-4 py-1.5 bg-petOrange text-white text-sm font-semibold rounded-full shadow-md hover:bg-petBlue hover:shadow-lg transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-petBlue focus:ring-opacity-50 login-btn-marker";
        }
        // Adiciona ANTES do último elemento se houver um link de carrinho, senão adiciona ao final
        const cartLinkDesktop = desktopNav.querySelector('a[href*="carrinho.html"]');
        if (cartLinkDesktop) {
             desktopNav.insertBefore(loginLink, cartLinkDesktop.nextSibling); // Insere depois do carrinho
        } else {
             desktopNav.appendChild(loginLink); // Adiciona ao final se não houver carrinho
        }
    }

    // --- Atualização Menu Mobile ---
    if (mobileNav) {
        const existingLinksMobile = mobileNav.querySelectorAll(
            'a[href*="login.html"], a[href*="perfil.html"], a.login-btn-marker-mobile'
        );
        existingLinksMobile.forEach((link) => link.remove());

        const mobileLoginLink = document.createElement("a");
        mobileLoginLink.classList.add("login-btn-marker-mobile");

        if (isUserLoggedIn) {
            mobileLoginLink.href = "perfil.html";
            mobileLoginLink.textContent = `Olá, ${userName}`;
            mobileLoginLink.className =
                "block py-2 text-petGray hover:text-petBlue font-medium login-btn-marker-mobile";
        } else {
            mobileLoginLink.href = "login.html";
            mobileLoginLink.textContent = "Acessar sua Conta";
            mobileLoginLink.className =
                "block w-full mt-2 py-2 bg-petOrange text-white text-center text-base font-semibold rounded-md hover:bg-petBlue hover:text-white transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-petBlue focus:ring-opacity-50 login-btn-marker-mobile";
        }
         // Adiciona ANTES do último elemento se houver um link de carrinho, senão adiciona ao final
        const cartLinkMobile = mobileNav.querySelector('a[href*="carrinho.html"]');
        if (cartLinkMobile) {
             mobileNav.insertBefore(mobileLoginLink, cartLinkMobile.nextSibling); // Insere depois do carrinho
        } else {
             mobileNav.appendChild(mobileLoginLink); // Adiciona ao final se não houver carrinho
        }
    }

    // Lógica do link Admin
    const adminLink = document.getElementById("admin-link");
    if (adminLink) {
        if (currentUser && currentUser.role === "admin") {
            adminLink.style.display = "block";
        } else {
            adminLink.style.display = "none";
        }
    }
}

// Função para adicionar contador do carrinho ao menu (Versão Final com Validação)
function addCartCounter(forceCount = null) {
    if (!isLoggedIn()) {
        const desktopNav = document.querySelector(".hidden.md\\:flex.space-x-6, .hidden.md\\:flex.space-x-8");
        const mobileMenu = document.getElementById("mobile-menu");
        if (desktopNav) {
            let cartLink = desktopNav.querySelector('a[href*="carrinho.html"]');
            if (cartLink) cartLink.remove();
        }
        if (mobileMenu) {
            let mobileCartLink = mobileMenu.querySelector('a[href*="carrinho.html"]');
            if (mobileCartLink) mobileCartLink.remove();
        }
        return;
    }

    let cart = [];
    let validItemCount = 0;
    try {
        const cartData = localStorage.getItem("petsync_cart");
        if (cartData) {
            cart = JSON.parse(cartData);
            if (!Array.isArray(cart)) cart = [];
        } else {
            localStorage.setItem("petsync_cart", JSON.stringify([]));
        }
    } catch (error) {
        console.error("Erro ao ler ou parsear o carrinho do localStorage em addCartCounter:", error);
        cart = [];
    }

    // *** CONTAGEM CORRIGIDA: Conta apenas itens válidos ***
    if (forceCount !== null && !isNaN(parseInt(forceCount))) {
        // Usa contagem forçada se válida e fornecida
        validItemCount = parseInt(forceCount);
    } else {
        // Calcula a contagem validando os itens do localStorage
        validItemCount = cart.reduce((total, item) => {
            if (!item || typeof item !== 'object' || !item.id || !item.name || typeof item.price === 'undefined' || typeof item.quantity === 'undefined') return total;
            const price = parseFloat(item.price) || 0;
            const quantity = parseInt(item.quantity) || 0;
            if (isNaN(price) || isNaN(quantity) || price <= 0 || quantity <= 0) return total;
            return total + quantity;
        }, 0);
    }
    // *** FIM DA CONTAGEM CORRIGIDA ***

    const isCartPage = window.location.pathname.includes("carrinho.html");
    const desktopNav = document.querySelector(".hidden.md\\:flex.space-x-6, .hidden.md\\:flex.space-x-8");
    const mobileMenu = document.getElementById("mobile-menu");

    // Remover links de carrinho existentes antes de adicionar/atualizar
    if (desktopNav) {
        let existingCartLink = desktopNav.querySelector('a[href*="carrinho.html"]');
        if (existingCartLink) existingCartLink.remove();
    }
    if (mobileMenu) {
        let existingMobileCartLink = mobileMenu.querySelector('a[href*="carrinho.html"]');
        if (existingMobileCartLink) existingMobileCartLink.remove();
    }

    // Não mostra o ícone na própria página do carrinho
    if (isCartPage) {
        return;
    }

    // Adicionar contador ao menu desktop (se houver itens VÁLIDOS)
    if (desktopNav && validItemCount > 0) {
        let cartLink = document.createElement("a");
        cartLink.href = "carrinho.html";
        cartLink.className = "relative text-petGray hover:text-petBlue";
        cartLink.innerHTML = `
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>`;

        cartLink.style.position = "relative";
        const cartCounter = document.createElement("span");
        cartCounter.id = "cart-counter";
        cartCounter.className =
            "absolute -top-1 -right-1 bg-petOrange text-white text-xs rounded-full h-5 w-5 flex items-center justify-center";
        cartCounter.textContent = validItemCount; // Usa contagem válida
        cartLink.appendChild(cartCounter);

        const loginLinkDesktop = desktopNav.querySelector(".login-btn-marker");
        if (loginLinkDesktop) {
            desktopNav.insertBefore(cartLink, loginLinkDesktop);
        } else {
            desktopNav.appendChild(cartLink);
        }
    }

    // Adicionar contador ao menu mobile (se houver itens VÁLIDOS)
    if (mobileMenu && validItemCount > 0) {
        let mobileCartLink = document.createElement("a");
        mobileCartLink.href = "carrinho.html";
        mobileCartLink.className = "block py-2 text-petGray hover:text-petBlue font-medium relative";
        mobileCartLink.innerHTML = `
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        `;

        mobileCartLink.style.position = "relative";
        const mobileCartCounter = document.createElement("span");
        mobileCartCounter.id = "cart-counter-mobile";
        mobileCartCounter.className =
            "absolute top-1 left-4 bg-petOrange text-white text-xs rounded-full h-5 w-5 flex items-center justify-center";
        mobileCartCounter.textContent = validItemCount; // Usa contagem válida
        mobileCartLink.appendChild(mobileCartCounter);

        const loginLinkMobile = mobileMenu.querySelector(".login-btn-marker-mobile");
        if (loginLinkMobile) {
            mobileMenu.insertBefore(mobileCartLink, loginLinkMobile);
        } else {
            mobileMenu.appendChild(mobileCartLink);
        }
    }
}
