-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19/11/2025 às 03:53
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gardenia`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartoes_credito`
--

CREATE TABLE `cartoes_credito` (
  `id_cartao` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titular` varchar(100) NOT NULL,
  `bandeira` varchar(50) NOT NULL,
  `ultimos_digitos` char(4) NOT NULL,
  `validade_mes` char(2) NOT NULL,
  `validade_ano` char(4) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cartoes_credito`
--

INSERT INTO `cartoes_credito` (`id_cartao`, `usuario_id`, `titular`, `bandeira`, `ultimos_digitos`, `validade_mes`, `validade_ano`, `token`, `principal`, `criado_em`) VALUES
(1, 3, 'Marya Eduarda', 'Visa', '1234', '12', '2028', NULL, 1, '2025-09-23 16:48:56'),
(2, 4, 'Clara', 'Mastercard', '5678', '06', '2027', NULL, 1, '2025-09-23 16:48:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

CREATE TABLE `enderecos` (
  `id_endereco` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `apelido` varchar(50) DEFAULT NULL,
  `cep` varchar(10) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `rua` varchar(255) NOT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `enderecos`
--

INSERT INTO `enderecos` (`id_endereco`, `usuario_id`, `apelido`, `cep`, `estado`, `cidade`, `bairro`, `rua`, `numero`, `complemento`, `principal`) VALUES
(1, 3, 'Casa', '19060-000', 'São Paulo', 'Presidente Prudente', 'Jardim Paulista', 'Rua das Flores', '123', 'Apto 101', 1),
(2, 4, 'Apartamento', '13020-000', 'São Paulo', 'Campinas', 'Vila Nova', 'Rua das Acácias', '250', 'Apto 202', 1),
(3, 6, 'Principal', '19505252', 'sp', 'martinopolis', NULL, 'rua da anhanguera', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario`
--

CREATE TABLE `funcionario` (
  `id_funcionario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` char(11) NOT NULL,
  `data_nascimento` date NOT NULL,
  `sexo` enum('M','F','O') NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `salario` decimal(10,2) NOT NULL,
  `data_admissao` date NOT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcionario`
--

INSERT INTO `funcionario` (`id_funcionario`, `nome`, `cpf`, `data_nascimento`, `sexo`, `cargo`, `departamento`, `salario`, `data_admissao`, `telefone`, `email`, `endereco`, `senha`, `foto`) VALUES
(1, 'Maria Oliveira', '98765432100', '1988-11-23', 'F', 'Gerente de Projetos', 'TI', 7500.00, '2024-01-15', '11999998888', 'maria.oliveira@empresa.com', 'Av. Paulista, 1000 - São Paulo/SP', '123456', NULL),
(2, 'João Silva', '12345678901', '1992-04-10', 'M', 'Analista de Sistemas', 'TI', 4800.00, '2023-09-01', '11988887777', 'joao.silva@empresa.com', 'Rua das Acácias, 250 - Campinas/SP', 'senha123', 'func_2_1761186780.jpeg');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pendente','concluido') DEFAULT 'pendente',
  `data_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `pagamento` enum('pix','cartao','boleto') DEFAULT 'pix',
  `endereco_id` int(11) DEFAULT NULL,
  `cartao_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `total`, `status`, `data_pedido`, `pagamento`, `endereco_id`, `cartao_id`) VALUES
(1, 3, 269.80, 'pendente', '2025-09-23 17:23:22', 'pix', NULL, NULL),
(11, 3, 24.00, 'concluido', '2025-10-23 11:52:43', 'pix', 1, 0),
(12, 3, 17.50, 'concluido', '2025-10-23 12:12:42', 'cartao', 1, 1),
(13, 3, 150.00, 'pendente', '2025-11-19 01:07:13', 'pix', 1, 0),
(14, 6, 270.00, 'concluido', '2025-11-19 02:20:10', 'boleto', 3, 0),
(15, 6, 270.00, 'concluido', '2025-11-19 02:26:25', 'pix', 3, 0),
(16, 6, 199.90, 'concluido', '2025-11-19 02:32:15', 'pix', 3, 0),
(17, 6, 139.90, 'pendente', '2025-11-19 02:53:06', 'boleto', 3, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_itens`
--

CREATE TABLE `pedido_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'produto',
  `detalhes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pedido_itens`
--

INSERT INTO `pedido_itens` (`id`, `pedido_id`, `produto_id`, `quantidade`, `preco_unitario`, `tipo`, `detalhes`) VALUES
(1, 1, 30, 1, 139.90, 'produto', NULL),
(2, 1, 29, 1, 129.90, 'produto', NULL),
(8, 11, NULL, 1, 24.00, 'buque', '{\"flores\":[{\"nome\":\"Girassol\",\"quantidade\":1,\"cor\":\"Amarelo\"}],\"embalagens\":[\"Papel Jornal vintage\",\"Papel transparente\"],\"laco\":\"La\\u00e7o Cetim Rosa Claro\",\"obs\":\"\"}'),
(9, 12, NULL, 1, 17.50, 'buque', '{\"flores\":[{\"nome\":\"Tulipa\",\"quantidade\":1,\"cor\":\"Rosa\"}],\"embalagens\":[\"Papel transparente\"],\"laco\":\"La\\u00e7o Cetim Rosa Claro\",\"obs\":\"\"}'),
(10, 13, 42, 1, 150.00, 'produto', ''),
(11, 14, 41, 1, 270.00, 'produto', ''),
(12, 15, 41, 1, 270.00, 'produto', ''),
(13, 16, 28, 1, 199.90, 'produto', ''),
(14, 17, 30, 1, 139.90, 'produto', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cores_disponiveis` varchar(255) DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `estoque` int(11) DEFAULT 0,
  `imagem` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `cores_disponiveis`, `preco`, `estoque`, `imagem`, `categoria`, `criado_em`) VALUES
(1, 'Rosa', 'Botão de rosa vermelha unitária', 'Vermelha,Rosa,Branca,Amarela', 12.90, 1000, 'rosa.jpg', 'flor', '2025-08-26 19:40:43'),
(2, 'Lírio', 'Flor de Lírio rosa, 1 unidade', 'Rosa,Amarela,Branca', 14.00, 300, 'lirio.jpg', 'flor', '2025-08-27 17:50:13'),
(3, 'Girassol', 'Flor girassol amarelo 1un. Radiante e cheio de energia, simboliza alegria e vitalidade.', 'Amarelo', 13.50, 400, 'girassol.jpg', 'flor', '2025-08-27 17:54:29'),
(4, 'Tulipa', 'Flor Tulipa, 1 unidade, radiante e delicada.', 'Rosa,Branca,Vermelha,Roxo', 12.00, 500, 'tulipa.jpg', 'flor', '2025-08-27 17:58:23'),
(5, 'Gerbera', 'Flor Gerbera uma unidade, deslumbrante e elegante', 'Laranja,Amarela,Rosa,Branca', 17.00, 60, 'Gerbera.jpg', 'flor', '2025-08-27 18:17:41'),
(6, 'Peônia', 'Flor Peônia uma unidade, originária da Ásia carrega o simbolismo de elegância', 'Branca,Roxo,Rosa', 16.00, 80, 'peonia.jpg', 'flor', '2025-08-27 18:23:57'),
(8, 'Orquídeas', 'exóticas e luxuosas, passam sofisticação em qualquer arranjo.', 'branca, lilás, rosa, roxa', 15.90, 70, 'orquidea.jpg', 'flor', '2025-08-31 23:26:43'),
(9, 'Astromélia', 'resistentes, variadas em cores e com ótima durabilidade.\r\nobs: todas elas contém pintinhas', 'branca, amarela, rosa, vermelha, laranja, lilás ', 18.00, 65, 'alstroemeria.jpg', 'flor', '2025-08-31 23:33:54'),
(10, 'Lisianthus', 'delicados, lembram pequenas rosas, ideais para os românticos.', 'branca, rosa, lilás', 13.00, 40, 'Lisianthus.jpg', 'flor', '2025-08-31 23:36:46'),
(11, 'Cravo', 'A escolha certa para sair do óbvio: clássicos, cheios de significado e muito duradouros.', 'vermelho, branco, rosa, branco com rosa, lilas', 14.50, 68, 'Carnation.jpg', 'flor', '2025-08-31 23:48:05'),
(12, 'Hortênsia', 'volumosas, preenchem bem o buquê e trazem suavidade.', 'azul, rosa, lilás, branca', 12.90, 80, 'hortensia.jpg', 'flor', '2025-08-31 23:51:49'),
(13, 'Dália', 'cheias de textura e volume, trazem um ar imponente ', 'vermelho, branco, amarelo, rosa, roxo, laranja', 17.90, 40, 'Dália.jpg', 'flor', '2025-08-31 23:55:55'),
(14, 'Copo-de-leite', 'Tem formato elegante e sofisticado, simboliza pureza, paz e renovação.', 'Branca', 20.00, 100, 'fresh flowers.jpg', 'flor', '2025-09-01 00:00:49'),
(15, 'Gardênia', 'A gardênia simboliza amor, pureza, gratidão, confiança e refinamento. É frequentemente associada a um amor secreto, mas também pode expressar um amor aberto e uma admiração sincera.', 'Branca', 19.90, 100, 'Gardenia.jpg', 'flor', '2025-09-01 00:09:38'),
(17, 'Papel Marrom', 'Papel Kraft:\r\nTem um aspecto mais rústico e é muito usado em embalagens modernas', 'marrom', 3.00, 50, 'papel-marrom.png', 'embalagem', '2025-09-02 23:44:28'),
(18, 'Papel jornal Branco', 'moderno e sofisticado', 'branco', 5.00, 100, 'papel-jornal-branco.png', 'embalagem', '2025-09-03 00:19:55'),
(19, 'Papel Jornal vintage', 'vintage', 'amarelo', 5.00, 100, 'papel-jornal-amarelo.png', 'embalagem', '2025-09-03 00:21:17'),
(20, 'Papel transparente', 'ótima escolha para dar enfaze nas flores', 'transparente', 3.50, 100, 'papel transparente.png', 'embalagem', '2025-09-03 00:23:52'),
(21, 'Papel verde', 'Elegante ', 'verde', 3.00, 60, 'papel verde.png', 'embalagem', '2025-09-03 00:25:32'),
(22, 'Papel Branco', 'para ocasiões especiais', 'Branca', 3.00, 100, 'papel branco.png', 'embalagem', '2025-09-03 00:27:46'),
(23, 'Papel azul', 'para transmitir calma', 'azul', 3.00, 80, 'papel azul.png', 'embalagem', '2025-09-03 00:28:40'),
(24, 'papel vermelho', 'romantico', 'vermelho', 3.00, 50, 'papel vermelho.png', 'embalagem', '2025-09-03 00:40:55'),
(25, 'papel vermelho com coração', 'vermelho e dourado', 'vermelho e dourado', 5.00, 40, 'papel vermelho com coração.png', 'embalagem', '2025-09-03 00:41:44'),
(26, 'Primavera Encantada', 'Uma combinação delicada de rosas, lírios e flores do campo, perfeita para presentear ou decorar ambientes.', 'Rosa, Branco, Amarelo, Lilás', 149.90, 15, 'Summer bouquet inspiration - pink roses and pink lilies.jpg', 'buque', '2025-09-03 01:46:37'),
(27, 'Romance Perfeito', 'Rosas vermelhas clássicas, ideal para declarar amor ou comemorar datas especiais.', 'vermelho, branco, rosa', 179.90, 30, 'buque romantico.png', 'buque', '2025-09-03 01:54:40'),
(28, 'Jardim Secreto', 'Mix de flores silvestres e verdes ornamentais, trazendo frescor e elegância para qualquer ocasião.', 'colorido', 199.90, 15, 'buque silvestre.png', 'buque', '2025-09-03 02:01:52'),
(29, 'Elegância de Tulipas', 'sofisticado e delicado, composto inteiramente por tulipas frescas, trazendo charme e alegria para qualquer ocasião.', 'Vermelho, Amarelo, Rosa, Branco, Roxo', 129.90, 18, 'buque tulipa.png', 'buque', '2025-09-03 02:10:38'),
(30, 'Sol Radiante', 'Um buquê alegre e vibrante, formado por girassóis que transmitem energia e otimismo. Perfeito para iluminar qualquer ambiente ou para presentear com um toque de felicidade.', 'amarelo', 139.90, 12, 'buque girassol.png', 'buque', '2025-09-03 02:16:22'),
(31, 'Laço Rústico', 'Laço Rústico para sair do convencional', 'marrom', 3.00, 100, 'laço rustico.png', 'laço', '2025-09-12 17:07:08'),
(32, 'laço cetim vermelho', 'laço cetim vermelho tradicional', 'vermelho', 2.00, 100, 'laço vermelho.png', 'laço', '2025-09-12 17:09:35'),
(33, 'Laço Cetim Rosa', 'Laço Cetim Rosa elegante', 'Rosa', 2.00, 160, 'laço rosa escuro.png', 'laço', '2025-09-12 17:10:53'),
(34, 'Laço Cetim Rosa Claro', 'Laço Cetim Rosa Claro', 'rosa claro', 2.00, 100, 'laço rosa claro.png', 'laço', '2025-09-12 17:11:36'),
(35, 'Laço Cetim Lilás', 'Laço Cetim Lilás', 'lilás', 2.00, 100, 'laço lilás.png', 'laço', '2025-09-12 17:12:23'),
(36, 'Laço Cetim Branco', 'Laço Cetim Branco', 'verde', 2.00, 100, 'laço branco.png', 'laço', '2025-09-12 17:13:29'),
(37, 'Laço Cetim Azul', 'Laço Cetim Azul', 'azul', 2.00, 100, 'laço azul.png', 'laço', '2025-09-12 17:14:11'),
(38, 'Laço Cetim Preto', 'Laço Cetim Preto', 'preto', 2.00, 100, 'laço preto.png', 'laço', '2025-09-12 17:15:07'),
(39, 'Laço Cetim Verde', 'Laço Cetim Verde', 'Verde', 2.00, 100, 'laço verde.png', 'laço', '2025-09-12 17:15:41'),
(40, 'Laço Cetim Amarelo', 'Laço Cetim Amarelo', 'Amarelo', 2.00, 100, 'laço amarelo.png', 'laço', '2025-09-12 17:16:39'),
(41, 'Noite Estrelada', 'Buquê \"Noite Estrelada\" inspirado na Obra do artista Van Gogh', 'azul com amarelo', 270.00, 30, 'noite_estrelada.png', 'buque', '2025-09-19 19:18:56'),
(42, 'Pureza de Alma', 'Margaridas brancas e lavandas para expressar sinceridade e paz.', 'branco com lilás', 150.00, 60, 'margarida e lavanda.png', 'buque', '2025-09-19 19:33:12'),
(43, 'Sonho de Nuvem', 'Hortênsias azuis com rosas brancas em um toque de leveza celestial.', 'azul e branco, azul e rosa', 180.00, 50, 'hortensia_e _rosa.png', 'buque', '2025-09-19 19:37:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `cpf` varchar(14) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `nascimento` date NOT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`usuario_id`, `nome`, `email`, `senha`, `cpf`, `telefone`, `nascimento`, `foto`) VALUES
(3, 'Marya Eduarda', 'marya@gmail.com', '$2y$10$EZWAZI2HnqPCJq8mYdBxwu8oMcTxE1cjNFTnBKTtD/6Y6FGwADPne', '138.209.818-96', '(18) 99728-4482', '2007-05-03', 'user_3_1761185457.jpeg'),
(4, 'clara', 'clara@gmail.com', '$2y$10$Byzygyj67BztjcUEQXkmc.bRo4NKQcg6kLOv2BGwDG.tiXQVAf4Mm', '839.849.398-94', '(19) 89906-9565', '2025-09-17', NULL),
(6, 'Marya Eduarda', 'emarya091@gmail.com', '$2y$10$uF0PgAR7ccXQB7sg.MMhhOffhqHCV0/5hO/OtuWkfJjS4IupuZNz.', '533.270.618-90', '(19) 89999-9999', '2007-05-03', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cartoes_credito`
--
ALTER TABLE `cartoes_credito`
  ADD PRIMARY KEY (`id_cartao`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD PRIMARY KEY (`id_endereco`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `funcionario`
--
ALTER TABLE `funcionario`
  ADD PRIMARY KEY (`id_funcionario`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuario_id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cartoes_credito`
--
ALTER TABLE `cartoes_credito`
  MODIFY `id_cartao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id_endereco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `funcionario`
--
ALTER TABLE `funcionario`
  MODIFY `id_funcionario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `usuario_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `cartoes_credito`
--
ALTER TABLE `cartoes_credito`
  ADD CONSTRAINT `cartoes_credito_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `enderecos`
--
ALTER TABLE `enderecos`
  ADD CONSTRAINT `enderecos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `pedido_itens_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
