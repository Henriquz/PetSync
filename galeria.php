<?php
include 'config.php';

// Paginação das fotos
$itens_por_pagina = 12;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$total_fotos = $mysqli->query("SELECT COUNT(*) FROM galeria")->fetch_row()[0];
$total_paginas = ceil($total_fotos / $itens_por_pagina);
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$fotos = $mysqli->query("SELECT * FROM galeria ORDER BY data_upload DESC LIMIT $itens_por_pagina OFFSET $offset");
$page_title = "Nossa Galeria";
require 'header.php';
?>
<style>
    /* Efeito de zoom suave no card da galeria */
    .gallery-card:hover .gallery-image {
        transform: scale(1.05);
    }
</style>
<main>
    <div class="bg-white">
        <div class="container mx-auto px-4 py-12 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-base font-semibold text-petBlue tracking-wide uppercase">Nossos Clientes Felizes</h2>
                <p class="mt-2 text-3xl font-extrabold text-petGray tracking-tight sm:text-4xl">Galeria de Fotos</p>
                <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
                    Confira alguns dos nossos amigos de quatro patas após um dia de cuidados e carinho no PetSync.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php if ($fotos->num_rows > 0): ?>
                    <?php while($foto = $fotos->fetch_assoc()): ?>
                        <div class="gallery-card group relative bg-gray-100 rounded-lg shadow-md overflow-hidden">
                             <button type="button" class="view-image-btn w-full h-full" data-img-src="/petsync/<?= htmlspecialchars($foto['url_imagem']) ?>">
                                <img src="/petsync/<?= htmlspecialchars($foto['url_imagem']) ?>" alt="<?= htmlspecialchars($foto['legenda']) ?>" class="gallery-image w-full h-72 object-cover transition-transform duration-300 ease-in-out">
                                <div class="absolute inset-0 bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center p-4">
                                    <div class="text-center text-white">
                                        <?php if($foto['nome_pet']): ?><p class="font-bold text-lg"><?= htmlspecialchars($foto['nome_pet']) ?></p><?php endif; ?>
                                        <?php if($foto['legenda']): ?><p class="text-sm"><?= htmlspecialchars($foto['legenda']) ?></p><?php endif; ?>
                                    </div>
                                </div>
                             </button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="col-span-full text-center text-gray-500 py-12">Nossa galeria está sendo preparada. Volte em breve!</p>
                <?php endif; ?>
            </div>

            <?php if($total_paginas > 1): ?>
            <nav class="mt-12 flex justify-center">
                 <a href="?pagina=<?= $pagina_atual - 1 ?>" class="<?= $pagina_atual <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-l-md text-gray-700 bg-white hover:bg-gray-50">Anterior</a>
                 <a href="?pagina=<?= $pagina_atual + 1 ?>" class="<?= $pagina_atual >= $total_paginas ? 'opacity-50 cursor-not-allowed' : '' ?> -ml-px relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-white hover:bg-gray-50">Próxima</a>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php 
require 'footer.php'; 
?>