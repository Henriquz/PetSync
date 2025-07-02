-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02-Jul-2025 às 22:47
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `petsync`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `servico` varchar(255) NOT NULL,
  `data_agendamento` datetime NOT NULL,
  `status` enum('Pendente','Confirmado','Em Andamento','Concluído','Cancelado') NOT NULL DEFAULT 'Pendente',
  `observacoes` text DEFAULT NULL,
  `observacoes_admin` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_entrega` varchar(50) NOT NULL,
  `endereco_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `agendamentos`
--

INSERT INTO `agendamentos` (`id`, `usuario_id`, `pet_id`, `servico`, `data_agendamento`, `status`, `observacoes`, `observacoes_admin`, `data_criacao`, `tipo_entrega`, `endereco_id`) VALUES
(14, 1, 33, 'Banho e Tosa', '2025-07-03 10:00:00', 'Concluído', '0', '', '2025-07-02 19:04:55', 'loja', NULL),
(15, 7, 34, 'Vacinação, Hospedagem', '2025-07-04 09:00:00', 'Cancelado', '0', NULL, '2025-07-02 19:14:58', 'delivery', 4),
(16, 1, 33, 'Consulta Veterinária', '2025-07-23 14:00:00', 'Cancelado', '0', 'testando apenas', '2025-07-02 19:19:59', 'loja', NULL),
(17, 1, 33, 'Hospedagem', '2025-07-03 10:00:00', 'Em Andamento', '0', NULL, '2025-07-02 19:21:17', 'loja', NULL),
(18, 3, 35, 'Consulta Veterinária', '2025-07-02 10:00:00', 'Cancelado', '0', 'n veio observação', '2025-07-02 19:25:56', 'delivery', 2),
(19, 1, 33, 'Banho e Tosa', '2025-07-02 17:00:00', 'Concluído', 'esse é apenas teste', 'tudo certo', '2025-07-02 19:30:32', 'loja', NULL),
(20, 1, 36, 'Banho e Tosa', '2025-07-03 10:00:00', 'Cancelado', 'tem alergia a pessoas', NULL, '2025-07-02 19:50:08', 'loja', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave`, `valor`) VALUES
(1, 'telefone_1', '(33) 5555-0000'),
(2, 'telefone_2', '(33) 99999-0000'),
(3, 'email_contato', 'e-mail@petsync.com.br'),
(4, 'horario_semana', 'Segunda a Sexta: 9h às 19h'),
(5, 'horario_sabado', 'Sábado: 8h às 22h'),
(6, 'endereco', 'R. João Pinheiro, 147, Sala 214 - Centro, Caratinga - MG'),
(7, 'mapa_url', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d7508.34123786262!2d-42.14313105!3d-19.790362!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xba90c4afd17a7b%3A0x76859e6d3039c93!2sR.%20Jo%C3%A3o%20Pinheiro%2C%20147%20-%20Centro%2C%20Caratinga%20-%20MG%2C%2035300-037!5e0!3m2!1spt-BR!2sbr!4v1750964848571!5m2!1spt-BR!2sbr\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
(8, 'sobre_titulo', 'Sobre a PetSync'),
(9, 'sobre_texto_1', 'A PetSync nasceu da paixão por animais e do desejo de oferecer o melhor cuidado possível para os pets. Fundada em 2025, nossa missão é proporcionar serviços de qualidade e produtos premium que contribuam para o bem-estar e felicidade dos animais de estima'),
(10, 'sobre_texto_2', 'Nossa equipe é formada por profissionais qualificados e apaixonados por pets, sempre prontos para atender você e seu melhor amigo com carinho e dedicação.'),
(11, 'stat_1_num', '2+'),
(12, 'stat_1_desc', 'Anos de experiência'),
(13, 'stat_2_num', '2k+'),
(14, 'stat_2_desc', 'Clientes felizes'),
(15, 'stat_3_num', '2+'),
(16, 'stat_3_desc', 'Profissionais'),
(17, 'stat_4_num', '2+'),
(18, 'stat_4_desc', 'Produtos');

-- --------------------------------------------------------

--
-- Estrutura da tabela `enderecos`
--

CREATE TABLE `enderecos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `rua` varchar(255) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `is_principal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `enderecos`
--

INSERT INTO `enderecos` (`id`, `usuario_id`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `is_principal`) VALUES
(1, 3, '35300-003', 'Vila Francisco de Assis', '11', '4', 'Centro', 'Caratinga', 'MG', 0),
(2, 3, '35300292', 'Rua Pedro Mourão', '45', '201', 'Santa Zita', 'Caratinga', 'MG', 0),
(3, 7, '35300-282', 'Rua Tupy', '12', '45', 'Santa Zita', 'Caratinga', 'MG', 0),
(4, 7, '35300-297', 'Rua Doutor Aluízio Muniz', '289', '10', 'Santa Zita', 'Caratinga', 'MG', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `horarios_disponiveis`
--

CREATE TABLE `horarios_disponiveis` (
  `id` int(11) NOT NULL,
  `horario` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `horarios_disponiveis`
--

INSERT INTO `horarios_disponiveis` (`id`, `horario`) VALUES
(1, '09:00:00'),
(2, '10:00:00'),
(3, '11:00:00'),
(4, '14:00:00'),
(5, '15:00:00'),
(6, '16:00:00'),
(7, '17:00:00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensagem` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `dono_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `especie` varchar(50) DEFAULT NULL,
  `raca` varchar(100) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `foto_url` varchar(255) DEFAULT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pets`
--

INSERT INTO `pets` (`id`, `dono_id`, `nome`, `especie`, `raca`, `data_nascimento`, `foto_url`, `genero`, `observacoes`, `data_cadastro`) VALUES
(32, 5, 'dog do Adm', 'Cão', 'Pitbull', NULL, 'pet_686581aa3041b_1751482794.png', NULL, NULL, '2025-07-02 18:59:54'),
(33, 1, 'tico', 'roedor', 'N/A', '0000-00-00', NULL, NULL, NULL, '2025-07-02 19:04:55'),
(34, 7, 'laika', 'Cão', 'Shih Tzu', '0000-00-00', NULL, NULL, NULL, '2025-07-02 19:14:58'),
(35, 3, 'dori', 'roedor', 'N/A', '0000-00-00', NULL, NULL, NULL, '2025-07-02 19:25:56'),
(36, 1, 'teco', 'Gato', 'Sphynx', NULL, NULL, NULL, NULL, '2025-07-02 19:50:08');

-- --------------------------------------------------------

--
-- Estrutura da tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `preco`, `imagem`, `data_cadastro`) VALUES
(1, 'Ração Premium 5kg', 'Nutrição completa para cães adultos de todas as raças.', '89.90', 'racao_premium.jpg', '2025-06-26 17:42:58'),
(3, 'cama', 'cama para gato', '20.00', '685dc6bdcc0a6.png', '2025-06-26 22:15:29');

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `senha`, `data_cadastro`, `is_admin`, `is_active`) VALUES
(1, 'jullye', 'jullye@jullye.com', '33999472125', '$2y$10$90g8fbCQ4t79tKKdxZ2f.u968ViDvo302dyvVTnRIbOjqeJCMtxj6', '2025-06-26 13:55:23', 0, 1),
(2, 'jullye@jullye.com', 'teste@teste.com', '99999999999', '$2y$10$.eaFQhEyzLR5dfQG8PZYNemvDl3CMS2hXo5Vjf5OH7gHR4jJj4YaO', '2025-06-26 14:00:03', 1, 1),
(3, 'Henrique Jullye', '123@gmail.com', '', '$2y$10$/My2AQh8G9wVFajNqZVIqOCTYDK1Qc3Uc1pJg.DWQh1hgekIr30Fq', '2025-06-26 14:07:50', 0, 1),
(5, 'admin', 'admin@admin.com', '(33) 33333-3333', '$2y$10$8ndbxvY8jEIX9W60N2gPeemjdgZVBP.4AJyWFP7zJYw6reewubvGe', '2025-06-26 17:55:08', 1, 1),
(6, 'teste', 'teste@admin.com', NULL, '$2y$10$ZVZS7YJX4oFm8FzgL9N3PO86stLS1gOgCAc/F/CO4.MwiFEZQlvXS', '2025-06-26 18:14:10', 1, 1),
(7, 'miguel araujo', 'miguel@mighuel.com', '339999999', '$2y$10$y0hXAEdagO.HrFCwWMlyRuQOSm/rb3MhbHoQXWDQh7x/hPupWIjci', '2025-06-26 22:07:00', 0, 1),
(8, 'miguel araujo', 'miguel@araujo.com', NULL, '$2y$10$ZadXVKxe6Ql/yC8AJ1tt6uJtNobosmFdvNuDGxFfGAXtZvS26Cwpm', '2025-06-26 22:22:28', 1, 1),
(11, 'chico le', 'chicole@gmail.com', '', '$2y$10$wM.rm/qsKGA1TTwwUIdw1e3pWCiBsN1RdmjvXhyisdFK6BM9RsvWe', '2025-06-30 18:21:35', 0, 1),
(13, 'teste novo cadastro', 'teste@kelly.com', '33999465022', '$2y$10$bX1Wupiyv2pc5pVfVt1Gpe.QyCu1mZvD5hJWnS3JR495YQJFaNufa', '2025-07-01 12:55:13', 0, 1),
(14, 'teste endereço', 'testeendereco@gmail.com', '', '$2y$10$2EZJ80avPnnVvDu/KALKA.oUChUa5hgfJrcYqQSvnyN.Aq28RvSeO', '2025-07-01 13:05:05', 0, 1),
(15, 'ola teste', 'teste@ola.com', '(33) 3 3333-3333', '$2y$10$t5vzIXQkaKB22QvXHq2EO.1IslJqD2Uh5NyRfbY5cnZas9p/PkMxG', '2025-07-01 13:53:17', 0, 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Índices para tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices para tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `horarios_disponiveis`
--
ALTER TABLE `horarios_disponiveis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `horario` (`horario`);

--
-- Índices para tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dono_id` (`dono_id`);

--
-- Índices para tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `horarios_disponiveis`
--
ALTER TABLE `horarios_disponiveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD CONSTRAINT `enderecos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`dono_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
