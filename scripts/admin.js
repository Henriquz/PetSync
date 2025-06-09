document.addEventListener('DOMContentLoaded', function() {
    // Verificar se o usuário é administrador
    if (!requireAdmin()) {
        return;
    }
    
    // Configurar nome do administrador
    const currentUser = JSON.parse(localStorage.getItem('petsync_current_user'));
    if (currentUser) {
        document.getElementById('admin-name').textContent = currentUser.name;
    }
    
    // Configurar logout
    document.getElementById('admin-logout').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // Configurar navegação por tabs
    setupTabs();
    
    // Carregar dados iniciais
    loadDashboardData();
    loadProductsTable();
    loadUsersTable();
    
    // Configurar formulário de produtos
    setupProductForm();
    
    // Link para voltar ao site
    document.querySelector('[data-tab="site"]').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'index.html';
    });
});

// Função para configurar navegação por tabs
function setupTabs() {
    const tabLinks = document.querySelectorAll('.admin-nav-item');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const tabId = this.getAttribute('data-tab');
            
            // Remover classe ativa de todos os links e conteúdos
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Adicionar classe ativa ao link e conteúdo clicado
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// Função para carregar dados do dashboard
function loadDashboardData() {
    // Carregar produtos
    const produtos = JSON.parse(localStorage.getItem('petsync_produtos')) || [];
    document.getElementById('total-produtos').textContent = produtos.length;
    
    // Carregar usuários
    const users = JSON.parse(localStorage.getItem('petsync_users')) || [];
    document.getElementById('total-usuarios').textContent = users.length;
}

// Função para carregar tabela de produtos
function loadProductsTable() {
    const tableBody = document.getElementById('products-table-body');
    const produtos = JSON.parse(localStorage.getItem('petsync_produtos')) || [];
    
    // Limpar tabela
    tableBody.innerHTML = '';
    
    // Adicionar produtos à tabela
    produtos.forEach(product => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>${product.id}</td>
            <td>${product.nome}</td>
            <td>${getCategoryName(product.categoria)}</td>
            <td>R$ ${product.preco.toFixed(2)}</td>
            <td>
                <button class="btn btn-primary btn-sm edit-product" data-id="${product.id}">Editar</button>
                <button class="btn btn-danger btn-sm delete-product" data-id="${product.id}">Excluir</button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Adicionar event listeners aos botões
    document.querySelectorAll('.edit-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = parseInt(this.getAttribute('data-id'));
            editProduct(productId);
        });
    });
    
    document.querySelectorAll('.delete-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = parseInt(this.getAttribute('data-id'));
            deleteProduct(productId);
        });
    });
}

// Função para carregar tabela de usuários
function loadUsersTable() {
    const tableBody = document.getElementById('users-table-body');
    const users = JSON.parse(localStorage.getItem('petsync_users')) || [];
    
    // Limpar tabela
    tableBody.innerHTML = '';
    
    // Adicionar usuários à tabela
    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Formatar data
        const createdAt = new Date(user.createdAt);
        const formattedDate = `${createdAt.getDate().toString().padStart(2, '0')}/${(createdAt.getMonth() + 1).toString().padStart(2, '0')}/${createdAt.getFullYear()} ${createdAt.getHours().toString().padStart(2, '0')}:${createdAt.getMinutes().toString().padStart(2, '0')}`;
        
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.name}</td>
            <td>${user.email}</td>
            <td>${user.role === 'admin' ? 'Administrador' : 'Cliente'}</td>
            <td>${formattedDate}</td>
        `;
        
        tableBody.appendChild(row);
    });
}

// Função para configurar formulário de produtos
function setupProductForm() {
    const addProductBtn = document.getElementById('add-product-btn');
    const productForm = document.getElementById('product-form');
    const productFormElement = document.getElementById('product-form-element');
    const cancelProductBtn = document.getElementById('cancel-product');
    
    // Botão para adicionar produto
    addProductBtn.addEventListener('click', function() {
        // Limpar formulário
        productFormElement.reset();
        document.getElementById('product-id').value = '';
        document.getElementById('product-form-title').textContent = 'Adicionar Produto';
        
        // Mostrar formulário
        productForm.classList.remove('hidden');
    });
    
    // Botão para cancelar
    cancelProductBtn.addEventListener('click', function() {
        productForm.classList.add('hidden');
    });
    
    // Submissão do formulário
    productFormElement.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Obter valores do formulário
        const productId = document.getElementById('product-id').value;
        const nome = document.getElementById('product-name').value;
        const categoria = document.getElementById('product-category').value;
        const preco = parseFloat(document.getElementById('product-price').value);
        const precoAntigo = document.getElementById('product-old-price').value ? parseFloat(document.getElementById('product-old-price').value) : null;
        const imagem = document.getElementById('product-image').value;
        const descricao = document.getElementById('product-description').value;
        const caracteristicas = document.getElementById('product-features').value.split('\n').filter(item => item.trim() !== '');
        
        // Validar campos
        if (!nome || !categoria || isNaN(preco) || !imagem || !descricao || caracteristicas.length === 0) {
            showAlert('product-alert', 'Por favor, preencha todos os campos obrigatórios.', 'danger');
            return;
        }
        
        // Obter produtos existentes
        const produtos = JSON.parse(localStorage.getItem('petsync_produtos')) || [];
        
        if (productId) {
            // Editar produto existente
            const index = produtos.findIndex(p => p.id === parseInt(productId));
            
            if (index !== -1) {
                produtos[index] = {
                    ...produtos[index],
                    nome,
                    categoria,
                    preco,
                    precoAntigo,
                    imagem,
                    descricao,
                    caracteristicas
                };
                
                showAlert('product-alert', 'Produto atualizado com sucesso!', 'success');
            }
        } else {
            // Adicionar novo produto
            const newProduct = {
                id: produtos.length > 0 ? Math.max(...produtos.map(p => p.id)) + 1 : 1,
                nome,
                categoria,
                preco,
                precoAntigo,
                imagem,
                descricao,
                caracteristicas
            };
            
            produtos.push(newProduct);
            showAlert('product-alert', 'Produto adicionado com sucesso!', 'success');
        }
        
        // Salvar produtos
        localStorage.setItem('petsync_produtos', JSON.stringify(produtos));
        
        // Atualizar tabela
        loadProductsTable();
        
        // Atualizar dashboard
        loadDashboardData();
        
        // Esconder formulário após 1 segundo
        setTimeout(() => {
            productForm.classList.add('hidden');
        }, 1000);
    });
}

// Função para editar produto
function editProduct(productId) {
    const produtos = JSON.parse(localStorage.getItem('petsync_produtos')) || [];
    const product = produtos.find(p => p.id === productId);
    
    if (product) {
        // Preencher formulário
        document.getElementById('product-id').value = product.id;
        document.getElementById('product-name').value = product.nome;
        document.getElementById('product-category').value = product.categoria;
        document.getElementById('product-price').value = product.preco;
        document.getElementById('product-old-price').value = product.precoAntigo || '';
        document.getElementById('product-image').value = product.imagem;
        document.getElementById('product-description').value = product.descricao;
        document.getElementById('product-features').value = product.caracteristicas.join('\n');
        
        // Atualizar título
        document.getElementById('product-form-title').textContent = 'Editar Produto';
        
        // Mostrar formulário
        document.getElementById('product-form').classList.remove('hidden');
    }
}

// Função para excluir produto
function deleteProduct(productId) {
    if (confirm('Tem certeza que deseja excluir este produto?')) {
        const produtos = JSON.parse(localStorage.getItem('petsync_produtos')) || [];
        const updatedProducts = produtos.filter(p => p.id !== productId);
        
        localStorage.setItem('petsync_produtos', JSON.stringify(updatedProducts));
        
        // Atualizar tabela
        loadProductsTable();
        
        // Atualizar dashboard
        loadDashboardData();
    }
}

// Função para obter nome da categoria
function getCategoryName(categorySlug) {
    const categories = {
        'racao': 'Ração',
        'brinquedos': 'Brinquedos',
        'acessorios': 'Acessórios',
        'higiene': 'Higiene',
        'medicamentos': 'Medicamentos'
    };
    return categories[categorySlug] || categorySlug;
}

// Função para mostrar alertas
function showAlert(elementId, message, type) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.textContent = message;
    element.className = `alert alert-${type}`;
    element.classList.remove('hidden');
    
    // Esconder alerta após 5 segundos se for sucesso
    if (type === 'success') {
        setTimeout(() => {
            element.classList.add('hidden');
        }, 5000);
    }
}

// Inicializar produtos padrão se não existirem
function initializeDefaultProducts() {
    const produtos = JSON.parse(localStorage.getItem('petsync_produtos')) || [];
    
    // Se não existirem produtos, criar produtos padrão
    if (produtos.length === 0) {
        const defaultProducts = [
            {
                id: 1,
                nome: "Ração Premium para Cães Adultos",
                categoria: "racao",
                preco: 89.90,
                precoAntigo: 109.90,
                descricao: "Ração premium balanceada para cães adultos de todas as raças. Formulada com ingredientes naturais e nutrientes essenciais para a saúde do seu pet.",
                imagem: "https://images.unsplash.com/photo-1589924691995-400dc9ecc119?q=80&w=2071&auto=format&fit=crop",
                caracteristicas: [
                    "Proteínas de alta qualidade",
                    "Sem corantes artificiais",
                    "Rico em ômega 3 e 6",
                    "Fortalece o sistema imunológico"
                ]
            },
            {
                id: 2,
                nome: "Brinquedo Interativo para Gatos",
                categoria: "brinquedos",
                preco: 45.50,
                precoAntigo: 59.90,
                descricao: "Brinquedo interativo que estimula o instinto de caça dos felinos. Mantém seu gato entretido e ativo por horas.",
                imagem: "https://images.unsplash.com/photo-1526336179256-1347bdb255ee?q=80&w=1887&auto=format&fit=crop",
                caracteristicas: [
                    "Material durável e não tóxico",
                    "Estimula o exercício físico",
                    "Reduz o estresse e ansiedade",
                    "Fácil de limpar"
                ]
            }
        ];
        
        localStorage.setItem('petsync_produtos', JSON.stringify(defaultProducts));
    }
}

// Inicializar produtos padrão
document.addEventListener("DOMContentLoaded", () => {
  const novoHorarioInput = document.getElementById("novoHorario");
  const adicionarBtn = document.getElementById("adicionarHorario");
  const salvarBtn = document.getElementById("salvarHorarios");
  const listaHorarios = document.getElementById("listaHorarios");

  if (!novoHorarioInput || !adicionarBtn || !salvarBtn || !listaHorarios) return;

  let horarios = [];

  adicionarBtn.addEventListener("click", () => {
    const horario = novoHorarioInput.value;
    if (horario && !horarios.includes(horario)) {
      horarios.push(horario);
      atualizarLista();
      novoHorarioInput.value = "";
    }
  });

  salvarBtn.addEventListener("click", async () => {
    try {
      const docRef = firebase.firestore().collection("config").doc("horarios");
      await docRef.set({ horarios });
      alert("Horários salvos com sucesso!");
    } catch (err) {
      console.error("Erro ao salvar horários:", err);
      alert("Erro ao salvar horários.");
    }
  });

  function atualizarLista() {
    listaHorarios.innerHTML = "";
    horarios.forEach((h) => {
      const li = document.createElement("li");
      li.textContent = h;
      listaHorarios.appendChild(li);
    });
  }
});
