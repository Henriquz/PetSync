<?php
include 'config.php';
$page_title = 'PetSync - Cuidado e carinho para seu melhor amigo';

// Busca as configurações do site
$configuracoes = [];
$result_config = $mysqli->query("SELECT chave, valor FROM configuracoes");
if ($result_config) {
    while ($row = $result_config->fetch_assoc()) {
        $configuracoes[$row['chave']] = $row['valor'];
    }
}

// Busca os 4 produtos mais recentes
$produtos = $mysqli->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

require 'header.php';
?>

<section class="hero-pattern py-16 md:py-24">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0">
                <h1 class="text-4xl md:text-5xl font-bold text-petGray mb-4">Cuidado e <span class="text-petBlue">carinho</span> para seu melhor amigo</h1>
                <p class="text-lg text-petGray mb-8">Oferecemos serviços completos de pet shop, banho e tosa, veterinária e produtos de qualidade para seu pet.</p>
                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="#services" class="bg-petOrange hover:bg-orange-600 text-white font-medium py-3 px-6 rounded-lg text-center transition duration-300">Nossos Serviços</a>
                    <?php
                    // Determina o link de agendamento correto com base no tipo de usuário
                    $link_agendamento = 'agendamento.php'; // Link padrão para clientes e visitantes

                    // Se o usuário estiver logado E for um administrador, muda o link
                    if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['is_admin'])) {
                        $link_agendamento = 'agendamento_admin.php';
                    }
                    ?>

                    <a href="<?= $link_agendamento ?>" class="bg-petBlue hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg text-center transition duration-300">Agendar Visita</a>
                </div>
            </div>
            <div class="md:w-1/2 flex justify-center">
                <div class="relative">
                    <div class="absolute -top-6 -left-6 w-24 h-24 bg-petOrange rounded-full opacity-20"></div>
                    <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-petBlue rounded-full opacity-20"></div>
                    <img src="Imagens/PetSync-dog.jpeg" alt="Cachorro feliz" class="w-[300px] h-auto rounded-lg shadow-lg relative z-10" />
                </div>
            </div>
        </div>
    </div> 
</section>

<section id="services" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-petGray">Nossos <span class="text-petBlue">Serviços</span></h2>
            <div class="w-24 h-1 bg-petOrange mx-auto mt-4"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="bg-white rounded-lg shadow-lg p-6 transition-transform duration-300 hover:-translate-y-2">
                <div class="w-16 h-16 bg-petOrange bg-opacity-20 rounded-full flex items-center justify-center mb-6 mx-auto"><svg class="w-8 h-8 text-petOrange" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7 2a1 1 0 00-.707 1.707L7 4.414v3.758a1 1 0 01-.293.707l-4 4C.817 14.769 2.156 18 4.828 18h10.343c2.673 0 4.012-3.231 2.122-5.121l-4-4A1 1 0 0113 8.172V4.414l.707-.707A1 1 0 0013 2H7zm2 6.172V4h2v4.172a3 3 0 00.879 2.12l1.027 1.028a4 4 0 00-2.171.102l-.47.156a4 4 0 01-2.53 0l-.563-.187a1.993 1.993 0 00-.114-.035l1.063-1.063A3 3 0 009 8.172z" clip-rule="evenodd"></path></svg></div>
                <h3 class="text-xl font-semibold text-petGray text-center mb-3">Banho e Tosa</h3>
                <p class="text-petGray text-center">Banhos relaxantes e tosa profissional para deixar seu pet limpo e estiloso.</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 transition-transform duration-300 hover:-translate-y-2">
                <div class="w-16 h-16 bg-petBlue bg-opacity-20 rounded-full flex items-center justify-center mb-6 mx-auto"><svg class="w-8 h-8 text-petBlue" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg></div>
                <h3 class="text-xl font-semibold text-petGray text-center mb-3">Veterinária</h3>
                <p class="text-petGray text-center">Atendimento veterinário completo com profissionais experientes e dedicados.</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 transition-transform duration-300 hover:-translate-y-2">
                <div class="w-16 h-16 bg-petOrange bg-opacity-20 rounded-full flex items-center justify-center mb-6 mx-auto"><svg class="w-8 h-8 text-petOrange" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path></svg></div>
                <h3 class="text-xl font-semibold text-petGray text-center mb-3">Pet Shop</h3>
                <p class="text-petGray text-center">Produtos de qualidade para alimentação, higiene, brinquedos e acessórios.</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 transition-transform duration-300 hover:-translate-y-2">
                <div class="w-16 h-16 bg-petBlue bg-opacity-20 rounded-full flex items-center justify-center mb-6 mx-auto"><svg class="w-8 h-8 text-petBlue" fill="currentColor" viewBox="0 0 20 20"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"></path></svg></div>
                <h3 class="text-xl font-semibold text-petGray text-center mb-3">Hospedagem</h3>
                <p class="text-petGray text-center">Hospedagem confortável e segura para seu pet durante suas viagens.</p>
            </div>
        </div>
    </div>
</section>

<section id="products" class="py-16 bg-petLightGray bg-opacity-30">
     <div class="container mx-auto px-4">
        <div class="text-center mb-16"><h2 class="text-3xl font-bold text-petGray">Produtos <span class="text-petOrange">Populares</span></h2><div class="w-24 h-1 bg-petBlue mx-auto mt-4"></div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php if (!empty($produtos)): foreach ($produtos as $produto): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2">
                <img src="Imagens/produtos/<?= htmlspecialchars($produto['imagem'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($produto['nome'] ?? 'Produto') ?>" class="w-full h-48 object-cover">
                <div class="p-4 flex flex-col justify-between" style="min-height: 160px;">
                    <div><h3 class="text-lg font-semibold text-petGray"><?= htmlspecialchars($produto['nome'] ?? '') ?></h3><p class="text-petGray text-sm mb-2 h-10"><?= htmlspecialchars($produto['descricao'] ?? '') ?></p></div>
                    <div class="flex justify-between items-center mt-4"><span class="text-petBlue font-bold">R$ <?= number_format($produto['preco'] ?? 0, 2, ',', '.') ?></span><button class="bg-petOrange hover:bg-orange-600 text-white px-3 py-1 rounded-md text-sm transition duration-300">Comprar</button></div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <p class="col-span-4 text-center text-petGray">Nenhum produto cadastrado no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="about" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0">
                <div class="relative">
                    <div class="absolute -top-6 -left-6 w-24 h-24 bg-petBlue rounded-full opacity-20"></div>
                    <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-petOrange rounded-full opacity-20"></div>
                    <div class="bg-petLightGray rounded-lg p-6 relative z-10">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-white rounded-lg p-4 shadow-md"><div class="text-4xl text-petOrange font-bold mb-2"><?= htmlspecialchars($configuracoes['stat_1_num'] ?? '') ?></div><div class="text-petGray"><?= htmlspecialchars($configuracoes['stat_1_desc'] ?? '') ?></div></div>
                            <div class="bg-white rounded-lg p-4 shadow-md"><div class="text-4xl text-petBlue font-bold mb-2"><?= htmlspecialchars($configuracoes['stat_2_num'] ?? '') ?></div><div class="text-petGray"><?= htmlspecialchars($configuracoes['stat_2_desc'] ?? '') ?></div></div>
                            <div class="bg-white rounded-lg p-4 shadow-md"><div class="text-4xl text-petOrange font-bold mb-2"><?= htmlspecialchars($configuracoes['stat_3_num'] ?? '') ?></div><div class="text-petGray"><?= htmlspecialchars($configuracoes['stat_3_desc'] ?? '') ?></div></div>
                            <div class="bg-white rounded-lg p-4 shadow-md"><div class="text-4xl text-petBlue font-bold mb-2"><?= htmlspecialchars($configuracoes['stat_4_num'] ?? '') ?></div><div class="text-petGray"><?= htmlspecialchars($configuracoes['stat_4_desc'] ?? '') ?></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="md:w-1/2 md:pl-12">
                <h2 class="text-3xl font-bold text-petGray mb-6"><?= htmlspecialchars($configuracoes['sobre_titulo'] ?? '') ?></h2>
                <p class="text-petGray mb-6"><?= nl2br(htmlspecialchars($configuracoes['sobre_texto_1'] ?? '')) ?></p>
                <p class="text-petGray mb-8"><?= nl2br(htmlspecialchars($configuracoes['sobre_texto_2'] ?? '')) ?></p>
            </div>
        </div>
    </div>
</section>

<section id="contact" class="py-16 bg-petLightGray bg-opacity-30">
    <div class="container mx-auto px-4">
      <div class="w-full">
        <div class="p-8 rounded-lg text-center">
          <h2 class="text-3xl font-bold text-petGray mb-2">Entre em <span class="text-petOrange">Contato</span></h2>
          <div class="w-24 h-1 bg-petBlue mx-auto mb-8"></div>
          <div class="grid grid-cols-1 md:grid-cols-3 justify-items-center gap-8">  
            <div class="flex flex-col items-center text-center max-w-xs">
              <div class="w-16 h-16 bg-petBlue bg-opacity-20 rounded-full flex items-center justify-center mb-4"><svg class="w-8 h-8 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg></div>
              <h4 class="font-semibold text-petGray mb-2">Telefone</h4>
              <p class="text-petGray"><?= htmlspecialchars($configuracoes['telefone_1'] ?? '') ?></p><p class="text-petGray"><?= htmlspecialchars($configuracoes['telefone_2'] ?? '') ?></p>
            </div>
            <div class="flex flex-col items-center text-center max-w-xs">
              <div class="w-16 h-16 bg-petOrange bg-opacity-20 rounded-full flex items-center justify-center mb-4"><svg class="w-8 h-8 text-petOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg></div>
              <h4 class="font-semibold text-petGray mb-2">Email</h4>
              <p class="text-petGray"><?= htmlspecialchars($configuracoes['email_contato'] ?? '') ?></p>
            </div>
            <div class="flex flex-col items-center text-center max-w-xs">
              <div class="w-16 h-16 bg-petBlue bg-opacity-20 rounded-full flex items-center justify-center mb-4"><svg class="w-8 h-8 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
              <h4 class="font-semibold text-petGray mb-2">Horário de Funcionamento</h4>
              <p class="text-petGray"><?= htmlspecialchars($configuracoes['horario_semana'] ?? '') ?></p><p class="text-petGray"><?= htmlspecialchars($configuracoes['horario_sabado'] ?? '') ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>

<section class="py-8 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-10"><h2 class="text-3xl font-bold text-petGray">Nossa <span class="text-petBlue">Localização</span></h2><div class="w-24 h-1 bg-petOrange mx-auto mt-4"></div></div>
        <div class="bg-white p-4 rounded-lg shadow-lg">
            <div class="bg-petLightGray rounded-lg overflow-hidden">
                <?php
                $iframe_code = $configuracoes['mapa_url'] ?? '';
                if (!empty(trim($iframe_code))) {
                    $iframe_code = preg_replace('/width=".*?"/i', '', $iframe_code);
                    $iframe_code = preg_replace('/height=".*?"/i', '', $iframe_code);
                    $iframe_code = str_replace('<iframe', '<iframe class="w-full h-96"', $iframe_code);
                    echo $iframe_code;
                } else {
                    echo '<p class="text-center p-10 text-petGray">Mapa não configurado.</p>';
                }
                ?>
                <div class="text-center py-4"><p class="text-petGray text-lg"><?= htmlspecialchars($configuracoes['endereco'] ?? 'Endereço não configurado') ?></p></div>
            </div>
        </div>
    </div>
</section>

<?php require 'footer.php'; ?>