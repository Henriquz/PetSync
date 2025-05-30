document.addEventListener("DOMContentLoaded", function () {
    // Inicializar o carrinho
    initCart();

    // Carregar itens do carrinho
    loadCartItems();

    // Configurar opções de entrega
    setupDeliveryOptions();

    // Configurar botão de checkout
    setupCheckoutButton();

    // Configurar botão Limpar Carrinho
    setupClearCartButton(); // <-- Nova chamada

    // Configurar notificações
    setupNotifications();
});

// Função para inicializar o carrinho
function initCart() {
    if (!localStorage.getItem("petsync_cart")) {
        localStorage.setItem("petsync_cart", JSON.stringify([]));
    }
}

// Função para adicionar item ao carrinho
function addToCart(product, quantity = 1) {
    if (typeof isLoggedIn !== 'function' || !isLoggedIn()) {
        showNotification("Você precisa estar logado para adicionar produtos ao carrinho", "error");
        setTimeout(() => {
            window.location.href = "login.html?redirect=produtos.html";
        }, 2000);
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
    showNotification(`${product.name} adicionado ao carrinho!`, "success");
    updateCartCounter();
}

// Função para remover item do carrinho
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
    loadCartItems();
    showNotification("Item removido do carrinho", "success");
    updateCartCounter();
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
            cart.splice(itemIndex, 1);
        }
        localStorage.setItem("petsync_cart", JSON.stringify(cart));
        loadCartItems();
        updateCartCounter();
    }
}

// Função para carregar itens do carrinho na página (Versão Robusta)
function loadCartItems() {
    const cartItemsContainer = document.getElementById("cart-items-container");
    const emptyCartMessage = document.getElementById("empty-cart-message");
    const checkoutButton = document.getElementById("checkout-button");
    const clearCartSection = document.getElementById("clear-cart-section"); // <-- Pega a seção do botão

    if (!cartItemsContainer || !emptyCartMessage || !clearCartSection) {
        console.error("Elementos essenciais do carrinho não encontrados no DOM.");
        return;
    }

    let cart = [];
    let validItemsCount = 0; // <-- Contador de itens válidos
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

    if (cart.length === 0) {
        emptyCartMessage.classList.remove("hidden");
        clearCartSection.classList.add("hidden"); // <-- Esconde botão se carrinho vazio
        if (checkoutButton) {
            checkoutButton.disabled = true;
            checkoutButton.classList.add("opacity-50", "cursor-not-allowed");
        }
        updateCartTotals(0, 0);
        return;
    }

    emptyCartMessage.classList.add("hidden");
    if (checkoutButton) {
        checkoutButton.disabled = false;
        checkoutButton.classList.remove("opacity-50", "cursor-not-allowed");
    }

    let subtotal = 0;

    cart.forEach((item, index) => {
        if (!item || typeof item !== 'object' || !item.id || !item.name || typeof item.price === 'undefined' || typeof item.quantity === 'undefined') {
            console.warn(`Item inválido no índice ${index} do carrinho:`, item);
            return;
        }

        const price = parseFloat(item.price) || 0;
        const quantity = parseInt(item.quantity) || 0;

        // Considera inválido se preço ou quantidade for zero ou NaN após conversão
        if (isNaN(price) || isNaN(quantity) || price <= 0 || quantity <= 0) {
             console.warn(`Item com preço/quantidade inválida no índice ${index}:`, item);
             return;
        }

        validItemsCount++; // <-- Incrementa contador de itens válidos
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

    // Mostra/Esconde botão Limpar Carrinho baseado se há itens VÁLIDOS
    if (validItemsCount > 0) {
        clearCartSection.classList.remove("hidden");
    } else {
        clearCartSection.classList.add("hidden");
        // Se não há itens válidos, mostra msg de vazio mesmo que cart.length > 0
        emptyCartMessage.classList.remove("hidden");
         if (checkoutButton) {
            checkoutButton.disabled = true;
            checkoutButton.classList.add("opacity-50", "cursor-not-allowed");
        }
    }

    addCartItemEvents();
    const shipping = subtotal >= 100 ? 0 : 10;
    updateCartTotals(subtotal, shipping);
}

// Função para adicionar eventos aos itens do carrinho
function addCartItemEvents() {
    document.querySelectorAll(".decrease-quantity").forEach((button) => {
        button.addEventListener("click", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            const quantityInput = document.querySelector(`.item-quantity[data-id="${productId}"]`);
            let newQuantity = parseInt(quantityInput.value) - 1;
            if (newQuantity < 1) newQuantity = 1;
            quantityInput.value = newQuantity;
            updateCartItemQuantity(productId, newQuantity);
        });
    });

    document.querySelectorAll(".increase-quantity").forEach((button) => {
        button.addEventListener("click", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            const quantityInput = document.querySelector(`.item-quantity[data-id="${productId}"]`);
            const newQuantity = parseInt(quantityInput.value) + 1;
            quantityInput.value = newQuantity;
            updateCartItemQuantity(productId, newQuantity);
        });
    });

    document.querySelectorAll(".item-quantity").forEach((input) => {
        input.addEventListener("change", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            let newQuantity = parseInt(this.value);
            if (isNaN(newQuantity) || newQuantity < 1) {
                newQuantity = 1;
                this.value = 1;
            }
            updateCartItemQuantity(productId, newQuantity);
        });
    });

    document.querySelectorAll(".remove-item").forEach((button) => {
        button.addEventListener("click", function () {
            const productId = parseInt(this.getAttribute("data-id"));
            removeFromCart(productId);
        });
    });
}

// Função para atualizar os totais do carrinho
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

// Função para configurar opções de entrega
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

// Função para configurar botão de checkout
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

            // Filtrar apenas itens válidos para o pedido final
            const validCartItems = cart.filter(item => {
                 if (!item || typeof item !== 'object' || !item.id || !item.name || typeof item.price === 'undefined' || typeof item.quantity === 'undefined') return false;
                 const price = parseFloat(item.price) || 0;
                 const quantity = parseInt(item.quantity) || 0;
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
                items: validCartItems, // Salva apenas itens válidos
                status: "Processando",
            });

            localStorage.setItem("petsync_cart", JSON.stringify([])); // Limpa o carrinho após pedido
            loadCartItems(); // Atualiza a exibição (mostrar vazio)
            updateCartCounter(); // Atualiza o contador para 0
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
    const modalContent = clearCartModal ? clearCartModal.querySelector("[data-modal-content]") : null;

    if (clearCartButton && clearCartModal && confirmClearButton && cancelClearButton && modalContent) {
        // Abrir o modal ao clicar em "Limpar Carrinho"
        clearCartButton.addEventListener("click", function () {
            clearCartModal.classList.remove("hidden");
            // Força reflow para garantir que a animação de entrada funcione
            void modalContent.offsetWidth;
            modalContent.style.transform = 'scale(1)';
            modalContent.style.opacity = '1';
        });

        // Função para fechar o modal
        const closeModal = () => {
            modalContent.style.transform = 'scale(0.95)';
            modalContent.style.opacity = '0';
            // Espera a animação de saída terminar antes de esconder
            setTimeout(() => {
                 clearCartModal.classList.add("hidden");
            }, 300); // Tempo da transição CSS
        };

        // Fechar modal ao clicar em "Cancelar"
        cancelClearButton.addEventListener("click", closeModal);

        // Fechar modal ao clicar fora dele (no overlay)
        clearCartModal.addEventListener("click", function(event) {
            if (event.target === clearCartModal) {
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
        console.error("Elementos do modal de limpeza de carrinho não encontrados!");
    }
}

// Função para salvar pedido (exemplo, adaptar conforme necessário)
function saveOrder(order) {
    let orders = JSON.parse(localStorage.getItem("petsync_orders")) || [];
    orders.push(order);
    localStorage.setItem("petsync_orders", JSON.stringify(orders));
}

// Função para atualizar o contador de itens no carrinho (exemplo)
function updateCartCounter() {
    const cartCounter = document.getElementById("cart-counter"); // Assumindo que existe um elemento com este ID
    if (cartCounter) {
        let cart = [];
         try {
            cart = JSON.parse(localStorage.getItem("petsync_cart")) || [];
            if (!Array.isArray(cart)) cart = [];
        } catch (e) {
            cart = [];
        }
        const count = cart.reduce((sum, item) => sum + (item.quantity || 0), 0);
        cartCounter.textContent = count;
        cartCounter.style.display = count > 0 ? "inline-block" : "none";
    }
}

// Funções de Notificação (mantendo as originais)
let notificationTimeout;
function showNotification(message, type = "success") {
    const notificationArea = document.getElementById("notification-area") || createNotificationArea();
    const notification = document.createElement("div");
    notification.className = `notification ${type}`;
    notification.textContent = message;

    notificationArea.appendChild(notification);

    // Força reflow para aplicar a transição
    void notification.offsetWidth;

    notification.classList.add("show");

    clearTimeout(notificationTimeout);
    notificationTimeout = setTimeout(() => {
        notification.classList.remove("show");
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300); // Espera a transição de saída terminar
    }, 3000);
}

function createNotificationArea() {
    const area = document.createElement("div");
    area.id = "notification-area";
    area.style.position = "fixed";
    area.style.top = "20px";
    area.style.right = "20px";
    area.style.zIndex = "1050"; // Acima do modal
    document.body.appendChild(area);
    return area;
}

function setupNotifications() {
    // Apenas garante que a área exista se ainda não foi criada
    if (!document.getElementById("notification-area")) {
        createNotificationArea();
    }
}


