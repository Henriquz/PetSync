</main>

<footer class="bg-petGray text-white py-3 mt-auto">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex justify-center items-center h-full">
            <p class="text-sm text-gray-300">
                &copy; <?php echo date('Y'); ?> PetSync. Todos os direitos reservados a Sync Group.
            </p>

            <div class="absolute top-0 right-0 h-full flex items-center pr-4 sm:pr-6 lg:pr-8">
                <a href="https://www.instagram.com/syncgroup_/" target="_blank" rel="noopener noreferrer"
                   class="text-gray-300 hover:text-white transition-colors duration-300"
                   title="Siga-nos no Instagram">
                    <span class="sr-only">Instagram</span>
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 1.5A4 4 0 0 0 3.5 7.5v9A4 4 0 0 0 7.5 20.5h9a4 4 0 0 0 4-4v-9a4 4 0 0 0-4-4h-9Zm9.25 1.75a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<script>
    // Script para menu mobile
    document.getElementById('mobile-menu-button')?.addEventListener('click', () => {
        document.getElementById('mobile-menu')?.classList.toggle('hidden');
    });

    // Script para máscara de telefone
    const telInput = document.getElementById('telefone');
    if (telInput) {
        // Exemplo de máscara simples (adicione sua lib ou lógica aqui)
        telInput.addEventListener('input', () => {
            let value = telInput.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            telInput.value = value;
        });
    }

    // Script para dropdown de usuário
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');

    if (userMenuButton) {
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });

        window.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }
</script>

</body>
</html>
