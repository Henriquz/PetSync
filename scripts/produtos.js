document.addEventListener('DOMContentLoaded', function() {
    // Carregar produtos
    loadProducts();
    
    // Configurar filtros de categoria
    setupCategoryFilters();
    
    // Configurar busca de produtos
    setupProductSearch();
    
    // Configurar modal de produto
    setupProductModal();
    
    // Atualizar contador do carrinho
    updateCartCounter();
});

// Array de produtos (simulando banco de dados)
const products = [
    {
        id: 1,
        name: 'Ração Premium para Cães',
        category: 'racao',
        price: 89.90,
        oldPrice: 109.90,
        image: 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Ração premium para cães adultos de todas as raças. Formulada com ingredientes naturais e nutrientes essenciais.',
        features: [
            'Proteínas de alta qualidade',
            'Sem corantes artificiais',
            'Fortalece o sistema imunológico',
            'Melhora a saúde do pelo e da pele'
        ]
    },
    {
        id: 2,
        name: 'Brinquedo Interativo para Gatos',
        category: 'brinquedos',
        price: 45.90,
        oldPrice: null,
        image: 'https://images.unsplash.com/photo-1526336179256-1347bdb255ee?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Brinquedo interativo que estimula o instinto de caça dos gatos, proporcionando diversão e exercício.',
        features: [
            'Estimula o instinto natural',
            'Material durável e seguro',
            'Ajuda a reduzir o estresse',
            'Ideal para gatos de todas as idades'
        ]
    },
    {
        id: 3,
        name: 'Coleira Antipulgas',
        category: 'acessorios',
        price: 35.50,
        oldPrice: 42.90,
        image: 'https://images.unsplash.com/photo-1599443015574-f61d38122a34?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Coleira antipulgas e carrapatos para cães, com proteção de longa duração e resistente à água.',
        features: [
            'Proteção por até 8 meses',
            'Resistente à água',
            'Sem cheiro',
            'Segura para pets e família'
        ]
    },
    {
        id: 4,
        name: 'Shampoo Hipoalergênico',
        category: 'higiene',
        price: 28.90,
        oldPrice: null,
        image: 'https://images.unsplash.com/photo-1584305574647-0cc949a2bb9f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Shampoo hipoalergênico para cães e gatos com pele sensível. Limpa profundamente sem ressecar.',
        features: [
            'Fórmula suave',
            'pH balanceado',
            'Sem parabenos',
            'Hidrata a pele e o pelo'
        ]
    },
    {
        id: 5,
        name: 'Suplemento Vitamínico',
        category: 'medicamentos',
        price: 65.00,
        oldPrice: 79.90,
        image: 'https://images.unsplash.com/photo-1585435557885-42e2e1099b37?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Suplemento vitamínico completo para cães e gatos, fortalece o sistema imunológico e promove saúde.',
        features: [
            'Complexo vitamínico completo',
            'Melhora a imunidade',
            'Fortalece ossos e articulações',
            'Fácil administração'
        ]
    },
    {
        id: 6,
        name: 'Cama Ortopédica para Cães',
        category: 'acessorios',
        price: 129.90,
        oldPrice: 159.90,
        image: 'https://images.unsplash.com/photo-1541599540903-216a46ca1dc0?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Cama ortopédica para cães de médio e grande porte, ideal para pets idosos ou com problemas articulares.',
        features: [
            'Espuma de memória',
            'Capa removível e lavável',
            'Alivia pressão nas articulações',
            'Base antiderrapante'
        ]
    },
    {
        id: 7,
        name: 'Ração Úmida para Gatos',
        category: 'racao',
        price: 5.90,
        oldPrice: null,
        image: 'https://images.unsplash.com/photo-1600628421055-4d30de868b8f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Ração úmida premium para gatos, com pedaços suculentos em molho. Sabor irresistível para os felinos.',
        features: [
            'Alto teor de proteínas',
            'Sem conservantes artificiais',
            'Hidratação adequada',
            'Ideal para gatos exigentes'
        ]
    },
    {
        id: 8,
        name: 'Bola de Borracha Resistente',
        category: 'brinquedos',
        price: 22.50,
        oldPrice: 29.90,
        image: 'https://images.unsplash.com/photo-1575859431774-2e57ed632664?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        description: 'Bola de borracha super resistente para cães, ideal para brincadeiras de buscar e morder.',
        features: [
            'Borracha natural resistente',
            'Quica alto',
            'Flutua na água',
            'Cores vibrantes'
        ]
    }
];

// Função para carregar produtos na página
function loadProducts(filteredProducts = null ) {
    const productsContainer = document.getElementById('products-container');
    if (!productsContainer) return;
    
    // Limpar container
    productsContainer.innerHTML = '';
    
    // Usar produtos filtrados ou todos os produtos
    const productsToShow = filteredProducts || products;
    
    // Verificar se há produtos para mostrar
    if (productsToShow.length === 0) {
        productsContainer.innerHTML = `
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-xl font-medium text-gray-500">Nenhum produto encontrado</h3>
                <p class="text-gray-500 mt-2">Tente ajustar seus filtros ou termos de busca.</p>
            </div>
        `;
        return;
    }
    
    // Adicionar cada produto ao container
    productsToShow.forEach(product => {
        const productCard = document.createElement('div' );
        productCard.className = 'product-card bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-2';
        productCard.innerHTML = `
            <div class="relative">
                <img src="${product.image}" alt="${product.name}" class="w-full h-48 object-cover">
                <span class="absolute top-2 right-2 bg-petLightGray text-petGray px-2 py-1 rounded-full text-xs">${product.category}</span>
                ${product.oldPrice ? `<span class="absolute top-2 left-2 bg-petOrange text-white px-2 py-1 rounded-full text-xs">Oferta</span>` : ''}
            </div>
            <div class="p-4">
                <h3 class="text-lg font-bold text-petGray mb-1">${product.name}</h3>
                <div class="flex items-center mb-3">
                    <span class="text-xl font-bold text-petBlue">R$ ${product.price.toFixed(2)}</span>
                    ${product.oldPrice ? `<span class="ml-2 text-sm text-gray-500 line-through">R$ ${product.oldPrice.toFixed(2)}</span>` : ''}
                </div>
                <div class="flex justify-between items-center">
                    <button class="view-product bg-petBlue hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-sm transition duration-300" data-id="${product.id}">Ver Detalhes</button>
                    <button class="add-to-cart bg-petOrange hover:bg-orange-600 text-white p-2 rounded-lg transition duration-300" data-id="${product.id}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        productsContainer.appendChild(productCard );
    });
    
    // Adicionar eventos aos botões após adicionar ao DOM
    addProductEvents();
}

// Função para adicionar eventos aos produtos
function addProductEvents() {
    // Botões de visualizar produto
    document.querySelectorAll('.view-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = parseInt(this.getAttribute('data-id'));
            openProductModal(productId);
        });
    });
    
    // Botões de adicionar ao carrinho
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = parseInt(this.getAttribute('data-id'));
            const product = products.find(p => p.id === productId);
            
            if (product) {
                // Verificar se o script de carrinho está disponível
                if (typeof addToCart === 'function') {
                    addToCart(product, 1);
                    if (typeof updateCartCounter === 'function') { // Adicionado para atualizar o contador
                        updateCartCounter();
                    }
                    // Adicionar notificação estilizada
                    if (typeof showNotification === 'function') {
                        showNotification(`${product.name} adicionado ao carrinho!`, 'success');
                    }
                } else {
                    // Adicionar elemento de notificação ao DOM se não existir
                    addNotificationElement();
                    
                    // Mostrar notificação estilizada
                    showNotification(`${product.name} adicionado ao carrinho!`, 'success');
                }
            }
        });
    });
}

// Função para configurar filtros de categoria
function setupCategoryFilters() {
    const categoryFilters = document.querySelectorAll('.category-filter');
    
    categoryFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            // Remover classe ativa de todos os filtros
            categoryFilters.forEach(f => f.classList.remove('active'));
            
            // Adicionar classe ativa ao filtro clicado
            this.classList.add('active');
            
            const category = this.getAttribute('data-category');
            
            // Filtrar produtos
            if (category === 'todos') {
                loadProducts();
            } else {
                const filteredProducts = products.filter(product => product.category === category);
                loadProducts(filteredProducts);
            }
        });
    });
}

// Função para configurar busca de produtos
function setupProductSearch() {
    const searchInput = document.getElementById('search-products');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            if (searchTerm === '') {
                loadProducts();
                return;
            }
            
            const filteredProducts = products.filter(product => 
                product.name.toLowerCase().includes(searchTerm) || 
                product.category.toLowerCase().includes(searchTerm) ||
                product.description.toLowerCase().includes(searchTerm)
            );
            
            loadProducts(filteredProducts);
        });
    }
}

// Função para configurar modal de produto
function setupProductModal() {
    const productModal = document.getElementById('product-modal');
    const closeModal = document.getElementById('close-modal');
    
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            productModal.classList.add('hidden');
        });
    }
    
    // Configurar botões de quantidade no modal
    const decreaseQuantity = document.getElementById('decrease-quantity');
    const increaseQuantity = document.getElementById('increase-quantity');
    const productQuantity = document.getElementById('product-quantity');
    
    if (decreaseQuantity && increaseQuantity && productQuantity) {
        decreaseQuantity.addEventListener('click', function() {
            let quantity = parseInt(productQuantity.value);
            if (quantity > 1) {
                productQuantity.value = quantity - 1;
            }
        });
        
        increaseQuantity.addEventListener('click', function() {
            let quantity = parseInt(productQuantity.value);
            productQuantity.value = quantity + 1;
        });
        
        productQuantity.addEventListener('change', function() {
            let quantity = parseInt(this.value);
            if (isNaN(quantity) || quantity < 1) {
                this.value = 1;
            }
        });
    }
    
    // Configurar botão de adicionar ao carrinho no modal
    const addToCartButton = document.getElementById('add-to-cart');
    
    if (addToCartButton) {
        addToCartButton.addEventListener('click', function() {
            const productId = parseInt(this.getAttribute('data-id'));
            const product = products.find(p => p.id === productId);
            
            if (product) {
                const quantity = parseInt(productQuantity.value);
                
                // Verificar se o script de carrinho está disponível
                if (typeof addToCart === 'function') {
                    addToCart(product, quantity);
                    if (typeof updateCartCounter === 'function') { // Adicionado para atualizar o contador
                        updateCartCounter();
                    }
                    // Adicionar notificação estilizada
                    if (typeof showNotification === 'function') {
                        showNotification(`${quantity}x ${product.name} adicionado ao carrinho!`, 'success');
                    }
                } else {
                    // Adicionar elemento de notificação ao DOM se não existir
                    addNotificationElement();
                    
                    // Mostrar notificação estilizada
                    showNotification(`${quantity}x ${product.name} adicionado ao carrinho!`, 'success');
                }
                
                // Fechar modal
                productModal.classList.add('hidden');
            }
        });
    }
}

// Função para abrir modal de produto
function openProductModal(productId) {
    const product = products.find(p => p.id === productId);
    
    if (!product) return;
    
    const productModal = document.getElementById('product-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalImage = document.getElementById('modal-image');
    const modalCategory = document.getElementById('modal-category');
    const modalDescription = document.getElementById('modal-description');
    const modalFeatures = document.getElementById('modal-features');
    const modalPrice = document.getElementById('modal-price');
    const modalOldPrice = document.getElementById('modal-old-price');
    const addToCartButton = document.getElementById('add-to-cart');
    const productQuantity = document.getElementById('product-quantity');
    
    if (productModal && modalTitle && modalImage && modalCategory && modalDescription && 
        modalFeatures && modalPrice && modalOldPrice && addToCartButton && productQuantity) {
        
        // Preencher dados do produto
        modalTitle.textContent = product.name;
        modalImage.src = product.image;
        modalImage.alt = product.name;
        modalCategory.textContent = product.category;
        modalDescription.textContent = product.description;
        
        // Limpar e preencher características
        modalFeatures.innerHTML = '';
        product.features.forEach(feature => {
            const li = document.createElement('li');
            li.textContent = feature;
            modalFeatures.appendChild(li);
        });
        
        // Preencher preços
        modalPrice.textContent = `R$ ${product.price.toFixed(2)}`;
        
        if (product.oldPrice) {
            modalOldPrice.textContent = `R$ ${product.oldPrice.toFixed(2)}`;
            modalOldPrice.classList.remove('hidden');
        } else {
            modalOldPrice.classList.add('hidden');
        }
        
        // Resetar quantidade
        productQuantity.value = 1;
        
        // Definir ID do produto no botão de adicionar ao carrinho
        addToCartButton.setAttribute('data-id', product.id);
        
        // Mostrar modal
        productModal.classList.remove('hidden');
    }
}

// Função para atualizar contador do carrinho
function updateCartCounter() {
    const cart = JSON.parse(localStorage.getItem('petsync_cart')) || [];
    const itemCount = cart.reduce((total, item) => total + item.quantity, 0);
    
    // Atualizar contador em todas as páginas
    if (typeof addCartCounter === 'function') {
        addCartCounter(itemCount);
    }
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
    const style = document.createElement('style' );
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
