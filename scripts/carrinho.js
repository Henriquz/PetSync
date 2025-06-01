document.addEventListener("DOMContentLoaded", function () {
    // Inicializar o carrinho
    initCart();

    // Carregar itens do carrinho (se estiver na página do carrinho)
    if (document.getElementById("cart-items-container")) {
        loadCartItems();
        setupDeliveryOptions();
        setupCheckoutButton();
        setupClearCartButton(); // Certifica-se que esta função é chamada
    }

    // Configurar notificações (em todas as páginas)
    setupNotifications();

    // Atualizar o contador do carrinho na barra de navegação (em todas as páginas)
    updateCartCounter();
});

// Função para inicializar o carrinho
function initCart() {
    if (!localStorage.getItem("petsync_cart")) {
        localStorage.setItem("petsync_cart", JSON.stringify([]));
    }
}

// Função para adicionar item ao carrinho
function addToCart(product, quantity = 1) {
    let cart = [];
    try {
        cart = JSON.parse(localStorage.getItem("petsync_cart")) || [];
        if (!Array.isArray(cart)) cart = [];
    } catch (e) {
        console.error("Erro ao ler carrinho:", e);
        cart = [];
        localStorage.setItem("petsync_cart", JSON.stringify([]));
    }

    const existingItemIndex = cart.findIndex((item) => item.id === product.id);

    if (existingItemIndex !== -1) {
        cart[existingItemIndex].quantity += quantity;
    } else {
        cart.push({
            ...product,
            quantity,
        });
    }

    localStorage.setItem("petsync_cart", JSON.stringify(cart));

    // Atualiza o ícone e contador do carrinho na navegação
    updateCartCounter();

    // Verifica o status de login (se a função existir)
    if (typeof isLoggedIn === 'function' && !isLoggedIn()) {
        showNotification("Item adicionado! Faça login para salvar seu carrinho.", "error");
        setTimeout(() => {
            const currentPage = window.location.pathname.split('/').pop() || 'index.html';
            window.location.href = `login.html?redirect=${encodeURIComponent(currentPage)}`;
        }, 2500);
    } else {
        showNotification(`${product.name} adicionado ao carrinho!`, "success");
    }
}

// Função para remover item do carrinho (usada internamente por updateCartItemQuantity)
function removeFromCart(productId) {
    let cart = [];
    try {
        cart = JSON.parse(localStorage.getItem("petsync_cart")) || [];
        if (!Array.isArray(cart)) cart = [];
    } catch (e) {
        console.error("Erro ao ler carrinho:", e);
        cart = [];
    }

    const updatedCart = cart.filter((item) => item.id !== productId);
    localStorage.setItem("petsync_cart", JSON.stringify(updatedCart));

    // Recarrega itens se estiver na página do carrinho
    if (document.getElementById("cart-items-container")) {
        loadCartItems();
    }

    showNotification("Item removido do carrinho", "success");
    updateCartCounter(); // Atualiza o contador na navegação
}

// Função para atualizar quantidade de um item no carrinho
function updateCartItemQuantity(productId, quantity) {
    let cart = [];
    try {
        cart = JSON.parse(localStorage.getItem("petsync_cart")) || [];
        if (!Array.isArray(cart)) cart = [];
    } catch (e) {
        console.error("Erro ao ler carrinho:", e);
        cart = [];
    }

    const itemIndex = cart.findIndex((item) => item.id === productId);

    if (itemIndex !== -1) {
        cart[itemIndex].quantity = quantity;
        if (quantity <= 0) {
            // Remove o item se a quantidade for 0 ou menor
            cart.splice(itemIndex, 1);
            showNotification("Item removido do carrinho", "success");
        } else {
            showNotification("Quantidade atualizada", "success");
        }
        localStorage.setItem("petsync_cart", JSON.stringify(cart));

        // Recarrega itens se estiver na página do carrinho
        if (document.getElementById("cart-items-container")) {
            loadCartItems();
        }
        updateCartCounter(); // Atualiza o contador na navegação
    }
}

// Função para carregar itens do carrinho na página (Executar apenas em carrinho.html)
function loadCartItems() {
    const cartItemsContainer = document.getElementById("cart-items-container");
    const emptyCartMessage = document.getElementById("empty-cart-message");
    const checkoutButton = document.getElementById("checkout-button");
    const clearCartSection = document.getElementById("clear-cart-section");

    // Verifica se os elementos existem (para evitar erros em outras páginas)
    if (!cartItemsContainer || !emptyCartMessage || !clearCartSection) {
        return;
    }

    let cart = [];
    try {
        const cartData = localStorage.getItem("petsync_cart");
        if (cartData) {
            cart = JSON.parse(cartData);
            if (!Array.isArray(cart)) {
                console.warn("Dados do carrinho no localStorage não são um array. Resetando carrinho.");
                cart = [];
                localStorage.setItem("petsync_cart", JSON.stringify([]));
            }
        } else {
            localStorage.setItem("petsync_cart", JSON.stringify([]));
        }
    } catch (error) {
        console.error("Erro ao ler ou parsear o carrinho do localStorage:", error);
        cart = [];
        localStorage.setItem("petsync_cart", JSON.stringify([]));
    }

    cartItemsContainer.innerHTML = "";
    let subtotal = 0;

    // Filtra itens inválidos antes de iterar
    const validCart = cart.filter(item => {
        if (!item || typeof item !== 'object' || !item.id || !item.name || typeof item.price === 'undefined' || typeof item.quantity === 'undefined') {
            console.warn("Item inválido detectado e removido:", item);
            return false;
        }
        const price = parseFloat(item.price);
        const quantity = parseInt(item.quantity);
        if (isNaN(price) || isNaN(quantity) || price <= 0 || quantity <= 0) {
            console.warn("Item com preço/quantidade inválida detectado e removido:", item);
            return false;
        }
        return true;
    });

    // Salva o carrinho filtrado de volta no localStorage se houve remoção de inválidos
    if (validCart.length !== cart.length) {
        localStorage.setItem("petsync_cart", JSON.stringify(validCart));
    }

    if (validCart.length === 0) {
        emptyCartMessage.classList.remove("hidden");
        clearCartSection.classList.add("hidden");
        if (checkoutButton) {
            checkoutButton.disabled = true;
            checkoutButton.classList.add("opacity-50", "cursor-not-allowed");
        }
        updateCartTotals(0, 0);
        updateCartCounter(); // Garante que o contador da nav seja 0
        return;
    }

    emptyCartMessage.classList.add("hidden");
    clearCartSection.classList.remove("hidden"); // Mostra botão Limpar
    if (checkoutButton) {
        checkoutButton.disabled = false;
        checkoutButton.classList.remove("opacity-50", "cursor-not-allowed");
    }

    validCart.forEach((item) => {
        const price = parseFloat(item.price);
        const quantity = parseInt(item.quantity);
        const itemTotal = price * quantity;
        subtotal += itemTotal;

        const imageUrl = item.image || 'https://via.placeholder.com/80x80?text=PetSync';
        const category = item.category || 'Sem categoria';

        const itemElement = document.createElement("div");
        itemElement.className = "cart-item p-6 border-b border-gray-200 flex flex-col md:flex-row items-start md:items-center";
        itemElement.innerHTML = `
            <div class="md:w-1/6 mb-4 md:mb-0">
                <img src="${imageUrl}" alt="${item.name}" class="w-20 h-20 object-cover rounded-lg">
            </div>
            <div class="md:w-3/6 md:pl-4">
                <h3 class="font-bold text-petGray">${item.name}</h3>
                <p class="text-sm text-gray-500">${category}</p>
            </div>
            <div class="md:w-1/6 flex items-center mt-2 md:mt-0">
                <button class="decrease-quantity px-2 py-1 border border-gray-300 rounded-l-md bg-gray-100 hover:bg-gray-200" data-id="${item.id}">-</button>
                <input type="number" value="${quantity}" min="1" class="item-quantity w-12 text-center border-t border-b border-gray-300" data-id="${item.id}">
                <button class="increase-quantity px-2 py-1 border border-gray-300 rounded-r-md bg-gray-100 hover:bg-gray-200" data-id="${item.id}">+</button>
            </div>
            <div class="md:w-1/6 text-right mt-2 md:mt-0">
                <p class="font-bold text-petBlue">R$ ${itemTotal.toFixed(2)}</p>
                <p class="text-sm text-gray-500">R$ ${price.toFixed(2)} cada</p>
            </div>
            <div class="md:w-1/12 text-right mt-2 md:mt-0">
                <button class="remove-item text-red-500 hover:text-red-700" data-id="${item.id}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        `;
        cartItemsContainer.appendChild(itemElement);
    });

    addCartItemEvents();
    const shipping = subtotal >= 100 ? 0 : 10;
    updateCartTotals(subtotal, shipping);
    updateCartCounter(); // Atualiza o contador na navegação
}

// Função para adicionar eventos aos itens do carrinho
function addCartItemEvents() {
    document.querySelectorAll(".decrease-quantity").forEach((button) => {
        button.addEventListener("click", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            const quantityInput = document.querySelector(`.item-quantity[data-id="${productId}"]`);
            let newQuantity = parseInt(quantityInput.value) - 1;
            // Chama updateCartItemQuantity que lida com a remoção se newQuantity <= 0
            updateCartItemQuantity(productId, newQuantity);
        });
    });

    document.querySelectorAll(".increase-quantity").forEach((button) => {
        button.addEventListener("click", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            const quantityInput = document.querySelector(`.item-quantity[data-id="${productId}"]`);
            const newQuantity = parseInt(quantityInput.value) + 1;
            updateCartItemQuantity(productId, newQuantity);
        });
    });

    document.querySelectorAll(".item-quantity").forEach((input) => {
        input.addEventListener("change", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            let newQuantity = parseInt(this.value);
            if (isNaN(newQuantity) || newQuantity < 1) {
                newQuantity = 1; // Se inválido, volta para 1
                this.value = 1;
            }
            updateCartItemQuantity(productId, newQuantity);
        });
    });

    document.querySelectorAll(".remove-item").forEach((button) => {
        button.addEventListener("click", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            // Chama a função que remove (quantidade 0) e atualiza o localStorage e a UI
            updateCartItemQuantity(productId, 0);
        });
    });
}

// Função para atualizar os totais do carrinho (apenas na página do carrinho)
function updateCartTotals(subtotal, shipping) {
    const subtotalElement = document.getElementById("cart-subtotal");
    const shippingElement = document.getElementById("cart-shipping");
    const totalElement = document.getElementById("cart-total");

    if (subtotalElement && shippingElement && totalElement) {
        subtotalElement.textContent = `R$ ${subtotal.toFixed(2)}`;
        shippingElement.textContent = shipping === 0 ? "Grátis" : `R$ ${shipping.toFixed(2)}`;
        const total = subtotal + shipping;
        totalElement.textContent = `R$ ${total.toFixed(2)}`;
    }
}

// Função para configurar opções de entrega (apenas na página do carrinho)
function setupDeliveryOptions() {
    const deliveryOptions = document.querySelectorAll(".delivery-option");
    deliveryOptions.forEach((option) => {
        option.addEventListener("click", function () {
            deliveryOptions.forEach((opt) => opt.classList.remove("selected"));
            this.classList.add("selected");
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
}

// Função para configurar botão de checkout (apenas na página do carrinho)
function setupCheckoutButton() {
    const checkoutButton = document.getElementById("checkout-button");
    const confirmationModal = document.getElementById("confirmation-modal");
    const closeConfirmation = document.getElementById("close-confirmation");

    if (checkoutButton && confirmationModal && closeConfirmation) {
        checkoutButton.addEventListener("click", function () {
            const selectedDelivery = document.querySelector('input[name="delivery-option"]:checked');
            if (!selectedDelivery) {
                showNotification("Por favor, selecione uma opção de entrega", "error");
                return;
            }

            let cart = [];
            try {
                cart = JSON.parse(localStorage.getItem("petsync_cart")) || [];
                if (!Array.isArray(cart)) cart = [];
            } catch (e) {
                console.error("Erro ao ler carrinho:", e);
                cart = [];
            }

            const validCartItems = cart.filter(item => {
                if (!item || typeof item !== 'object' || !item.id || !item.name || typeof item.price === 'undefined' || typeof item.quantity === 'undefined') return false;
                const price = parseFloat(item.price);
                const quantity = parseInt(item.quantity);
                return !(isNaN(price) || isNaN(quantity) || price <= 0 || quantity <= 0);
            });

            if (validCartItems.length === 0) {
                showNotification("Seu carrinho está vazio ou contém apenas itens inválidos.", "error");
                return;
            }

            const orderNumber = Math.floor(100000 + Math.random() * 900000);
            const orderDate = new Date().toLocaleDateString("pt-BR");
            const totalElement = document.getElementById("cart-total");
            const deliveryOption = selectedDelivery.id;
            let deliveryText = "";
            switch (deliveryOption) {
                case "option-address": deliveryText = "Receber no meu endereço"; break;
                case "option-pet": deliveryText = "Receber junto do pet"; break;
                case "option-pickup": deliveryText = "Irei buscar pessoalmente"; break;
            }

            document.getElementById("order-number").textContent = `#${orderNumber}`;
            document.getElementById("order-date").textContent = orderDate;
            document.getElementById("order-total").textContent = totalElement.textContent;
            document.getElementById("order-delivery").textContent = deliveryText;

            confirmationModal.classList.remove("hidden");

            saveOrder({
                id: orderNumber,
                date: orderDate,
                total: totalElement.textContent,
                delivery: deliveryText,
                items: validCartItems,
                status: "Processando",
            });

            localStorage.setItem("petsync_cart", JSON.stringify([]));
            loadCartItems();
            updateCartCounter();
        });

        closeConfirmation.addEventListener("click", function () {
            confirmationModal.classList.add("hidden");
            window.location.href = "index.html";
        });
    }
}

// *** FUNÇÃO MODIFICADA: Configurar Botão Limpar Carrinho com Modal ***
function setupClearCartButton() {
    const clearCartButton = document.getElementById("clear-cart-button");
    const clearCartModal = document.getElementById("clear-cart-confirmation-modal");
    const confirmClearButton = document.getElementById("confirm-clear-cart");
    const cancelClearButton = document.getElementById("cancel-clear-cart");
    const modalOverlay = clearCartModal; // O overlay é o próprio modal
    const modalContent = clearCartModal ? clearCartModal.querySelector("[data-modal-content]") : null;

    if (clearCartButton && clearCartModal && confirmClearButton && cancelClearButton && modalContent && modalOverlay) {
        
        // Função para abrir o modal com animação
        const openModal = () => {
            clearCartModal.classList.remove("hidden");
            // Força reflow para garantir que a transição inicial funcione
            void modalOverlay.offsetWidth;
            modalOverlay.classList.remove("opacity-0");
            modalContent.classList.remove("opacity-0", "scale-95");
            modalContent.classList.add("scale-100"); // Animação de entrada
        };

        // Função para fechar o modal com animação
        const closeModal = () => {
            modalOverlay.classList.add("opacity-0");
            modalContent.classList.add("opacity-0", "scale-95");
            modalContent.classList.remove("scale-100"); // Animação de saída
            // Espera a animação de saída terminar antes de esconder
            setTimeout(() => {
                 clearCartModal.classList.add("hidden");
            }, 300); // Tempo da transição CSS (definido no HTML/CSS)
        };

        // Abrir o modal ao clicar em "Limpar Carrinho"
        clearCartButton.addEventListener("click", openModal);

        // Fechar modal ao clicar em "Cancelar"
        cancelClearButton.addEventListener("click", closeModal);

        // Fechar modal ao clicar fora dele (no overlay)
        modalOverlay.addEventListener("click", function(event) {
            // Verifica se o clique foi no overlay e não no conteúdo do modal
            if (event.target === modalOverlay) {
                closeModal();
            }
        });

        // Executar a limpeza ao clicar em "Confirmar Limpeza"
        confirmClearButton.addEventListener("click", function () {
            // Limpa o localStorage
            localStorage.setItem("petsync_cart", JSON.stringify([]));

            // Recarrega a visualização do carrinho (mostrará vazio)
            loadCartItems();

            // Atualiza o contador na navegação
            updateCartCounter();

            // Fecha o modal
            closeModal();

            // Mostra notificação de sucesso
            showNotification("Carrinho limpo com sucesso!", "success");
        });
    } else {
        // Apenas loga um erro se o botão principal existir mas os elementos do modal não
        if (clearCartButton) {
            console.error("Elementos do modal de confirmação de limpeza de carrinho não encontrados no HTML!");
        }
    }
}

// Função para salvar pedido
function saveOrder(order) {
    let orders = JSON.parse(localStorage.getItem("petsync_orders")) || [];
    orders.push(order);
    localStorage.setItem("petsync_orders", JSON.stringify(orders));
}

// Função para atualizar o contador e visibilidade do link do carrinho na navegação
function updateCartCounter() {
    const cartLink = document.getElementById("cart-link");
    const cartCounter = document.getElementById("cart-counter");
    const mobileCartLink = document.getElementById("mobile-cart-link");
    const mobileCartCounter = document.getElementById("mobile-cart-counter");

    if (!cartLink || !cartCounter || !mobileCartLink || !mobileCartCounter) {
        return;
    }

    let cart = [];
    try {
        cart = JSON.parse(localStorage.getItem("petsync_cart")) || [];
        if (!Array.isArray(cart)) cart = [];
    } catch (e) {
        cart = [];
    }

    // Calcula o número total de itens (somando quantidades)
    const count = cart.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);

    // Atualiza o texto dos contadores
    cartCounter.textContent = count;
    mobileCartCounter.textContent = count;

    // Controla a visibilidade do link e do contador
    if (count > 0) {
        cartLink.classList.remove("hidden");
        cartCounter.classList.remove("hidden");
        mobileCartLink.classList.remove("hidden");
        mobileCartCounter.classList.remove("hidden");
    } else {
        cartLink.classList.add("hidden");
        cartCounter.classList.add("hidden");
        mobileCartLink.classList.add("hidden");
        mobileCartCounter.classList.add("hidden");
    }
}

// Funções de Notificação
let notificationTimeout;

function showNotification(message, type = "success") {
    const notificationArea = document.getElementById("notification-area") || createNotificationArea();
    notificationArea.innerHTML = ''; // Limpa notificações anteriores

    const notification = document.createElement("div");
    let bgColor = type === "success" ? "bg-green-500" : "bg-red-500";
    notification.className = `relative p-4 pr-8 rounded-md shadow-lg text-white ${bgColor} transition-opacity duration-300 ease-out opacity-0`;
    notification.style.minWidth = '250px';

    const messageSpan = document.createElement("span");
    messageSpan.textContent = message;

    const closeButton = document.createElement("button");
    closeButton.innerHTML = "&times;";
    closeButton.className = "absolute top-1 right-2 text-xl font-bold leading-none hover:text-gray-200";
    closeButton.setAttribute("aria-label", "Fechar");

    closeButton.onclick = () => {
        clearTimeout(notificationTimeout);
        notification.classList.remove("opacity-100");
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    };

    notification.appendChild(messageSpan);
    notification.appendChild(closeButton);
    notificationArea.appendChild(notification);

    void notification.offsetWidth;
    notification.classList.add("opacity-100");

    clearTimeout(notificationTimeout);
    notificationTimeout = setTimeout(() => {
        notification.classList.remove("opacity-100");
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

function createNotificationArea() {
    let area = document.getElementById("notification-area");
    if (!area) {
        area = document.createElement("div");
        area.id = "notification-area";
        area.style.position = "fixed";
        area.style.top = "20px";
        area.style.right = "20px";
        area.style.zIndex = "1050";
        area.style.display = "flex";
        area.style.flexDirection = "column";
        area.style.gap = "10px";
        document.body.appendChild(area);
    }
    return area;
}

function setupNotifications() {
    if (!document.getElementById("notification-area")) {
        createNotificationArea();
    }
}

