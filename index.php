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

// Array com todos os serviços possíveis (sem alterações)
$todos_servicos = [
    ['chave' => 'servico_banho_tosa_ativo', 'titulo' => 'Banho e Tosa', 'descricao' => 'Banhos relaxantes e tosa profissional para deixar seu pet limpo e estiloso.', 'icone' => '<svg class="w-8 h-8 text-petOrange" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7 2a1 1 0 00-.707 1.707L7 4.414v3.758a1 1 0 01-.293.707l-4 4C.817 14.769 2.156 18 4.828 18h10.343c2.673 0 4.012-3.231 2.122-5.121l-4-4A1 1 0 0113 8.172V4.414l.707-.707A1 1 0 0013 2H7zm2 6.172V4h2v4.172a3 3 0 00.879 2.12l1.027 1.028a4 4 0 00-2.171.102l-.47.156a4 4 0 01-2.53 0l-.563-.187a1.993 1.993 0 00-.114-.035l1.063-1.063A3 3 0 009 8.172z" clip-rule="evenodd"></path></svg>', 'cor' => 'petOrange'],
    ['chave' => 'servico_veterinaria_ativo', 'titulo' => 'Veterinária', 'descricao' => 'Atendimento veterinário completo com profissionais experientes e dedicados.', 'icone' => '<svg class="w-8 h-8 text-petBlue" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg>', 'cor' => 'petBlue'],
    ['chave' => 'servico_pet_shop_ativo', 'titulo' => 'Pet Shop', 'descricao' => 'Produtos de qualidade para alimentação, higiene, brinquedos e acessórios.', 'icone' => '<svg class="w-8 h-8 text-petOrange" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path></svg>', 'cor' => 'petOrange'],
    ['chave' => 'servico_hospedagem_ativo', 'titulo' => 'Hospedagem', 'descricao' => 'Hospedagem confortável e segura para seu pet durante suas viagens.', 'icone' => '<svg class="w-8 h-8 text-petBlue" fill="currentColor" viewBox="0 0 20 20"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"></path></svg>', 'cor' => 'petBlue'],
    ['chave' => 'servico_adestramento_ativo', 'titulo' => 'Adestramento', 'descricao' => 'Aulas e treinamentos para ensinar comandos e corrigir comportamentos.', 'icone' => '<svg class="w-8 h-8 text-petOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75-10.5a4.5 4.5 0 017.5 0c0 1.536-.84 2.843-2.12 3.532m-3.26 0v6.84m0-6.84a2.25 2.25 0 012.25-2.25h.008a2.25 2.25 0 012.25 2.25v.008a2.25 2.25 0 01-2.25 2.25h-.008a2.25 2.25 0 01-2.25-2.25v-.008z" /></svg>', 'cor' => 'petOrange'],
    ['chave' => 'servico_day_care_ativo', 'titulo' => 'Day Care (Creche)', 'descricao' => 'Espaço seguro e divertido para seu pet socializar e se exercitar.', 'icone' => '<svg class="w-8 h-8 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m-3-1l-3-1m3 1v5.5" /></svg>', 'cor' => 'petBlue'],
    ['chave' => 'servico_pet_taxi_ativo', 'titulo' => 'Pet Táxi', 'descricao' => 'Transporte porta a porta com veículos adaptados e equipe treinada.', 'icone' => '<svg class="w-8 h-8 text-petOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.5-11.25-1.5.75M12 4.5l-1.5.75M12 4.5c-2.3 0-4.42.86-6 2.25M12 4.5c2.3 0 4.42.86 6 2.25m-12 2.25c-.26.22-.5.46-.72.72m13.44 0c.26.22.5.46.72.72M6 10.5H4.5a2.25 2.25 0 00-2.25 2.25v.5a2.25 2.25 0 002.25 2.25H6m12 0h1.5a2.25 2.25 0 002.25-2.25v-.5a2.25 2.25 0 00-2.25-2.25H18m-6 3.75h.008v.008H12v-.008zm-3.75 0h.008v.008H8.25v-.008zm7.5 0h.008v.008H15.75v-.008z" /></svg>', 'cor' => 'petOrange'],
    ['chave' => 'servico_vacinacao_ativo', 'titulo' => 'Vacinação', 'descricao' => 'Aplicação de vacinas e vermífugos para a saúde preventiva do seu pet.', 'icone' => '<svg class="w-8 h-8 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>', 'cor' => 'petBlue'],
    ['chave' => 'servico_consultoria_nutricional_ativo', 'titulo' => 'Consultoria Nutricional', 'descricao' => 'Orientações personalizadas sobre dieta, rações e suplementação.', 'icone' => '<svg class="w-8 h-8 text-petOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 9.563C9 9.252 9.252 9 9.563 9h4.874c.311 0 .563.252.563.563v4.874c0 .311-.252.563-.563.563H9.564A.562.562 0 019 14.437V9.564z" /></svg>', 'cor' => 'petOrange'],
    ['chave' => 'servico_sessao_fotos_ativo', 'titulo' => 'Sessão de Fotos Pet', 'descricao' => 'Ensaio fotográfico profissional para registrar momentos especiais do seu pet.', 'icone' => '<svg class="w-8 h-8 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" /></svg>', 'cor' => 'petBlue'],
    ['chave' => 'servico_fisioterapia_ativo', 'titulo' => 'Fisioterapia', 'descricao' => 'Tratamento de lesões, pós-operatório ou doenças degenerativas.', 'icone' => '<svg class="w-8 h-8 text-petOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>', 'cor' => 'petOrange'],
    ['chave' => 'servico_pet_sitter_ativo', 'titulo' => 'Pet Sitter', 'descricao' => 'Serviço de passeio diário ou hospedagem domiciliar para seu pet.', 'icone' => '<svg class="w-8 h-8 text-petBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 11.25h6M9 15.75h6" /></svg>', 'cor' => 'petBlue'],
    ['chave' => 'servico_outros_ativo', 'titulo' => 'Outros Serviços', 'descricao' => 'Entre em contato, oferecemos diversas soluções para melhor atendê-lo.', 'icone' => '<svg class="w-8 h-8 text-petOrange" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>', 'cor' => 'petOrange'],
];

// Filtra apenas os serviços que estão ativos na configuração
$servicos_selecionados = [];
foreach ($todos_servicos as $servico) {
    if (!empty($configuracoes[$servico['chave']])) {
        $servicos_selecionados[] = $servico;
    }
}

// --- LÓGICA ATUALIZADA ---
// Busca os 4 produtos mais recentes COM ESTOQUE
$produtos_com_estoque = $mysqli->query("SELECT * FROM produtos WHERE estoque > 0 ORDER BY id DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
$loja_ativa = !empty($configuracoes['exibir_secao_produtos']);

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
                    $link_agendamento = 'agendamento.php';
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
        
        <?php if (!empty($servicos_selecionados)): ?>
        <div class="flex flex-wrap justify-center items-stretch gap-8">
            <?php foreach ($servicos_selecionados as $servico): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 transition-transform duration-300 hover:-translate-y-2 w-full max-w-xs flex flex-col">
                <div class="w-16 h-16 bg-<?= $servico['cor'] ?> bg-opacity-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <?= $servico['icone'] ?>
                </div>
                <h3 class="text-xl font-semibold text-petGray text-center mb-3"><?= htmlspecialchars($servico['titulo']) ?></h3>
                <p class="text-petGray text-center flex-grow"><?= htmlspecialchars($servico['descricao']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="text-center text-petGray">Nenhum serviço selecionado para exibição. Entre em contato para mais informações.</p>
        <?php endif; ?>

    </div>
</section>

<?php // --- SEÇÃO DE PRODUTOS TOTALMENTE ATUALIZADA --- ?>
<?php if ($loja_ativa): ?>
<section id="products" class="py-16 bg-petLightGray bg-opacity-30">
     <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-petGray">Produtos <span class="text-petOrange">Populares</span></h2>
            <div class="w-24 h-1 bg-petBlue mx-auto mt-4"></div>
        </div>
        
        <?php if (!empty($produtos_com_estoque)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($produtos_com_estoque as $produto): ?>
                <a href="loja.php" class="block bg-white rounded-lg shadow-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2">
                    <img src="Imagens/produtos/<?= htmlspecialchars($produto['imagem'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($produto['nome'] ?? 'Produto') ?>" class="w-full h-48 object-cover">
                    <div class="p-4 flex flex-col justify-between" style="min-height: 160px;">
                        <div>
                            <h3 class="text-lg font-semibold text-petGray"><?= htmlspecialchars($produto['nome'] ?? '') ?></h3>
                            <p class="text-petGray text-sm mb-2 h-10"><?= htmlspecialchars($produto['descricao'] ?? '') ?></p>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-petBlue font-bold">R$ <?= number_format($produto['preco'] ?? 0, 2, ',', '.') ?></span>
                            <span class="bg-petOrange text-white px-3 py-1 rounded-md text-sm font-semibold">Ver na Loja</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-petGray">Ops, nossos itens populares estão temporariamente indisponíveis. Já estamos trabalhando no novo estoque e ele estará disponível em breve. Agradecemos sua paciência!</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>


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
                <?php for ($i = 1; $i <= 2; $i++): ?>
                    <?php if (!empty($configuracoes["telefone_$i"])): ?>
                        <?php 
                            $is_whatsapp = !empty($configuracoes["telefone_{$i}_is_whatsapp"]);
                            $numero_puro = preg_replace('/\D/', '', $configuracoes["telefone_$i"]);
                            $link = $is_whatsapp ? "https://wa.me/55{$numero_puro}" : "tel:{$numero_puro}";
                        ?>
                        <a href="<?= $link ?>" target="_blank" class="text-petGray hover:text-petBlue flex items-center justify-center space-x-2 p-1">
                            <span><?= htmlspecialchars($configuracoes["telefone_$i"]) ?></span>
                            <?php if ($is_whatsapp): ?>
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.894 11.892-1.99 0-3.903-.52-5.586-1.459L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.433-9.89-9.889-9.89-5.452 0-9.887 4.428-9.888 9.89 .001 2.23 1.056 4.438 2.671 6.06L4.77 21.616l1.885-1.414z"/></svg>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
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