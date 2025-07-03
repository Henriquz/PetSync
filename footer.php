</main>

<footer class="bg-petGray text-white py-3 mt-auto">
    <div class="container mx-auto px-4 sm:px-6 lg:pr-8 relative">
        <div class="flex justify-center items-center h-full">
            <p class="text-sm text-gray-300">
                &copy; <?php echo date('Y'); ?> PetSync. Todos os direitos reservados a Sync Group.
            </p>
            <div class="absolute top-0 right-0 h-full flex items-center pr-4 sm:pr-6 lg:pr-8">
                <a href="https://www.instagram.com/syncgroup_/" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-white transition-colors duration-300" title="Siga-nos no Instagram">
                    <span class="sr-only">Instagram</span>
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 1.5A4 4 0 0 0 3.5 7.5v9A4 4 0 0 0 7.5 20.5h9a4 4 0 0 0 4-4v-9a4 4 0 0 0-4-4h-9Zm9.25 1.75a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<div id="image-modal" class="image-modal">
    <div class="image-modal-content">
        <img id="modal-image-src" src="" alt="Visualização da Imagem">
    </div>
    <button id="image-modal-close-btn" class="image-modal-close" title="Fechar">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        const imageModal = document.getElementById('image-modal');
        const modalImage = document.getElementById('modal-image-src');
        const closeModalBtn = document.getElementById('image-modal-close-btn');
        const clientBell = document.getElementById('notification-bell-container');
        const clientDropdown = document.getElementById('notification-dropdown');
        const adminBell = document.getElementById('admin-notification-bell-container');
        const adminDropdown = document.getElementById('admin-notification-dropdown');

        document.getElementById('mobile-menu-button')?.addEventListener('click', () => {
            document.getElementById('mobile-menu')?.classList.toggle('hidden');
        });

        if (userMenuButton) {
            userMenuButton.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
                clientDropdown?.classList.add('hidden');
                adminDropdown?.classList.add('hidden');
            });
        }

        const openImageModal = (imageUrl) => {
            if(imageModal && modalImage) {
                modalImage.src = imageUrl;
                imageModal.classList.add('open');
            }
        };
        const closeImageModal = () => {
            if(imageModal) imageModal.classList.remove('open');
        };

        closeModalBtn?.addEventListener('click', closeImageModal);
        imageModal?.addEventListener('click', (e) => {
            if (e.target === imageModal) closeImageModal();
        });
        
        document.body.addEventListener('click', function(e) {
            const viewImageBtn = e.target.closest('.view-image-btn');
            if (viewImageBtn) {
                e.preventDefault();
                e.stopPropagation();
                openImageModal(viewImageBtn.dataset.imgSrc || viewImageBtn.getAttribute('data-img-src'));
            }
        });

        if (clientBell) {
            const clientList = document.getElementById('notification-list');
            const clientClearBtn = document.getElementById('clear-read-btn');
            const clientApiCall = async (action, body = {}) => {
                const formData = new FormData();
                formData.append('action', action);
                for (const key in body) formData.append(key, body[key]);
                const response = await fetch('/petsync/ajax_notificacoes.php', { method: 'POST', body: formData });
                return response.json();
            };
            const clientUpdateCount = (count) => {
                const countEl = document.getElementById('notification-count');
                if (count > 0) { countEl.textContent = count; countEl.classList.remove('hidden'); } 
                else { countEl.classList.add('hidden'); }
            };
            const clientRender = (notifications) => {
                if (!notifications || notifications.length === 0) { clientList.innerHTML = '<p class="text-center text-sm text-gray-500 py-4">Nenhuma notificação.</p>'; return; }
                clientList.innerHTML = ''; 
                notifications.forEach(notif => {
                    const isUnread = notif.lida == 0; const isAlert = notif.tipo === 'alerta'; const hasImage = notif.imagem_url != null;
                    let bgClass = isUnread ? 'bg-blue-50' : 'bg-white';
                    if (isAlert && isUnread) bgClass = 'bg-orange-50';
                    const imageHTML = hasImage ? `<button data-img-src="/petsync/${notif.imagem_url}" class="view-image-btn text-xs inline-flex items-center gap-1 font-semibold text-petBlue hover:underline mt-2"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 00.75-.75v-3.69l-2.72-2.72a.75.75 0 00-1.06 0L11.5 12.25l-1.72-1.72a.75.75 0 00-1.06 0l-2.97 2.97zM12 7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" /></svg>Ver Imagem</button>` : '';
                    const itemHTML = `<div id="notif-item-${notif.id}" data-id="${notif.id}" class="notification-item-container border-b border-gray-100"><a href="${notif.link ? '/petsync/'+notif.link : '#'}" class="notification-item block p-3 transition-colors duration-150 hover:bg-gray-100 ${bgClass}"><div class="flex items-start">${isAlert ? '<div class="mr-3 pt-1"><svg class="w-5 h-5 text-petOrange" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg></div>' : ''}<div class="flex-1"><p class="text-sm text-petGray ${isUnread ? 'font-semibold' : ''}">${notif.mensagem}</p>${imageHTML}<time class="text-xs text-gray-400 mt-2 block">${new Date(notif.data_criacao).toLocaleString('pt-BR', {day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'})}</time></div></div>${isUnread ? '<button class="dismiss-btn text-gray-400" title="Dispensar"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></button>' : ''}</a></div>`;
                    clientList.innerHTML += itemHTML;
                });
            };
            const clientFetchAndUpdate = async () => {
                const data = await clientApiCall('get_notificacoes');
                if (data && !data.error) { clientUpdateCount(data.unread_count); clientRender(data.notificacoes); }
            };
            const clientMarkOneAsRead = async (id) => {
                const res = await clientApiCall('marcar_uma_lida', { notificacao_id: id });
                if (res.sucesso && res.updated) {
                    const countEl = document.getElementById('notification-count');
                    clientUpdateCount(Math.max(0, parseInt(countEl.textContent || '0') - 1));
                    const itemEl = document.getElementById(`notif-item-${id}`);
                    if(itemEl) { itemEl.querySelector('.dismiss-btn')?.remove(); itemEl.querySelector('a').classList.remove('bg-blue-50', 'bg-orange-50'); itemEl.querySelector('p')?.classList.remove('font-semibold'); }
                }
            };
            clientBell.addEventListener('click', (e) => { e.stopPropagation(); clientDropdown.classList.toggle('hidden'); userMenu?.classList.add('hidden'); adminDropdown?.classList.add('hidden'); });
            clientList.addEventListener('click', (e) => {
                const container = e.target.closest('.notification-item-container'); if (!container) return;
                if (e.target.closest('.dismiss-btn')) { e.preventDefault(); e.stopPropagation(); clientMarkOneAsRead(container.dataset.id); }
                else if (e.target.closest('a') && !e.target.closest('.view-image-btn')) { clientMarkOneAsRead(container.dataset.id); }
            });
            clientClearBtn.addEventListener('click', async () => { await clientApiCall('limpar_lidas'); await clientFetchAndUpdate(); });
            clientFetchAndUpdate(); setInterval(clientFetchAndUpdate, 60000);
        }

        if (adminBell) {
            const adminList = document.getElementById('admin-notification-list');
            const adminClearBtn = document.getElementById('admin-clear-read-btn');
            const adminApiCall = async (action) => {
                const response = await fetch(`/petsync/admin/ajax_notificacoes_admin.php?action=${action}`);
                return response.json();
            };
            const adminFetchAndUpdate = async () => {
                const data = await adminApiCall('get_notificacoes');
                const countEl = document.getElementById('admin-notification-count');
                if(data && data.unread_count > 0) {
                    countEl.textContent = data.unread_count;
                    countEl.classList.remove('hidden');
                } else {
                    countEl.classList.add('hidden');
                }
                if(data && data.notificacoes.length > 0) {
                    adminList.innerHTML = '';
                    data.notificacoes.forEach(notif => {
                        const bgClass = notif.lida == 0 ? 'bg-blue-50 font-semibold' : '';
                        const itemHTML = `<a href="/petsync/${notif.link}" class="block px-4 py-3 text-sm text-petGray hover:bg-petLightGray transition-colors ${bgClass}"><p>${notif.mensagem}</p><time class="text-xs text-gray-400 mt-1 block font-normal">${new Date(notif.data_criacao).toLocaleString('pt-BR', {day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'})}</time></a>`;
                        adminList.innerHTML += itemHTML;
                    });
                } else {
                    adminList.innerHTML = '<p class="text-center text-sm text-gray-500 py-4">Nenhum agendamento novo.</p>';
                }
            };
            adminBell.addEventListener('click', (e) => {
                e.stopPropagation();
                adminDropdown.classList.toggle('hidden');
                userMenu?.classList.add('hidden');
                clientDropdown?.classList.add('hidden');
                const countEl = document.getElementById('admin-notification-count');
                if (!adminDropdown.classList.contains('hidden') && parseInt(countEl.textContent || '0') > 0) {
                    adminApiCall('marcar_todas_lidas').then(() => {
                        countEl.classList.add('hidden');
                        adminList.querySelectorAll('a').forEach(link => link.classList.remove('bg-blue-50', 'font-semibold'));
                    });
                }
            });
            adminClearBtn.addEventListener('click', async () => {
                await adminApiCall('limpar_lidas');
                await adminFetchAndUpdate();
            });
            adminFetchAndUpdate();
            setInterval(adminFetchAndUpdate, 60000);
        }

        window.addEventListener('click', (e) => {
            if (clientBell && !clientBell.contains(e.target) && !clientDropdown.contains(e.target)) {
                clientDropdown?.classList.add('hidden');
            }
            if (adminBell && !adminBell.contains(e.target) && !adminDropdown.contains(e.target)) {
                adminDropdown?.classList.add('hidden');
            }
            if (userMenuButton && !userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu?.classList.add('hidden');
            }
        });
    });
</script>

</body>
</html>