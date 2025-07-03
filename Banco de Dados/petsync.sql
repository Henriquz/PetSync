-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 03-Jul-2025 às 22:25
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
(17, 1, 33, 'Hospedagem', '2025-07-03 10:00:00', 'Concluído', '0', 'feito', '2025-07-02 19:21:17', 'loja', NULL),
(18, 3, 35, 'Consulta Veterinária', '2025-07-02 10:00:00', 'Cancelado', '0', 'n veio observação', '2025-07-02 19:25:56', 'delivery', 2),
(19, 1, 33, 'Banho e Tosa', '2025-07-02 17:00:00', 'Concluído', 'esse é apenas teste', 'tudo certo', '2025-07-02 19:30:32', 'loja', NULL),
(20, 1, 36, 'Banho e Tosa', '2025-07-03 10:00:00', 'Cancelado', 'tem alergia a pessoas', NULL, '2025-07-02 19:50:08', 'loja', NULL),
(21, 1, 36, 'Banho e Tosa', '2025-07-10 10:00:00', 'Cancelado', '', 'teste', '2025-07-03 12:57:02', 'loja', NULL),
(22, 1, 33, 'banho', '2025-07-07 17:15:00', 'Pendente', '', NULL, '2025-07-03 19:49:38', 'delivery', 5),
(23, 1, 37, 'tosa', '2025-07-07 16:00:00', 'Pendente', '', NULL, '2025-07-03 19:50:24', 'delivery', 5),
(24, 1, 36, 'tosa', '2025-07-07 09:15:00', 'Pendente', '', NULL, '2025-07-03 20:15:17', 'loja', NULL);

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
(18, 'stat_4_desc', 'Produtos'),
(19, 'permitir_delivery', '1'),
(20, 'permitir_cliente_leva_e_busca', '1');

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
(4, 7, '35300-297', 'Rua Doutor Aluízio Muniz', '289', '10', 'Santa Zita', 'Caratinga', 'MG', 0),
(5, 1, '35300299', 'Rua Catarina Maria Passos', '34', '', 'Santa Zita', 'Caratinga', 'MG', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `galeria`
--

CREATE TABLE `galeria` (
  `id` int(11) NOT NULL,
  `url_imagem` varchar(255) NOT NULL,
  `nome_pet` varchar(100) DEFAULT NULL,
  `legenda` text DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `galeria`
--

INSERT INTO `galeria` (`id`, `url_imagem`, `nome_pet`, `legenda`, `data_upload`) VALUES
(2, 'uploads/galeria/galeria_68669379c84848.14573502.jpg', 'Tico', 'tico em seu lazer', '2025-07-03 14:28:09');

-- --------------------------------------------------------

--
-- Estrutura da tabela `horarios_atendimento`
--

CREATE TABLE `horarios_atendimento` (
  `id` int(11) NOT NULL,
  `dia_semana` int(11) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `pausa_inicio` time DEFAULT NULL,
  `pausa_fim` time DEFAULT NULL,
  `capacidade_por_slot` int(11) NOT NULL DEFAULT 1,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `horarios_atendimento`
--

INSERT INTO `horarios_atendimento` (`id`, `dia_semana`, `hora_inicio`, `hora_fim`, `pausa_inicio`, `pausa_fim`, `capacidade_por_slot`, `ativo`) VALUES
(1, 1, '09:00:00', '18:00:00', '12:00:00', '14:00:00', 3, 0),
(2, 2, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(3, 3, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(4, 4, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(5, 5, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(6, 6, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(7, 0, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(8, 1, '09:00:00', '18:00:00', '12:00:00', '14:00:00', 3, 0),
(9, 2, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(10, 3, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(11, 4, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(12, 5, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(13, 6, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(14, 0, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(15, 1, '09:00:00', '18:00:00', '12:00:00', '14:00:00', 3, 1),
(16, 2, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(17, 3, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(18, 4, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(19, 5, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(20, 6, '09:00:00', '18:00:00', NULL, NULL, 1, 0),
(21, 0, '09:00:00', '18:00:00', NULL, NULL, 1, 0);

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
  `tipo` enum('automatica','alerta') NOT NULL DEFAULT 'automatica',
  `imagem_url` varchar(255) DEFAULT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `visivel` tinyint(1) NOT NULL DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `mensagem`, `link`, `tipo`, `imagem_url`, `lida`, `visivel`, `data_criacao`) VALUES
(1, 1, 'Oba! O atendimento para tico foi concluído e seu pet já pode ser retirado!', 'meus_agendamentos.php', 'automatica', NULL, 1, 0, '2025-07-03 11:40:08'),
(2, 2, 'Novo agendamento de jullye para teco.', 'admin/gerencia_agendamentos.php', 'automatica', NULL, 0, 1, '2025-07-03 12:57:02'),
(3, 5, 'Novo agendamento de jullye para teco.', 'admin/gerencia_agendamentos.php', 'automatica', NULL, 1, 0, '2025-07-03 12:57:02'),
(4, 6, 'Novo agendamento de jullye para teco.', 'admin/gerencia_agendamentos.php', 'automatica', NULL, 0, 1, '2025-07-03 12:57:02'),
(5, 8, 'Novo agendamento de jullye para teco.', 'admin/gerencia_agendamentos.php', 'automatica', NULL, 0, 1, '2025-07-03 12:57:02'),
(6, 1, 'O atendimento para teco foi iniciado!', 'meus_agendamentos.php', 'automatica', NULL, 1, 0, '2025-07-03 12:57:36'),
(7, 1, 'Atenção: o agendamento para teco foi cancelado. Motivo: teste', 'meus_agendamentos.php', 'automatica', NULL, 1, 0, '2025-07-03 13:03:40'),
(8, 1, 'esse é apenas um aviso de teste', '', 'alerta', 'uploads/notificacoes/notif_686682d80445e4.38444887.png', 1, 0, '2025-07-03 13:17:12'),
(9, 3, 'esse é apenas um aviso de teste', '', 'alerta', 'uploads/notificacoes/notif_686682d80445e4.38444887.png', 0, 1, '2025-07-03 13:17:12'),
(10, 7, 'esse é apenas um aviso de teste', '', 'alerta', 'uploads/notificacoes/notif_686682d80445e4.38444887.png', 0, 1, '2025-07-03 13:17:12'),
(11, 11, 'esse é apenas um aviso de teste', '', 'alerta', 'uploads/notificacoes/notif_686682d80445e4.38444887.png', 0, 1, '2025-07-03 13:17:12'),
(12, 13, 'esse é apenas um aviso de teste', '', 'alerta', 'uploads/notificacoes/notif_686682d80445e4.38444887.png', 0, 1, '2025-07-03 13:17:12'),
(13, 14, 'esse é apenas um aviso de teste', '', 'alerta', 'uploads/notificacoes/notif_686682d80445e4.38444887.png', 0, 1, '2025-07-03 13:17:12'),
(14, 15, 'esse é apenas um aviso de teste', '', 'alerta', 'uploads/notificacoes/notif_686682d80445e4.38444887.png', 0, 1, '2025-07-03 13:17:12'),
(15, 1, 'oi jullye', '', 'alerta', NULL, 1, 0, '2025-07-03 13:17:22'),
(16, 1, 'rlx', '', 'alerta', 'uploads/notificacoes/notif_6866863cb611c4.41160386.jpg', 1, 0, '2025-07-03 13:31:40'),
(17, 3, 'rlx', '', 'alerta', 'uploads/notificacoes/notif_6866863cb611c4.41160386.jpg', 0, 1, '2025-07-03 13:31:40'),
(18, 7, 'rlx', '', 'alerta', 'uploads/notificacoes/notif_6866863cb611c4.41160386.jpg', 0, 1, '2025-07-03 13:31:40'),
(19, 11, 'rlx', '', 'alerta', 'uploads/notificacoes/notif_6866863cb611c4.41160386.jpg', 0, 1, '2025-07-03 13:31:40'),
(20, 13, 'rlx', '', 'alerta', 'uploads/notificacoes/notif_6866863cb611c4.41160386.jpg', 0, 1, '2025-07-03 13:31:40'),
(21, 14, 'rlx', '', 'alerta', 'uploads/notificacoes/notif_6866863cb611c4.41160386.jpg', 0, 1, '2025-07-03 13:31:40'),
(22, 15, 'rlx', '', 'alerta', 'uploads/notificacoes/notif_6866863cb611c4.41160386.jpg', 0, 1, '2025-07-03 13:31:40'),
(23, 1, 'ola', '', 'alerta', 'uploads/notificacoes/notif_6866886c9e17c9.56459004.png', 1, 0, '2025-07-03 13:41:00'),
(24, 3, 'ola', '', 'alerta', 'uploads/notificacoes/notif_6866886c9e17c9.56459004.png', 0, 1, '2025-07-03 13:41:00'),
(25, 7, 'ola', '', 'alerta', 'uploads/notificacoes/notif_6866886c9e17c9.56459004.png', 0, 1, '2025-07-03 13:41:00'),
(26, 11, 'ola', '', 'alerta', 'uploads/notificacoes/notif_6866886c9e17c9.56459004.png', 0, 1, '2025-07-03 13:41:00'),
(27, 13, 'ola', '', 'alerta', 'uploads/notificacoes/notif_6866886c9e17c9.56459004.png', 0, 1, '2025-07-03 13:41:00'),
(28, 14, 'ola', '', 'alerta', 'uploads/notificacoes/notif_6866886c9e17c9.56459004.png', 0, 1, '2025-07-03 13:41:00'),
(29, 15, 'ola', '', 'alerta', 'uploads/notificacoes/notif_6866886c9e17c9.56459004.png', 0, 1, '2025-07-03 13:41:00'),
(30, 1, 'Nesta breve mensagem demonstrativa, apresento um exemplo de texto contendo exatamente duzentos e cinquenta e quatro caracteres, incluindo espaços, pontuações e letras. Obrigado pela atenção e confiança em meu trabalho. Tenha um ótimo dia! Até breve! :)!!', '', 'alerta', NULL, 1, 0, '2025-07-03 14:05:15'),
(31, 3, 'Nesta breve mensagem demonstrativa, apresento um exemplo de texto contendo exatamente duzentos e cinquenta e quatro caracteres, incluindo espaços, pontuações e letras. Obrigado pela atenção e confiança em meu trabalho. Tenha um ótimo dia! Até breve! :)!!', '', 'alerta', NULL, 1, 0, '2025-07-03 14:05:15'),
(32, 7, 'Nesta breve mensagem demonstrativa, apresento um exemplo de texto contendo exatamente duzentos e cinquenta e quatro caracteres, incluindo espaços, pontuações e letras. Obrigado pela atenção e confiança em meu trabalho. Tenha um ótimo dia! Até breve! :)!!', '', 'alerta', NULL, 0, 1, '2025-07-03 14:05:15'),
(33, 11, 'Nesta breve mensagem demonstrativa, apresento um exemplo de texto contendo exatamente duzentos e cinquenta e quatro caracteres, incluindo espaços, pontuações e letras. Obrigado pela atenção e confiança em meu trabalho. Tenha um ótimo dia! Até breve! :)!!', '', 'alerta', NULL, 0, 1, '2025-07-03 14:05:15'),
(34, 13, 'Nesta breve mensagem demonstrativa, apresento um exemplo de texto contendo exatamente duzentos e cinquenta e quatro caracteres, incluindo espaços, pontuações e letras. Obrigado pela atenção e confiança em meu trabalho. Tenha um ótimo dia! Até breve! :)!!', '', 'alerta', NULL, 0, 1, '2025-07-03 14:05:15'),
(35, 14, 'Nesta breve mensagem demonstrativa, apresento um exemplo de texto contendo exatamente duzentos e cinquenta e quatro caracteres, incluindo espaços, pontuações e letras. Obrigado pela atenção e confiança em meu trabalho. Tenha um ótimo dia! Até breve! :)!!', '', 'alerta', NULL, 0, 1, '2025-07-03 14:05:15'),
(36, 15, 'Nesta breve mensagem demonstrativa, apresento um exemplo de texto contendo exatamente duzentos e cinquenta e quatro caracteres, incluindo espaços, pontuações e letras. Obrigado pela atenção e confiança em meu trabalho. Tenha um ótimo dia! Até breve! :)!!', '', 'alerta', NULL, 0, 1, '2025-07-03 14:05:15');

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
(36, 1, 'teco', 'Gato', 'Sphynx', NULL, NULL, NULL, NULL, '2025-07-02 19:50:08'),
(37, 1, 'tico e teco', 'Gato', 'Sphynx', NULL, NULL, NULL, NULL, '2025-07-03 19:50:24');

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
(3, 'cama', 'cama para gato', '20.00', '685dc6bdcc0a6.png', '2025-06-26 22:15:29');

-- --------------------------------------------------------

--
-- Estrutura da tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `duracao_minutos` int(11) NOT NULL DEFAULT 60,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `duracao_minutos`, `descricao`, `ativo`) VALUES
(1, 'banho', 40, NULL, 1),
(2, 'tosa', 25, NULL, 1);

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
-- Índices para tabela `galeria`
--
ALTER TABLE `galeria`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `horarios_atendimento`
--
ALTER TABLE `horarios_atendimento`
  ADD PRIMARY KEY (`id`);

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
-- Índices para tabela `servicos`
--
ALTER TABLE `servicos`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `galeria`
--
ALTER TABLE `galeria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `horarios_atendimento`
--
ALTER TABLE `horarios_atendimento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `horarios_disponiveis`
--
ALTER TABLE `horarios_disponiveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
