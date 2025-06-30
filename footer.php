</main>
    <footer class="bg-petGray text-white py-2 mt-auto">
      </footer>

    <script>
      // Script para menu mobile (continua igual)
      document.getElementById('mobile-menu-button')?.addEventListener('click', () => {
        document.getElementById('mobile-menu')?.classList.toggle('hidden');
      });

      // Script para máscara de telefone (continua igual)
      const telInput = document.getElementById('telefone');
      if (telInput) { /* ... */ }


      // ===== INÍCIO DO NOVO SCRIPT PARA DROPDOWN DE USUÁRIO =====
      const userMenuButton = document.getElementById('user-menu-button');
      const userMenu = document.getElementById('user-menu');

      if (userMenuButton) {
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });

        // Opcional: Fecha o menu se o usuário clicar fora dele
        window.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
      }
      // ===== FIM DO NOVO SCRIPT PARA DROPDOWN DE USUÁRIO =====

    </script>
</body>
</html>