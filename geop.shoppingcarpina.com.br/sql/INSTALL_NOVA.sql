-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 23/07/2026 às 09:08
-- Versão do servidor: 10.11.14-MariaDB-cll-lve
-- Versão do PHP: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `shoppingcarpinac_geopsc`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `anexos_os`
--

CREATE TABLE `anexos_os` (
  `id` int(11) NOT NULL,
  `ordem_servico_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('FOTO_ANTES','FOTO_DEPOIS','DOCUMENTO','OUTRO') DEFAULT 'OUTRO',
  `arquivo_url` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `anexos_os`
--

INSERT INTO `anexos_os` (`id`, `ordem_servico_id`, `usuario_id`, `tipo`, `arquivo_url`, `descricao`, `criado_em`) VALUES
(1, 3, 2, 'FOTO_ANTES', 'uploads/os_3_1778607273_4ab6d130.jpg', 'Pilar Sujo', '2026-05-12 14:34:33'),
(2, 4, 2, 'FOTO_ANTES', 'uploads/os_4_1778608577_d01ac02e.jpg', '', '2026-05-12 14:56:17'),
(3, 4, 2, 'FOTO_ANTES', 'uploads/os_4_1778608605_dadf321b.jpeg', '', '2026-05-12 14:56:45'),
(4, 4, 2, 'FOTO_ANTES', 'uploads/os_4_1778608902_eaf27dd8.jpg', '', '2026-05-12 15:01:42'),
(5, 6, 2, 'FOTO_ANTES', 'uploads/os_6_1778696163_9344c543.jpg', '', '2026-05-13 15:16:03'),
(6, 5, 2, 'FOTO_DEPOIS', 'uploads/os_5_1778781052_5f4b096c.jpeg', '', '2026-05-14 14:50:52'),
(7, 5, 2, 'FOTO_ANTES', 'uploads/os_5_1778781131_8b5c3651.jpg', '', '2026-05-14 14:52:11'),
(8, 9, 2, 'FOTO_ANTES', 'uploads/os_9_1778781945_c3bbd935.jpg', '', '2026-05-14 15:05:45'),
(9, 9, 2, 'FOTO_ANTES', 'uploads/os_9_1778782001_0d763adb.jpg', '', '2026-05-14 15:06:41'),
(10, 9, 2, 'FOTO_ANTES', 'uploads/os_9_1778782555_9256fcab.jpeg', '', '2026-05-14 15:15:55'),
(11, 9, 2, 'FOTO_DEPOIS', 'uploads/os_9_1778782632_f743222d.jpeg', '', '2026-05-14 15:17:12'),
(12, 9, 2, 'FOTO_DEPOIS', 'uploads/os_9_1778782667_9170fd3f.jpeg', '', '2026-05-14 15:17:47'),
(13, 9, 2, 'FOTO_DEPOIS', 'uploads/os_9_1778782699_44d0c536.jpeg', '', '2026-05-14 15:18:19'),
(14, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778783767_abfd2ceb.jpeg', '', '2026-05-14 15:36:07'),
(15, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778783792_99d6b651.jpeg', '', '2026-05-14 15:36:32'),
(16, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778783823_ef8f8d0a.jpeg', '', '2026-05-14 15:37:03'),
(17, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778783849_69248c86.jpeg', '', '2026-05-14 15:37:29'),
(18, 6, 2, 'FOTO_ANTES', 'uploads/os_6_1778783908_9dab873c.jpeg', '', '2026-05-14 15:38:28'),
(19, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778783964_f00eb32c.jpeg', '', '2026-05-14 15:39:24'),
(20, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778783992_b32973c6.jpeg', '', '2026-05-14 15:39:52'),
(21, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778784019_19f960eb.jpeg', '', '2026-05-14 15:40:19'),
(22, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778784626_126b2463.jpeg', '', '2026-05-14 15:50:26'),
(23, 6, 2, 'FOTO_DEPOIS', 'uploads/os_6_1778784708_c3a45ac7.jpeg', '', '2026-05-14 15:51:48'),
(24, 10, 2, 'FOTO_ANTES', 'uploads/os_10_1778785140_d610f7fb.jpeg', '', '2026-05-14 15:59:00'),
(25, 10, 2, 'FOTO_ANTES', 'uploads/os_10_1778785165_a3a62352.jpeg', '', '2026-05-14 15:59:25'),
(26, 10, 2, 'FOTO_ANTES', 'uploads/os_10_1778785191_386c19d0.jpeg', '', '2026-05-14 15:59:51'),
(27, 10, 2, 'FOTO_DEPOIS', 'uploads/os_10_1778785223_deb43673.jpeg', '', '2026-05-14 16:00:23'),
(28, 10, 2, 'FOTO_DEPOIS', 'uploads/os_10_1778785251_919c939d.jpeg', '', '2026-05-14 16:00:51'),
(29, 10, 2, 'FOTO_DEPOIS', 'uploads/os_10_1778785365_024a3fb5.jpeg', '', '2026-05-14 16:02:45'),
(30, 13, 2, 'FOTO_ANTES', 'uploads/os_13_1778840430_fae78f9d.jpg', '', '2026-05-15 07:20:30'),
(31, 13, 2, 'FOTO_ANTES', 'uploads/os_13_1778840472_5a7c40cb.jpg', '', '2026-05-15 07:21:12'),
(32, 13, 2, 'FOTO_ANTES', 'uploads/os_13_1778840497_709b383c.jpg', '', '2026-05-15 07:21:37'),
(33, 4, 2, 'FOTO_DEPOIS', 'uploads/os_4_1778843849_1497f99f.jpeg', '', '2026-05-15 08:17:29'),
(34, 4, 2, 'FOTO_DEPOIS', 'uploads/os_4_1778843889_f6c69a19.jpeg', '', '2026-05-15 08:18:09'),
(35, 4, 2, 'FOTO_DEPOIS', 'uploads/os_4_1778844052_a0f9c94c.jpeg', '', '2026-05-15 08:20:52'),
(36, 4, 2, 'FOTO_DEPOIS', 'uploads/os_4_1778844095_427c6ee3.jpeg', '', '2026-05-15 08:21:35'),
(37, 4, 2, 'FOTO_DEPOIS', 'uploads/os_4_1778844130_ff64441d.jpeg', '', '2026-05-15 08:22:10'),
(38, 4, 2, 'FOTO_DEPOIS', 'uploads/os_4_1778844216_578a3057.jpeg', '', '2026-05-15 08:23:36'),
(39, 4, 2, 'FOTO_DEPOIS', 'uploads/os_4_1778844316_4e950ead.jpeg', '', '2026-05-15 08:25:16'),
(40, 11, 2, 'FOTO_ANTES', 'uploads/os_11_1778844635_e1c5985e.jpeg', '', '2026-05-15 08:30:35'),
(41, 11, 2, 'FOTO_ANTES', 'uploads/os_11_1778844660_fe3f7fa3.jpeg', '', '2026-05-15 08:31:00'),
(42, 13, 2, 'FOTO_DEPOIS', 'uploads/os_13_1778855065_f5bba11b.jpeg', '', '2026-05-15 11:24:25'),
(43, 13, 2, 'FOTO_DEPOIS', 'uploads/os_13_1778855118_68dba85b.jpeg', '', '2026-05-15 11:25:18'),
(44, 13, 2, 'FOTO_DEPOIS', 'uploads/os_13_1778855152_eb8894cf.jpeg', '', '2026-05-15 11:25:52'),
(45, 14, 2, 'FOTO_ANTES', 'uploads/os_14_1778856017_d48b98c7.jpeg', '', '2026-05-15 11:40:17'),
(46, 14, 2, 'FOTO_ANTES', 'uploads/os_14_1778856042_d27c0b6c.jpeg', '', '2026-05-15 11:40:42'),
(47, 14, 2, 'FOTO_DEPOIS', 'uploads/os_14_1778856108_8f163478.jpeg', '', '2026-05-15 11:41:48'),
(48, 14, 2, 'FOTO_DEPOIS', 'uploads/os_14_1778856245_0b07940d.jpeg', '', '2026-05-15 11:44:05'),
(49, 14, 2, 'FOTO_DEPOIS', 'uploads/os_14_1778856293_020105ea.jpeg', '', '2026-05-15 11:44:53'),
(50, 15, 2, 'FOTO_DEPOIS', 'uploads/os_15_1778867558_c4a13177.jpeg', '', '2026-05-15 14:52:38'),
(51, 16, 2, 'FOTO_ANTES', 'uploads/os_16_1779111302_03f34395.jpeg', '', '2026-05-18 10:35:02'),
(52, 16, 2, 'FOTO_ANTES', 'uploads/os_16_1779111328_f0fc1ab4.jpeg', '', '2026-05-18 10:35:28'),
(53, 16, 2, 'FOTO_DEPOIS', 'uploads/os_16_1779111450_990959bf.jpeg', '', '2026-05-18 10:37:30'),
(54, 17, 2, 'FOTO_ANTES', 'uploads/os_17_1779111753_410681bf.jpg', '', '2026-05-18 10:42:33'),
(55, 17, 2, 'FOTO_DEPOIS', 'uploads/os_17_1779111833_67b3472b.jpeg', '', '2026-05-18 10:43:53'),
(56, 17, 2, 'FOTO_DEPOIS', 'uploads/os_17_1779111868_99d8dad4.jpeg', '', '2026-05-18 10:44:28'),
(57, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779112817_4aa02aa7.jpeg', '', '2026-05-18 11:00:17'),
(58, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779112844_b72148be.jpeg', '', '2026-05-18 11:00:44'),
(59, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779112939_d896cc01.jpeg', '', '2026-05-18 11:02:19'),
(60, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779113002_f2d1d257.jpeg', '', '2026-05-18 11:03:22'),
(61, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779113027_5ef536be.jpeg', '', '2026-05-18 11:03:47'),
(62, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779113063_298fa605.jpeg', '', '2026-05-18 11:04:23'),
(63, 19, 2, 'FOTO_ANTES', 'uploads/os_19_1779128735_4e04e66e.jpeg', '', '2026-05-18 15:25:35'),
(64, 19, 2, 'FOTO_ANTES', 'uploads/os_19_1779128791_a6ee56e1.jpeg', '', '2026-05-18 15:26:31'),
(65, 19, 2, 'FOTO_ANTES', 'uploads/os_19_1779128817_fd630d3c.jpeg', '', '2026-05-18 15:26:57'),
(66, 19, 2, 'FOTO_DEPOIS', 'uploads/os_19_1779128861_fd81da72.jpeg', '', '2026-05-18 15:27:41'),
(67, 19, 2, 'FOTO_DEPOIS', 'uploads/os_19_1779128919_0ac05cd6.jpeg', '', '2026-05-18 15:28:39'),
(68, 19, 2, 'FOTO_DEPOIS', 'uploads/os_19_1779129002_3f8bb125.jpeg', '', '2026-05-18 15:30:02'),
(69, 23, 2, 'FOTO_ANTES', 'uploads/os_23_1779134313_0062beb1.jpg', '', '2026-05-18 16:58:33'),
(70, 23, 2, 'FOTO_ANTES', 'uploads/os_23_1779134454_19b5e617.jpg', '', '2026-05-18 17:00:54'),
(71, 18, 2, 'FOTO_ANTES', 'uploads/os_18_1779186060_94a989eb.jpeg', '', '2026-05-19 07:21:00'),
(72, 18, 2, 'FOTO_ANTES', 'uploads/os_18_1779186297_6806caed.jpeg', '', '2026-05-19 07:24:57'),
(73, 18, 2, 'FOTO_DEPOIS', 'uploads/os_18_1779186339_5d31be49.jpeg', '', '2026-05-19 07:25:39'),
(74, 18, 2, 'FOTO_DEPOIS', 'uploads/os_18_1779186370_b2cf3540.jpeg', '', '2026-05-19 07:26:10'),
(75, 21, 2, 'FOTO_ANTES', 'uploads/os_21_1779201554_717009d8.jpeg', '', '2026-05-19 11:39:14'),
(76, 21, 2, 'FOTO_ANTES', 'uploads/os_21_1779201584_98cdda3d.jpeg', '', '2026-05-19 11:39:44'),
(77, 21, 2, 'FOTO_ANTES', 'uploads/os_21_1779201625_c727db0b.jpeg', '', '2026-05-19 11:40:25'),
(78, 21, 2, 'FOTO_ANTES', 'uploads/os_21_1779201674_e5a59433.jpeg', '', '2026-05-19 11:41:14'),
(79, 21, 2, 'FOTO_DEPOIS', 'uploads/os_21_1779201704_4cbfdd17.jpeg', '', '2026-05-19 11:41:44'),
(80, 21, 2, 'FOTO_DEPOIS', 'uploads/os_21_1779201757_5bbe45b9.jpeg', '', '2026-05-19 11:42:37'),
(81, 21, 2, 'FOTO_DEPOIS', 'uploads/os_21_1779201782_9c15b5ff.jpeg', '', '2026-05-19 11:43:02'),
(82, 21, 2, 'FOTO_DEPOIS', 'uploads/os_21_1779201809_55f7243b.jpeg', '', '2026-05-19 11:43:29'),
(83, 20, 2, 'FOTO_DEPOIS', 'uploads/os_20_1779201977_f92bd12e.jpeg', '', '2026-05-19 11:46:17'),
(84, 20, 2, 'FOTO_ANTES', 'uploads/os_20_1779202036_53422406.jpeg', '', '2026-05-19 11:47:16'),
(85, 20, 2, 'FOTO_ANTES', 'uploads/os_20_1779202061_d45bf40a.jpeg', '', '2026-05-19 11:47:41'),
(86, 20, 2, 'FOTO_ANTES', 'uploads/os_20_1779202161_55b3f9dd.jpeg', '', '2026-05-19 11:49:21'),
(87, 20, 2, 'FOTO_ANTES', 'uploads/os_20_1779202198_d2cf74e2.jpeg', '', '2026-05-19 11:49:58'),
(88, 20, 2, 'FOTO_DEPOIS', 'uploads/os_20_1779202224_8dead011.jpeg', '', '2026-05-19 11:50:24'),
(89, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779202443_4d0c6fb6.jpeg', '', '2026-05-19 11:54:03'),
(90, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779202527_8b11215b.jpeg', '', '2026-05-19 11:55:27'),
(91, 24, 2, 'FOTO_ANTES', 'uploads/os_24_1779202700_a2201491.jpeg', '', '2026-05-19 11:58:20'),
(92, 24, 2, 'FOTO_ANTES', 'uploads/os_24_1779202744_631dfa27.jpeg', '', '2026-05-19 11:59:04'),
(93, 24, 2, 'FOTO_ANTES', 'uploads/os_24_1779202769_ee29236b.jpeg', '', '2026-05-19 11:59:29'),
(94, 24, 2, 'FOTO_ANTES', 'uploads/os_24_1779202825_6e5f0ae5.jpeg', '', '2026-05-19 12:00:25'),
(95, 24, 2, 'FOTO_DEPOIS', 'uploads/os_24_1779202867_a1084a9e.jpeg', '', '2026-05-19 12:01:07'),
(96, 24, 2, 'FOTO_DEPOIS', 'uploads/os_24_1779202897_96b21bf2.jpeg', '', '2026-05-19 12:01:37'),
(97, 24, 2, 'FOTO_DEPOIS', 'uploads/os_24_1779202941_3d039e95.jpeg', '', '2026-05-19 12:02:21'),
(98, 24, 2, 'FOTO_DEPOIS', 'uploads/os_24_1779202984_0cb4b5b9.jpeg', '', '2026-05-19 12:03:04'),
(99, 23, 2, 'FOTO_DEPOIS', 'uploads/os_23_1779218726_68bde062.jpeg', '', '2026-05-19 16:25:26'),
(100, 23, 2, 'FOTO_DEPOIS', 'uploads/os_23_1779218786_50f77fdf.jpeg', '', '2026-05-19 16:26:26'),
(101, 23, 2, 'FOTO_DEPOIS', 'uploads/os_23_1779218860_bd907e50.jpeg', '', '2026-05-19 16:27:40'),
(102, 23, 2, 'FOTO_DEPOIS', 'uploads/os_23_1779218895_5b12bd3a.jpeg', '', '2026-05-19 16:28:15'),
(103, 23, 2, 'FOTO_DEPOIS', 'uploads/os_23_1779218925_6155bed3.jpeg', '', '2026-05-19 16:28:45'),
(104, 23, 2, 'FOTO_DEPOIS', 'uploads/os_23_1779219028_157898c5.jpeg', '', '2026-05-19 16:30:28'),
(105, 28, 2, 'FOTO_DEPOIS', 'uploads/os_28_1779274516_09a1f3e5.jpeg', '', '2026-05-20 07:55:16'),
(106, 28, 2, 'FOTO_DEPOIS', 'uploads/os_28_1779274561_632ae356.jpg', '', '2026-05-20 07:56:01'),
(107, 28, 2, 'FOTO_DEPOIS', 'uploads/os_28_1779274599_11ffe913.jpg', '', '2026-05-20 07:56:39'),
(108, 30, 2, 'FOTO_ANTES', 'uploads/os_30_1779279162_b5c611c7.jpeg', '', '2026-05-20 09:12:42'),
(109, 30, 2, 'FOTO_DEPOIS', 'uploads/os_30_1779279210_278bf655.jpeg', '', '2026-05-20 09:13:30'),
(110, 33, 2, 'FOTO_DEPOIS', 'uploads/os_33_1779300898_e107009f.jpeg', '', '2026-05-20 15:14:58'),
(111, 33, 2, 'FOTO_DEPOIS', 'uploads/os_33_1779300926_617a91b0.jpeg', '', '2026-05-20 15:15:26'),
(112, 34, 2, 'FOTO_DEPOIS', 'uploads/os_34_1779301809_2d05febd.jpeg', '', '2026-05-20 15:30:09'),
(113, 29, 2, 'FOTO_ANTES', 'uploads/os_29_1779362194_31b8066a.jpeg', '', '2026-05-21 08:16:34'),
(114, 29, 2, 'FOTO_DEPOIS', 'uploads/os_29_1779362558_8a199876.jpg', '', '2026-05-21 08:22:38'),
(115, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374089_c9dbe331.jpeg', '', '2026-05-21 11:34:49'),
(116, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374119_28953e36.jpeg', '', '2026-05-21 11:35:19'),
(117, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374177_5be8183b.jpeg', '', '2026-05-21 11:36:17'),
(118, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374535_12117ea6.jpeg', '', '2026-05-21 11:42:15'),
(119, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374631_1a036a6a.jpeg', '', '2026-05-21 11:43:51'),
(120, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374670_8d4a367b.jpeg', '', '2026-05-21 11:44:30'),
(121, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374696_0102ebc1.jpeg', '', '2026-05-21 11:44:56'),
(122, 35, 2, 'FOTO_ANTES', 'uploads/os_35_1779374868_73fde5fa.jpeg', '', '2026-05-21 11:47:48'),
(123, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779374984_18c51f73.jpeg', '', '2026-05-21 11:49:44'),
(124, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779375014_50159463.jpeg', '', '2026-05-21 11:50:14'),
(125, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779375053_85f2f1db.jpeg', '', '2026-05-21 11:50:53'),
(126, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779375088_d3873ad4.jpeg', '', '2026-05-21 11:51:28'),
(127, 26, 2, 'FOTO_ANTES', 'uploads/os_26_1779375415_aa8bf5b8.jpeg', '', '2026-05-21 11:56:55'),
(128, 26, 2, 'FOTO_ANTES', 'uploads/os_26_1779375438_bdfe617a.jpeg', '', '2026-05-21 11:57:18'),
(129, 26, 2, 'FOTO_ANTES', 'uploads/os_26_1779375466_cc97cc08.jpeg', '', '2026-05-21 11:57:46'),
(130, 26, 2, 'FOTO_ANTES', 'uploads/os_26_1779375487_b9a0a62d.jpeg', '', '2026-05-21 11:58:07'),
(131, 26, 2, 'FOTO_ANTES', 'uploads/os_26_1779375512_bb193d71.jpeg', '', '2026-05-21 11:58:32'),
(132, 26, 2, 'FOTO_ANTES', 'uploads/os_26_1779375537_ab9791ae.jpeg', '', '2026-05-21 11:58:57'),
(133, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779375817_0fbfb02c.jpeg', '', '2026-05-21 12:03:37'),
(134, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779375859_f38b9ccc.jpeg', '', '2026-05-21 12:04:19'),
(135, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779375890_897b3509.jpeg', '', '2026-05-21 12:04:50'),
(136, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779376351_06a1da73.jpeg', '', '2026-05-21 12:12:31'),
(137, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779376416_c24def8b.jpeg', '', '2026-05-21 12:13:36'),
(138, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779376448_9dc4e30a.jpeg', '', '2026-05-21 12:14:08'),
(139, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779376478_bf5774a5.jpeg', '', '2026-05-21 12:14:38'),
(140, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779376511_20bfaf96.jpeg', '', '2026-05-21 12:15:11'),
(141, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779376647_b70fc0bd.jpeg', '', '2026-05-21 12:17:27'),
(142, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779376751_661b7c21.jpeg', '', '2026-05-21 12:19:11'),
(143, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1779376794_d701d57d.jpeg', '', '2026-05-21 12:19:54'),
(144, 32, 2, 'FOTO_ANTES', 'uploads/os_32_1779376870_cf701c92.jpeg', '', '2026-05-21 12:21:10'),
(145, 32, 2, 'FOTO_ANTES', 'uploads/os_32_1779377086_6a5e6bd8.jpeg', '', '2026-05-21 12:24:46'),
(146, 32, 2, 'FOTO_ANTES', 'uploads/os_32_1779377113_03d78318.jpeg', '', '2026-05-21 12:25:13'),
(147, 36, 2, 'FOTO_ANTES', 'uploads/os_36_1779377714_795a965d.png', '', '2026-05-21 12:35:14'),
(148, 36, 2, 'FOTO_ANTES', 'uploads/os_36_1779377743_f1327dca.png', '', '2026-05-21 12:35:43'),
(149, 38, 2, 'FOTO_ANTES', 'uploads/os_38_1779378203_6d0c973a.jpg', '', '2026-05-21 12:43:23'),
(150, 38, 2, 'FOTO_ANTES', 'uploads/os_38_1779378237_4bc8cd83.jpg', '', '2026-05-21 12:43:57'),
(151, 41, 2, 'FOTO_ANTES', 'uploads/os_41_1779378490_84d1e6cc.jpg', '', '2026-05-21 12:48:10'),
(152, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779385736_2d8f9672.jpeg', '', '2026-05-21 14:48:56'),
(153, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779385767_b213b776.jpeg', '', '2026-05-21 14:49:27'),
(154, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779385870_396a4921.jpeg', '', '2026-05-21 14:51:10'),
(155, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779385945_1e7dc96e.jpeg', '', '2026-05-21 14:52:25'),
(156, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779385971_7311ea34.jpeg', '', '2026-05-21 14:52:51'),
(157, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779386004_3fedf6b3.jpeg', '', '2026-05-21 14:53:24'),
(158, 26, 2, 'FOTO_DEPOIS', 'uploads/os_26_1779386048_60bb482e.jpeg', '', '2026-05-21 14:54:08'),
(159, 42, 2, 'FOTO_ANTES', 'uploads/os_42_1779445304_4eaa909e.jpeg', '', '2026-05-22 07:21:44'),
(160, 42, 2, 'FOTO_ANTES', 'uploads/os_42_1779445339_1facd9c5.jpeg', '', '2026-05-22 07:22:19'),
(161, 42, 2, 'FOTO_ANTES', 'uploads/os_42_1779445365_8837c4cc.jpeg', '', '2026-05-22 07:22:45'),
(162, 42, 2, 'FOTO_ANTES', 'uploads/os_42_1779445442_cb8e1f98.jpeg', '', '2026-05-22 07:24:02'),
(163, 42, 2, 'FOTO_DEPOIS', 'uploads/os_42_1779445676_63b2e4ed.jpeg', '', '2026-05-22 07:27:56'),
(164, 42, 2, 'FOTO_DEPOIS', 'uploads/os_42_1779445709_474b155b.jpeg', '', '2026-05-22 07:28:29'),
(165, 42, 2, 'FOTO_DEPOIS', 'uploads/os_42_1779445754_0ff26f3e.jpeg', '', '2026-05-22 07:29:14'),
(166, 44, 2, 'FOTO_ANTES', 'uploads/os_44_1779446685_9c5ed388.jpg', '', '2026-05-22 07:44:45'),
(167, 44, 2, 'FOTO_ANTES', 'uploads/os_44_1779446716_c16a23e8.jpg', '', '2026-05-22 07:45:16'),
(168, 45, 2, 'FOTO_ANTES', 'uploads/os_45_1779455263_11d196b7.jpeg', '', '2026-05-22 10:07:43'),
(169, 45, 2, 'FOTO_ANTES', 'uploads/os_45_1779455278_8e234b5c.jpeg', '', '2026-05-22 10:07:58'),
(170, 45, 2, 'FOTO_ANTES', 'uploads/os_45_1779455300_567cc5e3.jpeg', '', '2026-05-22 10:08:20'),
(171, 45, 2, 'FOTO_ANTES', 'uploads/os_45_1779455311_b1e527cf.jpeg', '', '2026-05-22 10:08:31'),
(172, 45, 2, 'FOTO_DEPOIS', 'uploads/os_45_1779456463_dd0dade2.jpeg', '', '2026-05-22 10:27:43'),
(173, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779476540_088eda7c.jpeg', '', '2026-05-22 16:02:20'),
(174, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779476576_5888393f.jpeg', '', '2026-05-22 16:02:56'),
(175, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779476592_68e36943.jpeg', '', '2026-05-22 16:03:12'),
(176, 35, 2, 'FOTO_DEPOIS', 'uploads/os_35_1779476610_d286bb0a.jpeg', '', '2026-05-22 16:03:30'),
(177, 45, 2, 'FOTO_DEPOIS', 'uploads/os_45_1779715598_7a78ddf2.jpeg', '', '2026-05-25 10:26:38'),
(178, 45, 2, 'FOTO_DEPOIS', 'uploads/os_45_1779715619_e13ea22c.jpeg', '', '2026-05-25 10:26:59'),
(179, 48, 2, 'FOTO_DEPOIS', 'uploads/os_48_1779716006_47b44496.jpeg', '', '2026-05-25 10:33:26'),
(180, 48, 2, 'FOTO_DEPOIS', 'uploads/os_48_1779716031_3bfc67db.jpeg', '', '2026-05-25 10:33:51'),
(181, 48, 2, 'FOTO_DEPOIS', 'uploads/os_48_1779716048_d1b2c5c0.jpeg', '', '2026-05-25 10:34:08'),
(182, 48, 2, 'FOTO_DEPOIS', 'uploads/os_48_1779716061_b32ad9bc.jpeg', '', '2026-05-25 10:34:21'),
(183, 48, 2, 'FOTO_DEPOIS', 'uploads/os_48_1779716073_1c0ae6ad.jpeg', '', '2026-05-25 10:34:33'),
(184, 49, 2, 'FOTO_DEPOIS', 'uploads/os_49_1779716159_e66319e5.jpeg', '', '2026-05-25 10:35:59'),
(185, 49, 2, 'FOTO_DEPOIS', 'uploads/os_49_1779716172_cc31cc76.jpeg', '', '2026-05-25 10:36:12'),
(186, 49, 2, 'FOTO_DEPOIS', 'uploads/os_49_1779716184_8317615b.jpeg', '', '2026-05-25 10:36:24'),
(187, 49, 2, 'FOTO_DEPOIS', 'uploads/os_49_1779716200_9b3e29f4.jpeg', '', '2026-05-25 10:36:40'),
(188, 50, 2, 'FOTO_ANTES', 'uploads/os_50_1779729211_e15aa165.jpeg', '', '2026-05-25 14:13:31'),
(189, 50, 2, 'FOTO_ANTES', 'uploads/os_50_1779729233_a440b73a.jpeg', '', '2026-05-25 14:13:53'),
(190, 50, 2, 'FOTO_ANTES', 'uploads/os_50_1779729246_05276750.jpeg', '', '2026-05-25 14:14:06'),
(191, 50, 2, 'FOTO_ANTES', 'uploads/os_50_1779729267_29701956.jpeg', '', '2026-05-25 14:14:27'),
(192, 51, 2, 'FOTO_ANTES', 'uploads/os_51_1779735448_d0178080.jpeg', '', '2026-05-25 15:57:28'),
(193, 51, 2, 'FOTO_ANTES', 'uploads/os_51_1779735458_4e926a77.jpeg', '', '2026-05-25 15:57:38'),
(194, 31, 2, 'FOTO_ANTES', 'uploads/os_31_1779798677_ca04cd86.jpeg', '', '2026-05-26 09:31:17'),
(195, 31, 2, 'FOTO_DEPOIS', 'uploads/os_31_1779798720_76ac3dc8.jpeg', '', '2026-05-26 09:32:00'),
(196, 31, 2, 'FOTO_DEPOIS', 'uploads/os_31_1779798758_5b21f0e4.jpeg', '', '2026-05-26 09:32:38'),
(197, 54, 2, 'FOTO_ANTES', 'uploads/os_54_1779810786_bb03ae57.jpeg', '', '2026-05-26 12:53:06'),
(198, 55, 2, 'FOTO_ANTES', 'uploads/os_55_1779811220_b5d6f603.jpeg', '', '2026-05-26 13:00:20'),
(199, 56, 2, 'FOTO_ANTES', 'uploads/os_56_1779821265_e74d276d.jpeg', '', '2026-05-26 15:47:45'),
(200, 56, 2, 'FOTO_ANTES', 'uploads/os_56_1779821362_4d384f9b.jpeg', '', '2026-05-26 15:49:22'),
(201, 56, 2, 'FOTO_ANTES', 'uploads/os_56_1779821382_8338d2ae.jpeg', '', '2026-05-26 15:49:42'),
(202, 56, 2, 'FOTO_ANTES', 'uploads/os_56_1779821406_b44aba6e.jpeg', '', '2026-05-26 15:50:06'),
(203, 56, 2, 'FOTO_ANTES', 'uploads/os_56_1779821422_031d6f67.jpeg', '', '2026-05-26 15:50:22'),
(204, 56, 2, 'FOTO_DEPOIS', 'uploads/os_56_1779821440_547e929c.jpeg', '', '2026-05-26 15:50:40'),
(205, 56, 2, 'FOTO_ANTES', 'uploads/os_56_1779821466_e59c5b00.jpeg', '', '2026-05-26 15:51:06'),
(206, 56, 2, 'FOTO_DEPOIS', 'uploads/os_56_1779821548_30b4e332.jpeg', '', '2026-05-26 15:52:28'),
(207, 56, 2, 'FOTO_DEPOIS', 'uploads/os_56_1779821582_51d1d3ef.jpeg', '', '2026-05-26 15:53:02'),
(208, 56, 2, 'FOTO_DEPOIS', 'uploads/os_56_1779821638_73b966a5.jpeg', '', '2026-05-26 15:53:58'),
(209, 56, 2, 'FOTO_DEPOIS', 'uploads/os_56_1779821666_1cb4c733.jpeg', '', '2026-05-26 15:54:26'),
(210, 51, 2, 'FOTO_DEPOIS', 'uploads/os_51_1779877549_bd94ebf7.jpeg', '', '2026-05-27 07:25:49'),
(211, 51, 2, 'FOTO_DEPOIS', 'uploads/os_51_1779877568_249aec42.jpeg', '', '2026-05-27 07:26:08'),
(212, 51, 2, 'FOTO_DEPOIS', 'uploads/os_51_1779877584_1c76fd43.jpeg', '', '2026-05-27 07:26:24'),
(213, 53, 2, 'FOTO_ANTES', 'uploads/os_53_1779878435_27227907.png', '', '2026-05-27 07:40:35'),
(214, 53, 2, 'FOTO_ANTES', 'uploads/os_53_1779878450_16818124.jpg', '', '2026-05-27 07:40:50'),
(215, 53, 2, 'FOTO_ANTES', 'uploads/os_53_1779878479_b0c70fff.jpg', '', '2026-05-27 07:41:19'),
(216, 53, 2, 'FOTO_ANTES', 'uploads/os_53_1779878502_59bdcad2.jpg', '', '2026-05-27 07:41:42'),
(217, 53, 2, 'FOTO_DEPOIS', 'uploads/os_53_1779878530_f03e9548.jpeg', '', '2026-05-27 07:42:10'),
(218, 53, 2, 'FOTO_DEPOIS', 'uploads/os_53_1779878559_0668b46b.jpeg', '', '2026-05-27 07:42:39'),
(219, 50, 2, 'FOTO_DEPOIS', 'uploads/os_50_1779878967_2123b0f9.jpeg', '', '2026-05-27 07:49:27'),
(220, 50, 2, 'FOTO_DEPOIS', 'uploads/os_50_1779878988_319bb67d.jpeg', '', '2026-05-27 07:49:48'),
(221, 50, 2, 'FOTO_DEPOIS', 'uploads/os_50_1779879015_02f8316b.jpeg', '', '2026-05-27 07:50:15'),
(222, 50, 2, 'FOTO_DEPOIS', 'uploads/os_50_1779879029_b0bac31c.jpeg', '', '2026-05-27 07:50:29'),
(223, 50, 2, 'FOTO_DEPOIS', 'uploads/os_50_1779879254_85dfd1c7.jpeg', '', '2026-05-27 07:54:14'),
(224, 50, 2, 'FOTO_DEPOIS', 'uploads/os_50_1779879336_15c74b4b.jpeg', '', '2026-05-27 07:55:36'),
(225, 57, 2, 'FOTO_ANTES', 'uploads/os_57_1779879980_7e85b833.jpeg', '', '2026-05-27 08:06:20'),
(226, 57, 2, 'FOTO_ANTES', 'uploads/os_57_1779879992_892e3903.jpeg', '', '2026-05-27 08:06:32'),
(227, 57, 2, 'FOTO_DEPOIS', 'uploads/os_57_1779880011_6a09ca50.jpeg', '', '2026-05-27 08:06:51'),
(228, 57, 2, 'FOTO_DEPOIS', 'uploads/os_57_1779880027_28c95bf5.jpeg', '', '2026-05-27 08:07:07'),
(229, 59, 2, 'FOTO_ANTES', 'uploads/os_59_1779883000_6602c63c.jpeg', '', '2026-05-27 08:56:40'),
(230, 59, 2, 'FOTO_ANTES', 'uploads/os_59_1779883013_1b325ca7.jpeg', '', '2026-05-27 08:56:53'),
(231, 59, 2, 'FOTO_DEPOIS', 'uploads/os_59_1779883033_bbddd195.jpeg', '', '2026-05-27 08:57:13'),
(232, 59, 2, 'FOTO_DEPOIS', 'uploads/os_59_1779883051_c5cfd452.jpeg', '', '2026-05-27 08:57:31'),
(233, 54, 2, 'FOTO_DEPOIS', 'uploads/os_54_1779970906_cf60ecc6.jpeg', '', '2026-05-28 09:21:46'),
(234, 12, 2, 'FOTO_ANTES', 'uploads/os_12_1779971081_5d5dac54.jpg', '', '2026-05-28 09:24:41'),
(235, 12, 2, 'FOTO_DEPOIS', 'uploads/os_12_1779971235_0a0eaf5d.jpeg', '', '2026-05-28 09:27:15'),
(236, 62, 2, 'FOTO_ANTES', 'uploads/os_62_1779993448_2bba4421.jpeg', '', '2026-05-28 15:37:28'),
(237, 63, 2, 'FOTO_ANTES', 'uploads/os_63_1779998000_a0a4a9c2.jpeg', '', '2026-05-28 16:53:20'),
(238, 63, 2, 'FOTO_ANTES', 'uploads/os_63_1779998016_b8d3d12d.jpeg', '', '2026-05-28 16:53:36'),
(239, 63, 2, 'FOTO_ANTES', 'uploads/os_63_1779998030_3d6e4393.jpeg', '', '2026-05-28 16:53:50'),
(240, 63, 2, 'FOTO_DEPOIS', 'uploads/os_63_1779998044_bd853f28.jpeg', '', '2026-05-28 16:54:04'),
(241, 63, 2, 'FOTO_DEPOIS', 'uploads/os_63_1779998059_dad8e8ff.jpeg', '', '2026-05-28 16:54:19'),
(242, 63, 2, 'FOTO_DEPOIS', 'uploads/os_63_1779998071_04e35fb4.jpeg', '', '2026-05-28 16:54:31'),
(243, 55, 2, 'FOTO_DEPOIS', 'uploads/os_55_1780050390_267170ca.jpeg', '', '2026-05-29 07:26:30'),
(244, 62, 2, 'FOTO_DEPOIS', 'uploads/os_62_1780050453_203ef70c.jpeg', '', '2026-05-29 07:27:33'),
(245, 64, 2, 'FOTO_ANTES', 'uploads/os_64_1780050957_32a6ac2f.jpeg', '', '2026-05-29 07:35:57'),
(246, 64, 2, 'FOTO_ANTES', 'uploads/os_64_1780050968_a5e1ad00.jpeg', '', '2026-05-29 07:36:08'),
(247, 64, 2, 'FOTO_ANTES', 'uploads/os_64_1780050981_48594072.jpeg', '', '2026-05-29 07:36:21'),
(248, 64, 2, 'FOTO_ANTES', 'uploads/os_64_1780050994_be43b558.jpeg', '', '2026-05-29 07:36:34'),
(249, 64, 2, 'FOTO_DEPOIS', 'uploads/os_64_1780309091_5fc08cf9.jpeg', '', '2026-06-01 07:18:11'),
(250, 64, 2, 'FOTO_DEPOIS', 'uploads/os_64_1780309108_409d1348.jpeg', '', '2026-06-01 07:18:28'),
(251, 65, 2, 'FOTO_ANTES', 'uploads/os_65_1780309460_1f23aed3.jpeg', '', '2026-06-01 07:24:20'),
(252, 65, 2, 'FOTO_ANTES', 'uploads/os_65_1780309473_f2833b29.jpeg', '', '2026-06-01 07:24:33'),
(253, 65, 2, 'FOTO_DEPOIS', 'uploads/os_65_1780309529_0651ab93.jpeg', '', '2026-06-01 07:25:29'),
(254, 65, 2, 'FOTO_DEPOIS', 'uploads/os_65_1780309547_ecf2ed1a.jpeg', '', '2026-06-01 07:25:47'),
(255, 66, 2, 'FOTO_ANTES', 'uploads/os_66_1780309765_d3e1f57d.jpeg', '', '2026-06-01 07:29:25'),
(256, 66, 2, 'FOTO_ANTES', 'uploads/os_66_1780309781_14bec474.jpeg', '', '2026-06-01 07:29:41'),
(257, 66, 2, 'FOTO_DEPOIS', 'uploads/os_66_1780309800_0be30150.jpeg', '', '2026-06-01 07:30:00'),
(258, 66, 2, 'FOTO_DEPOIS', 'uploads/os_66_1780309815_be9267c8.jpeg', '', '2026-06-01 07:30:15'),
(259, 66, 2, 'FOTO_DEPOIS', 'uploads/os_66_1780309832_8170c360.jpeg', '', '2026-06-01 07:30:32'),
(260, 68, 2, 'FOTO_ANTES', 'uploads/os_68_1780316092_d0105ecf.jpeg', '', '2026-06-01 09:14:52'),
(261, 68, 2, 'FOTO_ANTES', 'uploads/os_68_1780316105_b23d15bb.jpeg', '', '2026-06-01 09:15:05'),
(262, 68, 2, 'FOTO_DEPOIS', 'uploads/os_68_1780316119_f3db1e4b.jpeg', '', '2026-06-01 09:15:19'),
(263, 69, 2, 'DOCUMENTO', 'uploads/os_69_1780318086_269bef69.pdf', '', '2026-06-01 09:48:06'),
(264, 70, 2, 'FOTO_ANTES', 'uploads/os_70_1780329725_ba4c74a8.jpg', '', '2026-06-01 13:02:05'),
(265, 70, 2, 'FOTO_ANTES', 'uploads/os_70_1780329748_bdcb399d.jpeg', '', '2026-06-01 13:02:28'),
(266, 70, 2, 'DOCUMENTO', 'uploads/os_70_1780330143_93692517.pdf', '', '2026-06-01 13:09:03'),
(267, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780409514_904c877b.jpeg', '', '2026-06-02 11:11:54'),
(268, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780409633_c7bcbab5.jpeg', '', '2026-06-02 11:13:53'),
(269, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780409654_668d1b95.jpeg', '', '2026-06-02 11:14:14'),
(270, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780409743_ec5a795c.jpeg', '', '2026-06-02 11:15:43'),
(271, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780409872_b65acfaa.jpeg', '', '2026-06-02 11:17:52'),
(272, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780409882_6add5101.jpeg', '', '2026-06-02 11:18:02'),
(273, 75, 2, 'FOTO_DEPOIS', 'uploads/os_75_1780411141_0382ae97.jpeg', '', '2026-06-02 11:39:01'),
(274, 75, 2, 'FOTO_ANTES', 'uploads/os_75_1780411149_086f26fd.jpeg', '', '2026-06-02 11:39:09'),
(275, 75, 2, 'FOTO_DEPOIS', 'uploads/os_75_1780411164_2a9d0f7e.jpeg', '', '2026-06-02 11:39:24'),
(276, 75, 2, 'FOTO_ANTES', 'uploads/os_75_1780411178_d0d6744a.jpeg', '', '2026-06-02 11:39:38'),
(277, 44, 2, 'FOTO_DEPOIS', 'uploads/os_44_1780411463_4e9e7821.jpeg', '', '2026-06-02 11:44:23'),
(278, 44, 2, 'FOTO_DEPOIS', 'uploads/os_44_1780411478_cf5b8310.jpeg', '', '2026-06-02 11:44:38'),
(279, 44, 2, 'FOTO_DEPOIS', 'uploads/os_44_1780411501_b38656d8.jpeg', '', '2026-06-02 11:45:01'),
(280, 44, 2, 'FOTO_DEPOIS', 'uploads/os_44_1780411520_e81f6b7e.jpeg', '', '2026-06-02 11:45:20'),
(281, 76, 2, 'FOTO_ANTES', 'uploads/os_76_1780413739_77a2907c.jpeg', '', '2026-06-02 12:22:19'),
(282, 76, 2, 'FOTO_DEPOIS', 'uploads/os_76_1780413755_03d8e477.jpeg', '', '2026-06-02 12:22:35'),
(283, 76, 2, 'FOTO_DEPOIS', 'uploads/os_76_1780413769_a84abca3.jpeg', '', '2026-06-02 12:22:49'),
(284, 76, 2, 'FOTO_DEPOIS', 'uploads/os_76_1780413795_7a7b3139.jpeg', '', '2026-06-02 12:23:15'),
(285, 76, 2, 'FOTO_DEPOIS', 'uploads/os_76_1780413810_d9dd5070.jpeg', '', '2026-06-02 12:23:30'),
(286, 69, 2, 'FOTO_ANTES', 'uploads/os_69_1780420232_adc48bf4.jpeg', '', '2026-06-02 14:10:32'),
(287, 86, 2, 'FOTO_ANTES', 'uploads/os_86_1780427815_3e4cb846.jpeg', '', '2026-06-02 16:16:55'),
(288, 86, 2, 'FOTO_ANTES', 'uploads/os_86_1780427848_0dd26773.jpeg', '', '2026-06-02 16:17:28'),
(289, 69, 2, 'FOTO_DEPOIS', 'uploads/os_69_1780481795_e810aeab.jpeg', '', '2026-06-03 07:16:35'),
(290, 69, 2, 'FOTO_DEPOIS', 'uploads/os_69_1780481809_505aedc1.jpeg', '', '2026-06-03 07:16:49'),
(291, 69, 2, 'FOTO_DEPOIS', 'uploads/os_69_1780481820_be2a50ed.jpeg', '', '2026-06-03 07:17:00'),
(292, 69, 2, 'FOTO_DEPOIS', 'uploads/os_69_1780481843_eff1d847.jpeg', '', '2026-06-03 07:17:23'),
(293, 72, 1, 'OUTRO', 'uploads/os_72_1780492630_e76c4c17.jpeg', 'Passagem do cabo pelas áreas técnicas do shopping', '2026-06-03 10:17:10'),
(294, 60, 2, 'FOTO_ANTES', 'uploads/os_60_1780492681_d1a5eea1.png', '', '2026-06-03 10:18:01'),
(295, 87, 2, 'FOTO_ANTES', 'uploads/os_87_1780493689_23575ae6.jpeg', '', '2026-06-03 10:34:49'),
(296, 87, 2, 'FOTO_DEPOIS', 'uploads/os_87_1780493705_08b301b7.jpeg', '', '2026-06-03 10:35:05'),
(297, 87, 2, 'FOTO_DEPOIS', 'uploads/os_87_1780493746_053449c6.jpeg', '', '2026-06-03 10:35:46'),
(298, 87, 2, 'FOTO_DEPOIS', 'uploads/os_87_1780493764_2a69f121.jpeg', '', '2026-06-03 10:36:04'),
(299, 87, 2, 'FOTO_DEPOIS', 'uploads/os_87_1780494408_29f3fdff.jpeg', '', '2026-06-03 10:46:48'),
(300, 87, 2, 'FOTO_ANTES', 'uploads/os_87_1780494433_35ed1fe6.jpeg', '', '2026-06-03 10:47:13'),
(301, 88, 2, 'FOTO_ANTES', 'uploads/os_88_1780500864_a034c348.jpeg', '', '2026-06-03 12:34:24'),
(302, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511231_c67de862.jpeg', '', '2026-06-03 15:27:11'),
(303, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511249_34f3d4a9.jpeg', '', '2026-06-03 15:27:29'),
(304, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511270_41e88d72.jpeg', '', '2026-06-03 15:27:50'),
(305, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511281_3b5aa63a.jpeg', '', '2026-06-03 15:28:01'),
(306, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511312_88632a58.jpeg', '', '2026-06-03 15:28:32'),
(307, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511405_994b6737.jpeg', '', '2026-06-03 15:30:05'),
(308, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511422_fb814e6a.jpeg', '', '2026-06-03 15:30:22'),
(309, 88, 2, 'FOTO_DEPOIS', 'uploads/os_88_1780511433_a6485ea5.jpeg', '', '2026-06-03 15:30:33'),
(310, 93, 2, 'FOTO_ANTES', 'uploads/os_93_1780512847_bc77c76e.jpeg', '', '2026-06-03 15:54:07'),
(311, 93, 2, 'FOTO_DEPOIS', 'uploads/os_93_1780512882_6c34ce6c.jpeg', '', '2026-06-03 15:54:42'),
(312, 68, 2, 'FOTO_DEPOIS', 'uploads/os_68_1780513020_f6229cd2.jpeg', '', '2026-06-03 15:57:00'),
(313, 68, 2, 'FOTO_DEPOIS', 'uploads/os_68_1780513534_b9ce5aae.jpeg', '', '2026-06-03 16:05:34'),
(314, 68, 2, 'FOTO_DEPOIS', 'uploads/os_68_1780513547_63c7a00a.jpeg', '', '2026-06-03 16:05:47'),
(315, 68, 2, 'FOTO_DEPOIS', 'uploads/os_68_1780513560_8fa40c50.jpeg', '', '2026-06-03 16:06:01'),
(316, 68, 2, 'FOTO_DEPOIS', 'uploads/os_68_1780513573_885fdf37.jpeg', '', '2026-06-03 16:06:13'),
(317, 94, 2, 'FOTO_ANTES', 'uploads/os_94_1780513951_0dc45653.jpeg', '', '2026-06-03 16:12:31'),
(318, 94, 2, 'FOTO_DEPOIS', 'uploads/os_94_1780513964_d8bbc776.jpeg', '', '2026-06-03 16:12:44'),
(319, 95, 2, 'FOTO_ANTES', 'uploads/os_95_1780514737_46cbcb23.jpeg', '', '2026-06-03 16:25:37'),
(320, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780569878_c9d15dde.jpeg', '', '2026-06-04 07:44:38'),
(321, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780572077_9b4fbae8.jpg', '', '2026-06-04 08:21:17'),
(322, 96, 2, 'FOTO_ANTES', 'uploads/os_96_1780573353_21448ca2.png', '', '2026-06-04 08:42:33'),
(323, 96, 2, 'FOTO_ANTES', 'uploads/os_96_1780573378_04a77fe8.jpeg', '', '2026-06-04 08:42:58'),
(324, 96, 2, 'FOTO_DEPOIS', 'uploads/os_96_1780573401_f9ed56ea.jpeg', '', '2026-06-04 08:43:21'),
(325, 96, 2, 'FOTO_DEPOIS', 'uploads/os_96_1780573414_1d86be05.jpeg', '', '2026-06-04 08:43:34'),
(326, 94, 2, 'FOTO_DEPOIS', 'uploads/os_94_1780579853_eb720a94.jpeg', '', '2026-06-04 10:30:53'),
(327, 97, 2, 'FOTO_ANTES', 'uploads/os_97_1780599601_d320d771.jpeg', '', '2026-06-04 16:00:01'),
(328, 97, 2, 'FOTO_DEPOIS', 'uploads/os_97_1780599613_c71ba00b.jpeg', '', '2026-06-04 16:00:13'),
(329, 98, 2, 'FOTO_DEPOIS', 'uploads/os_98_1780600035_7a4994cf.jpeg', '', '2026-06-04 16:07:15'),
(330, 94, 2, 'FOTO_DEPOIS', 'uploads/os_94_1780600837_f7a42c04.jpeg', '', '2026-06-04 16:20:37'),
(331, 94, 2, 'FOTO_DEPOIS', 'uploads/os_94_1780600853_2d571e6f.jpeg', '', '2026-06-04 16:20:53'),
(332, 99, 2, 'FOTO_ANTES', 'uploads/os_99_1780654820_a338eb68.jpeg', '', '2026-06-05 07:20:20'),
(333, 99, 2, 'FOTO_ANTES', 'uploads/os_99_1780654830_8d6e43f5.jpeg', '', '2026-06-05 07:20:30'),
(334, 44, 2, 'FOTO_DEPOIS', 'uploads/os_44_1780684208_53fabe6b.jpeg', '', '2026-06-05 15:30:08'),
(335, 100, 2, 'FOTO_ANTES', 'uploads/os_100_1780684335_4beb550b.jpeg', '', '2026-06-05 15:32:15'),
(336, 100, 2, 'FOTO_ANTES', 'uploads/os_100_1780684347_21e6b6c7.jpeg', '', '2026-06-05 15:32:27'),
(337, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780914252_b68f7e25.jpeg', '', '2026-06-08 07:24:12'),
(338, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780914264_5f228e32.jpeg', '', '2026-06-08 07:24:24'),
(339, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780914274_9b0fa34b.jpeg', '', '2026-06-08 07:24:34'),
(340, 3, 2, 'FOTO_DEPOIS', 'uploads/os_3_1780914285_124f4ed7.jpeg', '', '2026-06-08 07:24:45'),
(341, 103, 2, 'FOTO_ANTES', 'uploads/os_103_1781002707_dcc22f2e.jpeg', '', '2026-06-09 07:58:27'),
(342, 103, 2, 'FOTO_ANTES', 'uploads/os_103_1781002782_9e66e7d2.jpg', '', '2026-06-09 07:59:42'),
(343, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781002803_ef66fb1a.jpeg', '', '2026-06-09 08:00:03'),
(344, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781002814_9c2fb9c5.jpeg', '', '2026-06-09 08:00:14'),
(345, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781002825_cf22dc54.jpeg', '', '2026-06-09 08:00:25'),
(346, 101, 2, 'FOTO_ANTES', 'uploads/os_101_1781003601_ff28e20a.jpeg', '', '2026-06-09 08:13:21'),
(347, 101, 2, 'FOTO_ANTES', 'uploads/os_101_1781003621_973bb068.jpeg', '', '2026-06-09 08:13:41'),
(348, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781003886_71f9cd2e.jpeg', '', '2026-06-09 08:18:06'),
(349, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781003898_a17eeb49.jpeg', '', '2026-06-09 08:18:18'),
(350, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781003915_8bcc6040.jpeg', '', '2026-06-09 08:18:35'),
(351, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781003927_1efbcbaa.jpeg', '', '2026-06-09 08:18:47'),
(352, 104, 2, 'FOTO_DEPOIS', 'uploads/os_104_1781006597_59bbfff2.jpeg', '', '2026-06-09 09:03:17'),
(353, 102, 2, 'FOTO_ANTES', 'uploads/os_102_1781099524_28cd69fb.jpeg', '', '2026-06-10 10:52:04'),
(354, 102, 2, 'FOTO_ANTES', 'uploads/os_102_1781099543_aca443d2.jpeg', '', '2026-06-10 10:52:23'),
(355, 102, 2, 'FOTO_ANTES', 'uploads/os_102_1781099561_0ba0450a.jpeg', '', '2026-06-10 10:52:41'),
(356, 102, 2, 'FOTO_ANTES', 'uploads/os_102_1781099583_ad2cd284.jpeg', '', '2026-06-10 10:53:03'),
(357, 102, 2, 'FOTO_ANTES', 'uploads/os_102_1781099650_7ea73d6f.jpeg', '', '2026-06-10 10:54:10'),
(358, 102, 2, 'FOTO_ANTES', 'uploads/os_102_1781099660_0f523182.jpeg', '', '2026-06-10 10:54:20'),
(359, 102, 2, 'FOTO_ANTES', 'uploads/os_102_1781099678_80b5625d.jpeg', '', '2026-06-10 10:54:38'),
(360, 102, 2, 'FOTO_DEPOIS', 'uploads/os_102_1781099699_824ee687.jpeg', '', '2026-06-10 10:54:59'),
(361, 102, 2, 'FOTO_DEPOIS', 'uploads/os_102_1781099713_a4450e19.jpeg', '', '2026-06-10 10:55:13'),
(362, 102, 2, 'FOTO_DEPOIS', 'uploads/os_102_1781099732_69a3bdd7.jpeg', '', '2026-06-10 10:55:32'),
(363, 102, 2, 'FOTO_DEPOIS', 'uploads/os_102_1781099755_c0b5a009.jpeg', '', '2026-06-10 10:55:55'),
(364, 102, 2, 'FOTO_DEPOIS', 'uploads/os_102_1781099770_1f3a68ea.jpeg', '', '2026-06-10 10:56:10'),
(365, 102, 2, 'FOTO_DEPOIS', 'uploads/os_102_1781099789_e84e7260.jpeg', '', '2026-06-10 10:56:29'),
(366, 101, 2, 'FOTO_ANTES', 'uploads/os_101_1781100157_80aeca9b.jpeg', '', '2026-06-10 11:02:37'),
(367, 101, 2, 'FOTO_DEPOIS', 'uploads/os_101_1781100204_5090f71c.jpeg', '', '2026-06-10 11:03:24'),
(368, 101, 2, 'FOTO_DEPOIS', 'uploads/os_101_1781100220_3af076c6.jpeg', '', '2026-06-10 11:03:40'),
(369, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781102393_3fbbbb63.jpeg', '', '2026-06-10 11:39:53'),
(370, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781102412_a8083e03.jpeg', '', '2026-06-10 11:40:12'),
(371, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781102427_530c15e7.jpeg', '', '2026-06-10 11:40:27'),
(372, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781102443_4c3139a2.jpeg', '', '2026-06-10 11:40:43'),
(373, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781102457_7ad4ee75.jpeg', '', '2026-06-10 11:40:57'),
(374, 103, 2, 'FOTO_DEPOIS', 'uploads/os_103_1781102471_a7e628fd.jpeg', '', '2026-06-10 11:41:11'),
(375, 103, 2, 'DOCUMENTO', 'uploads/os_103_1781102776_d5ea984a.pdf', '', '2026-06-10 11:46:16'),
(376, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781102887_3758c836.jpeg', '', '2026-06-10 11:48:07'),
(377, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781102901_bb5b4e34.jpeg', '', '2026-06-10 11:48:21'),
(378, 95, 2, 'FOTO_DEPOIS', 'uploads/os_95_1781103547_bcf525af.jpeg', '', '2026-06-10 11:59:07'),
(379, 105, 2, 'FOTO_ANTES', 'uploads/os_105_1781111602_6eb2d40d.jpeg', '', '2026-06-10 14:13:22'),
(380, 106, 2, 'FOTO_ANTES', 'uploads/os_106_1781187291_b88d3572.jpeg', '', '2026-06-11 11:14:51'),
(381, 106, 2, 'FOTO_ANTES', 'uploads/os_106_1781187308_0a01b6a1.jpeg', '', '2026-06-11 11:15:08'),
(382, 106, 2, 'FOTO_ANTES', 'uploads/os_106_1781187357_da24987d.jpeg', '', '2026-06-11 11:15:57'),
(383, 106, 2, 'FOTO_ANTES', 'uploads/os_106_1781187375_a4038c1a.jpeg', '', '2026-06-11 11:16:15'),
(384, 106, 2, 'FOTO_DEPOIS', 'uploads/os_106_1781187402_9bde6d52.jpeg', '', '2026-06-11 11:16:42'),
(385, 106, 2, 'FOTO_DEPOIS', 'uploads/os_106_1781187422_6b983aff.jpeg', '', '2026-06-11 11:17:02'),
(386, 106, 2, 'FOTO_DEPOIS', 'uploads/os_106_1781187437_e20464ed.jpeg', '', '2026-06-11 11:17:17'),
(387, 106, 2, 'FOTO_DEPOIS', 'uploads/os_106_1781187461_b15c0594.jpeg', '', '2026-06-11 11:17:41'),
(388, 106, 2, 'FOTO_DEPOIS', 'uploads/os_106_1781187486_9cd5f055.jpeg', '', '2026-06-11 11:18:06'),
(389, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781188574_ed61d4f8.jpeg', '', '2026-06-11 11:36:14'),
(390, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781188592_a50687bf.jpeg', '', '2026-06-11 11:36:32'),
(391, 99, 2, 'FOTO_DEPOIS', 'uploads/os_99_1781188614_4a774c8e.jpeg', '', '2026-06-11 11:36:54'),
(392, 107, 2, 'FOTO_ANTES', 'uploads/os_107_1781189486_8b90a403.jpeg', '', '2026-06-11 11:51:26'),
(393, 107, 2, 'FOTO_DEPOIS', 'uploads/os_107_1781189507_c8d06131.jpeg', '', '2026-06-11 11:51:47'),
(394, 95, 2, 'FOTO_DEPOIS', 'uploads/os_95_1781190215_425121be.jpeg', '', '2026-06-11 12:03:35'),
(395, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1781190873_da136a9b.jpeg', '', '2026-06-11 12:14:33'),
(396, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1781190890_db925be5.jpeg', '', '2026-06-11 12:14:50'),
(397, 11, 2, 'FOTO_DEPOIS', 'uploads/os_11_1781190909_f4ed02b8.jpeg', '', '2026-06-11 12:15:09'),
(398, 108, 2, 'FOTO_ANTES', 'uploads/os_108_1781205707_19298f1e.jpeg', '', '2026-06-11 16:21:47'),
(399, 108, 2, 'FOTO_ANTES', 'uploads/os_108_1781205718_52d1ef86.jpeg', '', '2026-06-11 16:21:58'),
(400, 108, 2, 'FOTO_DEPOIS', 'uploads/os_108_1781205736_e52d2cc8.jpeg', '', '2026-06-11 16:22:16'),
(401, 108, 2, 'FOTO_DEPOIS', 'uploads/os_108_1781205806_7c9f98fe.jpeg', '', '2026-06-11 16:23:26'),
(402, 109, 2, 'FOTO_ANTES', 'uploads/os_109_1781206448_27d80266.jpeg', '', '2026-06-11 16:34:08'),
(403, 109, 2, 'FOTO_ANTES', 'uploads/os_109_1781206459_0bfb154c.jpeg', '', '2026-06-11 16:34:19'),
(404, 109, 2, 'FOTO_ANTES', 'uploads/os_109_1781206488_13321b0d.jpeg', '', '2026-06-11 16:34:48'),
(405, 109, 2, 'FOTO_DEPOIS', 'uploads/os_109_1781206504_a4816306.jpeg', '', '2026-06-11 16:35:04'),
(406, 109, 2, 'FOTO_DEPOIS', 'uploads/os_109_1781206533_e9c6609c.jpeg', '', '2026-06-11 16:35:33'),
(407, 110, 2, 'FOTO_ANTES', 'uploads/os_110_1781208151_8e144af4.jpeg', '', '2026-06-11 17:02:31'),
(408, 100, 2, 'FOTO_DEPOIS', 'uploads/os_100_1781266069_20ec3a00.jpeg', '', '2026-06-12 09:07:49'),
(409, 100, 2, 'FOTO_DEPOIS', 'uploads/os_100_1781266082_a9e2be18.jpeg', '', '2026-06-12 09:08:02'),
(410, 109, 2, 'FOTO_ANTES', 'uploads/os_109_1781271954_47708216.jpeg', '', '2026-06-12 10:45:54'),
(411, 109, 2, 'FOTO_ANTES', 'uploads/os_109_1781271969_6bf4200b.jpeg', '', '2026-06-12 10:46:09'),
(412, 109, 2, 'FOTO_DEPOIS', 'uploads/os_109_1781272010_dcab8d82.jpeg', '', '2026-06-12 10:46:50'),
(413, 109, 2, 'FOTO_DEPOIS', 'uploads/os_109_1781272063_6f11d043.jpeg', '', '2026-06-12 10:47:43'),
(414, 109, 2, 'FOTO_DEPOIS', 'uploads/os_109_1781272082_4e09e8ba.jpeg', '', '2026-06-12 10:48:02'),
(415, 111, 2, 'FOTO_DEPOIS', 'uploads/os_111_1781272949_241d603a.jpeg', '', '2026-06-12 11:02:29'),
(416, 111, 2, 'FOTO_DEPOIS', 'uploads/os_111_1781272963_2e69508c.jpeg', '', '2026-06-12 11:02:43'),
(417, 111, 2, 'FOTO_DEPOIS', 'uploads/os_111_1781272989_443946eb.jpeg', '', '2026-06-12 11:03:09'),
(418, 111, 2, 'FOTO_DEPOIS', 'uploads/os_111_1781272999_30401b70.jpeg', '', '2026-06-12 11:03:19'),
(419, 95, 2, 'FOTO_DEPOIS', 'uploads/os_95_1781521483_2e1af9c7.jpeg', '', '2026-06-15 08:04:43'),
(420, 113, 2, 'FOTO_ANTES', 'uploads/os_113_1781531676_3107c222.jpeg', '', '2026-06-15 10:54:36'),
(421, 113, 2, 'FOTO_ANTES', 'uploads/os_113_1781531696_da34435c.jpeg', '', '2026-06-15 10:54:56'),
(422, 113, 2, 'FOTO_DEPOIS', 'uploads/os_113_1781531716_bee0b763.jpeg', '', '2026-06-15 10:55:16'),
(423, 113, 2, 'FOTO_DEPOIS', 'uploads/os_113_1781531787_e84ab1ec.jpeg', '', '2026-06-15 10:56:27'),
(424, 86, 2, 'FOTO_ANTES', 'uploads/os_86_1781604601_8e560f13.jpeg', '', '2026-06-16 07:10:01'),
(425, 86, 2, 'FOTO_ANTES', 'uploads/os_86_1781604620_5e25cc9b.jpeg', '', '2026-06-16 07:10:20'),
(426, 86, 2, 'FOTO_ANTES', 'uploads/os_86_1781604662_491d0f99.jpeg', '', '2026-06-16 07:11:02'),
(427, 86, 2, 'FOTO_ANTES', 'uploads/os_86_1781604771_2023164e.jpeg', '', '2026-06-16 07:12:51'),
(428, 86, 2, 'FOTO_DEPOIS', 'uploads/os_86_1781604818_d1450292.jpeg', '', '2026-06-16 07:13:38'),
(429, 86, 2, 'FOTO_DEPOIS', 'uploads/os_86_1781604869_b4cf646c.jpeg', '', '2026-06-16 07:14:29'),
(430, 86, 2, 'FOTO_DEPOIS', 'uploads/os_86_1781604935_32271795.jpeg', '', '2026-06-16 07:15:35'),
(431, 86, 2, 'FOTO_DEPOIS', 'uploads/os_86_1781604961_87b2fc7e.jpeg', '', '2026-06-16 07:16:01'),
(432, 86, 2, 'FOTO_DEPOIS', 'uploads/os_86_1781605081_99a65666.jpeg', '', '2026-06-16 07:18:01'),
(433, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1781609577_1aa7a875.jpeg', '', '2026-06-16 08:32:57'),
(434, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1781609588_6c47cd40.jpeg', '', '2026-06-16 08:33:08'),
(435, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1781609600_9f5ed304.jpeg', '', '2026-06-16 08:33:20'),
(436, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1781609622_9082981d.jpeg', '', '2026-06-16 08:33:42'),
(437, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781609660_cc6e0fc6.jpeg', '', '2026-06-16 08:34:20'),
(438, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781609675_b686e049.jpeg', '', '2026-06-16 08:34:35'),
(439, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781609687_d5f8ab40.jpeg', '', '2026-06-16 08:34:47'),
(440, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781609698_ad6af4f6.jpeg', '', '2026-06-16 08:34:58'),
(441, 112, 2, 'FOTO_DEPOIS', 'uploads/os_112_1781623384_6570fad2.jpeg', '', '2026-06-16 12:23:04'),
(442, 112, 2, 'FOTO_DEPOIS', 'uploads/os_112_1781623407_a759a4e6.jpeg', '', '2026-06-16 12:23:27'),
(443, 112, 2, 'FOTO_DEPOIS', 'uploads/os_112_1781623419_d1bddde0.jpeg', '', '2026-06-16 12:23:39'),
(444, 112, 2, 'FOTO_DEPOIS', 'uploads/os_112_1781623577_43e0fe66.jpeg', '', '2026-06-16 12:26:17'),
(445, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781690740_550a4466.jpeg', '', '2026-06-17 07:05:40'),
(446, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781690753_109a75af.jpeg', '', '2026-06-17 07:05:53'),
(447, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781690767_e5e0a013.jpeg', '', '2026-06-17 07:06:07'),
(448, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781690781_a2dd73bd.jpeg', '', '2026-06-17 07:06:21'),
(449, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781690822_6998fbeb.jpeg', '', '2026-06-17 07:07:02'),
(450, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781690931_cc9058e1.jpeg', '', '2026-06-17 07:08:51'),
(451, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1781690949_fa9821eb.jpeg', '', '2026-06-17 07:09:09'),
(452, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1781690961_dafd6aec.jpeg', '', '2026-06-17 07:09:21'),
(453, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1781690982_f7049205.jpeg', '', '2026-06-17 07:09:42'),
(454, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1781690997_f76edee8.jpeg', '', '2026-06-17 07:09:57'),
(455, 116, 2, 'FOTO_ANTES', 'uploads/os_116_1781725590_46008518.jpeg', '', '2026-06-17 16:46:30'),
(456, 116, 2, 'FOTO_ANTES', 'uploads/os_116_1781725600_31dd5bb9.jpeg', '', '2026-06-17 16:46:40'),
(457, 116, 2, 'FOTO_ANTES', 'uploads/os_116_1781725608_6d917caa.jpeg', '', '2026-06-17 16:46:48'),
(458, 116, 2, 'FOTO_ANTES', 'uploads/os_116_1781725618_6b014e17.jpeg', '', '2026-06-17 16:46:58'),
(459, 116, 2, 'FOTO_ANTES', 'uploads/os_116_1781725628_994045eb.jpeg', '', '2026-06-17 16:47:08'),
(460, 116, 2, 'FOTO_ANTES', 'uploads/os_116_1781725636_c6fe50f4.jpeg', '', '2026-06-17 16:47:16'),
(461, 117, 2, 'FOTO_ANTES', 'uploads/os_117_1781777537_09f23532.jpeg', '', '2026-06-18 07:12:17'),
(462, 117, 2, 'FOTO_ANTES', 'uploads/os_117_1781777549_76df8ffa.jpeg', '', '2026-06-18 07:12:29'),
(463, 117, 2, 'FOTO_ANTES', 'uploads/os_117_1781777563_36385e5f.jpeg', '', '2026-06-18 07:12:43'),
(464, 117, 2, 'FOTO_ANTES', 'uploads/os_117_1781777588_45a1dc92.jpeg', '', '2026-06-18 07:13:08'),
(465, 117, 2, 'FOTO_DEPOIS', 'uploads/os_117_1781779284_6bcd2a6c.jpeg', '', '2026-06-18 07:41:24'),
(466, 117, 2, 'FOTO_DEPOIS', 'uploads/os_117_1781779326_a65de985.jpeg', '', '2026-06-18 07:42:06'),
(467, 117, 2, 'FOTO_DEPOIS', 'uploads/os_117_1781779375_9a07bc96.jpeg', '', '2026-06-18 07:42:55'),
(468, 117, 2, 'FOTO_DEPOIS', 'uploads/os_117_1781779397_cded9db4.jpeg', '', '2026-06-18 07:43:17'),
(469, 117, 2, 'FOTO_DEPOIS', 'uploads/os_117_1781779421_53b35b6e.jpeg', '', '2026-06-18 07:43:41'),
(470, 117, 2, 'FOTO_DEPOIS', 'uploads/os_117_1781779450_dc659369.jpeg', '', '2026-06-18 07:44:10'),
(471, 117, 2, 'FOTO_DEPOIS', 'uploads/os_117_1781779465_68b2a014.jpeg', '', '2026-06-18 07:44:25'),
(472, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781780997_07dcd530.jpeg', '', '2026-06-18 08:09:57'),
(473, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781781018_59323a84.jpeg', '', '2026-06-18 08:10:18'),
(474, 115, 2, 'FOTO_ANTES', 'uploads/os_115_1781781038_9bb6c4e4.jpeg', '', '2026-06-18 08:10:38'),
(475, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1781781054_78081285.jpeg', '', '2026-06-18 08:10:54'),
(476, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1781781068_5cd7ed7b.jpeg', '', '2026-06-18 08:11:08'),
(477, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1781781079_253c45a3.jpeg', '', '2026-06-18 08:11:19'),
(478, 118, 2, 'FOTO_DEPOIS', 'uploads/os_118_1781795193_fd258a7a.jpeg', '', '2026-06-18 12:06:33'),
(479, 118, 2, 'FOTO_DEPOIS', 'uploads/os_118_1781795228_4112d179.jpeg', '', '2026-06-18 12:07:08'),
(480, 118, 2, 'FOTO_DEPOIS', 'uploads/os_118_1781795240_264008a7.jpeg', '', '2026-06-18 12:07:20'),
(481, 118, 2, 'FOTO_DEPOIS', 'uploads/os_118_1781795272_2c28b91d.jpeg', '', '2026-06-18 12:07:52'),
(482, 118, 2, 'FOTO_DEPOIS', 'uploads/os_118_1781795291_b2fd88fd.jpeg', '', '2026-06-18 12:08:11'),
(483, 105, 2, 'FOTO_DEPOIS', 'uploads/os_105_1781805272_bf9aea36.jpeg', '', '2026-06-18 14:54:32'),
(484, 105, 2, 'FOTO_DEPOIS', 'uploads/os_105_1781805283_2725078d.jpeg', '', '2026-06-18 14:54:43'),
(485, 105, 2, 'FOTO_DEPOIS', 'uploads/os_105_1781805293_d30e52f5.jpeg', '', '2026-06-18 14:54:53'),
(486, 105, 2, 'FOTO_DEPOIS', 'uploads/os_105_1781805304_a229a3d4.jpeg', '', '2026-06-18 14:55:04'),
(487, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808065_81c00e0f.jpeg', '', '2026-06-18 15:41:05'),
(488, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808079_ed315772.jpeg', '', '2026-06-18 15:41:19'),
(489, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808091_31e752f2.jpeg', '', '2026-06-18 15:41:31'),
(490, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808109_0ba4c77a.jpeg', '', '2026-06-18 15:41:49'),
(491, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808128_9b1e0d13.jpeg', '', '2026-06-18 15:42:08'),
(492, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808144_1e36defe.jpeg', '', '2026-06-18 15:42:24'),
(493, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808164_5a69eb07.jpeg', '', '2026-06-18 15:42:44'),
(494, 27, 2, 'FOTO_DEPOIS', 'uploads/os_27_1781808178_2c203641.jpeg', '', '2026-06-18 15:42:58'),
(495, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863685_c24eaafd.jpeg', '', '2026-06-19 07:08:05'),
(496, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863740_8c3abf8f.jpeg', '', '2026-06-19 07:09:00'),
(497, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863764_e9c78c85.jpeg', '', '2026-06-19 07:09:24'),
(498, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863815_3ddf40ac.jpeg', '', '2026-06-19 07:10:15'),
(499, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863832_46258644.jpeg', '', '2026-06-19 07:10:32'),
(500, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863869_eafd4740.jpeg', '', '2026-06-19 07:11:09'),
(501, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863894_a3accf71.jpeg', '', '2026-06-19 07:11:34'),
(502, 90, 2, 'FOTO_DEPOIS', 'uploads/os_90_1781863923_5ca1534e.jpeg', '', '2026-06-19 07:12:03'),
(503, 120, 2, 'FOTO_DEPOIS', 'uploads/os_120_1781864652_414f0b74.jpeg', '', '2026-06-19 07:24:12'),
(504, 120, 2, 'FOTO_DEPOIS', 'uploads/os_120_1781864671_7d8cf04c.jpeg', '', '2026-06-19 07:24:31'),
(505, 120, 2, 'FOTO_DEPOIS', 'uploads/os_120_1781864691_15d608da.jpeg', '', '2026-06-19 07:24:51'),
(506, 120, 2, 'FOTO_DEPOIS', 'uploads/os_120_1781864711_704c6712.jpeg', '', '2026-06-19 07:25:11'),
(507, 120, 2, 'FOTO_DEPOIS', 'uploads/os_120_1781864728_4137793c.jpeg', '', '2026-06-19 07:25:28'),
(508, 120, 2, 'FOTO_DEPOIS', 'uploads/os_120_1781864740_8904bdc6.jpeg', '', '2026-06-19 07:25:40'),
(509, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781867890_7f97ea65.jpeg', '', '2026-06-19 08:18:10'),
(510, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781867902_526a3121.jpeg', '', '2026-06-19 08:18:22'),
(511, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781867915_e82e9687.jpeg', '', '2026-06-19 08:18:35'),
(512, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1781867948_eb61eb58.jpeg', '', '2026-06-19 08:19:08'),
(513, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1781867964_ecf67205.jpeg', '', '2026-06-19 08:19:24'),
(514, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1781867977_aa8c13d4.jpeg', '', '2026-06-19 08:19:37'),
(515, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1781867992_80930fa7.jpeg', '', '2026-06-19 08:19:52'),
(516, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781871484_c3172337.jpeg', '', '2026-06-19 09:18:04'),
(517, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781871503_66a51843.jpeg', '', '2026-06-19 09:18:23'),
(518, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781871515_c5deac12.jpeg', '', '2026-06-19 09:18:35'),
(519, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781871541_a10b600f.jpeg', '', '2026-06-19 09:19:01'),
(520, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781871554_85e2faa5.jpeg', '', '2026-06-19 09:19:14');
INSERT INTO `anexos_os` (`id`, `ordem_servico_id`, `usuario_id`, `tipo`, `arquivo_url`, `descricao`, `criado_em`) VALUES
(521, 70, 2, 'FOTO_DEPOIS', 'uploads/os_70_1781878513_eef9a820.jpeg', '', '2026-06-19 11:15:13'),
(522, 70, 2, 'FOTO_DEPOIS', 'uploads/os_70_1781878544_5512a759.jpeg', '', '2026-06-19 11:15:44'),
(523, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781894632_bb0881b1.jpeg', '', '2026-06-19 15:43:52'),
(524, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781894646_b680e81a.jpeg', '', '2026-06-19 15:44:06'),
(525, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781894659_6f965194.jpeg', '', '2026-06-19 15:44:19'),
(526, 116, 2, 'FOTO_DEPOIS', 'uploads/os_116_1781894678_f13b0549.jpeg', '', '2026-06-19 15:44:38'),
(527, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1782124661_8431606a.jpeg', '', '2026-06-22 07:37:41'),
(528, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1782124696_7bfd9891.jpeg', '', '2026-06-22 07:38:16'),
(529, 114, 2, 'FOTO_ANTES', 'uploads/os_114_1782124720_931e15cb.jpeg', '', '2026-06-22 07:38:40'),
(530, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1782124807_72766a9f.jpeg', '', '2026-06-22 07:40:07'),
(531, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1782124829_183f07c8.jpeg', '', '2026-06-22 07:40:29'),
(532, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1782124844_6b350bcd.jpeg', '', '2026-06-22 07:40:44'),
(533, 114, 2, 'FOTO_DEPOIS', 'uploads/os_114_1782124856_dab10e80.jpeg', '', '2026-06-22 07:40:56'),
(534, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1782129695_0b6a3bda.jpeg', '', '2026-06-22 09:01:35'),
(535, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1782129710_341d8452.jpeg', '', '2026-06-22 09:01:50'),
(536, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1782129731_a17641ae.jpeg', '', '2026-06-22 09:02:11'),
(537, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1782129931_3bf27fd9.jpeg', '', '2026-06-22 09:05:31'),
(538, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1782129949_8624200e.jpeg', '', '2026-06-22 09:05:49'),
(539, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1782129966_10466618.jpeg', '', '2026-06-22 09:06:06'),
(540, 115, 2, 'FOTO_DEPOIS', 'uploads/os_115_1782129988_3453db7f.jpeg', '', '2026-06-22 09:06:28'),
(541, 121, 2, 'FOTO_ANTES', 'uploads/os_121_1782130500_c6138153.jpeg', '', '2026-06-22 09:15:00'),
(542, 121, 2, 'FOTO_ANTES', 'uploads/os_121_1782130523_cae04d73.jpeg', '', '2026-06-22 09:15:23'),
(543, 121, 2, 'FOTO_ANTES', 'uploads/os_121_1782130532_f38f1e81.jpeg', '', '2026-06-22 09:15:32'),
(544, 121, 2, 'FOTO_ANTES', 'uploads/os_121_1782130554_18391fa3.jpeg', '', '2026-06-22 09:15:54'),
(545, 122, 2, 'FOTO_ANTES', 'uploads/os_122_1782131010_6daf7d1d.jpeg', '', '2026-06-22 09:23:30'),
(546, 122, 2, 'FOTO_ANTES', 'uploads/os_122_1782131026_1219bb2e.jpeg', '', '2026-06-22 09:23:46'),
(547, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1782131051_0dec1462.jpeg', '', '2026-06-22 09:24:11'),
(548, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1782131069_343193ae.jpeg', '', '2026-06-22 09:24:29'),
(549, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1782131133_6587e251.jpeg', '', '2026-06-22 09:25:33'),
(550, 70, 2, 'FOTO_DEPOIS', 'uploads/os_70_1782382555_ebe22563.jpeg', '', '2026-06-25 07:15:55'),
(551, 123, 2, 'FOTO_ANTES', 'uploads/os_123_1782385563_256b6cb9.jpeg', '', '2026-06-25 08:06:03'),
(552, 123, 2, 'FOTO_DEPOIS', 'uploads/os_123_1782385580_3e465d2b.jpeg', '', '2026-06-25 08:06:20'),
(553, 123, 2, 'FOTO_DEPOIS', 'uploads/os_123_1782385590_e10d680a.jpeg', '', '2026-06-25 08:06:30'),
(554, 123, 2, 'FOTO_DEPOIS', 'uploads/os_123_1782385607_5290758f.jpeg', '', '2026-06-25 08:06:47'),
(555, 124, 2, 'FOTO_ANTES', 'uploads/os_124_1782412688_f99f933a.jpeg', '', '2026-06-25 15:38:08'),
(556, 125, 2, 'FOTO_ANTES', 'uploads/os_125_1782415282_42b6c9c9.jpeg', '', '2026-06-25 16:21:22'),
(557, 92, 2, 'FOTO_DEPOIS', 'uploads/os_92_1782729753_d83a4019.jpeg', '', '2026-06-29 07:42:33'),
(558, 92, 2, 'FOTO_DEPOIS', 'uploads/os_92_1782729768_8d6dc36a.jpeg', '', '2026-06-29 07:42:48'),
(559, 92, 2, 'FOTO_DEPOIS', 'uploads/os_92_1782729788_a3c53012.jpeg', '', '2026-06-29 07:43:08'),
(560, 92, 2, 'FOTO_DEPOIS', 'uploads/os_92_1782729806_184c7b2b.jpeg', '', '2026-06-29 07:43:26'),
(561, 92, 2, 'FOTO_DEPOIS', 'uploads/os_92_1782729835_f74fe00e.jpeg', '', '2026-06-29 07:43:55'),
(562, 92, 2, 'FOTO_DEPOIS', 'uploads/os_92_1782729860_16975cf5.jpeg', '', '2026-06-29 07:44:20'),
(563, 92, 2, 'FOTO_DEPOIS', 'uploads/os_92_1782729876_e7ca2322.jpeg', '', '2026-06-29 07:44:36'),
(564, 41, 2, 'FOTO_DEPOIS', 'uploads/os_41_1782836745_778d4472.jpeg', '', '2026-06-30 13:25:45'),
(565, 128, 2, 'FOTO_DEPOIS', 'uploads/os_128_1784628521_5fc7f9d3.jpeg', '', '2026-07-21 07:08:41'),
(566, 128, 2, 'FOTO_ANTES', 'uploads/os_128_1784628536_624e837d.jpeg', '', '2026-07-21 07:08:56'),
(567, 128, 2, 'FOTO_ANTES', 'uploads/os_128_1784628557_5681b7a9.jpeg', '', '2026-07-21 07:09:17'),
(568, 128, 2, 'FOTO_DEPOIS', 'uploads/os_128_1784628576_0dc56a65.jpeg', '', '2026-07-21 07:09:36'),
(569, 127, 2, 'FOTO_ANTES', 'uploads/os_127_1784629694_73759284.jpeg', '', '2026-07-21 07:28:14'),
(570, 127, 2, 'FOTO_ANTES', 'uploads/os_127_1784629710_53385532.jpeg', '', '2026-07-21 07:28:30'),
(571, 126, 2, 'FOTO_ANTES', 'uploads/os_126_1784631529_d65bc9da.jpeg', '', '2026-07-21 07:58:49'),
(572, 126, 2, 'FOTO_ANTES', 'uploads/os_126_1784631543_1026255f.jpeg', '', '2026-07-21 07:59:03'),
(573, 126, 2, 'FOTO_ANTES', 'uploads/os_126_1784631557_990c9918.jpeg', '', '2026-07-21 07:59:17'),
(574, 126, 2, 'FOTO_DEPOIS', 'uploads/os_126_1784631569_3076e936.jpeg', '', '2026-07-21 07:59:29'),
(575, 126, 2, 'FOTO_DEPOIS', 'uploads/os_126_1784631580_8ebec6a5.jpeg', '', '2026-07-21 07:59:40'),
(576, 126, 2, 'FOTO_DEPOIS', 'uploads/os_126_1784631594_b32b0022.jpeg', '', '2026-07-21 07:59:54'),
(577, 126, 2, 'FOTO_ANTES', 'uploads/os_126_1784631613_4e970d07.jpeg', '', '2026-07-21 08:00:13'),
(578, 126, 2, 'DOCUMENTO', 'uploads/os_126_1784631788_e56cdc5a.pdf', '', '2026-07-21 08:03:08'),
(579, 125, 2, 'FOTO_DEPOIS', 'uploads/os_125_1784632234_72ce42cc.jpeg', '', '2026-07-21 08:10:34'),
(580, 125, 2, 'FOTO_DEPOIS', 'uploads/os_125_1784632247_815ca6f1.jpeg', '', '2026-07-21 08:10:47'),
(581, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1784632696_55e41e1c.jpeg', '', '2026-07-21 08:18:16'),
(582, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1784634152_0718d613.jpeg', '', '2026-07-21 08:42:32'),
(583, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1784634164_440d8968.jpeg', '', '2026-07-21 08:42:44'),
(584, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1784634547_f61f3567.jpeg', '', '2026-07-21 08:49:07'),
(585, 122, 2, 'FOTO_DEPOIS', 'uploads/os_122_1784634561_e1d22f43.jpeg', '', '2026-07-21 08:49:21'),
(586, 129, 2, 'FOTO_ANTES', 'uploads/os_129_1784637329_0f5e1fc3.jpeg', '', '2026-07-21 09:35:29'),
(587, 129, 2, 'FOTO_ANTES', 'uploads/os_129_1784637351_bcf9c831.jpeg', '', '2026-07-21 09:35:51'),
(588, 129, 2, 'FOTO_DEPOIS', 'uploads/os_129_1784637365_3fac588c.jpeg', '', '2026-07-21 09:36:05'),
(589, 129, 2, 'FOTO_DEPOIS', 'uploads/os_129_1784637380_ee9558ff.jpeg', '', '2026-07-21 09:36:20'),
(590, 130, 2, 'FOTO_ANTES', 'uploads/os_130_1784653754_ef6b373a.jpeg', '', '2026-07-21 14:09:14'),
(591, 130, 2, 'FOTO_ANTES', 'uploads/os_130_1784653766_f7d28ca0.jpeg', '', '2026-07-21 14:09:26'),
(592, 130, 2, 'FOTO_ANTES', 'uploads/os_130_1784653779_df4ae5aa.jpeg', '', '2026-07-21 14:09:39'),
(593, 130, 2, 'FOTO_ANTES', 'uploads/os_130_1784653793_860ebcaf.jpeg', '', '2026-07-21 14:09:53'),
(594, 130, 2, 'FOTO_ANTES', 'uploads/os_130_1784653845_ff4f79e6.jpeg', '', '2026-07-21 14:10:45'),
(595, 130, 2, 'FOTO_ANTES', 'uploads/os_130_1784653859_f5bf84b1.jpeg', '', '2026-07-21 14:10:59'),
(596, 130, 2, 'FOTO_ANTES', 'uploads/os_130_1784653873_d973241b.jpeg', '', '2026-07-21 14:11:13'),
(597, 129, 2, 'FOTO_ANTES', 'uploads/os_129_1784737633_14205639.jpeg', '', '2026-07-22 13:27:13'),
(598, 129, 2, 'FOTO_DEPOIS', 'uploads/os_129_1784737649_eac461d7.jpeg', '', '2026-07-22 13:27:29'),
(599, 130, 2, 'FOTO_DEPOIS', 'uploads/os_130_1784738809_0586f657.jpeg', '', '2026-07-22 13:46:49'),
(600, 130, 2, 'FOTO_DEPOIS', 'uploads/os_130_1784738829_bd60b0e3.jpeg', '', '2026-07-22 13:47:09'),
(601, 130, 2, 'FOTO_DEPOIS', 'uploads/os_130_1784738855_799797aa.jpeg', '', '2026-07-22 13:47:35'),
(602, 131, 2, 'FOTO_ANTES', 'uploads/os_131_1784802306_9fe3c7cc.png', '', '2026-07-23 07:25:06'),
(603, 131, 2, 'FOTO_DEPOIS', 'uploads/os_131_1784802323_e8c3aeba.jpg', '', '2026-07-23 07:25:23'),
(604, 131, 2, 'FOTO_DEPOIS', 'uploads/os_131_1784802346_1f32f240.jpg', '', '2026-07-23 07:25:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_eventos`
--

CREATE TABLE `auditoria_eventos` (
  `id` int(11) NOT NULL,
  `entidade` varchar(80) NOT NULL,
  `entidade_id` int(11) DEFAULT NULL,
  `acao` varchar(80) NOT NULL,
  `campo` varchar(120) DEFAULT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_novo` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `auditoria_eventos`
--

INSERT INTO `auditoria_eventos` (`id`, `entidade`, `entidade_id`, `acao`, `campo`, `valor_anterior`, `valor_novo`, `usuario_id`, `ip`, `user_agent`, `criado_em`) VALUES
(1, 'ordem_servico', 24, 'EDITAR', 'prazo', '2026-05-19 16:00:00', '2026-06-19 16:00', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 15:31:58'),
(2, 'ordem_servico', 22, 'EDITAR', 'titulo', 'Impermeabilização clara-boia ', 'Impermeabilização clara-boia', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 15:32:23'),
(3, 'ordem_servico', 22, 'EDITAR', 'descricao', 'Substituir manta asfáltica da lateral da clara- boia.\r\n', 'Substituir manta asfáltica da lateral da clara- boia.', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 15:32:23'),
(4, 'ordem_servico', 22, 'EDITAR', 'prazo', '2026-05-19 18:00:00', '2026-05-21 18:00', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 15:32:23'),
(5, 'ordem_servico', 26, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 15:49:42'),
(6, 'manutencoes_preventivas', 1, 'CRIAR_PREVENTIVA', NULL, NULL, 'Limpeza Subestação', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 15:57:14'),
(7, 'ordem_servico', 23, 'ANEXO', 'arquivo', NULL, 'uploads/os_23_1779218726_68bde062.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:25:26'),
(8, 'ordem_servico', 23, 'ANEXO', 'arquivo', NULL, 'uploads/os_23_1779218786_50f77fdf.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:26:26'),
(9, 'ordem_servico', 23, 'ANEXO', 'arquivo', NULL, 'uploads/os_23_1779218860_bd907e50.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:27:40'),
(10, 'ordem_servico', 23, 'ANEXO', 'arquivo', NULL, 'uploads/os_23_1779218895_5b12bd3a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:28:15'),
(11, 'ordem_servico', 23, 'ANEXO', 'arquivo', NULL, 'uploads/os_23_1779218925_6155bed3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:28:45'),
(12, 'ordem_servico', 23, 'ANEXO', 'arquivo', NULL, 'uploads/os_23_1779219028_157898c5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:30:28'),
(13, 'ordem_servico', 23, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:30:44'),
(14, 'manutencoes_preventivas', 1, 'EDITAR_PREVENTIVA', NULL, NULL, 'Limpeza Subestação', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:36:25'),
(15, 'ordem_servico', 27, 'GERAR_PREVENTIVA', 'preventiva_id', NULL, '1', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:36:25'),
(16, 'ordem_servico', 27, 'ATRIBUIR_AGENTE_PREVENTIVA', 'agente_id', NULL, '9', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:36:25'),
(17, 'manutencoes_preventivas', 1, 'GERAR_OS_AO_ATRIBUIR_AGENTE', 'ordem_servico_id', NULL, '27', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:36:25'),
(18, 'ordem_servico', 27, 'EDITAR', 'descricao', 'Manutencao preventiva gerada automaticamente.\n\nLimpeza geral da subestação', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLimpeza geral da subestação', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:38:31'),
(19, 'ordem_servico', 27, 'EDITAR', 'prazo', '2026-05-21 19:36:25', '2026-06-16 12:00', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-19 16:38:31'),
(20, 'ordem_servico', 11, 'EDITAR', 'prazo', '2026-05-20 09:00:00', '2026-05-28 09:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:20:17'),
(21, 'ordem_servico', 25, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:36:16'),
(22, 'ordem_servico', 28, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:44:58'),
(23, 'ordem_servico', 28, 'ANEXO', 'arquivo', NULL, 'uploads/os_28_1779274516_09a1f3e5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:55:16'),
(24, 'ordem_servico', 28, 'ANEXO', 'arquivo', NULL, 'uploads/os_28_1779274561_632ae356.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:56:01'),
(25, 'ordem_servico', 28, 'ANEXO', 'arquivo', NULL, 'uploads/os_28_1779274599_11ffe913.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:56:39'),
(26, 'ordem_servico', 28, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:56:54'),
(27, 'ordem_servico', 28, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:57:09'),
(28, 'ordem_servico', 26, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:59:01'),
(29, 'ordem_servico', 26, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 07:59:01'),
(30, 'ordem_servico', 29, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 09:07:41'),
(31, 'ordem_servico', 30, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 09:09:47'),
(32, 'ordem_servico', 30, 'ANEXO', 'arquivo', NULL, 'uploads/os_30_1779279162_b5c611c7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 09:12:42'),
(33, 'ordem_servico', 30, 'ANEXO', 'arquivo', NULL, 'uploads/os_30_1779279210_278bf655.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 09:13:30'),
(34, 'ordem_servico', 30, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 09:13:44'),
(35, 'ordem_servico', 30, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 09:13:53'),
(36, 'ordem_servico', 31, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 09:17:05'),
(37, 'ordem_servico', 32, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 13:15:32'),
(38, 'ordem_servico', 33, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:12:42'),
(39, 'ordem_servico', 33, 'ANEXO', 'arquivo', NULL, 'uploads/os_33_1779300898_e107009f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:14:58'),
(40, 'ordem_servico', 33, 'ANEXO', 'arquivo', NULL, 'uploads/os_33_1779300926_617a91b0.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:15:26'),
(41, 'ordem_servico', 33, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:20:27'),
(42, 'ordem_servico', 33, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:20:36'),
(43, 'ordem_servico', 34, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:29:21'),
(44, 'ordem_servico', 34, 'ANEXO', 'arquivo', NULL, 'uploads/os_34_1779301809_2d05febd.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:30:09'),
(45, 'ordem_servico', 34, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:30:18'),
(46, 'ordem_servico', 34, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-20 15:30:24'),
(47, 'ordem_servico', 23, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 08:15:20'),
(48, 'ordem_servico', 29, 'ANEXO', 'arquivo', NULL, 'uploads/os_29_1779362194_31b8066a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 08:16:34'),
(49, 'ordem_servico', 29, 'ANEXO', 'arquivo', NULL, 'uploads/os_29_1779362558_8a199876.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 08:22:38'),
(50, 'ordem_servico', 29, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 08:23:12'),
(51, 'ordem_servico', 29, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 08:24:13'),
(52, 'ordem_servico', 35, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:32:40'),
(53, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374089_c9dbe331.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:34:49'),
(54, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374119_28953e36.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:35:19'),
(55, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374177_5be8183b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:36:17'),
(56, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374535_12117ea6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:42:15'),
(57, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374631_1a036a6a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:43:51'),
(58, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374670_8d4a367b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:44:30'),
(59, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374696_0102ebc1.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:44:56'),
(60, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374868_73fde5fa.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:47:48'),
(61, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779374984_18c51f73.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:49:44'),
(62, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779375014_50159463.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:50:14'),
(63, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779375053_85f2f1db.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:50:53'),
(64, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779375088_d3873ad4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:51:28'),
(65, 'ordem_servico', 35, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:52:22'),
(66, 'ordem_servico', 26, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:54:43'),
(67, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375415_aa8bf5b8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:56:55'),
(68, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375438_bdfe617a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:57:18'),
(69, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375466_cc97cc08.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:57:46'),
(70, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375487_b9a0a62d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:58:07'),
(71, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375512_bb193d71.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:58:32'),
(72, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375537_ab9791ae.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 11:58:57'),
(73, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375817_0fbfb02c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:03:37'),
(74, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375859_f38b9ccc.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:04:19'),
(75, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779375890_897b3509.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:04:50'),
(76, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779376351_06a1da73.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:12:31'),
(77, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779376416_c24def8b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:13:36'),
(78, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779376448_9dc4e30a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:14:08'),
(79, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779376478_bf5774a5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:14:38'),
(80, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779376511_20bfaf96.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:15:11'),
(81, 'ordem_servico', 26, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:15:23'),
(82, 'ordem_servico', 11, 'ANEXO', 'arquivo', NULL, 'uploads/os_11_1779376647_b70fc0bd.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:17:27'),
(83, 'ordem_servico', 11, 'ANEXO', 'arquivo', NULL, 'uploads/os_11_1779376751_661b7c21.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:19:11'),
(84, 'ordem_servico', 11, 'ANEXO', 'arquivo', NULL, 'uploads/os_11_1779376794_d701d57d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:19:54'),
(85, 'ordem_servico', 11, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:20:04'),
(86, 'ordem_servico', 32, 'ANEXO', 'arquivo', NULL, 'uploads/os_32_1779376870_cf701c92.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:21:10'),
(87, 'ordem_servico', 32, 'ANEXO', 'arquivo', NULL, 'uploads/os_32_1779377086_6a5e6bd8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:24:46'),
(88, 'ordem_servico', 32, 'ANEXO', 'arquivo', NULL, 'uploads/os_32_1779377113_03d78318.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:25:13'),
(89, 'ordem_servico', 32, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:25:30'),
(90, 'ordem_servico', 36, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:30:36'),
(91, 'ordem_servico', 36, 'ANEXO', 'arquivo', NULL, 'uploads/os_36_1779377714_795a965d.png', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:35:14'),
(92, 'ordem_servico', 36, 'ANEXO', 'arquivo', NULL, 'uploads/os_36_1779377743_f1327dca.png', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:35:43'),
(93, 'ordem_servico', 36, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:35:50'),
(94, 'ordem_servico', 36, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:36:12'),
(95, 'ordem_servico', 37, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:38:29'),
(96, 'ordem_servico', 38, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:42:35'),
(97, 'ordem_servico', 38, 'ANEXO', 'arquivo', NULL, 'uploads/os_38_1779378203_6d0c973a.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:43:23'),
(98, 'ordem_servico', 38, 'MOVER_FOTO', 'anexos_os.tipo', 'FOTO_DEPOIS', 'FOTO_ANTES', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:43:28'),
(99, 'ordem_servico', 38, 'ANEXO', 'arquivo', NULL, 'uploads/os_38_1779378237_4bc8cd83.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:43:57'),
(100, 'ordem_servico', 38, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:44:05'),
(101, 'ordem_servico', 39, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:44:47'),
(102, 'ordem_servico', 40, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:46:52'),
(103, 'ordem_servico', 41, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:47:33'),
(104, 'ordem_servico', 41, 'ANEXO', 'arquivo', NULL, 'uploads/os_41_1779378490_84d1e6cc.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:48:10'),
(105, 'ordem_servico', 39, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 12:49:03'),
(106, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779385736_2d8f9672.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:48:56'),
(107, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779385767_b213b776.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:49:27'),
(108, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779385870_396a4921.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:51:10'),
(109, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779385945_1e7dc96e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:52:25'),
(110, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779385971_7311ea34.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:52:51'),
(111, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779386004_3fedf6b3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:53:24'),
(112, 'ordem_servico', 26, 'ANEXO', 'arquivo', NULL, 'uploads/os_26_1779386048_60bb482e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:54:08'),
(113, 'ordem_servico', 26, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-21 14:56:11'),
(114, 'ordem_servico', 42, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:17:28'),
(115, 'ordem_servico', 42, 'ANEXO', 'arquivo', NULL, 'uploads/os_42_1779445304_4eaa909e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:21:44'),
(116, 'ordem_servico', 42, 'ANEXO', 'arquivo', NULL, 'uploads/os_42_1779445339_1facd9c5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:22:19'),
(117, 'ordem_servico', 42, 'ANEXO', 'arquivo', NULL, 'uploads/os_42_1779445365_8837c4cc.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:22:45'),
(118, 'ordem_servico', 42, 'ANEXO', 'arquivo', NULL, 'uploads/os_42_1779445442_cb8e1f98.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:24:02'),
(119, 'ordem_servico', 42, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:27:35'),
(120, 'ordem_servico', 42, 'ANEXO', 'arquivo', NULL, 'uploads/os_42_1779445676_63b2e4ed.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:27:56'),
(121, 'ordem_servico', 42, 'ANEXO', 'arquivo', NULL, 'uploads/os_42_1779445709_474b155b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:28:29'),
(122, 'ordem_servico', 42, 'ANEXO', 'arquivo', NULL, 'uploads/os_42_1779445754_0ff26f3e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:29:14'),
(123, 'ordem_servico', 42, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:31:52'),
(124, 'ordem_servico', 41, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:32:21'),
(125, 'ordem_servico', 41, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:32:34'),
(126, 'ordem_servico', 41, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:10'),
(127, 'ordem_servico', 41, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:10'),
(128, 'ordem_servico', 40, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:20'),
(129, 'ordem_servico', 40, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:20'),
(130, 'ordem_servico', 39, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:41'),
(131, 'ordem_servico', 39, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:41'),
(132, 'ordem_servico', 38, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:57'),
(133, 'ordem_servico', 38, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:33:57'),
(134, 'ordem_servico', 37, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:34:06'),
(135, 'ordem_servico', 37, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:34:06'),
(136, 'ordem_servico', 36, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:34:16'),
(137, 'ordem_servico', 36, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:34:16'),
(138, 'ordem_servico', 32, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:34:30'),
(139, 'ordem_servico', 32, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:34:30'),
(140, 'ordem_servico', 43, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:36:44'),
(141, 'ordem_servico', 43, 'MATERIAL', 'materiais', NULL, 'Fechadura La Fonte', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:40:40'),
(142, 'ordem_servico', 43, 'ALTERAR_STATUS', 'status', 'ABERTA', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:40:51'),
(143, 'ordem_servico', 44, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:44:12'),
(144, 'ordem_servico', 44, 'ANEXO', 'arquivo', NULL, 'uploads/os_44_1779446685_9c5ed388.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:44:45'),
(145, 'ordem_servico', 44, 'ANEXO', 'arquivo', NULL, 'uploads/os_44_1779446716_c16a23e8.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:45:16'),
(146, 'ordem_servico', 44, 'EDITAR', 'descricao', 'Limpeza da Fachada externa - Parede dos letreiros externo - C&A, Uninassau e Americanas.', 'Limpeza da Fachada externa - Parede dos letreiros externo - C&A, Uninassau, Americanas e a parede da Faculdade na Retangular.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:46:04'),
(147, 'ordem_servico', 44, 'EDITAR', 'prazo', '2026-06-15 16:00:00', '2026-06-15 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 07:46:04'),
(148, 'ordem_servico', 41, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 09:02:32'),
(149, 'ordem_servico', 40, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 09:02:49'),
(150, 'ordem_servico', 39, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 09:03:02'),
(151, 'ordem_servico', 38, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 09:03:15'),
(152, 'ordem_servico', 37, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 09:03:34'),
(153, 'ordem_servico', 36, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 09:03:47'),
(154, 'ordem_servico', 24, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 09:45:39'),
(155, 'ordem_servico', 45, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 10:04:45'),
(156, 'ordem_servico', 45, 'ANEXO', 'arquivo', NULL, 'uploads/os_45_1779455263_11d196b7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 10:07:43'),
(157, 'ordem_servico', 45, 'ANEXO', 'arquivo', NULL, 'uploads/os_45_1779455278_8e234b5c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 10:07:58'),
(158, 'ordem_servico', 45, 'ANEXO', 'arquivo', NULL, 'uploads/os_45_1779455300_567cc5e3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 10:08:20'),
(159, 'ordem_servico', 45, 'ANEXO', 'arquivo', NULL, 'uploads/os_45_1779455311_b1e527cf.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 10:08:31'),
(160, 'ordem_servico', 45, 'EDITAR', 'prazo', '2026-05-21 18:00:00', '2026-05-23 10:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 10:26:25'),
(161, 'ordem_servico', 45, 'ANEXO', 'arquivo', NULL, 'uploads/os_45_1779456463_dd0dade2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 10:27:43'),
(162, 'ordem_servico', 46, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 15:33:05'),
(163, 'ordem_servico', 46, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 15:33:38'),
(164, 'ordem_servico', 47, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 15:39:14'),
(165, 'ordem_servico', 47, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 15:39:29'),
(166, 'ordem_servico', 46, 'EDITAR', 'categoria_id', '14', '10', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 15:39:50'),
(167, 'ordem_servico', 46, 'EDITAR', 'prazo', '2026-05-22 08:00:00', '2026-05-22 08:00', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 15:39:50'),
(168, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779476540_088eda7c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 16:02:20'),
(169, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779476576_5888393f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 16:02:56'),
(170, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779476592_68e36943.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 16:03:12'),
(171, 'ordem_servico', 35, 'ANEXO', 'arquivo', NULL, 'uploads/os_35_1779476610_d286bb0a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 16:03:30'),
(172, 'ordem_servico', 35, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-22 16:03:50'),
(173, 'cftv_relatorio', 1, 'CRIAR_RELATORIO', NULL, NULL, '2026-05-25 DIURNO', 13, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 08:56:49'),
(174, 'ordem_servico', 45, 'ANEXO', 'arquivo', NULL, 'uploads/os_45_1779715598_7a78ddf2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:26:38'),
(175, 'ordem_servico', 45, 'ANEXO', 'arquivo', NULL, 'uploads/os_45_1779715619_e13ea22c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:26:59'),
(176, 'ordem_servico', 45, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:27:48'),
(177, 'ordem_servico', 45, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:27:49'),
(178, 'ordem_servico', 45, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:27:58'),
(179, 'ordem_servico', 48, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:32:54'),
(180, 'ordem_servico', 48, 'ANEXO', 'arquivo', NULL, 'uploads/os_48_1779716006_47b44496.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:33:26'),
(181, 'ordem_servico', 48, 'ANEXO', 'arquivo', NULL, 'uploads/os_48_1779716031_3bfc67db.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:33:51'),
(182, 'ordem_servico', 48, 'ANEXO', 'arquivo', NULL, 'uploads/os_48_1779716048_d1b2c5c0.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:34:08'),
(183, 'ordem_servico', 48, 'ANEXO', 'arquivo', NULL, 'uploads/os_48_1779716061_b32ad9bc.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:34:21'),
(184, 'ordem_servico', 48, 'ANEXO', 'arquivo', NULL, 'uploads/os_48_1779716073_1c0ae6ad.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:34:33'),
(185, 'ordem_servico', 48, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:34:47'),
(186, 'ordem_servico', 49, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:35:46'),
(187, 'ordem_servico', 49, 'ANEXO', 'arquivo', NULL, 'uploads/os_49_1779716159_e66319e5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:35:59'),
(188, 'ordem_servico', 49, 'ANEXO', 'arquivo', NULL, 'uploads/os_49_1779716172_cc31cc76.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:36:12'),
(189, 'ordem_servico', 49, 'ANEXO', 'arquivo', NULL, 'uploads/os_49_1779716184_8317615b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:36:24'),
(190, 'ordem_servico', 49, 'ANEXO', 'arquivo', NULL, 'uploads/os_49_1779716200_9b3e29f4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:36:40'),
(191, 'ordem_servico', 49, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:36:53'),
(192, 'cftv_liberacao', 1, 'CRIAR_LIBERACAO', NULL, NULL, 'Teste de teste', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:44:47'),
(193, 'cftv_liberacao_confirmacao', 1, 'CONFIRMAR_LIBERACAO', 'status_confirmacao', NULL, 'CONFIRMADA', 13, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:45:17'),
(194, 'cftv_relatorio', 1, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:46:38'),
(195, 'cftv_relatorio', 1, 'GERAR_TOKEN_EDICAO', NULL, NULL, 'Token valido por 24 horas', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:47:21'),
(196, 'cftv_relatorio', 1, 'USAR_TOKEN_EDICAO', NULL, NULL, 'Edicao liberada por token', 13, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 10:47:30'),
(197, 'ordem_servico', 50, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 14:13:11'),
(198, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779729211_e15aa165.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 14:13:31'),
(199, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779729233_a440b73a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 14:13:53'),
(200, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779729246_05276750.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 14:14:06'),
(201, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779729267_29701956.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 14:14:27');
INSERT INTO `auditoria_eventos` (`id`, `entidade`, `entidade_id`, `acao`, `campo`, `valor_anterior`, `valor_novo`, `usuario_id`, `ip`, `user_agent`, `criado_em`) VALUES
(202, 'ordem_servico', 50, 'ATRIBUIR', 'agente_id', '9', '9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 14:14:36'),
(203, 'ordem_servico', 50, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 14:14:36'),
(204, 'ordem_servico', 51, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 15:11:16'),
(205, 'ordem_servico', 51, 'EDITAR', 'descricao', 'Apoio do agente de manutenção no desligamento da energia e da internet.\r\nLevar o mesmo para DOCA. \r\nApós a pintura, colocar no lugar e ligar o mesmo.', 'Apoio do agente de manutenção no desligamento da energia e da internet, pois o ATM será pintado por um terceiro.\r\nLevar o mesmo para DOCA. \r\nApós a pintura, colocar no lugar e ligar o mesmo.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 15:13:47'),
(206, 'ordem_servico', 51, 'EDITAR', 'prazo', '2026-05-27 14:00:00', '2026-05-27 14:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 15:13:47'),
(207, 'ordem_servico', 51, 'ANEXO', 'arquivo', NULL, 'uploads/os_51_1779735448_d0178080.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 15:57:28'),
(208, 'ordem_servico', 51, 'ANEXO', 'arquivo', NULL, 'uploads/os_51_1779735458_4e926a77.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 15:57:38'),
(209, 'ordem_servico', 51, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 15:57:48'),
(210, 'ordem_servico', 52, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 16:15:34'),
(211, 'ordem_servico', 52, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ENCERRADA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 16:15:53'),
(212, 'ordem_servico', 52, 'ATRIBUIR', 'agente_id', NULL, '9', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 16:16:00'),
(213, 'ordem_servico', 52, 'ALTERAR_STATUS', 'status', 'ENCERRADA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 16:16:00'),
(214, 'ordem_servico', 52, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 16:16:15'),
(215, 'cftv_relatorio', 1, 'CRIAR_RELATORIO', NULL, NULL, '2026-05-25 DIURNO', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 16:46:41'),
(216, 'ordem_servico', 31, 'ANEXO', 'arquivo', NULL, 'uploads/os_31_1779798677_ca04cd86.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 09:31:17'),
(217, 'ordem_servico', 31, 'ANEXO', 'arquivo', NULL, 'uploads/os_31_1779798720_76ac3dc8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 09:32:00'),
(218, 'ordem_servico', 31, 'ANEXO', 'arquivo', NULL, 'uploads/os_31_1779798758_5b21f0e4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 09:32:38'),
(219, 'ordem_servico', 31, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 09:32:55'),
(220, 'ordem_servico', 31, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 09:33:06'),
(221, 'ordem_servico', 53, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 10:15:23'),
(222, 'ordem_servico', 54, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 12:33:17'),
(223, 'ordem_servico', 54, 'ANEXO', 'arquivo', NULL, 'uploads/os_54_1779810786_bb03ae57.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 12:53:06'),
(224, 'ordem_servico', 54, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 12:53:52'),
(225, 'ordem_servico', 55, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 12:58:46'),
(226, 'ordem_servico', 55, 'ANEXO', 'arquivo', NULL, 'uploads/os_55_1779811220_b5d6f603.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 13:00:20'),
(227, 'cftv_relatorio', 1, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 14:02:14'),
(228, 'ordem_servico', 22, 'EDITAR', 'prazo', '2026-05-21 18:00:00', '2026-05-29 18:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 14:33:36'),
(229, 'ordem_servico', 12, 'EDITAR', 'prazo', '2026-05-26 07:00:00', '2026-05-29 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 14:35:04'),
(230, 'cftv_relatorio', 2, 'CRIAR_RELATORIO', NULL, NULL, '2026-05-26 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-26 15:18:05'),
(231, 'ordem_servico', 56, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:44:08'),
(232, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821265_e74d276d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:47:45'),
(233, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821362_4d384f9b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:49:22'),
(234, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821382_8338d2ae.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:49:42'),
(235, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821406_b44aba6e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:50:06'),
(236, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821422_031d6f67.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:50:22'),
(237, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821440_547e929c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:50:40'),
(238, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821466_e59c5b00.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:51:06'),
(239, 'ordem_servico', 56, 'MOVER_FOTO', 'anexos_os.tipo', 'FOTO_DEPOIS', 'FOTO_ANTES', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:51:25'),
(240, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821548_30b4e332.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:52:28'),
(241, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821582_51d1d3ef.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:53:02'),
(242, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821638_73b966a5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:53:58'),
(243, 'ordem_servico', 56, 'ANEXO', 'arquivo', NULL, 'uploads/os_56_1779821666_1cb4c733.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:54:26'),
(244, 'ordem_servico', 56, 'EDITAR', 'descricao', 'Capinação acesso de pedestre até cancela de saída.', 'Capinação acesso de pedestre até cancela de saída.\r\nO serviço foi realizado nos dias 25 e 26.05.2026.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:55:05'),
(245, 'ordem_servico', 56, 'EDITAR', 'prazo', '2026-05-26 17:00:00', '2026-05-26 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:55:05'),
(246, 'ordem_servico', 56, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-26 15:55:14'),
(247, 'ordem_servico', 51, 'ANEXO', 'arquivo', NULL, 'uploads/os_51_1779877549_bd94ebf7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:25:49'),
(248, 'ordem_servico', 51, 'ANEXO', 'arquivo', NULL, 'uploads/os_51_1779877568_249aec42.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:26:08'),
(249, 'ordem_servico', 51, 'ANEXO', 'arquivo', NULL, 'uploads/os_51_1779877584_1c76fd43.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:26:24'),
(250, 'ordem_servico', 51, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:26:37'),
(251, 'ordem_servico', 51, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:26:54'),
(252, 'ordem_servico', 53, 'ANEXO', 'arquivo', NULL, 'uploads/os_53_1779878435_27227907.png', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:40:35'),
(253, 'ordem_servico', 53, 'ANEXO', 'arquivo', NULL, 'uploads/os_53_1779878450_16818124.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:40:50'),
(254, 'ordem_servico', 53, 'ANEXO', 'arquivo', NULL, 'uploads/os_53_1779878479_b0c70fff.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:41:19'),
(255, 'ordem_servico', 53, 'ANEXO', 'arquivo', NULL, 'uploads/os_53_1779878502_59bdcad2.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:41:42'),
(256, 'ordem_servico', 53, 'ANEXO', 'arquivo', NULL, 'uploads/os_53_1779878530_f03e9548.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:42:10'),
(257, 'ordem_servico', 53, 'ANEXO', 'arquivo', NULL, 'uploads/os_53_1779878559_0668b46b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:42:39'),
(258, 'ordem_servico', 53, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:42:49'),
(259, 'ordem_servico', 53, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:42:56'),
(260, 'ordem_servico', 53, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:43:03'),
(261, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779878967_2123b0f9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:49:27'),
(262, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779878988_319bb67d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:49:48'),
(263, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779879015_02f8316b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:50:15'),
(264, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779879029_b0bac31c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:50:29'),
(265, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779879254_85dfd1c7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:54:14'),
(266, 'ordem_servico', 50, 'ANEXO', 'arquivo', NULL, 'uploads/os_50_1779879336_15c74b4b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:55:36'),
(267, 'ordem_servico', 50, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:56:42'),
(268, 'ordem_servico', 50, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 07:56:54'),
(269, 'ordem_servico', 57, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:01:43'),
(270, 'ordem_servico', 58, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:04:35'),
(271, 'ordem_servico', 57, 'ANEXO', 'arquivo', NULL, 'uploads/os_57_1779879980_7e85b833.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:06:20'),
(272, 'ordem_servico', 57, 'ANEXO', 'arquivo', NULL, 'uploads/os_57_1779879992_892e3903.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:06:32'),
(273, 'ordem_servico', 57, 'ANEXO', 'arquivo', NULL, 'uploads/os_57_1779880011_6a09ca50.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:06:51'),
(274, 'ordem_servico', 57, 'ANEXO', 'arquivo', NULL, 'uploads/os_57_1779880027_28c95bf5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:07:07'),
(275, 'ordem_servico', 57, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:07:46'),
(276, 'ordem_servico', 57, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:07:51'),
(277, 'ordem_servico', 59, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:54:45'),
(278, 'ordem_servico', 59, 'ANEXO', 'arquivo', NULL, 'uploads/os_59_1779883000_6602c63c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:56:40'),
(279, 'ordem_servico', 59, 'ANEXO', 'arquivo', NULL, 'uploads/os_59_1779883013_1b325ca7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:56:53'),
(280, 'ordem_servico', 59, 'ANEXO', 'arquivo', NULL, 'uploads/os_59_1779883033_bbddd195.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:57:13'),
(281, 'ordem_servico', 59, 'ANEXO', 'arquivo', NULL, 'uploads/os_59_1779883051_c5cfd452.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:57:31'),
(282, 'ordem_servico', 59, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 08:57:45'),
(283, 'ordem_servico', 60, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 15:45:48'),
(284, 'ordem_servico', 54, 'ANEXO', 'arquivo', NULL, 'uploads/os_54_1779970906_cf60ecc6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:21:47'),
(285, 'ordem_servico', 54, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:21:54'),
(286, 'ordem_servico', 12, 'ANEXO', 'arquivo', NULL, 'uploads/os_12_1779971081_5d5dac54.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:24:41'),
(287, 'ordem_servico', 12, 'ANEXO', 'arquivo', NULL, 'uploads/os_12_1779971235_0a0eaf5d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:27:15'),
(288, 'ordem_servico', 12, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:28:47'),
(289, 'ordem_servico', 12, 'ATRIBUIR', 'agente_id', '4', '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:34:21'),
(290, 'ordem_servico', 12, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:34:21'),
(291, 'ordem_servico', 12, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 09:34:30'),
(292, 'ordem_servico', 11, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:00:52'),
(293, 'estoque_materiais', 1, 'CRIAR_ESTOQUE', NULL, NULL, 'ESSÊNCIA 100ML – FLORAROME – COCO BRASIL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:13:37'),
(294, 'estoque_materiais', 1, 'EDITAR_ESTOQUE', 'unidade', '', 'UND', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:14:27'),
(295, 'estoque_materiais', 1, 'EDITAR_ESTOQUE', 'quantidade_atual', '0.00', '5', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:14:27'),
(296, 'estoque_materiais', 1, 'EDITAR_ESTOQUE', 'estoque_minimo', '0.00', '1', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:14:27'),
(297, 'estoque_materiais', 1, 'EDITAR_ESTOQUE', 'custo_unitario', '0.00', '9.9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:14:27'),
(298, 'estoque_materiais', 2, 'CRIAR_ESTOQUE', NULL, NULL, 'ESSÊNCIA 100ML – FLORAROME – GABATINE', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:15:11'),
(299, 'estoque_materiais', 3, 'CRIAR_ESTOQUE', NULL, NULL, 'INSETICIDA AEROSSOL 300ML – KELLDRIN', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:15:51'),
(300, 'estoque_materiais', 4, 'CRIAR_ESTOQUE', NULL, NULL, 'PANO ALVEJADO- FIATEC – 60G', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:19:57'),
(301, 'estoque_materiais', 5, 'CRIAR_ESTOQUE', NULL, NULL, 'ESPONJA DUPLA FACE – NOBRE VERDE AMARELA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:20:29'),
(302, 'estoque_materiais', 6, 'CRIAR_ESTOQUE', NULL, NULL, 'LUVA NEOPREX – G – KALIPSO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:21:49'),
(303, 'estoque_materiais', 7, 'CRIAR_ESTOQUE', NULL, NULL, 'DESENGRAXANTE 5L – VALENÇA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:22:17'),
(304, 'estoque_materiais', 8, 'CRIAR_ESTOQUE', NULL, NULL, 'DETERGENTE LÍQUIDO 5L – VALENÇA LIMPA PISO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:22:45'),
(305, 'estoque_materiais', 9, 'CRIAR_ESTOQUE', NULL, NULL, 'CLORO 5L – VALENÇA – 5%', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:23:12'),
(306, 'estoque_materiais', 10, 'CRIAR_ESTOQUE', NULL, NULL, 'PANO MULTIUSO ROLO – MEDIX – 28 CM X 240M – 600 PANOS – AZUL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:24:05'),
(307, 'estoque_materiais', 11, 'CRIAR_ESTOQUE', NULL, NULL, 'REFIL MOP UMIDO 190G – ALGODÃO – C/ ROSCA STAR CLEAN', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:24:41'),
(308, 'estoque_materiais', 12, 'CRIAR_ESTOQUE', NULL, NULL, 'SACO DE LIXO 60L – PRETO – LEVE COM 100UND', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:25:01'),
(309, 'estoque_materiais', 13, 'CRIAR_ESTOQUE', NULL, NULL, 'SACO DE LIXO 100L – PRETO – LEVE COM 100UND', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:25:25'),
(310, 'estoque_materiais', 14, 'CRIAR_ESTOQUE', NULL, NULL, 'SACO DE LIXO 200L – PRETO – LEVE COM 100UND', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:25:56'),
(311, 'estoque_materiais', 15, 'CRIAR_ESTOQUE', NULL, NULL, 'SACO DE LIXO 450L – PRETO – LEVE COM 50UND', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:26:20'),
(312, 'estoque_materiais', 16, 'CRIAR_ESTOQUE', NULL, NULL, 'LUSTRA MÓVEIS 200ML – LAVANDA – FACILITA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:28:25'),
(313, 'estoque_materiais', 17, 'CRIAR_ESTOQUE', NULL, NULL, 'VASSOURA PLACAVA N03 SEM CABO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:28:49'),
(314, 'estoque_materiais', 18, 'CRIAR_ESTOQUE', NULL, NULL, 'CABO DE MADEIRA 1,2M – REVESTIDO COM ROSCA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 10:30:38'),
(315, 'cftv_relatorio', 2, 'AUTO_FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 14:23:26'),
(316, 'ordem_servico', 62, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 15:34:57'),
(317, 'ordem_servico', 62, 'ANEXO', 'arquivo', NULL, 'uploads/os_62_1779993448_2bba4421.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 15:37:28'),
(318, 'cftv_relatorio', 3, 'CRIAR_RELATORIO', NULL, NULL, '2026-05-28 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-05-28 16:27:07'),
(319, 'cftv_observacao', 1, 'ADICIONAR_OBSERVACAO', 'equipe', NULL, 'Fiscal de Mall', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-05-28 16:37:36'),
(320, 'ordem_servico', 63, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:52:13'),
(321, 'ordem_servico', 63, 'ANEXO', 'arquivo', NULL, 'uploads/os_63_1779998000_a0a4a9c2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:53:20'),
(322, 'ordem_servico', 63, 'ANEXO', 'arquivo', NULL, 'uploads/os_63_1779998016_b8d3d12d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:53:36'),
(323, 'ordem_servico', 63, 'ANEXO', 'arquivo', NULL, 'uploads/os_63_1779998030_3d6e4393.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:53:50'),
(324, 'ordem_servico', 63, 'ANEXO', 'arquivo', NULL, 'uploads/os_63_1779998044_bd853f28.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:54:04'),
(325, 'ordem_servico', 63, 'ANEXO', 'arquivo', NULL, 'uploads/os_63_1779998059_dad8e8ff.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:54:19'),
(326, 'ordem_servico', 63, 'ANEXO', 'arquivo', NULL, 'uploads/os_63_1779998071_04e35fb4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:54:31'),
(327, 'ordem_servico', 63, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:54:43'),
(328, 'ordem_servico', 63, 'ATRIBUIR', 'agente_id', '6', '6', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:54:45'),
(329, 'ordem_servico', 63, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:54:45'),
(330, 'ordem_servico', 63, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:55:37'),
(331, 'ordem_servico', 63, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 16:55:50'),
(332, 'cftv_relatorio', 3, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-05-28 17:09:24'),
(333, 'ordem_servico', 11, 'EDITAR', 'prazo', '2026-05-28 09:00:00', '2026-06-05 10:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:15:30'),
(334, 'ordem_servico', 64, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:21:06'),
(335, 'ordem_servico', 55, 'ANEXO', 'arquivo', NULL, 'uploads/os_55_1780050390_267170ca.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:26:30'),
(336, 'ordem_servico', 55, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:26:50'),
(337, 'ordem_servico', 62, 'ANEXO', 'arquivo', NULL, 'uploads/os_62_1780050453_203ef70c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:27:33'),
(338, 'ordem_servico', 62, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:28:07'),
(339, 'ordem_servico', 62, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:28:13'),
(340, 'ordem_servico', 57, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:28:56'),
(341, 'ordem_servico', 64, 'ANEXO', 'arquivo', NULL, 'uploads/os_64_1780050957_32a6ac2f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:35:57'),
(342, 'ordem_servico', 64, 'ANEXO', 'arquivo', NULL, 'uploads/os_64_1780050968_a5e1ad00.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:36:08'),
(343, 'ordem_servico', 64, 'ANEXO', 'arquivo', NULL, 'uploads/os_64_1780050981_48594072.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:36:21'),
(344, 'ordem_servico', 64, 'ANEXO', 'arquivo', NULL, 'uploads/os_64_1780050994_be43b558.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:36:34'),
(345, 'ordem_servico', 64, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-29 07:36:44'),
(346, 'cftv_relatorio', 4, 'CRIAR_RELATORIO', NULL, NULL, '2026-05-29 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-29 09:38:33'),
(347, 'cftv_relatorio', 4, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-29 16:29:57'),
(348, 'cftv_relatorio', 5, 'CRIAR_RELATORIO', NULL, NULL, '2026-05-30 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-05-30 16:20:08'),
(349, 'cftv_relatorio', 5, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-05-30 16:23:00'),
(350, 'cftv_relatorio', 5, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-05-30 17:02:44'),
(351, 'ordem_servico', 64, 'ANEXO', 'arquivo', NULL, 'uploads/os_64_1780309091_5fc08cf9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:18:11'),
(352, 'ordem_servico', 64, 'ANEXO', 'arquivo', NULL, 'uploads/os_64_1780309108_409d1348.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:18:28'),
(353, 'ordem_servico', 64, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:18:35'),
(354, 'ordem_servico', 64, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:18:44'),
(355, 'ordem_servico', 65, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:24:03'),
(356, 'ordem_servico', 65, 'ANEXO', 'arquivo', NULL, 'uploads/os_65_1780309460_1f23aed3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:24:20'),
(357, 'ordem_servico', 65, 'ANEXO', 'arquivo', NULL, 'uploads/os_65_1780309473_f2833b29.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:24:33'),
(358, 'ordem_servico', 65, 'ANEXO', 'arquivo', NULL, 'uploads/os_65_1780309529_0651ab93.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:25:29'),
(359, 'ordem_servico', 65, 'ANEXO', 'arquivo', NULL, 'uploads/os_65_1780309547_ecf2ed1a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:25:47'),
(360, 'ordem_servico', 65, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:25:52'),
(361, 'ordem_servico', 65, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:26:05'),
(362, 'ordem_servico', 65, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:26:15'),
(363, 'ordem_servico', 66, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:28:29'),
(364, 'ordem_servico', 66, 'ANEXO', 'arquivo', NULL, 'uploads/os_66_1780309765_d3e1f57d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:29:25'),
(365, 'ordem_servico', 66, 'ANEXO', 'arquivo', NULL, 'uploads/os_66_1780309781_14bec474.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:29:41'),
(366, 'ordem_servico', 66, 'ANEXO', 'arquivo', NULL, 'uploads/os_66_1780309800_0be30150.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:30:00'),
(367, 'ordem_servico', 66, 'ANEXO', 'arquivo', NULL, 'uploads/os_66_1780309815_be9267c8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:30:15'),
(368, 'ordem_servico', 66, 'ANEXO', 'arquivo', NULL, 'uploads/os_66_1780309832_8170c360.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:30:32'),
(369, 'ordem_servico', 66, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:30:47'),
(370, 'ordem_servico', 66, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:30:56'),
(371, 'ordem_servico', 67, 'CRIAR', NULL, NULL, 'OS criada com status ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:33:39'),
(372, 'cftv_relatorio', 6, 'CRIAR_RELATORIO', NULL, NULL, '2026-05-31 DIURNO', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:53:57'),
(373, 'cftv_relatorio', 6, 'AUTO_FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:53:57'),
(374, 'ordem_servico', 67, 'ATRIBUIR', 'agente_id', NULL, '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:55:02'),
(375, 'ordem_servico', 67, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 07:55:02'),
(376, 'ordem_servico', 68, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:13:56'),
(377, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780316092_d0105ecf.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:14:52'),
(378, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780316105_b23d15bb.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:15:05'),
(379, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780316119_f3db1e4b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:15:19'),
(380, 'ordem_servico', 68, 'EDITAR', 'descricao', 'Varrição do fundo do Lago\r\nSegunda- Feira: Samuel\r\nTerça- Feira:\r\nQuarta- Feira:\r\nQuinta- Feira:\r\nSexta- Feira:', 'Varrição do fundo do Lago + Limpeza da Lâmpadas aquáticas\r\nSegunda- Feira: Samuel\r\nTerça- Feira:\r\nQuarta- Feira:\r\nQuinta- Feira:\r\nSexta- Feira:', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:16:16'),
(381, 'ordem_servico', 68, 'EDITAR', 'prazo', '2026-06-05 10:00:00', '2026-06-05 10:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:16:16'),
(382, 'ordem_servico', 68, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:16:30'),
(383, 'ordem_servico', 21, 'EDITAR', 'prazo', '2026-05-31 00:00:00', '2026-06-05 15:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:17:24'),
(384, 'ordem_servico', 69, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:21:00'),
(385, 'ordem_servico', 69, 'MATERIAL', 'materiais', NULL, 'Lâmpada Slim 120cm 36w 6500k', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:23:29'),
(386, 'ordem_servico', 69, 'ANEXO', 'arquivo', NULL, 'uploads/os_69_1780318086_269bef69.pdf', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:48:06'),
(387, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:48:14'),
(388, 'ordem_servico', 69, 'ATRIBUIR', 'agente_id', '5', '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:48:21'),
(389, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 09:48:21'),
(390, 'cftv_relatorio', 7, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-01 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-01 11:43:33'),
(391, 'cftv_ocorrencia', 1, 'ADICIONAR_OCORRENCIA', 'nivel', NULL, 'NIVEL_1', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-01 12:00:58'),
(392, 'ordem_servico', 43, 'EDITAR', 'prazo', '2026-05-29 16:00:00', '2026-06-12 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 12:16:07'),
(393, 'ordem_servico', 22, 'EDITAR', 'prazo', '2026-05-29 18:00:00', '2026-06-12 18:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 12:16:39'),
(394, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 12:19:31'),
(395, 'ordem_servico', 70, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 13:01:34'),
(396, 'ordem_servico', 70, 'ANEXO', 'arquivo', NULL, 'uploads/os_70_1780329725_ba4c74a8.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 13:02:05'),
(397, 'ordem_servico', 70, 'ANEXO', 'arquivo', NULL, 'uploads/os_70_1780329748_bdcb399d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 13:02:28');
INSERT INTO `auditoria_eventos` (`id`, `entidade`, `entidade_id`, `acao`, `campo`, `valor_anterior`, `valor_novo`, `usuario_id`, `ip`, `user_agent`, `criado_em`) VALUES
(398, 'ordem_servico', 70, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 13:07:48'),
(399, 'ordem_servico', 70, 'ANEXO', 'arquivo', NULL, 'uploads/os_70_1780330143_93692517.pdf', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 13:09:03'),
(400, 'cftv_relatorio', 7, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-01 16:58:53'),
(401, 'ordem_servico', 71, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 09:47:42'),
(402, 'ordem_servico', 71, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ABERTA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:01:48'),
(403, 'ordem_servico', 71, 'REJEITAR_ENCERRAMENTO', 'status', 'ABERTA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:06:17'),
(404, 'ordem_servico', 71, 'REJEITAR_ENCERRAMENTO', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:06:37'),
(405, 'ordem_servico', 72, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:08:19'),
(406, 'ordem_servico', 72, 'MATERIAL', 'materiais', NULL, 'Cabo de Rede Cat-6', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:10:49'),
(407, 'ordem_servico', 72, 'EDITAR', 'descricao', 'Passar cabo de rede para o telão dos jogos da copa, puxando o cabo do local onde se encontra o projetor para a tomada próximo à o ACM do cinema.', 'Passar cabo de rede para o telão dos jogos da copa, puxando o cabo do local onde se encontra o projetor para a tomada próximo à o ACM do cinema.\r\n\r\nSolicitar ajuda de Samuel, para mobilização da plataforma e execução da passagem do cabo!', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:11:49'),
(408, 'ordem_servico', 72, 'EDITAR', 'prazo', '2026-06-03 19:00:00', '2026-06-03 19:00', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:11:49'),
(409, 'ordem_servico', 72, 'MATERIAL', 'materiais', NULL, 'RJ-45', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:12:22'),
(410, 'ordem_servico', 72, 'ATRIBUIR', 'agente_id', '3', '3', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:26:21'),
(411, 'ordem_servico', 72, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:26:21'),
(412, 'ordem_servico', 72, 'ATRIBUIR', 'agente_id', '3', '3', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:34:35'),
(413, 'ordem_servico', 72, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:34:35'),
(414, 'ordem_servico', 72, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:37:45'),
(415, 'ordem_servico', 72, 'ATRIBUIR', 'agente_id', '3', '3', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:38:22'),
(416, 'ordem_servico', 72, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:38:22'),
(417, 'ordem_servico', 72, 'ATRIBUIR', 'agente_id', '3', '3', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:38:55'),
(418, 'ordem_servico', 72, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:38:55'),
(419, 'ordem_servico', 73, 'CRIAR', NULL, NULL, 'OS criada com status ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 10:42:47'),
(420, 'ordem_servico', 44, 'ALTERAR_STATUS', 'status', 'ABERTA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:02:04'),
(421, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780409514_904c877b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:11:54'),
(422, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780409633_c7bcbab5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:13:53'),
(423, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780409654_668d1b95.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:14:14'),
(424, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780409743_ec5a795c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:15:43'),
(425, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780409872_b65acfaa.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:17:52'),
(426, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780409882_6add5101.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:18:02'),
(427, 'ordem_servico', 3, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:18:18'),
(428, 'ordem_servico', 61, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ENCERRADA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:21:48'),
(429, 'ordem_servico', 74, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas. Aberta pelo TI para o gestor definir o agente.', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:22:05'),
(430, 'ordem_servico', 75, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:35:20'),
(431, 'ordem_servico', 74, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:36:20'),
(432, 'ordem_servico', 44, 'ATRIBUIR', 'agente_id', NULL, '8', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:37:05'),
(433, 'ordem_servico', 44, 'ALTERAR_STATUS', 'status', 'PAUSADA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:37:05'),
(434, 'ordem_servico', 44, 'ATRIBUIR', 'agente_id', '8', '8', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:37:12'),
(435, 'ordem_servico', 44, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:37:12'),
(436, 'ordem_servico', 43, 'ATRIBUIR', 'agente_id', NULL, '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:37:29'),
(437, 'ordem_servico', 43, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:37:29'),
(438, 'ordem_servico', 43, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:37:32'),
(439, 'ordem_servico', 74, 'ATRIBUIR', 'agente_id', NULL, '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:38:07'),
(440, 'ordem_servico', 74, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:38:07'),
(441, 'ordem_servico', 75, 'EDITAR', 'prazo', '2025-06-02 07:00:00', '2026-06-02 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:38:39'),
(442, 'ordem_servico', 75, 'ANEXO', 'arquivo', NULL, 'uploads/os_75_1780411141_0382ae97.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:39:01'),
(443, 'ordem_servico', 75, 'ANEXO', 'arquivo', NULL, 'uploads/os_75_1780411149_086f26fd.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:39:09'),
(444, 'ordem_servico', 75, 'ANEXO', 'arquivo', NULL, 'uploads/os_75_1780411164_2a9d0f7e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:39:24'),
(445, 'ordem_servico', 75, 'ANEXO', 'arquivo', NULL, 'uploads/os_75_1780411178_d0d6744a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:39:38'),
(446, 'ordem_servico', 75, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:40:38'),
(447, 'ordem_servico', 75, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:40:46'),
(448, 'ordem_servico', 44, 'ANEXO', 'arquivo', NULL, 'uploads/os_44_1780411463_4e9e7821.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:44:23'),
(449, 'ordem_servico', 44, 'ANEXO', 'arquivo', NULL, 'uploads/os_44_1780411478_cf5b8310.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:44:38'),
(450, 'ordem_servico', 44, 'ANEXO', 'arquivo', NULL, 'uploads/os_44_1780411501_b38656d8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:45:01'),
(451, 'ordem_servico', 44, 'ANEXO', 'arquivo', NULL, 'uploads/os_44_1780411520_e81f6b7e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:45:20'),
(452, 'ordem_servico', 44, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:45:31'),
(453, 'ordem_servico', 44, 'ATRIBUIR', 'agente_id', '8', '8', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:45:39'),
(454, 'ordem_servico', 44, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 11:45:39'),
(455, 'ordem_servico', 76, 'CRIAR', NULL, NULL, 'OS criada. SLA: 24 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:19:22'),
(456, 'ordem_servico', 76, 'ATRIBUIR', 'agente_id', '9', '9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:19:33'),
(457, 'ordem_servico', 76, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:19:33'),
(458, 'ordem_servico', 76, 'EDITAR', 'prazo', '2026-06-02 12:30:00', '2026-06-02 12:30', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:19:52'),
(459, 'ordem_servico', 76, 'ANEXO', 'arquivo', NULL, 'uploads/os_76_1780413739_77a2907c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:22:19'),
(460, 'ordem_servico', 76, 'ANEXO', 'arquivo', NULL, 'uploads/os_76_1780413755_03d8e477.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:22:35'),
(461, 'ordem_servico', 76, 'ANEXO', 'arquivo', NULL, 'uploads/os_76_1780413769_a84abca3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:22:49'),
(462, 'ordem_servico', 76, 'ANEXO', 'arquivo', NULL, 'uploads/os_76_1780413795_7a7b3139.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:23:15'),
(463, 'ordem_servico', 76, 'ANEXO', 'arquivo', NULL, 'uploads/os_76_1780413810_d9dd5070.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:23:30'),
(464, 'ordem_servico', 76, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:25:21'),
(465, 'ordem_servico', 76, 'ATRIBUIR', 'agente_id', '9', '9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:25:31'),
(466, 'ordem_servico', 76, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:25:31'),
(467, 'ordem_servico', 76, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 12:25:42'),
(468, 'ordem_servico', 77, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 13:17:57'),
(469, 'ordem_servico', 78, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas. Aberta pelo TI para o gestor definir o agente.', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 13:20:25'),
(470, 'ordem_servico', 78, 'ATRIBUIR', 'agente_id', NULL, '9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 13:52:10'),
(471, 'ordem_servico', 78, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 13:52:10'),
(472, 'ordem_servico', 73, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:00:16'),
(473, 'ordem_servico', 69, 'ATRIBUIR', 'agente_id', '3', '5', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:10:10'),
(474, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:10:10'),
(475, 'ordem_servico', 69, 'ANEXO', 'arquivo', NULL, 'uploads/os_69_1780420232_adc48bf4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:10:32'),
(476, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:14:31'),
(477, 'ordem_servico', 69, 'EDITAR', 'descricao', 'Substituição das lâmpadas Slim 120 cm 36w 6500k nos corredores de serviço + DOCA.', 'Substituição das lâmpadas Slim 120 cm 36w 6500k nos corredores de serviço + DOCA.\r\nInformar a quantidade usada e registrar com fotos antes e depois e enviar para o Gestor de Operações.\r\nUsar o telefone da Central para registrar as fotos.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:16:21'),
(478, 'ordem_servico', 69, 'EDITAR', 'prazo', '2026-06-05 15:00:00', '2026-06-05 15:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 14:16:21'),
(479, 'ordem_servico', 85, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 15:01:48'),
(480, 'ordem_servico', 86, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 16:16:10'),
(481, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1780427815_3e4cb846.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 16:16:55'),
(482, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1780427848_0dd26773.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 16:17:28'),
(483, 'ordem_servico', 86, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-02 16:18:14'),
(484, 'ordem_servico', 68, 'ATRIBUIR', 'agente_id', '6', '6', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:12:49'),
(485, 'ordem_servico', 68, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:12:49'),
(486, 'ordem_servico', 68, 'EDITAR', 'descricao', 'Varrição do fundo do Lago + Limpeza da Lâmpadas aquáticas\r\nSegunda- Feira: Samuel\r\nTerça- Feira:\r\nQuarta- Feira:\r\nQuinta- Feira:\r\nSexta- Feira:', 'Varrição do fundo do Lago + Limpeza da Lâmpadas aquáticas\r\nSegunda- Feira: Samuel\r\nQuarta- Feira: Maob Vieira\r\nSexta- Feira:', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:13:28'),
(487, 'ordem_servico', 68, 'EDITAR', 'prazo', '2026-06-05 10:00:00', '2026-06-05 10:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:13:28'),
(488, 'ordem_servico', 69, 'ANEXO', 'arquivo', NULL, 'uploads/os_69_1780481795_e810aeab.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:16:35'),
(489, 'ordem_servico', 69, 'ANEXO', 'arquivo', NULL, 'uploads/os_69_1780481809_505aedc1.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:16:49'),
(490, 'ordem_servico', 69, 'ANEXO', 'arquivo', NULL, 'uploads/os_69_1780481820_be2a50ed.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:17:00'),
(491, 'ordem_servico', 69, 'ANEXO', 'arquivo', NULL, 'uploads/os_69_1780481843_eff1d847.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:17:23'),
(492, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:19:01'),
(493, 'ordem_servico', 69, 'ATRIBUIR', 'agente_id', '5', '5', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:19:19'),
(494, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:19:19'),
(495, 'ordem_servico', 69, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:19:53'),
(496, 'ordem_servico', 67, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 07:31:09'),
(497, 'ordem_servico', 72, 'ANEXO', 'arquivo', NULL, 'uploads/os_72_1780492630_e76c4c17.jpeg', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-03 10:17:10'),
(498, 'ordem_servico', 72, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-03 10:17:35'),
(499, 'ordem_servico', 72, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 1, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-03 10:17:51'),
(500, 'ordem_servico', 60, 'ANEXO', 'arquivo', NULL, 'uploads/os_60_1780492681_d1a5eea1.png', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:18:01'),
(501, 'ordem_servico', 60, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:18:29'),
(502, 'ordem_servico', 87, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:33:42'),
(503, 'ordem_servico', 87, 'ANEXO', 'arquivo', NULL, 'uploads/os_87_1780493689_23575ae6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:34:49'),
(504, 'ordem_servico', 87, 'ANEXO', 'arquivo', NULL, 'uploads/os_87_1780493705_08b301b7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:35:05'),
(505, 'ordem_servico', 87, 'MOVER_FOTO', 'anexos_os.tipo', 'FOTO_ANTES', 'FOTO_DEPOIS', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:35:28'),
(506, 'ordem_servico', 87, 'ANEXO', 'arquivo', NULL, 'uploads/os_87_1780493746_053449c6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:35:46'),
(507, 'ordem_servico', 87, 'ANEXO', 'arquivo', NULL, 'uploads/os_87_1780493764_2a69f121.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:36:04'),
(508, 'ordem_servico', 87, 'ANEXO', 'arquivo', NULL, 'uploads/os_87_1780494408_29f3fdff.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:46:48'),
(509, 'ordem_servico', 87, 'ANEXO', 'arquivo', NULL, 'uploads/os_87_1780494433_35ed1fe6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:47:13'),
(510, 'ordem_servico', 87, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ABERTA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 10:47:36'),
(511, 'ordem_servico', 88, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:33:25'),
(512, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780500864_a034c348.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:34:24'),
(513, 'ordem_servico', 88, 'EDITAR', 'descricao', 'Foi identificado no dia 01 (segunda- Feira, uma condensação na máquina TR 10 - Escravo.\r\nAbrimos um chamado com a empresa CG Climatização.', 'Foi identificado no dia 01 (segunda- Feira), uma condensação na máquina TR 10 - Escravo.\r\nAbrimos um chamado com a empresa CG Climatização.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:34:48'),
(514, 'ordem_servico', 88, 'EDITAR', 'prazo', '2026-06-03 16:00:00', '2026-06-03 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:34:48'),
(515, 'ordem_servico', 87, 'ATRIBUIR', 'agente_id', NULL, '16', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:35:56'),
(516, 'ordem_servico', 87, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:35:56'),
(517, 'ordem_servico', 87, 'ATRIBUIR', 'agente_id', '16', '16', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:36:20'),
(518, 'ordem_servico', 87, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:36:20'),
(519, 'ordem_servico', 87, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:36:26'),
(520, 'manutencoes_preventivas', 2, 'CRIAR_PREVENTIVA', NULL, NULL, 'Exaustores Banheiros', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:43:01'),
(521, 'ordem_servico', 89, 'GERAR_PREVENTIVA', 'preventiva_id', NULL, '2', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:43:01'),
(522, 'ordem_servico', 89, 'ATRIBUIR_AGENTE_PREVENTIVA', 'agente_id', NULL, '4', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:43:01'),
(523, 'manutencoes_preventivas', 2, 'GERAR_OS_AO_ATRIBUIR_AGENTE', 'ordem_servico_id', NULL, '89', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:43:01'),
(524, 'manutencoes_preventivas', 3, 'CRIAR_PREVENTIVA', NULL, NULL, 'Limpeza Locomotiva', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:44:21'),
(525, 'ordem_servico', 90, 'GERAR_PREVENTIVA', 'preventiva_id', NULL, '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:44:21'),
(526, 'ordem_servico', 90, 'ATRIBUIR_AGENTE_PREVENTIVA', 'agente_id', NULL, '16', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:44:21'),
(527, 'manutencoes_preventivas', 3, 'GERAR_OS_AO_ATRIBUIR_AGENTE', 'ordem_servico_id', NULL, '90', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:44:21'),
(528, 'manutencoes_preventivas', 2, 'EDITAR_PREVENTIVA', NULL, NULL, 'Exaustores Banheiros', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:44:35'),
(529, 'manutencoes_preventivas', 4, 'CRIAR_PREVENTIVA', NULL, NULL, 'Limpeza Retangular', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:50:24'),
(530, 'ordem_servico', 91, 'GERAR_PREVENTIVA', 'preventiva_id', NULL, '4', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:50:24'),
(531, 'ordem_servico', 91, 'ATRIBUIR_AGENTE_PREVENTIVA', 'agente_id', NULL, '22', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:50:24'),
(532, 'manutencoes_preventivas', 4, 'GERAR_OS_AO_ATRIBUIR_AGENTE', 'ordem_servico_id', NULL, '91', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:50:24'),
(533, 'manutencoes_preventivas', 5, 'CRIAR_PREVENTIVA', NULL, NULL, 'limpeza Praça de Alimentação', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:51:41'),
(534, 'ordem_servico', 92, 'GERAR_PREVENTIVA', 'preventiva_id', NULL, '5', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:51:41'),
(535, 'ordem_servico', 92, 'ATRIBUIR_AGENTE_PREVENTIVA', 'agente_id', NULL, '22', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:51:41'),
(536, 'manutencoes_preventivas', 5, 'GERAR_OS_AO_ATRIBUIR_AGENTE', 'ordem_servico_id', NULL, '92', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 12:51:41'),
(537, 'cftv_relatorio', 8, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-03 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-03 13:05:01'),
(538, 'cftv_relatorio', 8, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-03 13:05:59'),
(539, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511231_c67de862.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:27:11'),
(540, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511249_34f3d4a9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:27:29'),
(541, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511270_41e88d72.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:27:50'),
(542, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511281_3b5aa63a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:28:01'),
(543, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511312_88632a58.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:28:32'),
(544, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511405_994b6737.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:30:05'),
(545, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511422_fb814e6a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:30:22'),
(546, 'ordem_servico', 88, 'ANEXO', 'arquivo', NULL, 'uploads/os_88_1780511433_a6485ea5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:30:33'),
(547, 'ordem_servico', 88, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:32:47'),
(548, 'ordem_servico', 88, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:52:08'),
(549, 'ordem_servico', 93, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:53:49'),
(550, 'ordem_servico', 93, 'ANEXO', 'arquivo', NULL, 'uploads/os_93_1780512847_bc77c76e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:54:07'),
(551, 'ordem_servico', 93, 'EDITAR', 'descricao', 'Substituição de 01 (uma) lâmpada Slim 120cm 36w 6500k.', 'Substituição de 02 (duas) lâmpada Slim 120cm 36w 6500k.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:54:27'),
(552, 'ordem_servico', 93, 'EDITAR', 'prazo', '2026-06-03 17:00:00', '2026-06-03 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:54:27'),
(553, 'ordem_servico', 93, 'ANEXO', 'arquivo', NULL, 'uploads/os_93_1780512882_6c34ce6c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:54:42'),
(554, 'ordem_servico', 93, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:55:39'),
(555, 'ordem_servico', 93, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:55:45'),
(556, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780513020_f6229cd2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 15:57:00'),
(557, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780513534_b9ce5aae.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:05:34'),
(558, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780513547_63c7a00a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:05:47'),
(559, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780513560_8fa40c50.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:06:01'),
(560, 'ordem_servico', 68, 'ANEXO', 'arquivo', NULL, 'uploads/os_68_1780513573_885fdf37.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:06:13'),
(561, 'ordem_servico', 68, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:06:51'),
(562, 'ordem_servico', 94, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:11:45'),
(563, 'ordem_servico', 94, 'ANEXO', 'arquivo', NULL, 'uploads/os_94_1780513951_0dc45653.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:12:31'),
(564, 'ordem_servico', 94, 'ANEXO', 'arquivo', NULL, 'uploads/os_94_1780513964_d8bbc776.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:12:44'),
(565, 'ordem_servico', 94, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:12:54'),
(566, 'ordem_servico', 95, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:23:21'),
(567, 'ordem_servico', 95, 'ANEXO', 'arquivo', NULL, 'uploads/os_95_1780514737_46cbcb23.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:25:37'),
(568, 'ordem_servico', 95, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-03 16:25:46'),
(569, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780569878_c9d15dde.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 07:44:38'),
(570, 'ordem_servico', 3, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:19:38'),
(571, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780572077_9b4fbae8.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:21:17'),
(572, 'ordem_servico', 96, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:42:00'),
(573, 'ordem_servico', 96, 'ANEXO', 'arquivo', NULL, 'uploads/os_96_1780573353_21448ca2.png', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:42:33'),
(574, 'ordem_servico', 96, 'ANEXO', 'arquivo', NULL, 'uploads/os_96_1780573378_04a77fe8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:42:58'),
(575, 'ordem_servico', 96, 'ANEXO', 'arquivo', NULL, 'uploads/os_96_1780573401_f9ed56ea.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:43:21'),
(576, 'ordem_servico', 96, 'ANEXO', 'arquivo', NULL, 'uploads/os_96_1780573414_1d86be05.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:43:34'),
(577, 'ordem_servico', 96, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:44:30'),
(578, 'ordem_servico', 96, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 08:44:41'),
(579, 'ordem_servico', 94, 'ANEXO', 'arquivo', NULL, 'uploads/os_94_1780579853_eb720a94.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 10:30:53'),
(580, 'ordem_servico', 94, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 10:31:09'),
(581, 'ordem_servico', 97, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 15:59:29'),
(582, 'ordem_servico', 97, 'ANEXO', 'arquivo', NULL, 'uploads/os_97_1780599601_d320d771.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:00:01'),
(583, 'ordem_servico', 97, 'ANEXO', 'arquivo', NULL, 'uploads/os_97_1780599613_c71ba00b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:00:13'),
(584, 'ordem_servico', 97, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:00:55'),
(585, 'ordem_servico', 97, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:01:03'),
(586, 'ordem_servico', 98, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:05:03'),
(587, 'ordem_servico', 98, 'ANEXO', 'arquivo', NULL, 'uploads/os_98_1780600035_7a4994cf.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:07:15'),
(588, 'ordem_servico', 98, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:08:08'),
(589, 'ordem_servico', 98, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:18:24'),
(590, 'ordem_servico', 94, 'ANEXO', 'arquivo', NULL, 'uploads/os_94_1780600837_f7a42c04.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:20:37'),
(591, 'ordem_servico', 94, 'ANEXO', 'arquivo', NULL, 'uploads/os_94_1780600853_2d571e6f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:20:53'),
(592, 'ordem_servico', 94, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:44:18'),
(593, 'ordem_servico', 94, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-04 16:45:49'),
(594, 'ordem_servico', 99, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 07:17:43'),
(595, 'ordem_servico', 99, 'ATRIBUIR', 'agente_id', '3', '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 07:18:02');
INSERT INTO `auditoria_eventos` (`id`, `entidade`, `entidade_id`, `acao`, `campo`, `valor_anterior`, `valor_novo`, `usuario_id`, `ip`, `user_agent`, `criado_em`) VALUES
(596, 'ordem_servico', 99, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 07:18:02'),
(597, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1780654820_a338eb68.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 07:20:20'),
(598, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1780654830_8d6e43f5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 07:20:30'),
(599, 'cftv_relatorio', 8, 'AUTO_FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-05 07:25:13'),
(600, 'cftv_relatorio', 9, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-05 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-05 07:25:42'),
(601, 'cftv_relatorio', 9, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-05 07:26:50'),
(602, 'ordem_servico', 92, 'EDITAR', 'descricao', 'Manutencao preventiva gerada automaticamente.\n\nLavagem da praça de alimentação com a máquina.', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLavagem da praça de alimentação com a máquina.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:40:50'),
(603, 'ordem_servico', 92, 'EDITAR', 'prazo', '2026-06-05 15:51:41', '2026-06-25 15:51', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:40:50'),
(604, 'ordem_servico', 91, 'EDITAR', 'descricao', 'Manutencao preventiva gerada automaticamente.\n\nLavar com a maquina toda área da retangular.\r\nTer atenção próximo do quiosque Fondue di Chocolate.', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLavar com a maquina toda área da retangular.\r\nTer atenção próximo do quiosque Fondue di Chocolate.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:41:35'),
(605, 'ordem_servico', 91, 'EDITAR', 'prazo', '2026-06-05 15:50:24', '2026-06-21 15:50', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:41:35'),
(606, 'ordem_servico', 90, 'EDITAR', 'descricao', 'Manutencao preventiva gerada automaticamente.\n\nLimpeza da Locomotiva', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLimpeza da Locomotiva', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:41:52'),
(607, 'ordem_servico', 90, 'EDITAR', 'prazo', '2026-06-05 15:44:21', '2026-06-18 15:44', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:41:52'),
(608, 'ordem_servico', 89, 'EDITAR', 'descricao', 'Manutencao preventiva gerada automaticamente.\n\nLimpeza dos Exaustores: WC Fem. Mall, WC Masc. Mall, WC Família, Fraldário, WC Fem. Praça, WC Mas. Praça, Vest. Fem e Vest. Masc.', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLimpeza dos Exaustores: WC Fem. Mall, WC Masc. Mall, WC Família, Fraldário, WC Fem. Praça, WC Mas. Praça, Vest. Fem e Vest. Masc.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:42:20'),
(609, 'ordem_servico', 89, 'EDITAR', 'prazo', '2026-06-05 15:43:01', '2026-06-29 15:43', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 14:42:20'),
(610, 'ordem_servico', 44, 'ANEXO', 'arquivo', NULL, 'uploads/os_44_1780684208_53fabe6b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 15:30:08'),
(611, 'ordem_servico', 100, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 15:32:03'),
(612, 'ordem_servico', 100, 'ANEXO', 'arquivo', NULL, 'uploads/os_100_1780684335_4beb550b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 15:32:15'),
(613, 'ordem_servico', 100, 'ANEXO', 'arquivo', NULL, 'uploads/os_100_1780684347_21e6b6c7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 15:32:27'),
(614, 'ordem_servico', 100, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-05 15:34:29'),
(615, 'cftv_relatorio', 9, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-05 16:55:51'),
(616, 'cftv_relatorio', 9, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-05 16:55:55'),
(617, 'cftv_relatorio', 10, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-07 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-07 13:27:44'),
(618, 'cftv_relatorio', 10, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-07 13:38:05'),
(619, 'cftv_ocorrencia', 2, 'ADICIONAR_OCORRENCIA', 'nivel', NULL, 'NIVEL_1', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-07 16:59:13'),
(620, 'cftv_relatorio', 10, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.178 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-07 16:59:38'),
(621, 'ordem_servico', 78, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 1, '170.246.164.105', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1', '2026-06-07 17:33:57'),
(622, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780914252_b68f7e25.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:24:12'),
(623, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780914264_5f228e32.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:24:24'),
(624, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780914274_9b0fa34b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:24:34'),
(625, 'ordem_servico', 3, 'ANEXO', 'arquivo', NULL, 'uploads/os_3_1780914285_124f4ed7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:24:45'),
(626, 'ordem_servico', 3, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:26:11'),
(627, 'ordem_servico', 3, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:30:06'),
(628, 'ordem_servico', 3, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:30:13'),
(629, 'ordem_servico', 101, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-08 07:47:31'),
(630, 'ordem_servico', 102, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 07:49:09'),
(631, 'ordem_servico', 103, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 07:53:08'),
(632, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781002707_dcc22f2e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 07:58:27'),
(633, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781002782_9e66e7d2.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 07:59:42'),
(634, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781002803_ef66fb1a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:00:03'),
(635, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781002814_9c2fb9c5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:00:14'),
(636, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781002825_cf22dc54.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:00:25'),
(637, 'ordem_servico', 103, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:03:00'),
(638, 'ordem_servico', 101, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:06:48'),
(639, 'ordem_servico', 86, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:09:01'),
(640, 'ordem_servico', 86, 'EDITAR', 'prazo', '2026-06-09 07:00:00', '2026-06-13 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:09:23'),
(641, 'ordem_servico', 101, 'ANEXO', 'arquivo', NULL, 'uploads/os_101_1781003601_ff28e20a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:13:21'),
(642, 'ordem_servico', 101, 'ANEXO', 'arquivo', NULL, 'uploads/os_101_1781003621_973bb068.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:13:41'),
(643, 'ordem_servico', 101, 'EDITAR', 'prazo', '2026-06-08 15:00:00', '2026-06-11 15:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:14:11'),
(644, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781003886_71f9cd2e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:18:06'),
(645, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781003898_a17eeb49.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:18:18'),
(646, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781003915_8bcc6040.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:18:35'),
(647, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781003927_1efbcbaa.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:18:47'),
(648, 'ordem_servico', 99, 'EDITAR', 'prazo', '2026-06-05 16:00:00', '2026-06-10 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:18:57'),
(649, 'ordem_servico', 99, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 08:19:55'),
(650, 'ordem_servico', 104, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 09:02:04'),
(651, 'ordem_servico', 104, 'ANEXO', 'arquivo', NULL, 'uploads/os_104_1781006597_59bbfff2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 09:03:17'),
(652, 'ordem_servico', 104, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 09:04:13'),
(653, 'ordem_servico', 104, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 09:04:35'),
(654, 'ordem_servico', 104, 'SOLICITAR_TOKEN_REABERTURA', NULL, NULL, 'Solicitação de token para reabertura da OS.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 09:11:00'),
(655, 'ordem_servico', 104, 'MATERIAL', 'materiais', NULL, 'Bucha Drywall nº 02 com Parafusos', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 09:14:16'),
(656, 'ordem_servico', 104, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-09 09:14:31'),
(657, 'cftv_relatorio', 11, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-09 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.215 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-09 17:03:26'),
(658, 'cftv_relatorio', 11, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.215 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-09 17:05:02'),
(659, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099524_28cd69fb.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:52:04'),
(660, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099543_aca443d2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:52:23'),
(661, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099561_0ba0450a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:52:41'),
(662, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099583_ad2cd284.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:53:03'),
(663, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099650_7ea73d6f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:54:10'),
(664, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099660_0f523182.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:54:20'),
(665, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099678_80b5625d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:54:38'),
(666, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099699_824ee687.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:54:59'),
(667, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099713_a4450e19.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:55:13'),
(668, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099732_69a3bdd7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:55:32'),
(669, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099755_c0b5a009.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:55:55'),
(670, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099770_1f3a68ea.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:56:10'),
(671, 'ordem_servico', 102, 'ANEXO', 'arquivo', NULL, 'uploads/os_102_1781099789_e84e7260.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:56:29'),
(672, 'ordem_servico', 102, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:57:16'),
(673, 'ordem_servico', 102, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:57:24'),
(674, 'ordem_servico', 60, 'EDITAR', 'prazo', '2026-06-04 16:00:00', '2026-06-11 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 10:57:54'),
(675, 'ordem_servico', 21, 'EDITAR', 'prazo', '2026-06-05 15:00:00', '2026-06-30 15:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:01:29'),
(676, 'ordem_servico', 101, 'ANEXO', 'arquivo', NULL, 'uploads/os_101_1781100157_80aeca9b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:02:37'),
(677, 'ordem_servico', 101, 'MOVER_FOTO', 'anexos_os.tipo', 'FOTO_DEPOIS', 'FOTO_ANTES', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:02:43'),
(678, 'ordem_servico', 101, 'ANEXO', 'arquivo', NULL, 'uploads/os_101_1781100204_5090f71c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:03:24'),
(679, 'ordem_servico', 101, 'ANEXO', 'arquivo', NULL, 'uploads/os_101_1781100220_3af076c6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:03:40'),
(680, 'ordem_servico', 101, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:05:20'),
(681, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781102393_3fbbbb63.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:39:53'),
(682, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781102412_a8083e03.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:40:12'),
(683, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781102427_530c15e7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:40:27'),
(684, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781102443_4c3139a2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:40:43'),
(685, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781102457_7ad4ee75.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:40:57'),
(686, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781102471_a7e628fd.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:41:11'),
(687, 'ordem_servico', 103, 'EDITAR', 'prazo', '2026-06-10 12:59:00', '2026-06-10 12:59', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:45:10'),
(688, 'ordem_servico', 103, 'ANEXO', 'arquivo', NULL, 'uploads/os_103_1781102776_d5ea984a.pdf', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:46:16'),
(689, 'ordem_servico', 103, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:46:29'),
(690, 'ordem_servico', 101, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:47:02'),
(691, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781102887_3758c836.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:48:07'),
(692, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781102901_bb5b4e34.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:48:21'),
(693, 'ordem_servico', 99, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:51:28'),
(694, 'ordem_servico', 99, 'EDITAR', 'prazo', '2026-06-10 16:00:00', '2026-06-11 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:51:42'),
(695, 'ordem_servico', 95, 'ANEXO', 'arquivo', NULL, 'uploads/os_95_1781103547_bcf525af.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 11:59:07'),
(696, 'ordem_servico', 95, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:05:46'),
(697, 'ordem_servico', 11, 'EDITAR', 'prazo', '2026-06-05 10:00:00', '2026-06-30 10:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:06:20'),
(698, 'ordem_servico', 105, 'CRIAR', NULL, NULL, 'OS criada. SLA: 24 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:08:51'),
(699, 'ordem_servico', 105, 'ANEXO', 'arquivo', NULL, 'uploads/os_105_1781111602_6eb2d40d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:13:22'),
(700, 'ordem_servico', 105, 'EDITAR', 'prazo', '2026-06-12 17:00:00', '2026-06-12 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:13:30'),
(701, 'ordem_servico', 105, 'ATRIBUIR', 'agente_id', NULL, '9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:13:52'),
(702, 'ordem_servico', 105, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:13:52'),
(703, 'ordem_servico', 44, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 14:22:11'),
(704, 'ordem_servico', 106, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 16:33:28'),
(705, 'cftv_relatorio', 12, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-11 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-11 08:11:18'),
(706, 'cftv_relatorio', 12, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-11 08:14:03'),
(707, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187291_b88d3572.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:14:51'),
(708, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187308_0a01b6a1.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:15:08'),
(709, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187357_da24987d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:15:57'),
(710, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187375_a4038c1a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:16:15'),
(711, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187402_9bde6d52.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:16:42'),
(712, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187422_6b983aff.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:17:02'),
(713, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187437_e20464ed.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:17:17'),
(714, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187461_b15c0594.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:17:41'),
(715, 'ordem_servico', 106, 'ANEXO', 'arquivo', NULL, 'uploads/os_106_1781187486_9cd5f055.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:18:06'),
(716, 'ordem_servico', 106, 'ATRIBUIR', 'agente_id', '3', '6', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:18:31'),
(717, 'ordem_servico', 106, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:18:31'),
(718, 'ordem_servico', 106, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:25:48'),
(719, 'ordem_servico', 106, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:27:24'),
(720, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781188574_ed61d4f8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:36:14'),
(721, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781188592_a50687bf.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:36:32'),
(722, 'ordem_servico', 99, 'ANEXO', 'arquivo', NULL, 'uploads/os_99_1781188614_4a774c8e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:36:54'),
(723, 'ordem_servico', 99, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:45:10'),
(724, 'ordem_servico', 99, 'ATRIBUIR', 'agente_id', '3', '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:45:41'),
(725, 'ordem_servico', 99, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:45:41'),
(726, 'ordem_servico', 99, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:45:57'),
(727, 'ordem_servico', 107, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:49:43'),
(728, 'ordem_servico', 107, 'ANEXO', 'arquivo', NULL, 'uploads/os_107_1781189486_8b90a403.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:51:26'),
(729, 'ordem_servico', 107, 'ANEXO', 'arquivo', NULL, 'uploads/os_107_1781189507_c8d06131.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:51:47'),
(730, 'ordem_servico', 107, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 11:52:59'),
(731, 'ordem_servico', 95, 'ANEXO', 'arquivo', NULL, 'uploads/os_95_1781190215_425121be.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 12:03:35'),
(732, 'ordem_servico', 95, 'EDITAR', 'prazo', '2026-06-15 07:00:00', '2026-06-15 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 12:03:43'),
(733, 'ordem_servico', 11, 'ANEXO', 'arquivo', NULL, 'uploads/os_11_1781190873_da136a9b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 12:14:33'),
(734, 'ordem_servico', 11, 'ANEXO', 'arquivo', NULL, 'uploads/os_11_1781190890_db925be5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 12:14:50'),
(735, 'ordem_servico', 11, 'ANEXO', 'arquivo', NULL, 'uploads/os_11_1781190909_f4ed02b8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 12:15:09'),
(736, 'ordem_servico', 11, 'ALTERAR_STATUS', 'status', 'PAUSADA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 12:16:04'),
(737, 'ordem_servico', 60, 'EDITAR', 'prazo', '2026-06-11 16:00:00', '2026-06-17 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:06:37'),
(738, 'ordem_servico', 70, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:15:33'),
(739, 'ordem_servico', 70, 'EDITAR', 'prazo', '2026-06-11 16:00:00', '2026-06-13 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:15:50'),
(740, 'ordem_servico', 108, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:19:56'),
(741, 'ordem_servico', 108, 'ANEXO', 'arquivo', NULL, 'uploads/os_108_1781205707_19298f1e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:21:47'),
(742, 'ordem_servico', 108, 'ANEXO', 'arquivo', NULL, 'uploads/os_108_1781205718_52d1ef86.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:21:58'),
(743, 'ordem_servico', 108, 'ANEXO', 'arquivo', NULL, 'uploads/os_108_1781205736_e52d2cc8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:22:16'),
(744, 'ordem_servico', 108, 'ANEXO', 'arquivo', NULL, 'uploads/os_108_1781205806_7c9f98fe.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:23:26'),
(745, 'ordem_servico', 108, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:28:16'),
(746, 'ordem_servico', 108, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:28:23'),
(747, 'ordem_servico', 109, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:31:16'),
(748, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781206448_27d80266.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:34:08'),
(749, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781206459_0bfb154c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:34:19'),
(750, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781206488_13321b0d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:34:48'),
(751, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781206504_a4816306.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:35:04'),
(752, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781206533_e9c6609c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:35:33'),
(753, 'ordem_servico', 109, 'ATRIBUIR', 'agente_id', '6', '6', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:37:39'),
(754, 'ordem_servico', 109, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:37:39'),
(755, 'ordem_servico', 110, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 16:53:00'),
(756, 'cftv_relatorio', 12, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-11 16:56:27'),
(757, 'ordem_servico', 110, 'ATRIBUIR', 'agente_id', '6', '8', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 17:01:07'),
(758, 'ordem_servico', 110, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 17:01:07'),
(759, 'ordem_servico', 110, 'ATRIBUIR', 'agente_id', '8', '8', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 17:01:07'),
(760, 'ordem_servico', 110, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 17:01:07'),
(761, 'ordem_servico', 110, 'ANEXO', 'arquivo', NULL, 'uploads/os_110_1781208151_8e144af4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 17:02:31'),
(762, 'ordem_servico', 100, 'ANEXO', 'arquivo', NULL, 'uploads/os_100_1781266069_20ec3a00.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 09:07:49'),
(763, 'ordem_servico', 100, 'ANEXO', 'arquivo', NULL, 'uploads/os_100_1781266082_a9e2be18.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 09:08:02'),
(764, 'ordem_servico', 100, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 09:10:59'),
(765, 'ordem_servico', 100, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 09:11:13'),
(766, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781271954_47708216.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 10:45:54'),
(767, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781271969_6bf4200b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 10:46:09'),
(768, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781272010_dcab8d82.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 10:46:50'),
(769, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781272063_6f11d043.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 10:47:43'),
(770, 'ordem_servico', 109, 'ANEXO', 'arquivo', NULL, 'uploads/os_109_1781272082_4e09e8ba.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 10:48:02'),
(771, 'ordem_servico', 109, 'EDITAR', 'prazo', '2026-06-12 17:00:00', '2026-06-12 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 10:48:17'),
(772, 'ordem_servico', 111, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 10:51:09'),
(773, 'ordem_servico', 111, 'ANEXO', 'arquivo', NULL, 'uploads/os_111_1781272949_241d603a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:02:29'),
(774, 'ordem_servico', 111, 'ANEXO', 'arquivo', NULL, 'uploads/os_111_1781272963_2e69508c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:02:43'),
(775, 'ordem_servico', 111, 'ANEXO', 'arquivo', NULL, 'uploads/os_111_1781272989_443946eb.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:03:09'),
(776, 'ordem_servico', 111, 'ANEXO', 'arquivo', NULL, 'uploads/os_111_1781272999_30401b70.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:03:19'),
(777, 'ordem_servico', 111, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:04:23'),
(778, 'ordem_servico', 111, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-12 11:04:36'),
(779, 'cftv_relatorio', 13, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-13 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-13 16:53:27'),
(780, 'cftv_relatorio', 13, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-13 16:55:25'),
(781, 'cftv_relatorio', 14, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-15 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-15 05:20:03'),
(782, 'cftv_relatorio', 14, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-15 05:23:16'),
(783, 'ordem_servico', 43, 'EDITAR', 'prazo', '2026-06-12 16:00:00', '2026-06-19 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:02:51'),
(784, 'ordem_servico', 105, 'EDITAR', 'prazo', '2026-06-12 17:00:00', '2026-06-16 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:03:11'),
(785, 'ordem_servico', 95, 'ANEXO', 'arquivo', NULL, 'uploads/os_95_1781521483_2e1af9c7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:04:43'),
(786, 'ordem_servico', 95, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:06:32');
INSERT INTO `auditoria_eventos` (`id`, `entidade`, `entidade_id`, `acao`, `campo`, `valor_anterior`, `valor_novo`, `usuario_id`, `ip`, `user_agent`, `criado_em`) VALUES
(787, 'ordem_servico', 95, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:06:38'),
(788, 'ordem_servico', 70, 'EDITAR', 'prazo', '2026-06-13 16:00:00', '2026-06-15 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:23:15'),
(789, 'ordem_servico', 109, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:27:19'),
(790, 'ordem_servico', 109, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:27:27'),
(791, 'ordem_servico', 22, 'EDITAR', 'prazo', '2026-06-12 18:00:00', '2026-06-30 18:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:27:52'),
(792, 'ordem_servico', 32, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 08:39:50'),
(793, 'ordem_servico', 112, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:29:16'),
(794, 'ordem_servico', 113, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:53:24'),
(795, 'ordem_servico', 113, 'ANEXO', 'arquivo', NULL, 'uploads/os_113_1781531676_3107c222.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:54:36'),
(796, 'ordem_servico', 113, 'ANEXO', 'arquivo', NULL, 'uploads/os_113_1781531696_da34435c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:54:56'),
(797, 'ordem_servico', 113, 'ANEXO', 'arquivo', NULL, 'uploads/os_113_1781531716_bee0b763.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:55:16'),
(798, 'ordem_servico', 113, 'ANEXO', 'arquivo', NULL, 'uploads/os_113_1781531787_e84ab1ec.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:56:27'),
(799, 'ordem_servico', 113, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 10:59:47'),
(800, 'ordem_servico', 86, 'EDITAR', 'prazo', '2026-06-13 07:00:00', '2026-06-16 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 11:16:13'),
(801, 'ordem_servico', 114, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 14:20:44'),
(802, 'cftv_relatorio', 14, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-15 16:56:55'),
(803, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604601_8e560f13.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:10:01'),
(804, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604620_5e25cc9b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:10:20'),
(805, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604662_491d0f99.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:11:02'),
(806, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604771_2023164e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:12:51'),
(807, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604818_d1450292.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:13:38'),
(808, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604869_b4cf646c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:14:29'),
(809, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604935_32271795.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:15:35'),
(810, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781604961_87b2fc7e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:16:01'),
(811, 'ordem_servico', 86, 'ANEXO', 'arquivo', NULL, 'uploads/os_86_1781605081_99a65666.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:18:01'),
(812, 'ordem_servico', 86, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:23:20'),
(813, 'ordem_servico', 70, 'MATERIAL', 'materiais', NULL, 'Disco de Corte para Serra Mármore - Cortar Porcelanato', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:28:58'),
(814, 'ordem_servico', 70, 'MATERIAL', 'materiais', NULL, 'Colher de Pedreiro', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:30:11'),
(815, 'ordem_servico', 70, 'MATERIAL', 'materiais', NULL, 'Desempenadeira dentada para argamassa', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:31:45'),
(816, 'ordem_servico', 70, 'EDITAR', 'prazo', '2026-06-15 17:00:00', '2026-06-19 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 07:53:47'),
(817, 'ordem_servico', 114, 'EDITAR', 'prazo', '2026-06-16 17:00:00', '2026-06-19 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:31:59'),
(818, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609577_1aa7a875.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:32:57'),
(819, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609588_6c47cd40.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:33:08'),
(820, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609600_9f5ed304.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:33:20'),
(821, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609622_9082981d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:33:42'),
(822, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609660_cc6e0fc6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:34:20'),
(823, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609675_b686e049.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:34:35'),
(824, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609687_d5f8ab40.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:34:47'),
(825, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781609698_ad6af4f6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:34:58'),
(826, 'ordem_servico', 114, 'EDITAR', 'prazo', '2026-06-19 17:00:00', '2026-06-19 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 08:35:07'),
(827, 'ordem_servico', 112, 'ANEXO', 'arquivo', NULL, 'uploads/os_112_1781623384_6570fad2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 12:23:04'),
(828, 'ordem_servico', 112, 'ANEXO', 'arquivo', NULL, 'uploads/os_112_1781623407_a759a4e6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 12:23:27'),
(829, 'ordem_servico', 112, 'ANEXO', 'arquivo', NULL, 'uploads/os_112_1781623419_d1bddde0.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 12:23:39'),
(830, 'ordem_servico', 112, 'ANEXO', 'arquivo', NULL, 'uploads/os_112_1781623577_43e0fe66.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 12:26:17'),
(831, 'ordem_servico', 112, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 12:28:52'),
(832, 'ordem_servico', 105, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 06:53:34'),
(833, 'ordem_servico', 105, 'EDITAR', 'prazo', '2026-06-16 17:00:00', '2026-06-18 17:00', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 06:53:44'),
(834, 'ordem_servico', 115, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 06:56:45'),
(835, 'ordem_servico', 115, 'EDITAR', 'descricao', 'Manutenção preventiva dos ar condicionados do Expresso Cidadão', 'Manutenção preventiva dos ar condicionados do Expresso Cidadão.\r\nA manutenção é de responsabilidade da JM Engenharia (JUCA).\r\nOs agente de manutenção do Shopping apenas acompanha.', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 06:58:15'),
(836, 'ordem_servico', 115, 'EDITAR', 'prazo', '2026-06-27 22:30:00', '2026-06-27 22:30', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 06:58:15'),
(837, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690740_550a4466.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:05:40'),
(838, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690753_109a75af.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:05:53'),
(839, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690767_e5e0a013.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:06:07'),
(840, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690781_a2dd73bd.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:06:21'),
(841, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690822_6998fbeb.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:07:02'),
(842, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690931_cc9058e1.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:08:51'),
(843, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690949_fa9821eb.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:09:09'),
(844, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690961_dafd6aec.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:09:21'),
(845, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690982_f7049205.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:09:42'),
(846, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781690997_f76edee8.jpeg', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:09:57'),
(847, 'ordem_servico', 115, 'EDITAR', 'prazo', '2026-06-27 22:30:00', '2026-06-27 22:30', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:10:31'),
(848, 'ordem_servico', 115, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '164.163.13.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 07:57:11'),
(849, 'ordem_servico', 113, 'SOLICITAR_TOKEN_REABERTURA', NULL, NULL, 'Solicitação de token para reabertura da OS.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', '2026-06-17 13:19:20'),
(850, 'ordem_servico', 104, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', '2026-06-17 13:20:47'),
(851, 'ordem_servico', 113, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', '2026-06-17 13:21:29'),
(852, 'ordem_servico', 78, 'ALTERAR_STATUS', 'status', 'PAUSADA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:00:16'),
(853, 'ordem_servico', 116, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:45:33'),
(854, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781725590_46008518.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:46:30'),
(855, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781725600_31dd5bb9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:46:40'),
(856, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781725608_6d917caa.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:46:48'),
(857, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781725618_6b014e17.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:46:58'),
(858, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781725628_994045eb.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:47:08'),
(859, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781725636_c6fe50f4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:47:16'),
(860, 'ordem_servico', 116, 'EDITAR', 'prazo', '2026-06-19 17:00:00', '2026-06-19 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 16:47:30'),
(861, 'ordem_servico', 117, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:08:43'),
(862, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781777537_09f23532.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:12:17'),
(863, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781777549_76df8ffa.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:12:29'),
(864, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781777563_36385e5f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:12:43'),
(865, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781777588_45a1dc92.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:13:08'),
(866, 'ordem_servico', 117, 'EDITAR', 'prazo', '2026-06-22 07:00:00', '2026-06-22 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:13:36'),
(867, 'ordem_servico', 60, 'EDITAR', 'prazo', '2026-06-17 16:00:00', '2026-06-18 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:14:42'),
(868, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781779284_6bcd2a6c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:41:24'),
(869, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781779326_a65de985.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:42:06'),
(870, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781779375_9a07bc96.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:42:55'),
(871, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781779397_cded9db4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:43:17'),
(872, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781779421_53b35b6e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:43:41'),
(873, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781779450_dc659369.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:44:10'),
(874, 'ordem_servico', 117, 'ANEXO', 'arquivo', NULL, 'uploads/os_117_1781779465_68b2a014.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:44:25'),
(875, 'ordem_servico', 117, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:46:22'),
(876, 'ordem_servico', 32, 'EDITAR', 'prazo', '2026-06-15 17:00:00', '2026-06-30 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:47:12'),
(877, 'ordem_servico', 115, 'ATRIBUIR', 'agente_id', '9', '9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:47:49'),
(878, 'ordem_servico', 115, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 07:47:49'),
(879, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781780997_07dcd530.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:09:57'),
(880, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781781018_59323a84.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:10:18'),
(881, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781781038_9bb6c4e4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:10:38'),
(882, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781781054_78081285.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:10:54'),
(883, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781781068_5cd7ed7b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:11:08'),
(884, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1781781079_253c45a3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:11:19'),
(885, 'ordem_servico', 115, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:15:24'),
(886, 'ordem_servico', 115, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 08:50:51'),
(887, 'ordem_servico', 58, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:57:24'),
(888, 'ordem_servico', 118, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:04:06'),
(889, 'ordem_servico', 118, 'ANEXO', 'arquivo', NULL, 'uploads/os_118_1781795193_fd258a7a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:06:33'),
(890, 'ordem_servico', 118, 'ANEXO', 'arquivo', NULL, 'uploads/os_118_1781795228_4112d179.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:07:08'),
(891, 'ordem_servico', 118, 'ANEXO', 'arquivo', NULL, 'uploads/os_118_1781795240_264008a7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:07:20'),
(892, 'ordem_servico', 118, 'ANEXO', 'arquivo', NULL, 'uploads/os_118_1781795272_2c28b91d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:07:52'),
(893, 'ordem_servico', 118, 'ANEXO', 'arquivo', NULL, 'uploads/os_118_1781795291_b2fd88fd.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:08:11'),
(894, 'ordem_servico', 118, 'ATRIBUIR', 'agente_id', '19', '19', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:09:53'),
(895, 'ordem_servico', 118, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:09:53'),
(896, 'ordem_servico', 118, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 12:10:07'),
(897, 'ordem_servico', 105, 'ANEXO', 'arquivo', NULL, 'uploads/os_105_1781805272_bf9aea36.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 14:54:32'),
(898, 'ordem_servico', 105, 'ANEXO', 'arquivo', NULL, 'uploads/os_105_1781805283_2725078d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 14:54:43'),
(899, 'ordem_servico', 105, 'ANEXO', 'arquivo', NULL, 'uploads/os_105_1781805293_d30e52f5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 14:54:53'),
(900, 'ordem_servico', 105, 'ANEXO', 'arquivo', NULL, 'uploads/os_105_1781805304_a229a3d4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 14:55:04'),
(901, 'ordem_servico', 105, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:40:31'),
(902, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808065_81c00e0f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:41:05'),
(903, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808079_ed315772.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:41:19'),
(904, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808091_31e752f2.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:41:31'),
(905, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808109_0ba4c77a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:41:49'),
(906, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808128_9b1e0d13.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:42:08'),
(907, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808144_1e36defe.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:42:24'),
(908, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808164_5a69eb07.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:42:44'),
(909, 'ordem_servico', 27, 'ANEXO', 'arquivo', NULL, 'uploads/os_27_1781808178_2c203641.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:42:58'),
(910, 'ordem_servico', 27, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 15:46:09'),
(911, 'ordem_servico', 90, 'ATRIBUIR', 'agente_id', '16', '16', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 16:09:34'),
(912, 'ordem_servico', 90, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 16:09:34'),
(913, 'ordem_servico', 119, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 16:13:17'),
(914, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863685_c24eaafd.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:08:05'),
(915, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863740_8c3abf8f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:09:00'),
(916, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863764_e9c78c85.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:09:24'),
(917, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863815_3ddf40ac.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:10:15'),
(918, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863832_46258644.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:10:32'),
(919, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863869_eafd4740.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:11:09'),
(920, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863894_a3accf71.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:11:34'),
(921, 'ordem_servico', 90, 'ANEXO', 'arquivo', NULL, 'uploads/os_90_1781863923_5ca1534e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:12:03'),
(922, 'ordem_servico', 90, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:19:19'),
(923, 'ordem_servico', 120, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:23:46'),
(924, 'ordem_servico', 120, 'ATRIBUIR', 'agente_id', '16', '16', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:23:54'),
(925, 'ordem_servico', 120, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:23:54'),
(926, 'ordem_servico', 120, 'ANEXO', 'arquivo', NULL, 'uploads/os_120_1781864652_414f0b74.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:24:12'),
(927, 'ordem_servico', 120, 'ANEXO', 'arquivo', NULL, 'uploads/os_120_1781864671_7d8cf04c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:24:31'),
(928, 'ordem_servico', 120, 'ANEXO', 'arquivo', NULL, 'uploads/os_120_1781864691_15d608da.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:24:51'),
(929, 'ordem_servico', 120, 'ANEXO', 'arquivo', NULL, 'uploads/os_120_1781864711_704c6712.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:25:11'),
(930, 'ordem_servico', 120, 'ANEXO', 'arquivo', NULL, 'uploads/os_120_1781864728_4137793c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:25:28'),
(931, 'ordem_servico', 120, 'ANEXO', 'arquivo', NULL, 'uploads/os_120_1781864740_8904bdc6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:25:40'),
(932, 'ordem_servico', 120, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:26:43'),
(933, 'ordem_servico', 119, 'ATRIBUIR', 'agente_id', NULL, '9', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:31:40'),
(934, 'ordem_servico', 119, 'ALTERAR_STATUS', 'status', 'ABERTA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:31:40'),
(935, 'ordem_servico', 119, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 07:33:28'),
(936, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781867890_7f97ea65.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:18:10'),
(937, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781867902_526a3121.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:18:22'),
(938, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781867915_e82e9687.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:18:35'),
(939, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781867948_eb61eb58.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:19:08'),
(940, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781867964_ecf67205.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:19:24'),
(941, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781867977_aa8c13d4.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:19:37'),
(942, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1781867992_80930fa7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:19:52'),
(943, 'ordem_servico', 114, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:20:28'),
(944, 'ordem_servico', 114, 'EDITAR', 'prazo', '2026-06-19 17:00:00', '2026-06-20 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 08:20:44'),
(945, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781871484_c3172337.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 09:18:04'),
(946, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781871503_66a51843.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 09:18:23'),
(947, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781871515_c5deac12.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 09:18:35'),
(948, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781871541_a10b600f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 09:19:01'),
(949, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781871554_85e2faa5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 09:19:14'),
(950, 'ordem_servico', 116, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 09:19:24'),
(951, 'ordem_servico', 70, 'ANEXO', 'arquivo', NULL, 'uploads/os_70_1781878513_eef9a820.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 11:15:13'),
(952, 'ordem_servico', 70, 'ANEXO', 'arquivo', NULL, 'uploads/os_70_1781878544_5512a759.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 11:15:44'),
(953, 'ordem_servico', 70, 'EDITAR', 'prazo', '2026-06-19 17:00:00', '2026-06-22 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 15:31:49'),
(954, 'ordem_servico', 60, 'EDITAR', 'prazo', '2026-06-18 16:00:00', '2026-06-23 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 15:32:17'),
(955, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781894632_bb0881b1.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 15:43:52'),
(956, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781894646_b680e81a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 15:44:06'),
(957, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781894659_6f965194.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 15:44:19'),
(958, 'ordem_servico', 116, 'ANEXO', 'arquivo', NULL, 'uploads/os_116_1781894678_f13b0549.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 15:44:38'),
(959, 'ordem_servico', 116, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 15:46:48'),
(960, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1782124661_8431606a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:37:41'),
(961, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1782124696_7bfd9891.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:38:16'),
(962, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1782124720_931e15cb.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:38:40'),
(963, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1782124807_72766a9f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:40:07'),
(964, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1782124829_183f07c8.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:40:29'),
(965, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1782124844_6b350bcd.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:40:44'),
(966, 'ordem_servico', 114, 'ANEXO', 'arquivo', NULL, 'uploads/os_114_1782124856_dab10e80.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:40:56'),
(967, 'ordem_servico', 114, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 07:41:20'),
(968, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1782129695_0b6a3bda.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:01:35'),
(969, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1782129710_341d8452.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:01:50'),
(970, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1782129731_a17641ae.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:02:11'),
(971, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1782129931_3bf27fd9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:05:31'),
(972, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1782129949_8624200e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:05:49'),
(973, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1782129966_10466618.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:06:06'),
(974, 'ordem_servico', 115, 'ANEXO', 'arquivo', NULL, 'uploads/os_115_1782129988_3453db7f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:06:28'),
(975, 'ordem_servico', 115, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:09:49'),
(976, 'ordem_servico', 115, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:09:56'),
(977, 'ordem_servico', 91, 'EDITAR', 'prazo', '2026-06-21 15:50:00', '2026-06-26 15:50', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:10:50'),
(978, 'ordem_servico', 121, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:13:47'),
(979, 'ordem_servico', 121, 'ANEXO', 'arquivo', NULL, 'uploads/os_121_1782130500_c6138153.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:15:00'),
(980, 'ordem_servico', 121, 'ANEXO', 'arquivo', NULL, 'uploads/os_121_1782130523_cae04d73.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:15:23'),
(981, 'ordem_servico', 121, 'ANEXO', 'arquivo', NULL, 'uploads/os_121_1782130532_f38f1e81.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:15:32'),
(982, 'ordem_servico', 121, 'ANEXO', 'arquivo', NULL, 'uploads/os_121_1782130554_18391fa3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:15:54'),
(983, 'ordem_servico', 122, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:23:10');
INSERT INTO `auditoria_eventos` (`id`, `entidade`, `entidade_id`, `acao`, `campo`, `valor_anterior`, `valor_novo`, `usuario_id`, `ip`, `user_agent`, `criado_em`) VALUES
(984, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1782131010_6daf7d1d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:23:30'),
(985, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1782131026_1219bb2e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:23:46'),
(986, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1782131051_0dec1462.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:24:11'),
(987, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1782131069_343193ae.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:24:29'),
(988, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1782131133_6587e251.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:25:33'),
(989, 'ordem_servico', 122, 'EDITAR', 'prazo', '2026-07-31 07:00:00', '2026-07-31 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 09:25:47'),
(990, 'ordem_servico', 70, 'ANEXO', 'arquivo', NULL, 'uploads/os_70_1782382555_ebe22563.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:15:55'),
(991, 'ordem_servico', 70, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:16:47'),
(992, 'ordem_servico', 123, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:05:42'),
(993, 'ordem_servico', 123, 'ANEXO', 'arquivo', NULL, 'uploads/os_123_1782385563_256b6cb9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:06:03'),
(994, 'ordem_servico', 123, 'ANEXO', 'arquivo', NULL, 'uploads/os_123_1782385580_3e465d2b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:06:20'),
(995, 'ordem_servico', 123, 'ANEXO', 'arquivo', NULL, 'uploads/os_123_1782385590_e10d680a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:06:30'),
(996, 'ordem_servico', 123, 'ANEXO', 'arquivo', NULL, 'uploads/os_123_1782385607_5290758f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:06:47'),
(997, 'ordem_servico', 123, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:09:23'),
(998, 'ordem_servico', 122, 'MATERIAL', 'materiais', NULL, 'Lixa ferro nº 100', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:10:16'),
(999, 'ordem_servico', 122, 'MATERIAL', 'materiais', NULL, 'Lixa ferro nº 150', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:10:35'),
(1000, 'ordem_servico', 122, 'MATERIAL', 'materiais', NULL, 'Lixa ferro p/ Lixadeira nº100', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:10:49'),
(1001, 'ordem_servico', 122, 'MATERIAL', 'materiais', NULL, 'Cola Almasuper Marceneiro', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:11:11'),
(1002, 'ordem_servico', 122, 'MATERIAL', 'materiais', NULL, 'Fita crepe larga', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:11:28'),
(1003, 'ordem_servico', 122, 'MATERIAL', 'materiais', NULL, 'Tinta Alto brilho Areia Iquine Dialine Seca Rápido 3L', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:12:00'),
(1004, 'ordem_servico', 122, 'MATERIAL', 'materiais', NULL, 'Thinner Iquine 900ml', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:12:30'),
(1005, 'ordem_servico', 122, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:14:17'),
(1006, 'cftv_relatorio', 15, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-25 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-25 13:30:51'),
(1007, 'cftv_relatorio', 15, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-25 13:33:09'),
(1008, 'ordem_servico', 124, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 15:35:47'),
(1009, 'ordem_servico', 124, 'ANEXO', 'arquivo', NULL, 'uploads/os_124_1782412688_f99f933a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 15:38:08'),
(1010, 'ordem_servico', 124, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 15:38:56'),
(1011, 'ordem_servico', 124, 'MATERIAL', 'materiais', NULL, 'Lixa madeira nº 150', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 15:39:24'),
(1012, 'ordem_servico', 124, 'MATERIAL', 'materiais', NULL, 'Lixa madeira nº 100', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 15:39:45'),
(1013, 'ordem_servico', 124, 'MATERIAL', 'materiais', NULL, 'Lixa Ferro p/ Lixadeira nº150', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 15:40:08'),
(1014, 'ordem_servico', 124, 'MATERIAL', 'materiais', NULL, 'Thinner Iquine 900ml', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 15:40:26'),
(1015, 'ordem_servico', 124, 'EDITAR', 'prazo', '2026-07-31 07:00:00', '2026-07-10 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 16:17:21'),
(1016, 'ordem_servico', 124, 'EDITAR', 'titulo', 'Pintura e restauração dos Bancos do Mall', 'Pintura e restauração dos Bancos do Mall, Vestuários e Lixeira do Mall.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 16:19:28'),
(1017, 'ordem_servico', 124, 'EDITAR', 'descricao', 'Pintura e restauração dos bancos do Mall + Vestuários', 'Pintura e restauração dos bancos do Mall + Vestuários + Lixeira do Mall.\r\nEnvernizar a madeira e pintar a estrutura de ferro na cor branca.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 16:19:28'),
(1018, 'ordem_servico', 124, 'EDITAR', 'prazo', '2026-07-10 07:00:00', '2026-07-10 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 16:19:28'),
(1019, 'ordem_servico', 125, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 16:21:07'),
(1020, 'ordem_servico', 125, 'ANEXO', 'arquivo', NULL, 'uploads/os_125_1782415282_42b6c9c9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 16:21:22'),
(1021, 'ordem_servico', 125, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 16:21:37'),
(1022, 'cftv_relatorio', 15, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-25 16:30:53'),
(1023, 'cftv_relatorio', 16, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-27 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-27 17:04:42'),
(1024, 'cftv_relatorio', 16, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-27 17:05:37'),
(1025, 'cftv_relatorio', 16, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-27 17:05:43'),
(1026, 'ordem_servico', 60, 'EDITAR', 'prazo', '2026-06-23 16:00:00', '2026-07-15 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:34:41'),
(1027, 'ordem_servico', 92, 'ANEXO', 'arquivo', NULL, 'uploads/os_92_1782729753_d83a4019.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:42:33'),
(1028, 'ordem_servico', 92, 'ANEXO', 'arquivo', NULL, 'uploads/os_92_1782729768_8d6dc36a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:42:48'),
(1029, 'ordem_servico', 92, 'ANEXO', 'arquivo', NULL, 'uploads/os_92_1782729788_a3c53012.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:43:08'),
(1030, 'ordem_servico', 92, 'ANEXO', 'arquivo', NULL, 'uploads/os_92_1782729806_184c7b2b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:43:26'),
(1031, 'ordem_servico', 92, 'ANEXO', 'arquivo', NULL, 'uploads/os_92_1782729835_f74fe00e.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:43:55'),
(1032, 'ordem_servico', 92, 'ANEXO', 'arquivo', NULL, 'uploads/os_92_1782729860_16975cf5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:44:20'),
(1033, 'ordem_servico', 92, 'ANEXO', 'arquivo', NULL, 'uploads/os_92_1782729876_e7ca2322.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:44:36'),
(1034, 'ordem_servico', 92, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 07:51:29'),
(1035, 'ordem_servico', 119, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 08:05:42'),
(1036, 'ordem_servico', 119, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 08:08:10'),
(1037, 'cftv_relatorio', 17, 'CRIAR_RELATORIO', NULL, NULL, '2026-06-29 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-29 16:58:18'),
(1038, 'cftv_ocorrencia', 3, 'ADICIONAR_OCORRENCIA', 'nivel', NULL, 'NIVEL_2', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-29 17:05:00'),
(1039, 'cftv_relatorio', 17, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.91 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-06-29 17:06:54'),
(1040, 'ordem_servico', 119, 'EDITAR', 'prazo', '2026-06-26 16:00:00', '2026-07-22 16:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 10:03:38'),
(1041, 'ordem_servico', 125, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 10:04:57'),
(1042, 'ordem_servico', 91, 'EDITAR', 'prazo', '2026-06-26 15:50:00', '2026-07-01 15:50', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 10:05:48'),
(1043, 'ordem_servico', 89, 'EDITAR', 'prazo', '2026-06-29 15:43:00', '2026-07-15 15:43', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 10:06:52'),
(1044, 'ordem_servico', 11, 'EDITAR', 'prazo', '2026-06-30 10:00:00', '2026-07-17 10:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 10:07:39'),
(1045, 'ordem_servico', 21, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'PAUSADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 10:08:25'),
(1046, 'ordem_servico', 126, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 10:24:52'),
(1047, 'ordem_servico', 121, 'EDITAR', 'prazo', '2026-06-29 17:00:00', '2026-07-01 17:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:23:16'),
(1048, 'ordem_servico', 121, 'ATRIBUIR', 'agente_id', '9', '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:23:56'),
(1049, 'ordem_servico', 121, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:23:56'),
(1050, 'ordem_servico', 41, 'ANEXO', 'arquivo', NULL, 'uploads/os_41_1782836745_778d4472.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:25:45'),
(1051, 'ordem_servico', 41, 'ATRIBUIR', 'agente_id', '12', '3', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:26:06'),
(1052, 'ordem_servico', 41, 'ALTERAR_STATUS', 'status', 'PAUSADA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:26:06'),
(1053, 'ordem_servico', 41, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:26:13'),
(1054, 'ordem_servico', 40, 'ATRIBUIR', 'agente_id', '12', '12', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:44:30'),
(1055, 'ordem_servico', 40, 'ALTERAR_STATUS', 'status', 'PAUSADA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:44:30'),
(1056, 'ordem_servico', 40, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 13:45:40'),
(1057, 'cftv_relatorio', 18, 'CRIAR_RELATORIO', NULL, NULL, '2026-07-01 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-01 17:01:30'),
(1058, 'cftv_relatorio', 18, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-01 17:02:20'),
(1059, 'cftv_relatorio', 19, 'CRIAR_RELATORIO', NULL, NULL, '2026-07-07 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-07 11:04:19'),
(1060, 'cftv_relatorio', 19, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-07 11:05:17'),
(1061, 'cftv_relatorio', 19, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-07 16:17:26'),
(1062, 'ordem_servico', 124, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'EM_ANDAMENTO', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-09 10:57:20'),
(1063, 'ordem_servico', 89, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-09 11:15:02'),
(1064, 'ordem_servico', 127, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 30, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-09 14:44:20'),
(1065, 'cftv_relatorio', 20, 'CRIAR_RELATORIO', NULL, NULL, '2026-07-09 DIURNO', 15, '191.244.245.242', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-09 16:56:16'),
(1066, 'cftv_relatorio', 20, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '191.244.245.242', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-09 16:57:31'),
(1067, 'ordem_servico', 127, 'ALTERAR_STATUS', 'status', 'ABERTA', 'EM_ANDAMENTO', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-10 08:19:58'),
(1068, 'cftv_relatorio', 21, 'CRIAR_RELATORIO', NULL, NULL, '2026-07-13 DIURNO', 15, '191.244.224.90', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-13 17:07:22'),
(1069, 'cftv_relatorio', 21, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '191.244.224.90', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-13 17:08:18'),
(1070, 'ordem_servico', 122, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-14 08:47:21'),
(1071, 'ordem_servico', 91, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-14 10:24:30'),
(1072, 'cftv_relatorio', 22, 'CRIAR_RELATORIO', NULL, NULL, '2026-07-15 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-15 16:58:53'),
(1073, 'cftv_relatorio', 22, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-15 16:59:35'),
(1074, 'cftv_relatorio', 22, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/149.0.7827.163 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-15 16:59:39'),
(1075, 'perfil', 7, 'CRIAR', NULL, 'null', 'Gestor / Sindico', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 21:30:21'),
(1076, 'perfil', 1, 'EDITAR', NULL, '{\"id\":1,\"codigo\":\"TI\",\"nome\":\"TI\",\"descricao\":\"Administracao integral\",\"ativo\":1,\"administrativo\":1,\"sistema\":1,\"criado_por\":null,\"criado_em\":\"2026-07-15 21:28:13\",\"atualizado_em\":null}', 'TI', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 21:31:34'),
(1077, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'codigo', NULL, 'd9d21be54114e7c8', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:07:47'),
(1078, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, 'd9d21be54114e7c8', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:00'),
(1079, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '967d3c2a1247abbd', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:04'),
(1080, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '967d3c2a1247abbd', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:06'),
(1081, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:37'),
(1082, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:39'),
(1083, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:39'),
(1084, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'codigo', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:51'),
(1085, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'codigo', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:52'),
(1086, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'codigo', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:53'),
(1087, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:08:57'),
(1088, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '5feceb66ffc86f38', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:11:04'),
(1089, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:19:50'),
(1090, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:19:51'),
(1091, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, '7987a3da75411276', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:20:08'),
(1092, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'numero', NULL, '192', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:22:35'),
(1093, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'numero', NULL, 'd9d21be54114e7c8', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:23:03'),
(1094, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'numero', NULL, '193', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:23:06'),
(1095, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'numero', NULL, '13', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:26:50'),
(1096, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '193', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:32:29'),
(1097, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '193', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:32:40'),
(1098, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '195', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:37:00'),
(1099, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '196', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:37:20'),
(1100, 'patrimonio_consulta', NULL, 'NAO_ENCONTRADO', 'codigo', NULL, '349b6dd06d485a8e', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:37:30'),
(1101, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '195', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:37:35'),
(1102, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '191', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:37:51'),
(1103, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '174', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:38:03'),
(1104, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '156', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:40:33'),
(1105, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '195', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:40:45'),
(1106, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '146', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:41:01'),
(1107, 'patrimonio_consulta', NULL, 'ENCONTRADO', 'codigo', NULL, '192', 1, '164.163.15.184', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:41:27'),
(1108, 'ordem_servico', 110, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 09:00:25'),
(1109, 'ordem_servico', 11, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 09:01:03'),
(1110, 'ordem_servico', 127, 'ATRIBUIR', 'agente_id', NULL, '4', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-20 09:30:39'),
(1111, 'ordem_servico', 127, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ATRIBUIDA', 31, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-20 09:30:39'),
(1112, 'ordem_servico', 128, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:08:18'),
(1113, 'ordem_servico', 128, 'ANEXO', 'arquivo', NULL, 'uploads/os_128_1784628521_5fc7f9d3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:08:41'),
(1114, 'ordem_servico', 128, 'ANEXO', 'arquivo', NULL, 'uploads/os_128_1784628536_624e837d.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:08:56'),
(1115, 'ordem_servico', 128, 'MOVER_FOTO', 'anexos_os.tipo', 'FOTO_ANTES', 'FOTO_DEPOIS', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:09:06'),
(1116, 'ordem_servico', 128, 'ANEXO', 'arquivo', NULL, 'uploads/os_128_1784628557_5681b7a9.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:09:17'),
(1117, 'ordem_servico', 128, 'ANEXO', 'arquivo', NULL, 'uploads/os_128_1784628576_0dc56a65.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:09:36'),
(1118, 'ordem_servico', 128, 'EDITAR', 'prazo', '2026-07-24 15:00:00', '2026-07-24 15:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:09:52'),
(1119, 'ordem_servico', 128, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:10:08'),
(1120, 'ordem_servico', 127, 'MATERIAL', 'materiais', NULL, 'Disco de Corte para Serra Mármore - para ferro', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:12:56'),
(1121, 'ordem_servico', 127, 'MATERIAL', 'materiais', NULL, 'Broca Nº 10 - para concreto', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:14:50'),
(1122, 'ordem_servico', 127, 'MATERIAL', 'materiais', NULL, 'Linha de pedreiro', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:16:00'),
(1123, 'ordem_servico', 127, 'MATERIAL', 'materiais', NULL, 'Cola para fixação de Tachões - Balde 5 Kg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:26:05'),
(1124, 'ordem_servico', 127, 'ANEXO', 'arquivo', NULL, 'uploads/os_127_1784629694_73759284.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:28:14'),
(1125, 'ordem_servico', 127, 'ANEXO', 'arquivo', NULL, 'uploads/os_127_1784629710_53385532.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:28:30'),
(1126, 'ordem_servico', 127, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:28:42'),
(1127, 'ordem_servico', 127, 'EDITAR', 'prazo', '2026-07-11 17:44:20', '2026-08-15 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:29:27'),
(1128, 'ordem_servico', 127, 'ALTERAR_STATUS', 'status', 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:33:51'),
(1129, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631529_d65bc9da.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:58:49'),
(1130, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631543_1026255f.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:59:03'),
(1131, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631557_990c9918.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:59:17'),
(1132, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631569_3076e936.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:59:29'),
(1133, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631580_8ebec6a5.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:59:40'),
(1134, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631594_b32b0022.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 07:59:54'),
(1135, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631613_4e970d07.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:00:13'),
(1136, 'ordem_servico', 126, 'ANEXO', 'arquivo', NULL, 'uploads/os_126_1784631788_e56cdc5a.pdf', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:03:08'),
(1137, 'ordem_servico', 126, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:03:24'),
(1138, 'ordem_servico', 125, 'ANEXO', 'arquivo', NULL, 'uploads/os_125_1784632234_72ce42cc.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:10:34'),
(1139, 'ordem_servico', 125, 'ANEXO', 'arquivo', NULL, 'uploads/os_125_1784632247_815ca6f1.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:10:47'),
(1140, 'ordem_servico', 125, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:11:09'),
(1141, 'ordem_servico', 74, 'EDITAR', 'categoria_id', '14', '7', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:12:10'),
(1142, 'ordem_servico', 74, 'EDITAR', 'prazo', '2026-06-30 22:00:00', '2026-06-30 22:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:12:10'),
(1143, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1784632696_55e41e1c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:18:16'),
(1144, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1784634152_0718d613.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:42:32'),
(1145, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1784634164_440d8968.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:42:44'),
(1146, 'ordem_servico', 122, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:43:38'),
(1147, 'ordem_servico', 122, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:43:52'),
(1148, 'ordem_servico', 124, 'EDITAR', 'prazo', '2026-07-10 07:00:00', '2026-07-31 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:44:30'),
(1149, 'ordem_servico', 124, 'EDITAR', 'prazo', '2026-07-31 07:00:00', '2026-07-31 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:47:42'),
(1150, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1784634547_f61f3567.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:49:07'),
(1151, 'ordem_servico', 122, 'ANEXO', 'arquivo', NULL, 'uploads/os_122_1784634561_e1d22f43.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:49:21'),
(1152, 'ordem_servico', 122, 'EDITAR', 'prazo', '2026-07-31 07:00:00', '2026-07-31 07:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 08:49:29'),
(1153, 'ordem_servico', 129, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:30:33'),
(1154, 'ordem_servico', 129, 'ANEXO', 'arquivo', NULL, 'uploads/os_129_1784637329_0f5e1fc3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:35:29'),
(1155, 'ordem_servico', 129, 'ANEXO', 'arquivo', NULL, 'uploads/os_129_1784637351_bcf9c831.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:35:51'),
(1156, 'ordem_servico', 129, 'ANEXO', 'arquivo', NULL, 'uploads/os_129_1784637365_3fac588c.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:36:05'),
(1157, 'ordem_servico', 129, 'ANEXO', 'arquivo', NULL, 'uploads/os_129_1784637380_ee9558ff.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:36:20'),
(1158, 'ordem_servico', 129, 'ATRIBUIR', 'agente_id', '8', '8', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:36:51'),
(1159, 'ordem_servico', 129, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ATRIBUIDA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:36:51'),
(1160, 'ordem_servico', 129, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 09:36:59'),
(1161, 'cftv_relatorio', 23, 'CRIAR_RELATORIO', NULL, NULL, '2026-07-21 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-21 10:18:50'),
(1162, 'ordem_servico', 130, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:07:10'),
(1163, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784653754_ef6b373a.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:09:14'),
(1164, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784653766_f7d28ca0.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:09:26'),
(1165, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784653779_df4ae5aa.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:09:39'),
(1166, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784653793_860ebcaf.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:09:53'),
(1167, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784653845_ff4f79e6.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:10:45'),
(1168, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784653859_f5bf84b1.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:10:59'),
(1169, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784653873_d973241b.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:11:13'),
(1170, 'ordem_servico', 130, 'EDITAR', 'prazo', '2026-07-24 15:00:00', '2026-07-24 15:00', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-21 14:11:42'),
(1171, 'cftv_ocorrencia', 4, 'ADICIONAR_OCORRENCIA', 'nivel', NULL, 'NIVEL_1', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-21 15:59:47'),
(1172, 'cftv_ocorrencia', 5, 'ADICIONAR_OCORRENCIA', 'nivel', NULL, 'NIVEL_1', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-21 15:59:47'),
(1173, 'cftv_relatorio', 23, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-21 16:01:47'),
(1174, 'cftv_relatorio', 23, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-21 16:01:58'),
(1175, 'cftv_relatorio', 23, 'FINALIZAR_RELATORIO', 'status', 'EM_ANDAMENTO', 'FINALIZADO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-21 17:00:40'),
(1176, 'ordem_servico', 129, 'ANEXO', 'arquivo', NULL, 'uploads/os_129_1784737633_14205639.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-22 13:27:13');
INSERT INTO `auditoria_eventos` (`id`, `entidade`, `entidade_id`, `acao`, `campo`, `valor_anterior`, `valor_novo`, `usuario_id`, `ip`, `user_agent`, `criado_em`) VALUES
(1177, 'ordem_servico', 129, 'ANEXO', 'arquivo', NULL, 'uploads/os_129_1784737649_eac461d7.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-22 13:27:29'),
(1178, 'ordem_servico', 129, 'ALTERAR_STATUS', 'status', 'EM_ANDAMENTO', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-22 13:30:04'),
(1179, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784738809_0586f657.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-22 13:46:49'),
(1180, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784738829_bd60b0e3.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-22 13:47:09'),
(1181, 'ordem_servico', 130, 'ANEXO', 'arquivo', NULL, 'uploads/os_130_1784738855_799797aa.jpeg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-22 13:47:35'),
(1182, 'ordem_servico', 130, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'EM_ANDAMENTO', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-22 13:48:43'),
(1183, 'cftv_relatorio', 24, 'CRIAR_RELATORIO', NULL, NULL, '2026-07-23 DIURNO', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-23 07:11:40'),
(1184, 'cftv_relatorio', 24, 'SALVAR_RELATORIO', NULL, NULL, 'Atualizacao de plantao', 15, '138.219.243.220', 'Mozilla/5.0 (Linux; Android 13; SM-A225M Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 GEOP-SC-PDU-Android', '2026-07-23 07:12:26'),
(1185, 'ordem_servico', 131, 'CRIAR', NULL, NULL, 'OS criada. SLA: 48 horas.', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-23 07:24:40'),
(1186, 'ordem_servico', 131, 'ANEXO', 'arquivo', NULL, 'uploads/os_131_1784802306_9fe3c7cc.png', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-23 07:25:06'),
(1187, 'ordem_servico', 131, 'ANEXO', 'arquivo', NULL, 'uploads/os_131_1784802323_e8c3aeba.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-23 07:25:23'),
(1188, 'ordem_servico', 131, 'ANEXO', 'arquivo', NULL, 'uploads/os_131_1784802346_1f32f240.jpg', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-23 07:25:46'),
(1189, 'ordem_servico', 131, 'ALTERAR_STATUS', 'status', 'ATRIBUIDA', 'ENCERRADA', 2, '138.219.243.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-23 07:27:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 'Elétrica', 'Serviços elétricos', 1, '2026-05-05 10:40:36'),
(2, 'Hidráulica', 'Serviços hidráulicos', 1, '2026-05-05 10:40:36'),
(3, 'Civil', 'Manutenção predial', 1, '2026-05-05 10:40:36'),
(4, 'Climatização', 'Ar condicionado e ventilação', 1, '2026-05-05 10:40:36'),
(5, 'Pintura', 'Pintura e acabamento', 1, '2026-05-05 10:40:36'),
(6, 'Equipamentos', 'Máquinas e equipamentos', 1, '2026-05-05 10:40:36'),
(7, 'Outros', 'Demais demandas', 1, '2026-05-05 10:40:36'),
(8, 'Limpeza', '', 1, '2026-05-13 13:49:08'),
(9, 'Capinar', '', 1, '2026-05-13 13:49:16'),
(10, 'Infraestrutura', '', 1, '2026-05-13 15:42:02'),
(11, 'Manutenção Predial', '', 1, '2026-05-14 11:52:01'),
(12, 'Manutenção Elétrica', '', 1, '2026-05-14 11:52:04'),
(13, 'Manutenção Hidráulica', '', 1, '2026-05-14 11:52:08'),
(14, 'Capinação / Paisagismo ', '', 1, '2026-05-14 11:52:17'),
(15, 'Carpintaria', '', 1, '2026-05-14 11:52:22'),
(16, 'Infiltração ', '', 1, '2026-05-14 16:05:45'),
(17, 'Iluminação', '', 1, '2026-06-03 11:25:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cftv_liberacao_confirmacoes`
--

CREATE TABLE `cftv_liberacao_confirmacoes` (
  `id` int(11) NOT NULL,
  `relatorio_id` int(11) NOT NULL,
  `liberacao_id` int(11) NOT NULL,
  `status_confirmacao` varchar(40) NOT NULL DEFAULT 'PENDENTE',
  `horario_confirmacao` datetime DEFAULT NULL,
  `alteracao_observada` text DEFAULT NULL,
  `confirmado_por` int(11) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cftv_liberacoes`
--

CREATE TABLE `cftv_liberacoes` (
  `id` int(11) NOT NULL,
  `data_acesso` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fim` time DEFAULT NULL,
  `nome_completo` varchar(180) NOT NULL,
  `cpf` varchar(20) NOT NULL,
  `destino_tipo` varchar(20) NOT NULL DEFAULT 'LOJISTA',
  `destino_nome` varchar(180) NOT NULL,
  `motivo` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ATIVA',
  `criado_por` int(11) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_por` int(11) DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  `cancelado_por` int(11) DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cftv_observacoes`
--

CREATE TABLE `cftv_observacoes` (
  `id` int(11) NOT NULL,
  `relatorio_id` int(11) NOT NULL,
  `equipe` varchar(100) NOT NULL,
  `horario` datetime DEFAULT NULL,
  `local` varchar(160) DEFAULT NULL,
  `item` varchar(120) DEFAULT NULL,
  `classificacao` varchar(40) NOT NULL DEFAULT 'INFORMATIVA',
  `descricao` text DEFAULT NULL,
  `tempo_resposta_min` int(11) DEFAULT NULL,
  `providencia` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cftv_observacoes`
--

INSERT INTO `cftv_observacoes` (`id`, `relatorio_id`, `equipe`, `horario`, `local`, `item`, `classificacao`, `descricao`, `tempo_resposta_min`, `providencia`, `criado_em`) VALUES
(1, 3, 'Fiscal de Mall', '2026-05-28 16:42:00', 'Americanas', 'Postura operacional', 'NAO_CONFORMIDADE', 'Final de mall dentro da loja', NULL, '', '2026-05-28 16:37:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cftv_ocorrencias`
--

CREATE TABLE `cftv_ocorrencias` (
  `id` int(11) NOT NULL,
  `relatorio_id` int(11) NOT NULL,
  `horario` datetime DEFAULT NULL,
  `nivel` varchar(20) NOT NULL DEFAULT 'NIVEL_1',
  `tipo` varchar(100) DEFAULT NULL,
  `local` varchar(160) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `envolvidos` text DEFAULT NULL,
  `imagens` text DEFAULT NULL,
  `tempo_resposta_min` int(11) DEFAULT NULL,
  `equipes_acionadas` text DEFAULT NULL,
  `encerramento` text DEFAULT NULL,
  `preservar_imagens` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cftv_ocorrencias`
--

INSERT INTO `cftv_ocorrencias` (`id`, `relatorio_id`, `horario`, `nivel`, `tipo`, `local`, `descricao`, `envolvidos`, `imagens`, `tempo_resposta_min`, `equipes_acionadas`, `encerramento`, `preservar_imagens`, `criado_em`) VALUES
(1, 7, '2026-06-01 09:50:00', 'NIVEL_1', 'Colisão na na cancela.', 'Cancela de entrada 1.1', 'Ônibus não parou a tempo e colidiu na cancela de entrada danificando a mesma e impedindo seu funcionamento.', 'Motorista do ônibus do shopping.', 'Câmera 01/06/2026 hr : 09:50', 1, 'Cláudio(estacionamento) / gustavo (vigilante)', '01/06/2026 às 11:40', 0, '2026-06-01 12:00:58'),
(2, 10, '2026-06-07 14:49:00', 'NIVEL_1', 'Venda de figurinhas na praça de alimentação.', 'Praça de alimentação.', 'Um rapaz foi visto vendendo figurinhas junto ao quiosque que já é especializado para a venda , o proprietário não gostou e chamou o segurança do mall para informar . Com isso Foi informado para o mesmo que estava vendendo que não era permitido à venda, porém  ele relatou conhecer o dono do shopping, alegando que o seu pai era personal trailer do mesmo e que estaria permitido a venda. Dito isso foi solicitado o contato e nome para averiguação.', 'Sandro ( fiscal de mall ) \r\nMatheus ( estava vendendo figurinha )\r\nDono do quiosque de vendas de figurinha', '', 1, 'Sandro (fiscal de mall)', 'Em andamento para apuração.', 0, '2026-06-07 16:59:13'),
(3, 17, '2026-06-29 10:32:00', 'NIVEL_2', 'Ausência de pagamento; Saída pela lateral.', 'Estacionamento.', 'Um casal entrou no shopping passou do horário previsto de permanência gratuita e saiu pela lateral da cancela.', '', '[Nvr 3] cancela entrada 3.2  10:07', NULL, 'Central', '[Nvr 3] Cerca frente.61 10:32', 0, '2026-06-29 17:05:00'),
(4, 23, '2026-07-21 15:48:00', 'NIVEL_1', 'Colisão', 'Cancela de entrada.', 'Um cliente dirigindo uma Ford Ranger placa: QYL 7I06 , tentou adentrar ao shopping, porém durante uma trava , a cancela não abriu e o mesmo deu ré tentando acessar a outra cancela, não percebendo o Ford fiesta placa OYM 2G53 que estava atrás e batendo no mesmo .', 'Cláudio/Gustavo/clientes', '[NVR3]* Cancela de entrada 1.1', 1, 'Cláudio/Gustavo', '', 0, '2026-07-21 15:59:47'),
(5, 23, '2026-07-21 15:48:00', 'NIVEL_1', 'Colisão', 'Cancela de entrada.', 'Um cliente dirigindo uma Ford Ranger placa: QYL 7I06 , tentou adentrar ao shopping, porém durante uma trava , a cancela não abriu e o mesmo deu ré tentando acessar a outra cancela, não percebendo o Ford fiesta placa OYM 2G53 que estava atrás e batendo no mesmo .', 'Cláudio/Gustavo/clientes', '[NVR3]* Cancela de entrada 1.1', 1, 'Cláudio/Gustavo', '', 0, '2026-07-21 15:59:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cftv_relatorios`
--

CREATE TABLE `cftv_relatorios` (
  `id` int(11) NOT NULL,
  `data_plantao` date NOT NULL,
  `turno` varchar(30) NOT NULL DEFAULT 'DIURNO',
  `operador_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'EM_ANDAMENTO',
  `inicio_turno` time DEFAULT NULL,
  `fim_turno` time DEFAULT NULL,
  `passagem_servico` text DEFAULT NULL,
  `cameras_funcionando` tinyint(1) NOT NULL DEFAULT 0,
  `nvrs_gravando` tinyint(1) NOT NULL DEFAULT 0,
  `horario_sincronizado` tinyint(1) NOT NULL DEFAULT 0,
  `radios_ok` tinyint(1) NOT NULL DEFAULT 0,
  `falhas_livro` text DEFAULT NULL,
  `cameras_offline` int(11) NOT NULL DEFAULT 0,
  `rondas_virtuais` int(11) NOT NULL DEFAULT 0,
  `eventos_preventivos` int(11) NOT NULL DEFAULT 0,
  `fluxo_clientes` int(11) NOT NULL DEFAULT 0,
  `tempo_medio_resposta_min` int(11) DEFAULT NULL,
  `areas_prioritarias` text DEFAULT NULL,
  `situacoes_suspeitas` text DEFAULT NULL,
  `equipes_observadas` text DEFAULT NULL,
  `ocorrencias_resumo` text DEFAULT NULL,
  `observacoes_gerais` text DEFAULT NULL,
  `plano_melhoria` text DEFAULT NULL,
  `finalizado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cftv_relatorios`
--

INSERT INTO `cftv_relatorios` (`id`, `data_plantao`, `turno`, `operador_id`, `status`, `inicio_turno`, `fim_turno`, `passagem_servico`, `cameras_funcionando`, `nvrs_gravando`, `horario_sincronizado`, `radios_ok`, `falhas_livro`, `cameras_offline`, `rondas_virtuais`, `eventos_preventivos`, `fluxo_clientes`, `tempo_medio_resposta_min`, `areas_prioritarias`, `situacoes_suspeitas`, `equipes_observadas`, `ocorrencias_resumo`, `observacoes_gerais`, `plano_melhoria`, `finalizado_em`, `criado_em`, `atualizado_em`) VALUES
(1, '2026-05-25', 'DIURNO', 14, 'FINALIZADO', '19:46:00', '14:02:14', NULL, 0, 0, 0, 0, NULL, 0, 0, 0, 0, NULL, 'Entradas e saidas\nPraca de alimentacao\nEstacionamento\nDocas\nCasa de maquinas\nCorredores tecnicos\nAdministracao\nCaixa eletronico\nEscadas de emergencia\nAcessos de funcionarios\nCentral de residuos\nAreas externas', NULL, NULL, NULL, NULL, NULL, '2026-05-26 14:02:14', '2026-05-25 16:46:41', '2026-05-26 14:02:14'),
(2, '2026-05-26', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '14:23:24', '', 0, 0, 0, 0, '', 0, 0, 0, 0, NULL, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '', '', '', '', '', '2026-05-28 14:23:24', '2026-05-26 15:18:05', '2026-05-28 14:23:24'),
(3, '2026-05-28', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:09:24', '5.5', 1, 1, 0, 1, '0', 0, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '', '', '', '', '', '2026-05-28 17:09:24', '2026-05-28 16:27:07', '2026-05-28 17:09:24'),
(4, '2026-05-29', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '16:29:57', '5.5', 1, 1, 0, 1, '0', 0, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '0', '0', '5.5', 'Alguns pontos cegos, onde câmeras não tem visibilidade  de todo o lado externo do shopping.', '2026-05-29 16:29:57', '2026-05-29 09:38:33', '2026-05-29 16:29:57'),
(5, '2026-05-30', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-05-30 17:02:44', '2026-05-30 16:20:08', '2026-05-30 17:02:44'),
(6, '2026-05-31', 'DIURNO', 15, 'FINALIZADO', '10:53:00', '07:53:55', '', 0, 0, 0, 0, '', 0, 0, 0, 0, NULL, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '', '', '', '', '', '2026-06-01 07:53:55', '2026-06-01 07:53:57', '2026-06-01 07:53:55'),
(7, '2026-06-01', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '16:58:53', '5.5', 1, 1, 0, 1, '0', 1, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '', '', '', '', '', '2026-06-01 16:58:53', '2026-06-01 11:43:33', '2026-06-01 16:58:53'),
(8, '2026-06-03', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '07:24:49', '', 1, 1, 0, 1, '0', 1, 0, 0, 0, NULL, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '', '5.5', '', '', '', '2026-06-05 07:24:49', '2026-06-03 13:05:01', '2026-06-05 07:24:49'),
(9, '2026-06-05', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 1, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-06-05 16:55:55', '2026-06-05 07:25:42', '2026-06-05 16:55:55'),
(10, '2026-06-07', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '', '', '', '', '2026-06-07 16:59:38', '2026-06-07 13:27:44', '2026-06-07 16:59:38'),
(11, '2026-06-09', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 3, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-06-09 17:05:02', '2026-06-09 17:03:26', '2026-06-09 17:05:02'),
(12, '2026-06-11', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 3, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-06-11 16:56:27', '2026-06-11 08:11:18', '2026-06-11 16:56:27'),
(13, '2026-06-13', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 3, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-06-13 16:55:25', '2026-06-13 16:53:27', '2026-06-13 16:55:25'),
(14, '2026-06-15', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 3, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-06-15 16:56:55', '2026-06-15 05:20:03', '2026-06-15 16:56:55'),
(15, '2026-06-25', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-06-25 16:30:53', '2026-06-25 13:30:51', '2026-06-25 16:30:53'),
(16, '2026-06-27', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 0, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '', '', '', '', '2026-06-27 17:05:43', '2026-06-27 17:04:42', '2026-06-27 17:05:43'),
(17, '2026-06-29', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '', '', '5.5', '', '', '2026-06-29 17:06:54', '2026-06-29 16:58:18', '2026-06-29 17:06:54'),
(18, '2026-07-01', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '0', '0', '', '2026-07-01 17:02:20', '2026-07-01 17:01:30', '2026-07-01 17:02:20'),
(19, '2026-07-07', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 3, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-07-07 16:17:26', '2026-07-07 11:04:19', '2026-07-07 16:17:26'),
(20, '2026-07-09', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '5.5', '', '2026-07-09 16:57:31', '2026-07-09 16:56:16', '2026-07-09 16:57:31'),
(21, '2026-07-13', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 13, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-07-13 17:08:18', '2026-07-13 17:07:22', '2026-07-13 17:08:18'),
(22, '2026-07-15', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 3, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '5.5', '', '', '', '2026-07-15 16:59:39', '2026-07-15 16:58:53', '2026-07-15 16:59:39'),
(23, '2026-07-21', 'DIURNO', 15, 'FINALIZADO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '', '5.5', 'Colisão no local:\r\nCancela de entrada do meio.', '', '', '2026-07-21 17:00:40', '2026-07-21 10:18:50', '2026-07-21 17:00:40'),
(24, '2026-07-23', 'DIURNO', 15, 'EM_ANDAMENTO', '05:00:00', '17:00:00', '5.5', 1, 1, 0, 1, '0', 2, 0, 0, 0, 1, 'Entradas e saidas\r\nPraca de alimentacao\r\nEstacionamento\r\nDocas\r\nCasa de maquinas\r\nCorredores tecnicos\r\nAdministracao\r\nCaixa eletronico\r\nEscadas de emergencia\r\nAcessos de funcionarios\r\nCentral de residuos\r\nAreas externas', '0', '', '', '', '', NULL, '2026-07-23 07:11:40', '2026-07-23 07:12:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cftv_tokens_edicao`
--

CREATE TABLE `cftv_tokens_edicao` (
  `id` int(11) NOT NULL,
  `relatorio_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `gerado_por` int(11) NOT NULL,
  `usado_por` int(11) DEFAULT NULL,
  `usado_em` datetime DEFAULT NULL,
  `expira_em` datetime NOT NULL,
  `motivo` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_conversas`
--

CREATE TABLE `chat_conversas` (
  `id` int(11) NOT NULL,
  `usuario_1_id` int(11) NOT NULL,
  `usuario_2_id` int(11) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `chat_conversas`
--

INSERT INTO `chat_conversas` (`id`, `usuario_1_id`, `usuario_2_id`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 2, '2026-06-02 14:02:17', '2026-06-19 07:35:46'),
(2, 1, 5, '2026-06-02 14:03:09', '2026-06-02 14:03:09'),
(3, 2, 15, '2026-06-02 14:07:45', '2026-06-02 14:07:45'),
(4, 2, 11, '2026-06-02 14:07:49', '2026-06-02 14:07:49'),
(5, 2, 10, '2026-06-02 14:07:50', '2026-06-02 14:07:50'),
(6, 1, 10, '2026-06-02 14:15:21', '2026-06-02 14:15:21'),
(7, 1, 14, '2026-06-02 14:15:23', '2026-06-02 14:15:23'),
(8, 1, 12, '2026-06-02 14:22:02', '2026-06-02 14:22:02'),
(9, 1, 7, '2026-06-02 14:22:03', '2026-06-02 14:22:03'),
(10, 1, 8, '2026-06-02 14:22:05', '2026-06-02 14:22:05'),
(11, 1, 11, '2026-06-02 14:22:06', '2026-06-02 14:22:06'),
(12, 1, 6, '2026-06-02 14:22:06', '2026-06-02 14:22:06'),
(13, 1, 9, '2026-06-02 14:22:07', '2026-06-02 14:22:07'),
(14, 1, 4, '2026-06-02 14:22:08', '2026-06-02 14:22:08'),
(15, 2, 7, '2026-06-19 08:07:01', '2026-06-19 08:07:01'),
(16, 2, 12, '2026-06-19 08:07:02', '2026-06-19 08:07:02'),
(17, 2, 8, '2026-06-19 08:07:04', '2026-06-19 08:07:04'),
(18, 1, 32, '2026-07-15 15:39:54', '2026-07-15 21:51:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_mensagens`
--

CREATE TABLE `chat_mensagens` (
  `id` int(11) NOT NULL,
  `conversa_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `lida_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `chat_mensagens`
--

INSERT INTO `chat_mensagens` (`id`, `conversa_id`, `remetente_id`, `destinatario_id`, `mensagem`, `lida_em`, `criado_em`) VALUES
(1, 1, 1, 2, 'Testando chat em tempo real!', '2026-06-02 14:22:25', '2026-06-02 14:02:32'),
(2, 1, 1, 2, 'Me envia por favor o nome do pessoal da limpeza pra inserir no sistema!', '2026-06-02 14:22:25', '2026-06-02 14:05:36'),
(3, 1, 1, 2, 'opa', '2026-06-19 07:35:00', '2026-06-02 14:28:29'),
(4, 1, 2, 1, 'Bom dia, Júlio', '2026-06-19 08:55:11', '2026-06-19 07:35:18'),
(5, 1, 2, 1, 'Tudo bem?', '2026-06-19 08:55:11', '2026-06-19 07:35:22'),
(6, 1, 2, 1, 'Precisando do token para gerar Relatório', '2026-06-19 08:55:11', '2026-06-19 07:35:46'),
(7, 18, 1, 32, 'Boa Tarde!', NULL, '2026-07-15 15:40:01'),
(8, 18, 1, 32, 'oi', NULL, '2026-07-15 21:51:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cronograma_agentes`
--

CREATE TABLE `cronograma_agentes` (
  `id` int(11) NOT NULL,
  `agente_id` int(11) NOT NULL,
  `gestor_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `local_id` int(11) DEFAULT NULL,
  `dia_semana` tinyint(4) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `semana_inicio` date NOT NULL,
  `semana_fim` date NOT NULL,
  `status` enum('PLANEJADO','EM_ANDAMENTO','CONCLUIDO','CANCELADO') DEFAULT 'PLANEJADO',
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque_materiais`
--

CREATE TABLE `estoque_materiais` (
  `id` int(11) NOT NULL,
  `nome` varchar(160) NOT NULL,
  `unidade` varchar(30) DEFAULT NULL,
  `quantidade_atual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estoque_minimo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `custo_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `estoque_materiais`
--

INSERT INTO `estoque_materiais` (`id`, `nome`, `unidade`, `quantidade_atual`, `estoque_minimo`, `custo_unitario`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'ESSÊNCIA 100ML – FLORAROME – COCO BRASIL', 'UND', 5.00, 1.00, 9.90, 1, '2026-05-28 10:13:37', '2026-05-28 10:14:27'),
(2, 'ESSÊNCIA 100ML – FLORAROME – GABATINE', 'UND', 5.00, 1.00, 9.90, 1, '2026-05-28 10:15:11', NULL),
(3, 'INSETICIDA AEROSSOL 300ML – KELLDRIN', 'UND', 3.00, 1.00, 13.94, 1, '2026-05-28 10:15:51', NULL),
(4, 'PANO ALVEJADO- FIATEC – 60G', 'UND', 5.00, 1.00, 3.14, 1, '2026-05-28 10:19:57', NULL),
(5, 'ESPONJA DUPLA FACE – NOBRE VERDE AMARELA', 'UND', 8.00, 1.00, 1.07, 1, '2026-05-28 10:20:29', NULL),
(6, 'LUVA NEOPREX – G – KALIPSO', 'UND', 6.00, 1.00, 12.25, 1, '2026-05-28 10:21:49', NULL),
(7, 'DESENGRAXANTE 5L – VALENÇA', 'UND', 1.00, 1.00, 34.25, 1, '2026-05-28 10:22:17', NULL),
(8, 'DETERGENTE LÍQUIDO 5L – VALENÇA LIMPA PISO', 'UND', 3.00, 1.00, 18.53, 1, '2026-05-28 10:22:45', NULL),
(9, 'CLORO 5L – VALENÇA – 5%', 'UND', 8.00, 1.00, 26.16, 1, '2026-05-28 10:23:12', NULL),
(10, 'PANO MULTIUSO ROLO – MEDIX – 28 CM X 240M – 600 PANOS – AZUL', '', 1.00, 1.00, 112.26, 1, '2026-05-28 10:24:05', NULL),
(11, 'REFIL MOP UMIDO 190G – ALGODÃO – C/ ROSCA STAR CLEAN', 'UND', 9.00, 1.00, 16.83, 1, '2026-05-28 10:24:41', NULL),
(12, 'SACO DE LIXO 60L – PRETO – LEVE COM 100UND', '', 7.00, 1.00, 14.74, 1, '2026-05-28 10:25:01', NULL),
(13, 'SACO DE LIXO 100L – PRETO – LEVE COM 100UND', 'UND', 9.00, 1.00, 27.02, 1, '2026-05-28 10:25:25', NULL),
(14, 'SACO DE LIXO 200L – PRETO – LEVE COM 100UND', 'UND', 10.00, 1.00, 38.72, 1, '2026-05-28 10:25:56', NULL),
(15, 'SACO DE LIXO 450L – PRETO – LEVE COM 50UND', '', 6.00, 1.00, 39.87, 1, '2026-05-28 10:26:20', NULL),
(16, 'LUSTRA MÓVEIS 200ML – LAVANDA – FACILITA', 'UND', 2.00, 1.00, 5.98, 1, '2026-05-28 10:28:25', NULL),
(17, 'VASSOURA PLACAVA N03 SEM CABO', '', 2.00, 1.00, 10.90, 1, '2026-05-28 10:28:49', NULL),
(18, 'CABO DE MADEIRA 1,2M – REVESTIDO COM ROSCA', 'UND', 2.00, 1.00, 4.72, 1, '2026-05-28 10:30:38', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `gestor_tokens`
--

CREATE TABLE `gestor_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `target` varchar(50) NOT NULL,
  `valido_ate` datetime NOT NULL,
  `criado_por` int(11) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `gestor_tokens`
--

INSERT INTO `gestor_tokens` (`id`, `token`, `target`, `valido_ate`, `criado_por`, `criado_em`) VALUES
(1, '4e152879462dd8eb', 'GESTOR_REPORTS', '2026-05-19 18:36:19', 1, '2026-05-19 15:21:19'),
(2, '26981ad596750672', 'GESTOR_REPORTS', '2026-06-01 13:06:16', 1, '2026-06-01 09:51:16'),
(3, 'b24bf3baf9af117b', 'GESTOR_REPORTS', '2026-06-17 16:47:47', 1, '2026-06-17 13:32:47'),
(4, '794281ec9ef1de1d', 'GESTOR_REPORTS', '2026-06-18 17:53:11', 1, '2026-06-18 14:38:11'),
(5, '2059310448b490ce', 'GESTOR_REPORTS', '2026-06-19 12:10:03', 1, '2026-06-19 08:55:03'),
(6, '481bae88d2db0203', 'GESTOR_REPORTS', '2026-07-21 11:02:11', 1, '2026-07-21 10:47:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_os`
--

CREATE TABLE `historico_os` (
  `id` int(11) NOT NULL,
  `ordem_servico_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status_anterior` varchar(50) DEFAULT NULL,
  `status_novo` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `historico_os`
--

INSERT INTO `historico_os` (`id`, `ordem_servico_id`, `usuario_id`, `status_anterior`, `status_novo`, `descricao`, `criado_em`) VALUES
(4, 3, 2, NULL, 'ABERTA', 'OS criada pelo gestor/TI.', '2026-05-12 14:33:26'),
(5, 3, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-12 14:34:33'),
(6, 3, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-12 14:35:04'),
(7, 4, 2, NULL, 'ABERTA', 'OS criada pelo gestor/TI.', '2026-05-12 14:54:43'),
(8, 4, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-12 14:56:17'),
(9, 4, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-12 14:56:45'),
(11, 4, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-12 15:01:42'),
(12, 4, 1, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-12 15:17:39'),
(13, 3, 1, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-12 15:17:51'),
(14, 4, 2, 'ATRIBUIDA', 'ABERTA', 'Status atualizado.', '2026-05-12 15:22:05'),
(15, 4, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-12 15:22:35'),
(16, 4, 1, 'ATRIBUIDA', 'ABERTA', 'Responsável alterado.', '2026-05-12 15:22:40'),
(17, 3, 1, 'ATRIBUIDA', 'ABERTA', 'Responsável alterado.', '2026-05-12 15:22:45'),
(18, 4, 1, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-12 15:24:28'),
(19, 3, 1, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-12 15:24:40'),
(20, 4, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-13 12:58:45'),
(21, 5, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-13 14:56:52'),
(22, 6, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-13 15:13:15'),
(23, 6, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-13 15:16:03'),
(24, 7, 1, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-13 15:40:46'),
(25, 7, 1, 'ATRIBUIDA', 'ENCERRADA', 'instalação concluída', '2026-05-13 15:41:04'),
(26, 7, 1, 'ENCERRADA', 'ENCERRADA', 'OS editada. Campos alterados: Local, Categoria.', '2026-05-13 15:42:21'),
(27, 8, 1, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-13 15:43:27'),
(28, 8, 1, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-13 15:43:31'),
(29, 5, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-14 07:26:01'),
(30, 6, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-05-14 07:26:20'),
(31, 6, 2, 'PAUSADA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-14 07:26:40'),
(32, 4, 2, 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-05-14 07:27:00'),
(33, 3, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-14 07:27:32'),
(34, 5, 2, 'ENCERRADA', 'ENCERRADA', 'Anexo enviado.', '2026-05-14 14:50:52'),
(35, 5, 2, 'ENCERRADA', 'ENCERRADA', 'Anexo enviado.', '2026-05-14 14:52:11'),
(36, 5, 2, 'ENCERRADA', 'ENCERRADA', 'Status atualizado.', '2026-05-14 14:52:39'),
(37, 5, 2, 'ENCERRADA', 'ENCERRADA', '- Foram trocadas 02 (duas) Lâmpadas Nitrolux 9W', '2026-05-14 14:54:10'),
(38, 5, 2, 'ENCERRADA', 'ENCERRADA', 'OS editada. Campos alterados: Título.', '2026-05-14 14:54:54'),
(39, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', '- Foi pintada da data de hoje (14), 02 colunas do Mall.', '2026-05-14 15:01:31'),
(40, 9, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-14 15:04:39'),
(41, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:05:45'),
(42, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:06:41'),
(43, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-14 15:07:04'),
(44, 8, 1, 'ENCERRADA', 'ENCERRADA', 'Retirada de roteador do espaço cliente onde ira se instalar driftpark.', '2026-05-14 15:08:41'),
(45, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:15:55'),
(46, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:17:12'),
(47, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:17:47'),
(48, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:18:19'),
(49, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-14 15:20:04'),
(50, 9, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Tinta Piso', '2026-05-14 15:23:06'),
(51, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:36:07'),
(52, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:36:32'),
(53, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:37:03'),
(54, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:37:29'),
(55, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:38:28'),
(56, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:39:24'),
(57, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:39:52'),
(58, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:40:19'),
(59, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:50:26'),
(60, 6, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-14 15:51:48'),
(61, 6, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-05-14 15:52:36'),
(62, 9, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-14 15:53:21'),
(63, 10, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-14 15:56:30'),
(64, 10, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:59:00'),
(65, 10, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:59:25'),
(66, 10, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 15:59:51'),
(67, 10, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 16:00:23'),
(68, 10, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 16:00:51'),
(69, 10, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-14 16:02:45'),
(70, 10, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-14 16:03:21'),
(71, 10, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-14 16:05:49'),
(72, 11, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-14 16:29:22'),
(73, 12, 2, NULL, 'ABERTA', 'OS criada pelo gestor/TI.', '2026-05-14 16:34:17'),
(74, 4, 2, 'AGUARDANDO_MATERIAL', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-14 16:36:16'),
(75, 11, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-14 16:38:46'),
(76, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-14 16:44:03'),
(77, 13, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-15 07:19:49'),
(78, 13, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 07:20:30'),
(79, 13, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 07:21:12'),
(80, 13, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 07:21:37'),
(81, 13, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-15 07:21:55'),
(82, 13, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-15 07:24:42'),
(83, 13, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-15 07:24:59'),
(84, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Faltando a instalação de 04 (quatros) luminárias - 03 Três no Vestuário Feminino e 01 (uma) corredor da praça. As mesmas não foram instaladas por conta de uns parafusos que não couberam. Estaremos instalando hoje (15.05.2026)', '2026-05-15 08:16:45'),
(85, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-15 08:16:56'),
(86, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:17:29'),
(87, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:18:09'),
(88, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:20:52'),
(89, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:21:35'),
(90, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:22:10'),
(91, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:23:36'),
(92, 4, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:25:16'),
(93, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:30:35'),
(94, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 08:31:00'),
(95, 13, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 11:24:25'),
(96, 13, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 11:25:18'),
(97, 13, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-15 11:25:52'),
(98, 13, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-05-15 11:26:43'),
(99, 13, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-15 11:26:48'),
(100, 14, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-15 11:38:47'),
(101, 14, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 11:40:17'),
(102, 14, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 11:40:42'),
(103, 14, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 11:41:48'),
(104, 14, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 11:44:05'),
(105, 14, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 11:44:53'),
(106, 14, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-15 11:50:33'),
(107, 15, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-15 11:52:48'),
(108, 15, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-15 11:54:21'),
(109, 15, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-15 14:52:38'),
(110, 16, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-18 09:20:00'),
(111, 16, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 10:35:02'),
(112, 16, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 10:35:28'),
(113, 16, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 10:37:30'),
(114, 16, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-18 10:38:17'),
(115, 16, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-18 10:38:22'),
(116, 16, 2, 'ENCERRADA', 'ENCERRADA', 'Status atualizado.', '2026-05-18 10:38:22'),
(117, 16, 2, 'ENCERRADA', 'ENCERRADA', 'Status atualizado.', '2026-05-18 10:38:25'),
(118, 4, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-05-18 10:39:09'),
(119, 17, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-18 10:41:15'),
(120, 17, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 10:42:33'),
(121, 17, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 10:43:53'),
(122, 17, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 10:44:28'),
(123, 17, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-18 10:45:03'),
(124, 17, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-18 10:45:53'),
(125, 17, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-18 10:57:21'),
(126, 15, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-18 10:57:48'),
(127, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-18 11:00:17'),
(128, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-18 11:00:44'),
(129, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-18 11:02:19'),
(130, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-18 11:03:22'),
(131, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-18 11:03:47'),
(132, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-18 11:04:23'),
(133, 13, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-05-18 11:07:39'),
(134, 18, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-18 15:13:01'),
(135, 19, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-18 15:21:23'),
(136, 19, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-05-18 15:23:44'),
(137, 19, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 15:25:35'),
(138, 19, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 15:26:31'),
(139, 19, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 15:26:57'),
(140, 19, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 15:27:41'),
(141, 19, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 15:28:39'),
(142, 19, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-18 15:30:02'),
(143, 20, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-18 16:04:51'),
(144, 21, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-18 16:07:15'),
(145, 22, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-18 16:38:10'),
(146, 23, 2, NULL, 'ABERTA', 'OS criada pelo gestor/TI.', '2026-05-18 16:57:14'),
(147, 23, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-18 16:58:33'),
(148, 23, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-18 17:00:54'),
(149, 23, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-18 17:02:35'),
(150, 18, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 07:21:00'),
(151, 18, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 07:24:57'),
(152, 18, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 07:25:39'),
(153, 18, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 07:26:10'),
(154, 18, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-19 07:26:37'),
(155, 24, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-19 07:30:56'),
(156, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Lâmpada NITROLUX 9W E27 6500K', '2026-05-19 07:37:05'),
(157, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-19 07:37:33'),
(158, 24, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-19 09:05:51'),
(159, 23, 1, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-19 09:06:08'),
(160, 12, 1, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-19 09:06:20'),
(161, 7, 7, 'ENCERRADA', 'ABERTA', 'Status atualizado.', '2026-05-19 10:08:28'),
(162, 7, 7, 'ABERTA', 'ENCERRADA', 'Status atualizado.', '2026-05-19 10:08:43'),
(163, 7, 7, 'ENCERRADA', 'ENCERRADA', 'Tentativa de reabertura bloqueada. Senha/login do TI inválido ou não informado.', '2026-05-19 10:15:48'),
(164, 7, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-05-19 10:28:09'),
(165, 7, 7, 'ENCERRADA', 'ABERTA', 'Teste', '2026-05-19 10:28:25'),
(166, 7, 1, 'ABERTA', 'ENCERRADA', 'Status atualizado.', '2026-05-19 10:28:38'),
(167, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:39:14'),
(168, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:39:44'),
(169, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:40:25'),
(170, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:41:14'),
(171, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:41:44'),
(172, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:42:37'),
(173, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:43:02'),
(174, 21, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:43:29'),
(175, 21, 2, 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-05-19 11:43:56'),
(176, 20, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:46:17'),
(177, 20, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:47:16'),
(178, 20, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:47:41'),
(179, 20, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:49:21'),
(180, 20, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:49:58'),
(181, 20, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:50:24'),
(182, 20, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-19 11:51:13'),
(183, 19, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-19 11:52:41'),
(184, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 11:54:03'),
(185, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 11:55:27'),
(186, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-19 11:56:31'),
(187, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:58:20'),
(188, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:59:04'),
(189, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 11:59:29'),
(190, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 12:00:25'),
(191, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 12:01:07'),
(192, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 12:01:37'),
(193, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 12:02:21'),
(194, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-19 12:03:04'),
(195, 24, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-19 12:03:35'),
(196, 24, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-19 12:04:44'),
(197, 23, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-19 12:04:54'),
(198, 22, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-19 12:05:04'),
(199, 25, 2, NULL, 'ATRIBUIDA', 'OS criada pelo gestor/TI.', '2026-05-19 12:17:09'),
(200, 20, 1, 'ENCERRADA', 'ENCERRADA', 'Foto movida de FOTO ANTES para FOTO DEPOIS.', '2026-05-19 12:25:40'),
(201, 4, 1, 'ENCERRADA', 'ENCERRADA', 'Foto movida de FOTO ANTES para FOTO DEPOIS.', '2026-05-19 12:36:25'),
(202, 21, 1, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'OS editada. Campos alterados: Prazo.', '2026-05-19 13:49:38'),
(203, 3, 1, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-05-19 13:50:08'),
(204, 24, 1, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-05-19 15:31:58'),
(205, 22, 1, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Título, Descrição, Prazo.', '2026-05-19 15:32:23'),
(206, 26, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 24 horas.', '2026-05-19 15:49:42'),
(207, 23, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 16:25:26'),
(208, 23, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 16:26:26'),
(209, 23, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 16:27:40'),
(210, 23, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 16:28:15'),
(211, 23, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 16:28:45'),
(212, 23, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-19 16:30:28'),
(213, 23, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-19 16:30:44'),
(214, 27, 1, NULL, 'ATRIBUIDA', 'OS gerada automaticamente por manutencao preventiva atribuida a agente.', '2026-05-19 16:36:25'),
(215, 27, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição, Prazo.', '2026-05-19 16:38:31'),
(216, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-05-20 07:20:17'),
(217, 25, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-20 07:36:16'),
(218, 28, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-20 07:44:58'),
(219, 28, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 07:55:16'),
(220, 28, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 07:56:01'),
(221, 28, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 07:56:39'),
(222, 28, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-20 07:56:54'),
(223, 28, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-20 07:57:09'),
(224, 26, 2, 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-05-20 07:59:01'),
(225, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-05-20 07:59:01'),
(226, 29, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-20 09:07:41'),
(227, 30, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-20 09:09:47'),
(228, 30, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 09:12:42'),
(229, 30, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 09:13:30'),
(230, 30, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-20 09:13:44'),
(231, 30, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-20 09:13:53'),
(232, 31, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-20 09:17:05'),
(233, 32, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-05-20 13:15:32'),
(234, 33, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-20 15:12:42'),
(235, 33, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 15:14:58'),
(236, 33, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 15:15:26'),
(237, 33, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-20 15:20:27'),
(238, 33, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-20 15:20:36'),
(239, 34, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-20 15:29:21'),
(240, 34, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-20 15:30:09'),
(241, 34, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-20 15:30:18'),
(242, 34, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-20 15:30:24'),
(243, 23, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-05-21 08:15:20'),
(244, 29, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 08:16:34'),
(245, 29, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 08:22:38'),
(246, 29, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-21 08:23:12'),
(247, 29, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-21 08:24:13'),
(248, 35, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-21 11:32:40'),
(249, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:34:49'),
(250, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:35:19'),
(251, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:36:17'),
(252, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:42:15'),
(253, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:43:51'),
(254, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:44:30'),
(255, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:44:56'),
(256, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:47:48'),
(257, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:49:44'),
(258, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:50:14'),
(259, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:50:53'),
(260, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-21 11:51:28'),
(261, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-21 11:52:22'),
(262, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'CORREÇÃO DE VAZAMENTO E CARGA DE GÁS, EQUIPAMENTO  CENTRAL DUTADO DE 180.000 BTUS, CONSIDERAR 10KG DE  GÁS PARA REALIZAR A CARGA COMPLETA E USO DE  NITROGÊNIO', '2026-05-21 11:54:43'),
(263, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 11:56:55'),
(264, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 11:57:18'),
(265, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 11:57:46'),
(266, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 11:58:07'),
(267, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 11:58:32'),
(268, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 11:58:57'),
(269, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:03:37'),
(270, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:04:19'),
(271, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:04:50'),
(272, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:12:31'),
(273, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:13:36'),
(274, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:14:08'),
(275, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:14:38'),
(276, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 12:15:11'),
(277, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-05-21 12:15:23'),
(278, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-21 12:17:27'),
(279, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-21 12:19:11'),
(280, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-05-21 12:19:54'),
(281, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-21 12:20:04'),
(282, 32, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:21:10'),
(283, 32, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:24:46'),
(284, 32, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:25:13'),
(285, 32, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-21 12:25:30'),
(286, 36, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-05-21 12:30:36'),
(287, 36, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:35:14'),
(288, 36, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:35:43'),
(289, 36, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-21 12:35:50'),
(290, 36, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-21 12:36:12'),
(291, 37, 2, NULL, 'ABERTA', 'OS criada. SLA: 24 horas.', '2026-05-21 12:38:29'),
(292, 38, 2, NULL, 'ABERTA', 'OS criada. SLA: 24 horas.', '2026-05-21 12:42:35'),
(293, 38, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:43:23'),
(294, 38, 2, 'ABERTA', 'ABERTA', 'Foto movida de FOTO DEPOIS para FOTO ANTES.', '2026-05-21 12:43:28'),
(295, 38, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:43:57'),
(296, 38, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-21 12:44:05'),
(297, 39, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-05-21 12:44:47'),
(298, 40, 2, NULL, 'ABERTA', 'OS criada. SLA: 24 horas.', '2026-05-21 12:46:52'),
(299, 41, 2, NULL, 'ABERTA', 'OS criada. SLA: 24 horas.', '2026-05-21 12:47:33'),
(300, 41, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-21 12:48:10'),
(301, 39, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-21 12:49:03'),
(302, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 14:48:56'),
(303, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 14:49:27'),
(304, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 14:51:10'),
(305, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 14:52:25'),
(306, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 14:52:51'),
(307, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 14:53:24'),
(308, 26, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Anexo enviado.', '2026-05-21 14:54:08'),
(309, 26, 2, 'AGUARDANDO_MATERIAL', 'ENCERRADA', 'Status atualizado.', '2026-05-21 14:56:11'),
(310, 42, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-22 07:17:28'),
(311, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 07:21:44'),
(312, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 07:22:19'),
(313, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 07:22:45'),
(314, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 07:24:02'),
(315, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'No deslocamento um 01 (uma) porta de vidro, a mesma quebro, causando alguns arranhões no auxiliares de limpeza.', '2026-05-22 07:27:35'),
(316, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 07:27:56'),
(317, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 07:28:29'),
(318, 42, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 07:29:14'),
(319, 42, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-22 07:31:52'),
(320, 41, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-22 07:32:21'),
(321, 41, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-05-22 07:32:34'),
(322, 41, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-22 07:33:10'),
(323, 40, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-22 07:33:20'),
(324, 39, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-22 07:33:41'),
(325, 38, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-22 07:33:57'),
(326, 37, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-22 07:34:06'),
(327, 36, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-22 07:34:16'),
(328, 32, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-22 07:34:30'),
(329, 43, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-05-22 07:36:44'),
(330, 43, 2, 'ABERTA', 'ABERTA', 'Material registrado: Fechadura La Fonte', '2026-05-22 07:40:40'),
(331, 43, 2, 'ABERTA', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-05-22 07:40:51'),
(332, 44, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-05-22 07:44:12'),
(333, 44, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-22 07:44:45'),
(334, 44, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-05-22 07:45:16'),
(335, 44, 2, 'ABERTA', 'ABERTA', 'OS editada. Campos alterados: Descrição.', '2026-05-22 07:46:04'),
(336, 41, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-05-22 09:02:32'),
(337, 40, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-05-22 09:02:49'),
(338, 39, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-05-22 09:03:02'),
(339, 38, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-05-22 09:03:15'),
(340, 37, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-05-22 09:03:34'),
(341, 36, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-05-22 09:03:47'),
(342, 24, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-05-22 09:45:39'),
(343, 45, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-22 10:04:45'),
(344, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 10:07:43'),
(345, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 10:07:58'),
(346, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 10:08:20'),
(347, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 10:08:31'),
(348, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-05-22 10:26:25'),
(349, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 10:27:43'),
(350, 46, 1, NULL, 'ATRIBUIDA', 'OS criada. SLA: 4 horas.', '2026-05-22 15:33:05'),
(351, 46, 1, 'ATRIBUIDA', 'ENCERRADA', 'Foram feitas as trocas e devidos testes!', '2026-05-22 15:33:38'),
(352, 47, 1, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-22 15:39:14'),
(353, 47, 1, 'ATRIBUIDA', 'ENCERRADA', 'Troca feita testes executados', '2026-05-22 15:39:29'),
(354, 46, 1, 'ENCERRADA', 'ENCERRADA', 'OS editada. Campos alterados: Categoria.', '2026-05-22 15:39:50'),
(355, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 16:02:20'),
(356, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 16:02:56'),
(357, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 16:03:12'),
(358, 35, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-22 16:03:30'),
(359, 35, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-22 16:03:50'),
(360, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:26:38'),
(361, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:26:59'),
(362, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Usamos material que tínhamos guardado', '2026-05-25 10:27:48'),
(363, 45, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Usamos material que tínhamos guardado', '2026-05-25 10:27:49'),
(364, 45, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-25 10:27:58'),
(365, 48, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-25 10:32:54'),
(366, 48, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:33:26'),
(367, 48, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:33:51'),
(368, 48, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:34:08'),
(369, 48, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:34:21'),
(370, 48, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:34:33'),
(371, 48, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-25 10:34:47'),
(372, 49, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-25 10:35:46'),
(373, 49, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:35:59'),
(374, 49, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:36:12'),
(375, 49, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:36:24'),
(376, 49, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 10:36:40'),
(377, 49, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-25 10:36:53'),
(378, 50, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 24 horas.', '2026-05-25 14:13:11'),
(379, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 14:13:31'),
(380, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 14:13:53'),
(381, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 14:14:06'),
(382, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 14:14:27'),
(383, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-25 14:14:36'),
(384, 51, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-25 15:11:16'),
(385, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-05-25 15:13:47'),
(386, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 15:57:28'),
(387, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-25 15:57:38'),
(388, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-25 15:57:48'),
(389, 52, 1, NULL, 'ABERTA', 'OS criada. SLA: 72 horas.', '2026-05-25 16:15:34'),
(390, 52, 1, 'ABERTA', 'ENCERRADA', 'Feira\r\n', '2026-05-25 16:15:53'),
(391, 52, 1, 'ENCERRADA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-25 16:16:00'),
(392, 52, 1, 'ATRIBUIDA', 'ENCERRADA', 'troca feita', '2026-05-25 16:16:15'),
(393, 31, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 09:31:17'),
(394, 31, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 09:32:00'),
(395, 31, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 09:32:38'),
(396, 31, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-26 09:32:55'),
(397, 31, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-26 09:33:06'),
(398, 53, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-26 10:15:23'),
(399, 54, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-26 12:33:17'),
(400, 54, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 12:53:06'),
(401, 54, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-26 12:53:52'),
(402, 55, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-26 12:58:46'),
(403, 55, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 13:00:20'),
(404, 22, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-05-26 14:33:36'),
(405, 12, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-05-26 14:35:04'),
(406, 56, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-26 15:44:08'),
(407, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:47:45'),
(408, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:49:22'),
(409, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:49:42'),
(410, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:50:06'),
(411, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:50:22'),
(412, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:50:40'),
(413, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:51:06'),
(414, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foto movida de FOTO DEPOIS para FOTO ANTES.', '2026-05-26 15:51:25'),
(415, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:52:28'),
(416, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:53:02'),
(417, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:53:58'),
(418, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-26 15:54:26'),
(419, 56, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-05-26 15:55:05'),
(420, 56, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-26 15:55:14'),
(421, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:25:49'),
(422, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:26:08'),
(423, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:26:24'),
(424, 51, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-27 07:26:37'),
(425, 51, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-27 07:26:54'),
(426, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:40:35'),
(427, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:40:50'),
(428, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:41:19'),
(429, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:41:42'),
(430, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:42:10'),
(431, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:42:39'),
(432, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-27 07:42:49'),
(433, 53, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-27 07:42:56'),
(434, 53, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-27 07:43:03'),
(435, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:49:27'),
(436, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:49:48'),
(437, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:50:15'),
(438, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:50:29'),
(439, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:54:14'),
(440, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 07:55:36'),
(441, 50, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-27 07:56:42'),
(442, 50, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-27 07:56:54'),
(443, 57, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-27 08:01:43'),
(444, 58, 1, NULL, 'ABERTA', 'OS criada. SLA: 24 horas.', '2026-05-27 08:04:35'),
(445, 57, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:06:20'),
(446, 57, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:06:32'),
(447, 57, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:06:51'),
(448, 57, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:07:07'),
(449, 57, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-27 08:07:46'),
(450, 57, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-05-27 08:07:51'),
(451, 59, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-27 08:54:45'),
(452, 59, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:56:40'),
(453, 59, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:56:53'),
(454, 59, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:57:13'),
(455, 59, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-27 08:57:31'),
(456, 59, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-27 08:57:45'),
(457, 60, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-27 15:45:48'),
(458, 61, 1, NULL, 'ABERTA', 'OS criada a partir do chamado T260528-D97B1E.', '2026-05-28 09:18:29'),
(459, 54, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 09:21:47'),
(460, 54, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-28 09:21:54'),
(461, 12, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 09:24:41'),
(462, 12, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 09:27:15'),
(463, 12, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-28 09:28:47'),
(464, 12, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-05-28 09:32:54'),
(465, 12, 2, 'ENCERRADA', 'ABERTA', 'Substituição do Agente', '2026-05-28 09:34:13'),
(466, 12, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-28 09:34:21'),
(467, 12, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-28 09:34:30'),
(468, 11, 2, 'EM_ANDAMENTO', 'PAUSADA', 'O colaborador Moab Vieira, não estava se sentido bem, foi liberado para a unidade de emergência na data de Ontem, dia 27.05.2056, onde foi medicado e afastado das atividade por três dias.', '2026-05-28 10:00:52'),
(469, 62, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-28 15:34:57'),
(470, 62, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 15:37:28'),
(471, 63, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-28 16:52:13'),
(472, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 16:53:20'),
(473, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 16:53:36'),
(474, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 16:53:50'),
(475, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 16:54:04'),
(476, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 16:54:19'),
(477, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-28 16:54:31'),
(478, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-28 16:54:43'),
(479, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado.', '2026-05-28 16:54:45'),
(480, 63, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-28 16:55:37'),
(481, 63, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-28 16:55:50'),
(482, 11, 2, 'PAUSADA', 'PAUSADA', 'OS editada. Campos alterados: Prazo.', '2026-05-29 07:15:30'),
(483, 64, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-05-29 07:21:06'),
(484, 55, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-29 07:26:30'),
(485, 55, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-29 07:26:50'),
(486, 62, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-29 07:27:33'),
(487, 62, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'O serviço foi realizado com sucesso.', '2026-05-29 07:28:07'),
(488, 62, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-05-29 07:28:13'),
(489, 57, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-05-29 07:28:56'),
(490, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-29 07:35:57'),
(491, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-29 07:36:08'),
(492, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-29 07:36:21'),
(493, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-05-29 07:36:34'),
(494, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-05-29 07:36:44'),
(495, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:18:11'),
(496, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:18:28'),
(497, 64, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-01 07:18:35'),
(498, 64, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-01 07:18:44'),
(499, 65, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-01 07:24:03'),
(500, 65, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:24:20'),
(501, 65, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:24:33'),
(502, 65, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:25:29'),
(503, 65, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:25:47'),
(504, 65, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-01 07:25:52'),
(505, 65, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Capinação realizada!', '2026-06-01 07:26:05'),
(506, 65, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-01 07:26:15'),
(507, 66, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-01 07:28:29'),
(508, 66, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:29:25'),
(509, 66, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:29:41'),
(510, 66, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:30:00'),
(511, 66, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:30:15'),
(512, 66, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 07:30:32'),
(513, 66, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Capinação realizada!!!', '2026-06-01 07:30:47'),
(514, 66, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-01 07:30:56'),
(515, 67, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-06-01 07:33:39'),
(516, 67, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado.', '2026-06-01 07:55:02'),
(517, 68, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-01 09:13:56'),
(518, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 09:14:52'),
(519, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 09:15:05'),
(520, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 09:15:19'),
(521, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-06-01 09:16:16'),
(522, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-01 09:16:30'),
(523, 21, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'OS editada. Campos alterados: Prazo.', '2026-06-01 09:17:24'),
(524, 69, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-01 09:21:00'),
(525, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Lâmpada Slim 120cm 36w 6500k', '2026-06-01 09:23:29'),
(526, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 09:48:06'),
(527, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-01 09:48:14'),
(528, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado.', '2026-06-01 09:48:21'),
(529, 43, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'OS editada. Campos alterados: Prazo.', '2026-06-01 12:16:07'),
(530, 22, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-06-01 12:16:39'),
(531, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Orçamento aprovado.\r\nPrevisão de chegado do material, quarta- feira, dia 03.06.2026.', '2026-06-01 12:19:31'),
(532, 70, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-01 13:01:34'),
(533, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 13:02:05'),
(534, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 13:02:28'),
(535, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Orçamento Aprovado!!!', '2026-06-01 13:07:48'),
(536, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-01 13:09:03'),
(541, 72, 1, NULL, 'ATRIBUIDA', 'OS criada. SLA: 24 horas.', '2026-06-02 10:08:19'),
(542, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Cabo de Rede Cat-6', '2026-06-02 10:10:49'),
(543, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-06-02 10:11:49'),
(544, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: RJ-45', '2026-06-02 10:12:22'),
(545, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 10:26:21'),
(546, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 10:34:35'),
(547, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-02 10:37:45'),
(548, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Moab Vieira Dos Santos, Samuel Jose da Paz.', '2026-06-02 10:38:22'),
(549, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 10:38:55'),
(550, 73, 1, NULL, 'ATRIBUIDA', 'OS criada. SLA: 72 horas.', '2026-06-02 10:42:47'),
(551, 44, 2, 'ABERTA', 'PAUSADA', 'Status atualizado.', '2026-06-02 11:02:04'),
(552, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-02 11:11:54'),
(553, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-02 11:13:53'),
(554, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-02 11:14:14'),
(555, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-02 11:15:43'),
(556, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-02 11:17:52'),
(557, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-02 11:18:02'),
(558, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-06-02 11:18:18'),
(559, 61, 1, 'ABERTA', 'ENCERRADA', 'Status atualizado.', '2026-06-02 11:21:48'),
(560, 74, 1, NULL, 'ABERTA', 'OS criada. SLA: 48 horas. Aberta pelo TI para o gestor definir o agente.', '2026-06-02 11:22:05'),
(561, 75, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-02 11:35:20'),
(562, 74, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-06-02 11:36:20'),
(563, 44, 2, 'PAUSADA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 11:37:05'),
(564, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 11:37:12'),
(565, 43, 2, 'AGUARDANDO_MATERIAL', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-02 11:37:29'),
(566, 43, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-02 11:37:32'),
(567, 74, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-02 11:38:07'),
(568, 75, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-02 11:38:39'),
(569, 75, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:39:01'),
(570, 75, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:39:09'),
(571, 75, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:39:24'),
(572, 75, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:39:38'),
(573, 75, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi usada uma tinta que tínhamos em estoque, não gerando custo adicional.', '2026-06-02 11:40:38'),
(574, 75, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-02 11:40:46'),
(575, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:44:23'),
(576, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:44:38'),
(577, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:45:01'),
(578, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 11:45:20'),
(579, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-02 11:45:31'),
(580, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 11:45:39'),
(581, 76, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 24 horas.', '2026-06-02 12:19:22'),
(582, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 12:19:33'),
(583, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-02 12:19:52'),
(584, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 12:22:19'),
(585, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 12:22:35'),
(586, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 12:22:49'),
(587, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 12:23:15'),
(588, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 12:23:30');
INSERT INTO `historico_os` (`id`, `ordem_servico_id`, `usuario_id`, `status_anterior`, `status_novo`, `descricao`, `criado_em`) VALUES
(589, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi solicitado pelo proprietário da BOBS o apoio da equipe de manutenção para desobstruir a linha de esgoto da loja. O Agente de Manutenção Tarciso e ASG Samuel, conseguiram desobstruir a linha, muita gordura nas paredes do cano.', '2026-06-02 12:25:21'),
(590, 76, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-02 12:25:31'),
(591, 76, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-02 12:25:42'),
(593, 78, 1, NULL, 'ABERTA', 'OS criada. SLA: 48 horas. Aberta pelo TI para o gestor definir o agente.', '2026-06-02 13:20:25'),
(594, 78, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-02 13:52:10'),
(595, 73, 1, 'ATRIBUIDA', 'ENCERRADA', 'Ficamos com 3 nobreaks funcionando! ', '2026-06-02 14:00:16'),
(596, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-02 14:10:10'),
(597, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 14:10:32'),
(598, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Informar a quantidade usada e registrar com fotos antes e depois e enviar para o Gestor de Operações. Usar o telefone da Central para registrar as fotos.', '2026-06-02 14:14:31'),
(599, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-06-02 14:16:21'),
(601, 86, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-02 16:16:10'),
(602, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 16:16:55'),
(603, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-02 16:17:28'),
(604, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-02 16:18:14'),
(605, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Moab Vieira Dos Santos.', '2026-06-03 07:12:49'),
(606, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-06-03 07:13:28'),
(607, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 07:16:35'),
(608, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 07:16:49'),
(609, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 07:17:00'),
(610, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 07:17:23'),
(611, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foram substituídas 07 (sete) unidades de Lâmpadas Slim 120cm 36w 650k, nos corredores de serviço e DOCA.', '2026-06-03 07:19:01'),
(612, 69, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-03 07:19:19'),
(613, 69, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-03 07:19:53'),
(614, 67, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-06-03 07:31:09'),
(615, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 10:17:10'),
(616, 72, 1, 'ATRIBUIDA', 'ATRIBUIDA', 'Cabo no local indicado. Finalizado antes da abertura do Shopping.', '2026-06-03 10:17:35'),
(617, 72, 1, 'ATRIBUIDA', 'ENCERRADA', 'Cabo no local indicado. Finalizado antes da abertura do Shopping.', '2026-06-03 10:17:51'),
(618, 60, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 10:18:01'),
(619, 60, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-03 10:18:29'),
(620, 87, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-06-03 10:33:42'),
(621, 87, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-06-03 10:34:49'),
(622, 87, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-06-03 10:35:05'),
(623, 87, 2, 'ABERTA', 'ABERTA', 'Foto movida de FOTO ANTES para FOTO DEPOIS.', '2026-06-03 10:35:28'),
(624, 87, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-06-03 10:35:46'),
(625, 87, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-06-03 10:36:04'),
(626, 87, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-06-03 10:46:48'),
(627, 87, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-06-03 10:47:13'),
(628, 87, 2, 'ABERTA', 'ABERTA', 'Status atualizado.', '2026-06-03 10:47:36'),
(629, 88, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-03 12:33:25'),
(630, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 12:34:24'),
(631, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-06-03 12:34:48'),
(632, 87, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Leonilton Barbosa.', '2026-06-03 12:35:56'),
(633, 87, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Leonilton Barbosa.', '2026-06-03 12:36:20'),
(634, 87, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-03 12:36:26'),
(635, 89, 2, NULL, 'ATRIBUIDA', 'OS gerada automaticamente por manutencao preventiva atribuida a agente.', '2026-06-03 12:43:01'),
(636, 90, 2, NULL, 'ATRIBUIDA', 'OS gerada automaticamente por manutencao preventiva atribuida a agente.', '2026-06-03 12:44:21'),
(637, 91, 2, NULL, 'ATRIBUIDA', 'OS gerada automaticamente por manutencao preventiva atribuida a agente.', '2026-06-03 12:50:24'),
(638, 92, 2, NULL, 'ATRIBUIDA', 'OS gerada automaticamente por manutencao preventiva atribuida a agente.', '2026-06-03 12:51:41'),
(639, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:27:11'),
(640, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:27:29'),
(641, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:27:50'),
(642, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:28:01'),
(643, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:28:32'),
(644, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:30:05'),
(645, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:30:22'),
(646, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:30:33'),
(647, 88, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi observado vazamentos nas válvulas e reparado.\r\nTambém foi trocado a correia da maquina.\r\nReferencia da correia: B-42', '2026-06-03 15:32:47'),
(648, 88, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-03 15:52:08'),
(649, 93, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-03 15:53:49'),
(650, 93, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:54:07'),
(651, 93, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-06-03 15:54:27'),
(652, 93, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:54:42'),
(653, 93, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foram substituída 02 (duas) lâmpadas do refeitório. ', '2026-06-03 15:55:39'),
(654, 93, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-03 15:55:45'),
(655, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 15:57:00'),
(656, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 16:05:34'),
(657, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 16:05:47'),
(658, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 16:06:01'),
(659, 68, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 16:06:13'),
(660, 68, 2, 'ATRIBUIDA', 'ENCERRADA', '- Varremos o Lago hoje com o Colaborador Moab Vieira.', '2026-06-03 16:06:51'),
(661, 94, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-03 16:11:45'),
(662, 94, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 16:12:31'),
(663, 94, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 16:12:44'),
(664, 94, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-06-03 16:12:54'),
(665, 95, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-03 16:23:21'),
(666, 95, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-03 16:25:37'),
(667, 95, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-06-03 16:25:46'),
(668, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-04 07:44:38'),
(669, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Foram pintadas as colunas da Tropical até a Melissa.\r\n', '2026-06-04 08:19:38'),
(670, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-04 08:21:17'),
(671, 96, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-04 08:42:00'),
(672, 96, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-04 08:42:33'),
(673, 96, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-04 08:42:58'),
(674, 96, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-04 08:43:21'),
(675, 96, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-04 08:43:34'),
(676, 96, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'A mesma foi retirada, pois havia risco de acidente. ', '2026-06-04 08:44:30'),
(677, 96, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-04 08:44:41'),
(678, 94, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-04 10:30:53'),
(679, 94, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-06-04 10:31:09'),
(680, 97, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-04 15:59:29'),
(681, 97, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-04 16:00:01'),
(682, 97, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-04 16:00:13'),
(683, 97, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi substituída 02 (duas) lâmpadas PAR30 12w 960Lumes 2700K Branca Morna.', '2026-06-04 16:00:55'),
(684, 97, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-04 16:01:03'),
(685, 98, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-04 16:05:03'),
(686, 98, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-04 16:07:15'),
(687, 98, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi substituída 02 (duas)lâmpadas PAR30 12w 960Lumes 2700K Branca Morna.\r\nO agente esquece de tirar as fotos de antes.', '2026-06-04 16:08:08'),
(688, 98, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-04 16:18:24'),
(689, 94, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-04 16:20:37'),
(690, 94, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-04 16:20:53'),
(691, 94, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Foi realizado uma poda e limpeza no Cajueiro da frente do Shopping.', '2026-06-04 16:44:18'),
(692, 94, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-06-04 16:45:49'),
(693, 99, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-05 07:17:43'),
(694, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-05 07:18:02'),
(695, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-05 07:20:20'),
(696, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-05 07:20:30'),
(697, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição, Prazo.', '2026-06-05 14:40:50'),
(698, 91, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição, Prazo.', '2026-06-05 14:41:35'),
(699, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição, Prazo.', '2026-06-05 14:41:52'),
(700, 89, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição, Prazo.', '2026-06-05 14:42:20'),
(701, 44, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-05 15:30:08'),
(702, 100, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-05 15:32:03'),
(703, 100, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-05 15:32:15'),
(704, 100, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-05 15:32:27'),
(705, 100, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Foi solicitado pela Sra. Julliana (Preditiva) o apoio na limpeza da fachada.', '2026-06-05 15:34:29'),
(706, 78, 1, 'ATRIBUIDA', 'PAUSADA', 'Não está faltando peças dentro', '2026-06-07 17:33:57'),
(707, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-08 07:24:12'),
(708, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-08 07:24:24'),
(709, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-08 07:24:34'),
(710, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-08 07:24:45'),
(711, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Finalizado os retorque nas colunas do Mall e também retocamos a parede do corredor do Mall.', '2026-06-08 07:26:11'),
(712, 3, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Constantemente pintamos a parede do corredor do Mall (Caixa 24hs) pois a mesma suja com facilidade devido ao uso do caixa 24hs. Sugestão: Aplicar um revestimento (Cerâmica) na parede, evitando a sugira e o gasto com tintas e mão de obra.', '2026-06-08 07:30:06'),
(713, 3, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-06-08 07:30:13'),
(714, 101, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-08 07:47:31'),
(715, 102, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-09 07:49:09'),
(716, 103, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-09 07:53:08'),
(717, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 07:58:27'),
(718, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 07:59:42'),
(719, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:00:03'),
(720, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:00:14'),
(721, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:00:25'),
(722, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'O verificado que a maquina 10TR estava condensado, abrimos a máquina de ventilação e vimos a correia da polia folgada. Fizemos o aperto e religamos a mesma.\r\nA CG Climatização foi acionada para verificação da mesma. \r\nPrevisão do atendimento é para o dia 09.06.2026.', '2026-06-09 08:03:00'),
(723, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Não foi possível realizar o reparo da infiltração devido as chuvas. ', '2026-06-09 08:06:48'),
(724, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'O Agente não conseguiu devido demandas emergenciais que surgiram (Evento Feirinhas no Final de semana).', '2026-06-09 08:09:01'),
(725, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-09 08:09:23'),
(726, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:13:21'),
(727, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:13:41'),
(728, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-09 08:14:11'),
(729, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:18:06'),
(730, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:18:18'),
(731, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:18:35'),
(732, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 08:18:47'),
(733, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-09 08:18:57'),
(734, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Duas válvulas apresentou vazamentos.\r\nEstaremos realizando o reparo das mesmas.', '2026-06-09 08:19:55'),
(735, 104, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-09 09:02:04'),
(736, 104, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-09 09:03:17'),
(737, 104, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'A lousa foi instalada conforme solicitação e orientação da Arquiteta Iuzianny.', '2026-06-09 09:04:13'),
(738, 104, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-09 09:04:35'),
(739, 104, 2, 'ENCERRADA', 'ENCERRADA', 'Solicitou token de reabertura ao TI: Solicitação de token para reabertura da OS.', '2026-06-09 09:11:00'),
(740, 104, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-06-09 09:12:58'),
(741, 104, 2, 'ENCERRADA', 'ABERTA', 'Acrescentar informações', '2026-06-09 09:13:31'),
(742, 104, 2, 'ABERTA', 'ABERTA', 'Material registrado: Bucha Drywall nº 02 com Parafusos', '2026-06-09 09:14:16'),
(743, 104, 2, 'ABERTA', 'ENCERRADA', 'Status atualizado.', '2026-06-09 09:14:31'),
(744, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:52:04'),
(745, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:52:23'),
(746, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:52:41'),
(747, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:53:03'),
(748, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:54:10'),
(749, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:54:20'),
(750, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:54:38'),
(751, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:54:59'),
(752, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:55:13'),
(753, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:55:32'),
(754, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:55:55'),
(755, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:56:10'),
(756, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 10:56:29'),
(757, 102, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi realizada a capinação e limpeza do jardim dos cisnes.', '2026-06-10 10:57:16'),
(758, 102, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-10 10:57:24'),
(759, 60, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-10 10:57:54'),
(760, 21, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'OS editada. Campos alterados: Prazo.', '2026-06-10 11:01:29'),
(761, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:02:37'),
(762, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foto movida de FOTO DEPOIS para FOTO ANTES.', '2026-06-10 11:02:43'),
(763, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:03:24'),
(764, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:03:40'),
(765, 101, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Realizamos o reparo onde possivelmente era o ponto de infiltração. Aguardaremos as próximas chuvas para o teste.', '2026-06-10 11:05:20'),
(766, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:39:53'),
(767, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:40:12'),
(768, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:40:27'),
(769, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:40:43'),
(770, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:40:57'),
(771, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:41:11'),
(772, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-10 11:45:10'),
(773, 103, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:46:16'),
(774, 103, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-10 11:46:29'),
(775, 101, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-10 11:47:02'),
(776, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:48:07'),
(777, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-10 11:48:21'),
(778, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi realizado vazamento das válvulas.\r\nO pessoal da assistência da PDU irá realizar o reboco hoje (10.06.2026) no expediente da tarde.\r\nFoi liberado 50% do Wc Fem. Mall para o uso dos clientes. ', '2026-06-10 11:51:28'),
(779, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-10 11:51:42'),
(780, 95, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-10 11:59:07'),
(781, 95, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Foram limpos 06 exaustores faltando 06.', '2026-06-10 14:05:46'),
(782, 11, 2, 'PAUSADA', 'PAUSADA', 'OS editada. Campos alterados: Prazo.', '2026-06-10 14:06:20'),
(783, 105, 2, NULL, 'ABERTA', 'OS criada. SLA: 24 horas.', '2026-06-10 14:08:51'),
(784, 105, 2, 'ABERTA', 'ABERTA', 'Anexo enviado.', '2026-06-10 14:13:22'),
(785, 105, 2, 'ABERTA', 'ABERTA', 'OS editada sem alteração de dados.', '2026-06-10 14:13:30'),
(786, 105, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-10 14:13:52'),
(787, 44, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-10 14:22:11'),
(788, 106, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-10 16:33:28'),
(789, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:14:51'),
(790, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:15:08'),
(791, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:15:57'),
(792, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:16:15'),
(793, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:16:42'),
(794, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:17:02'),
(795, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:17:17'),
(796, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:17:41'),
(797, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:18:06'),
(798, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-11 11:18:31'),
(799, 106, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Limpeza e manutenção das caixas de gorduras e E.T.E realizadas!\r\nA próxima será no dia 11 de agosto.', '2026-06-11 11:25:48'),
(800, 106, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-11 11:27:24'),
(801, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:36:14'),
(802, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:36:32'),
(803, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:36:54'),
(804, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Finalizado a instalação da 05 válvulas no Wc Femino Mall.\r\nFaltando apenas a instalação do revestimento que estar no processo de compra.', '2026-06-11 11:45:10'),
(805, 99, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Alexandre Jose da Silva.', '2026-06-11 11:45:41'),
(806, 99, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-11 11:45:57'),
(807, 107, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-11 11:49:43'),
(808, 107, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:51:26'),
(809, 107, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 11:51:47'),
(810, 107, 2, 'ATRIBUIDA', 'ENCERRADA', 'Substituição realizada!', '2026-06-11 11:52:59'),
(811, 95, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-11 12:03:35'),
(812, 95, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada sem alteração de dados.', '2026-06-11 12:03:43'),
(813, 11, 2, 'PAUSADA', 'PAUSADA', 'Anexo enviado.', '2026-06-11 12:14:33'),
(814, 11, 2, 'PAUSADA', 'PAUSADA', 'Anexo enviado.', '2026-06-11 12:14:50'),
(815, 11, 2, 'PAUSADA', 'PAUSADA', 'Anexo enviado.', '2026-06-11 12:15:09'),
(816, 11, 2, 'PAUSADA', 'EM_ANDAMENTO', 'Retomamos a limpeza do ACM interno do Lago - Corredor Preditiva Sentido Praça de Alimentação.', '2026-06-11 12:16:04'),
(817, 60, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-11 16:06:37'),
(818, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'O serviço atrasou devido a nossa serra mármore que, estar precisando do Gatilho chave interruptor Serra Mármore Bosch GDC 150.', '2026-06-11 16:15:33'),
(819, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-11 16:15:50'),
(820, 108, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-11 16:19:56'),
(821, 108, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:21:47'),
(822, 108, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:21:58'),
(823, 108, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:22:16'),
(824, 108, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:23:26'),
(825, 108, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Iniciamos hoje o serviço de manutenção do sistema de incêndio do SC.  O sistema estava com falhas nas Seguintes lojas: CEF, Capilé e Farmácia Laranjinha. Todas as falhas foram corrigidas. Sistema operando sem falhas!', '2026-06-11 16:28:16'),
(826, 108, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-11 16:28:23'),
(827, 109, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-11 16:31:16'),
(828, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:34:08'),
(829, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:34:19'),
(830, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:34:48'),
(831, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:35:04'),
(832, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 16:35:33'),
(833, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-11 16:37:39'),
(834, 110, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-11 16:53:00'),
(835, 110, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-11 17:01:07'),
(836, 110, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-11 17:01:07'),
(837, 110, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-11 17:02:31'),
(838, 100, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-12 09:07:49'),
(839, 100, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-12 09:08:02'),
(840, 100, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Limpeza realizada.\r\nPróxima limpeza programada para o dia 11.07.2026 ', '2026-06-12 09:10:59'),
(841, 100, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-06-12 09:11:13'),
(842, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 10:45:54'),
(843, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 10:46:09'),
(844, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 10:46:50'),
(845, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 10:47:43'),
(846, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 10:48:02'),
(847, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-12 10:48:17'),
(848, 111, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-12 10:51:09'),
(849, 111, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 11:02:29'),
(850, 111, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 11:02:43'),
(851, 111, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 11:03:09'),
(852, 111, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-12 11:03:19'),
(853, 111, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Limpeza realizada.\r\nEstaremos recebendo a visita da Genesis (Equipe de Luciano Lacerda) na terça- feira.', '2026-06-12 11:04:23'),
(854, 111, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-12 11:04:36'),
(855, 104, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-06-14 13:11:51'),
(856, 43, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-15 08:02:51'),
(857, 105, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-15 08:03:11'),
(858, 95, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-15 08:04:43'),
(859, 95, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Limpeza realizada.\r\nPróxima limpeza programada para o dia 15.07.2026', '2026-06-15 08:06:32'),
(860, 95, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-06-15 08:06:38'),
(861, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-15 08:23:15'),
(862, 109, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Capinação realizada!', '2026-06-15 08:27:19'),
(863, 109, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-15 08:27:27'),
(864, 22, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-06-15 08:27:52'),
(865, 32, 2, 'ATRIBUIDA', 'PAUSADA', 'Status atualizado.', '2026-06-15 08:39:50'),
(866, 112, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-15 10:29:16'),
(867, 113, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-15 10:53:24'),
(868, 113, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-15 10:54:36'),
(869, 113, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-15 10:54:56'),
(870, 113, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-15 10:55:16'),
(871, 113, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-15 10:56:27'),
(872, 113, 2, 'ATRIBUIDA', 'ENCERRADA', 'Foram instalados as lousas nas referidas salas e deixado 01 (um) apagador e 03 (três) marcadores nas cores, preto azul e verde. Foram entregues 02  (dois) marcadores a Silvana para colocar em estoque.', '2026-06-15 10:59:47'),
(873, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-15 11:16:13'),
(874, 114, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-15 14:20:44'),
(875, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:10:01'),
(876, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:10:20'),
(877, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:11:02'),
(878, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:12:51'),
(879, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:13:38'),
(880, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:14:29'),
(881, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:15:35'),
(882, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:16:01'),
(883, 86, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 07:18:01'),
(884, 86, 2, 'ATRIBUIDA', 'ENCERRADA', 'Limpeza realizada.\r\nPróxima limpeza programada para o dia 16.07.2026.', '2026-06-16 07:23:20'),
(885, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Disco de Corte para Serra Mármore - Cortar Porcelanato', '2026-06-16 07:28:58'),
(886, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Colher de Pedreiro', '2026-06-16 07:30:11'),
(887, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Desempenadeira dentada para argamassa', '2026-06-16 07:31:45'),
(888, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-16 07:53:47'),
(889, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-16 08:31:59'),
(890, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:32:57'),
(891, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:33:08'),
(892, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:33:20'),
(893, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:33:42'),
(894, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:34:20'),
(895, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:34:35'),
(896, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:34:47'),
(897, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 08:34:58'),
(898, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-16 08:35:07'),
(899, 112, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 12:23:04'),
(900, 112, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 12:23:27'),
(901, 112, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 12:23:39'),
(902, 112, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-16 12:26:17'),
(903, 112, 2, 'ATRIBUIDA', 'ENCERRADA', 'Máquina funcionando normalmente.\r\nA mesma está dividindo uma FASE com o exaustor, isso pode ter ocasionado o disparo do disjuntor, porém não podemos afirmar essa situação.\r\nFicaremos observando o funcionamento de ambos.', '2026-06-16 12:28:52'),
(904, 105, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'O Sr. Pedro Miguel (COGERA) nos informou que por conta dos planejamento, achou melhor remarcar a visita para o dia 18.06.2026.', '2026-06-17 06:53:34'),
(905, 105, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-17 06:53:44'),
(906, 115, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-17 06:56:45'),
(907, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Descrição.', '2026-06-17 06:58:15'),
(908, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:05:40'),
(909, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:05:53'),
(910, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:06:07'),
(911, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:06:21'),
(912, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:07:02'),
(913, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:08:51'),
(914, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:09:09'),
(915, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:09:21'),
(916, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:09:42'),
(917, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 07:09:57'),
(918, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-17 07:10:31'),
(919, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foi feita a manutenção de 02 (duas) máquinas k7 , faltando 02 (duas) K7 e 06 (seis) Splits.\r\nA programação é fazer mais 02 (duas) K7 hoje e finalizar o restante no sábado.', '2026-06-17 07:57:11'),
(920, 104, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-06-17 13:17:35'),
(921, 113, 2, 'ENCERRADA', 'ENCERRADA', 'Solicitou token de reabertura ao TI: Solicitação de token para reabertura da OS.', '2026-06-17 13:19:20'),
(922, 104, 2, 'ENCERRADA', 'ABERTA', 'Abertura pendente fechamento', '2026-06-17 13:20:43'),
(923, 104, 2, 'ABERTA', 'ENCERRADA', 'Status atualizado.', '2026-06-17 13:20:47'),
(924, 113, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-06-17 13:21:09'),
(925, 113, 2, 'ENCERRADA', 'ABERTA', 'token pendente', '2026-06-17 13:21:25'),
(926, 113, 2, 'ABERTA', 'ENCERRADA', 'Status atualizado.', '2026-06-17 13:21:29'),
(927, 78, 2, 'PAUSADA', 'ENCERRADA', 'Status atualizado.', '2026-06-17 16:00:16'),
(928, 116, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-17 16:45:33'),
(929, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 16:46:30'),
(930, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 16:46:40'),
(931, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 16:46:48'),
(932, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 16:46:58'),
(933, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 16:47:08'),
(934, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-17 16:47:16'),
(935, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-17 16:47:30'),
(936, 117, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-18 07:08:43'),
(937, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:12:17'),
(938, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:12:29'),
(939, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:12:43'),
(940, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:13:08'),
(941, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-18 07:13:36'),
(942, 60, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-18 07:14:42'),
(943, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:41:24'),
(944, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:42:06'),
(945, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:42:55'),
(946, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:43:17'),
(947, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:43:41'),
(948, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:44:10'),
(949, 117, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 07:44:25'),
(950, 117, 2, 'ATRIBUIDA', 'ENCERRADA', 'Os retoques foram realizados!\r\nUsamos tinta (Branco) que temos em estoque.', '2026-06-18 07:46:22'),
(951, 32, 2, 'PAUSADA', 'PAUSADA', 'OS editada. Campos alterados: Prazo.', '2026-06-18 07:47:12'),
(952, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Alexandre Jose da Silva.', '2026-06-18 07:47:49'),
(953, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 08:09:57'),
(954, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 08:10:18'),
(955, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 08:10:38'),
(956, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 08:10:54'),
(957, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 08:11:08'),
(958, 115, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 08:11:19'),
(959, 115, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Foram realizadas a limpeza de mais 02 (dois) K7s.\r\nNa sexta (19), após expediente do Expresso Cidadão, daremos continuidade, limpando as máquinas Splits (evaporadoras) e no Sábado (20) serão lavadas as Condensadoras. ', '2026-06-18 08:15:24'),
(960, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'O serviço da sexta (19) e sábado (20) será acompanhado pelo Sr. João Paulo - Colaborador do Expresso Cidadão.', '2026-06-18 08:50:51'),
(961, 58, 2, 'ABERTA', 'ENCERRADA', 'Status atualizado.', '2026-06-18 10:57:24'),
(962, 118, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-18 12:04:06'),
(963, 118, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 12:06:33'),
(964, 118, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 12:07:08'),
(965, 118, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 12:07:20'),
(966, 118, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 12:07:52'),
(967, 118, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 12:08:11'),
(968, 118, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Geovanny Cristhian.', '2026-06-18 12:09:53'),
(969, 118, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-18 12:10:07'),
(970, 105, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 14:54:32'),
(971, 105, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 14:54:43'),
(972, 105, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 14:54:53'),
(973, 105, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 14:55:04'),
(974, 105, 2, 'ATRIBUIDA', 'ENCERRADA', 'A empresa responsável pela manutenção esteve no local a inspeção e aproveitou para realizar a manutenção mensal.  \r\n', '2026-06-18 15:40:31'),
(975, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:41:05'),
(976, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:41:19'),
(977, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:41:31'),
(978, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:41:49'),
(979, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:42:08'),
(980, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:42:24'),
(981, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:42:44'),
(982, 27, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-18 15:42:58'),
(983, 27, 2, 'ATRIBUIDA', 'ENCERRADA', 'Limpeza Realizada.\r\nPróxima programada para o dia 14.07.2026.', '2026-06-18 15:46:09'),
(984, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Leonilton Barbosa.', '2026-06-18 16:09:34'),
(985, 119, 2, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-06-18 16:13:17'),
(986, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:08:05'),
(987, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:09:00'),
(988, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:09:24'),
(989, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:10:15'),
(990, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:10:32'),
(991, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:11:09'),
(992, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:11:34'),
(993, 90, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:12:03'),
(994, 90, 2, 'ATRIBUIDA', 'ENCERRADA', 'Limpeza da Locomotiva realizada.\r\nProgramada a próxima para o dia 26.06.2026.', '2026-06-19 07:19:19'),
(995, 120, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-19 07:23:46'),
(996, 120, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Leonilton Barbosa.', '2026-06-19 07:23:54'),
(997, 120, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:24:12'),
(998, 120, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:24:31'),
(999, 120, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:24:51'),
(1000, 120, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:25:11'),
(1001, 120, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:25:28'),
(1002, 120, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 07:25:40'),
(1003, 120, 2, 'ATRIBUIDA', 'ENCERRADA', 'Realizada a limpeza dos balizadores.\r\nPróxima programada para o dia 26.06.2026.', '2026-06-19 07:26:43'),
(1004, 119, 2, 'ABERTA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-19 07:31:40'),
(1005, 119, 2, 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 'Luminária fora de circulação.\r\nEstamos analisando outras opções no mercado, seguindo as mesmas discrições e apresentar ao setor de qualidade.', '2026-06-19 07:33:28'),
(1006, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 08:18:10'),
(1007, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 08:18:22'),
(1008, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 08:18:35'),
(1009, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 08:19:08'),
(1010, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 08:19:24'),
(1011, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 08:19:37'),
(1012, 114, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 08:19:52'),
(1013, 114, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Mais uma parte do estacionamento capinada.', '2026-06-19 08:20:28'),
(1014, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-06-19 08:20:44'),
(1015, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 09:18:04'),
(1016, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 09:18:23'),
(1017, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 09:18:35'),
(1018, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 09:19:01'),
(1019, 116, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 09:19:14'),
(1020, 116, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-06-19 09:19:24'),
(1021, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 11:15:13'),
(1022, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-19 11:15:44'),
(1023, 113, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-06-19 14:22:56'),
(1024, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-19 15:31:49'),
(1025, 60, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-19 15:32:17'),
(1026, 116, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-19 15:43:52'),
(1027, 116, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-19 15:44:06'),
(1028, 116, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-19 15:44:19'),
(1029, 116, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-19 15:44:38'),
(1030, 116, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Realizada aplicação de terra adubada nas gramas amendoim.\r\nPróxima aplicação programada para o dia 16.07.2026', '2026-06-19 15:46:48'),
(1031, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 07:37:41'),
(1032, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 07:38:16'),
(1033, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 07:38:40'),
(1034, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 07:40:07'),
(1035, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 07:40:29'),
(1036, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 07:40:44'),
(1037, 114, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 07:40:56'),
(1038, 114, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Limpeza realizada \r\n', '2026-06-22 07:41:20'),
(1039, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 09:01:35'),
(1040, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 09:01:50'),
(1041, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 09:02:11'),
(1042, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 09:05:31'),
(1043, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 09:05:49'),
(1044, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 09:06:06'),
(1045, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-06-22 09:06:28'),
(1046, 115, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Foi finalizado a manutenção dos ar condicionados do Expresso Cidadão no dia 20 (sábado).\r\nTodos os ar condicionado limpos e higienizados.\r\nObs: a segunda máquina K7 da entrada a esquerda, estava condensando. Foi realizada a correção de Gás e ficaremos observando o funcionamento da mesma. ', '2026-06-22 09:09:49'),
(1047, 115, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-06-22 09:09:56'),
(1048, 91, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-22 09:10:50'),
(1049, 121, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-22 09:13:47'),
(1050, 121, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:15:00'),
(1051, 121, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:15:23'),
(1052, 121, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:15:32'),
(1053, 121, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:15:54'),
(1054, 122, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-22 09:23:10'),
(1055, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:23:30'),
(1056, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:23:46'),
(1057, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:24:11'),
(1058, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:24:29'),
(1059, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-22 09:25:33'),
(1060, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-06-22 09:25:47'),
(1061, 70, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-25 07:15:55'),
(1062, 70, 2, 'ATRIBUIDA', 'ENCERRADA', 'Serviço finalizado.', '2026-06-25 07:16:47'),
(1063, 123, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-25 08:05:42'),
(1064, 123, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-25 08:06:03'),
(1065, 123, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-25 08:06:20'),
(1066, 123, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-25 08:06:30'),
(1067, 123, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-25 08:06:47'),
(1068, 123, 2, 'ATRIBUIDA', 'ENCERRADA', 'Retoque concluído!', '2026-06-25 08:09:23'),
(1069, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Lixa ferro nº 100', '2026-06-25 08:10:16'),
(1070, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Lixa ferro nº 150', '2026-06-25 08:10:35'),
(1071, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Lixa ferro p/ Lixadeira nº100', '2026-06-25 08:10:49'),
(1072, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Cola Almasuper Marceneiro', '2026-06-25 08:11:11'),
(1073, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Fita crepe larga', '2026-06-25 08:11:28'),
(1074, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Tinta Alto brilho Areia Iquine Dialine Seca Rápido 3L', '2026-06-25 08:12:00'),
(1075, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Thinner Iquine 900ml', '2026-06-25 08:12:30'),
(1076, 122, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-25 08:14:17'),
(1077, 124, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-25 15:35:47'),
(1078, 124, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-25 15:38:08'),
(1079, 124, 2, 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-06-25 15:38:56'),
(1080, 124, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Material registrado: Lixa madeira nº 150', '2026-06-25 15:39:24'),
(1081, 124, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Material registrado: Lixa madeira nº 100', '2026-06-25 15:39:45'),
(1082, 124, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Material registrado: Lixa Ferro p/ Lixadeira nº150', '2026-06-25 15:40:08'),
(1083, 124, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'Material registrado: Thinner Iquine 900ml', '2026-06-25 15:40:26'),
(1084, 124, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'OS editada. Campos alterados: Prazo.', '2026-06-25 16:17:21'),
(1085, 124, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'OS editada. Campos alterados: Título, Descrição.', '2026-06-25 16:19:28'),
(1086, 125, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-25 16:21:07'),
(1087, 125, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-25 16:21:22'),
(1088, 125, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-06-25 16:21:37'),
(1089, 60, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-29 07:34:41'),
(1090, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-29 07:42:33'),
(1091, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-29 07:42:48'),
(1092, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-29 07:43:08'),
(1093, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-29 07:43:26'),
(1094, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-29 07:43:55'),
(1095, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-29 07:44:20'),
(1096, 92, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-06-29 07:44:36'),
(1097, 92, 2, 'ATRIBUIDA', 'ENCERRADA', 'Limpeza realizada.\r\nPróxima programada para o dia 05.07.2026', '2026-06-29 07:51:29'),
(1098, 119, 2, 'AGUARDANDO_MATERIAL', 'EM_ANDAMENTO', 'Não estamos conseguindo encontrar a lâmpada no mercado, iremos fazer um teste com uma lâmpada muito parecida. Um fornecedor vai nos emprestar 03 (três) unidades para os teste.\r\nO prazo é para o dia 22.07.2026.', '2026-06-29 08:05:42'),
(1099, 119, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-06-29 08:08:10'),
(1100, 119, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-06-30 10:03:38'),
(1101, 125, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Aguardando Material', '2026-06-30 10:04:57'),
(1102, 91, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-30 10:05:48');
INSERT INTO `historico_os` (`id`, `ordem_servico_id`, `usuario_id`, `status_anterior`, `status_novo`, `descricao`, `criado_em`) VALUES
(1103, 89, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-30 10:06:52'),
(1104, 11, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-06-30 10:07:39'),
(1105, 21, 2, 'AGUARDANDO_MATERIAL', 'PAUSADA', 'Status atualizado.', '2026-06-30 10:08:25'),
(1106, 126, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-06-30 10:24:52'),
(1107, 121, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Prazo.', '2026-06-30 13:23:16'),
(1108, 121, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-30 13:23:56'),
(1109, 41, 2, 'PAUSADA', 'PAUSADA', 'Anexo enviado.', '2026-06-30 13:25:45'),
(1110, 41, 2, 'PAUSADA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-06-30 13:26:06'),
(1111, 41, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-06-30 13:26:13'),
(1112, 40, 2, 'PAUSADA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-06-30 13:44:30'),
(1113, 40, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-06-30 13:45:40'),
(1114, 124, 31, 'AGUARDANDO_MATERIAL', 'EM_ANDAMENTO', 'Status atualizado.', '2026-07-09 10:57:20'),
(1115, 113, 31, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-07-09 10:58:39'),
(1116, 104, 31, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-07-09 10:58:46'),
(1117, 89, 31, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-07-09 11:15:02'),
(1118, 127, 30, NULL, 'ABERTA', 'OS criada. SLA: 48 horas.', '2026-07-09 14:44:20'),
(1119, 127, 31, 'ABERTA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-07-10 08:19:58'),
(1120, 122, 31, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-07-14 08:47:21'),
(1121, 91, 31, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-07-14 10:24:30'),
(1122, 113, 1, 'ENCERRADA', 'ENCERRADA', 'TI gerou token para reabertura da OS. Validade: 24 horas.', '2026-07-15 13:13:52'),
(1123, 110, 31, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-07-17 09:00:25'),
(1124, 11, 31, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-07-17 09:01:03'),
(1125, 127, 31, 'EM_ANDAMENTO', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): nenhum.', '2026-07-20 09:30:39'),
(1126, 128, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-07-21 07:08:18'),
(1127, 128, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:08:41'),
(1128, 128, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:08:56'),
(1129, 128, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Foto movida de FOTO ANTES para FOTO DEPOIS.', '2026-07-21 07:09:06'),
(1130, 128, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:09:17'),
(1131, 128, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:09:36'),
(1132, 128, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-07-21 07:09:52'),
(1133, 128, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Status atualizado.', '2026-07-21 07:10:08'),
(1134, 127, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Disco de Corte para Serra Mármore - para ferro', '2026-07-21 07:12:56'),
(1135, 127, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Broca Nº 10 - para concreto', '2026-07-21 07:14:50'),
(1136, 127, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Linha de pedreiro', '2026-07-21 07:16:00'),
(1137, 127, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Material registrado: Cola para fixação de Tachões - Balde 5 Kg', '2026-07-21 07:26:05'),
(1138, 127, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:28:14'),
(1139, 127, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:28:30'),
(1140, 127, 2, 'ATRIBUIDA', 'AGUARDANDO_MATERIAL', 'Status atualizado.', '2026-07-21 07:28:42'),
(1141, 127, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'OS editada. Campos alterados: Prazo.', '2026-07-21 07:29:27'),
(1142, 127, 2, 'AGUARDANDO_MATERIAL', 'AGUARDANDO_MATERIAL', 'O prazo para instalação para o dia 15.08. foi colocado devido a chegada do material, caso ele chegue antes, anteciparemos a instalação.', '2026-07-21 07:33:51'),
(1143, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:58:49'),
(1144, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:59:03'),
(1145, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:59:17'),
(1146, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:59:29'),
(1147, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:59:40'),
(1148, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 07:59:54'),
(1149, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 08:00:13'),
(1150, 126, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 08:03:08'),
(1151, 126, 2, 'ATRIBUIDA', 'ENCERRADA', 'Status atualizado.', '2026-07-21 08:03:24'),
(1152, 125, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-21 08:10:34'),
(1153, 125, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-21 08:10:47'),
(1154, 125, 2, 'EM_ANDAMENTO', 'ENCERRADA', 'Status atualizado.', '2026-07-21 08:11:09'),
(1155, 74, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada. Campos alterados: Categoria.', '2026-07-21 08:12:10'),
(1156, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-21 08:18:16'),
(1157, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-21 08:42:32'),
(1158, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-21 08:42:44'),
(1159, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Foram pintadas 09 (nove) cadeiras.', '2026-07-21 08:43:38'),
(1160, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Status atualizado.', '2026-07-21 08:43:52'),
(1161, 124, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada. Campos alterados: Prazo.', '2026-07-21 08:44:30'),
(1162, 124, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada sem alteração de dados.', '2026-07-21 08:47:42'),
(1163, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-21 08:49:07'),
(1164, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-21 08:49:21'),
(1165, 122, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'OS editada sem alteração de dados.', '2026-07-21 08:49:29'),
(1166, 129, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-07-21 09:30:33'),
(1167, 129, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 09:35:29'),
(1168, 129, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 09:35:51'),
(1169, 129, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 09:36:05'),
(1170, 129, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 09:36:20'),
(1171, 129, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Responsável alterado. Auxiliar(es): Samuel Jose da Paz.', '2026-07-21 09:36:51'),
(1172, 129, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Status atualizado.', '2026-07-21 09:36:59'),
(1173, 130, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-07-21 14:07:10'),
(1174, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 14:09:14'),
(1175, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 14:09:26'),
(1176, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 14:09:39'),
(1177, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 14:09:53'),
(1178, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 14:10:45'),
(1179, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 14:10:59'),
(1180, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-21 14:11:13'),
(1181, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'OS editada sem alteração de dados.', '2026-07-21 14:11:42'),
(1182, 129, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-22 13:27:13'),
(1183, 129, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Anexo enviado.', '2026-07-22 13:27:29'),
(1184, 129, 2, 'EM_ANDAMENTO', 'EM_ANDAMENTO', 'Foram lavadas hoje, 22 (vinte duas) partes.', '2026-07-22 13:30:04'),
(1185, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-22 13:46:49'),
(1186, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-22 13:47:09'),
(1187, 130, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-22 13:47:35'),
(1188, 130, 2, 'ATRIBUIDA', 'EM_ANDAMENTO', 'Podamos hoje, algumas árvores do Jardim dos Cisnes.', '2026-07-22 13:48:43'),
(1189, 131, 2, NULL, 'ATRIBUIDA', 'OS criada. SLA: 48 horas.', '2026-07-23 07:24:40'),
(1190, 131, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-23 07:25:06'),
(1191, 131, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-23 07:25:23'),
(1192, 131, 2, 'ATRIBUIDA', 'ATRIBUIDA', 'Anexo enviado.', '2026-07-23 07:25:46'),
(1193, 131, 2, 'ATRIBUIDA', 'ENCERRADA', 'Foi verificado o rompimento do Oring de vedação.\r\nFizemos a substituição do mesmo, sanando o vazamento.', '2026-07-23 07:27:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `locais`
--

CREATE TABLE `locais` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `locais`
--

INSERT INTO `locais` (`id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 'Praça de Alimentação', 'Área da praça de alimentação', 1, '2026-05-05 10:40:36'),
(2, 'Estacionamento', 'Área externa de estacionamento', 1, '2026-05-05 10:40:36'),
(3, 'Corredor Principal', 'Corredor de maior fluxo', 1, '2026-05-05 10:40:36'),
(4, 'Banheiros', 'Banheiros do shopping', 1, '2026-05-05 10:40:36'),
(5, 'Administração', 'Área administrativa', 1, '2026-05-05 10:40:36'),
(6, 'Casa de Máquinas', 'Área técnica', 1, '2026-05-05 10:40:36'),
(7, 'Fachada externa', '', 1, '2026-05-13 13:48:57'),
(8, 'Parque das aves', '', 1, '2026-05-13 13:49:26'),
(9, 'Corredor de serviço ADM', '', 1, '2026-05-13 15:41:52'),
(10, 'Cisterna', '', 1, '2026-05-14 11:47:00'),
(11, 'Casa de Bombas – Água e Incêndio', '', 1, '2026-05-14 11:47:04'),
(12, 'E.T.E', '', 1, '2026-05-14 11:47:10'),
(13, 'Central de Gás', '', 1, '2026-05-14 11:47:15'),
(14, 'Sala de Resíduos', '', 1, '2026-05-14 11:47:21'),
(15, 'Central de Resíduos', '', 1, '2026-05-14 11:47:27'),
(16, 'DOCA', '', 1, '2026-05-14 11:47:32'),
(17, 'Gerador / Subestação', '', 1, '2026-05-14 11:47:37'),
(18, 'Corredor de Serviço – Praça', '', 1, '2026-05-14 11:47:54'),
(19, 'WC Masc. Praça', '', 1, '2026-05-14 11:48:05'),
(20, 'WC Fem. Praça', '', 1, '2026-05-14 11:48:11'),
(21, 'Recepção', '', 1, '2026-05-14 11:48:15'),
(22, 'Sala de Reunião 01', '', 1, '2026-05-14 11:48:19'),
(23, 'Sala de Reunião 02', '', 1, '2026-05-14 11:48:24'),
(24, 'Sala de Reunião 03', '', 1, '2026-05-14 11:48:27'),
(25, 'WC Masc. Mall', '', 1, '2026-05-14 11:48:40'),
(26, 'WC Fem. WC Masc. ', '', 1, '2026-05-14 11:48:46'),
(27, 'WC Fem. Mall', '', 1, '2026-05-14 11:48:57'),
(28, 'WC Família', '', 1, '2026-05-14 11:49:00'),
(29, 'Fraldário', '', 1, '2026-05-14 11:49:05'),
(30, 'Corredor Mall – Estacionamento', '', 1, '2026-05-14 11:49:09'),
(31, 'Corredor Mall – Tropical', '', 1, '2026-05-14 11:49:14'),
(32, 'Corredor Mall – PDU', '', 1, '2026-05-14 11:49:18'),
(33, 'Corredor Mall – C&A', '', 1, '2026-05-14 11:49:35'),
(34, 'Corredor Mall - Preditiva', '', 1, '2026-05-14 11:49:43'),
(35, 'Fachada interna', '', 1, '2026-05-14 11:49:47'),
(36, 'Fachada Externa', '', 1, '2026-05-14 11:49:52'),
(37, 'Entrada Triangular', '', 1, '2026-05-14 11:49:57'),
(38, 'Entrada Retangular', '', 1, '2026-05-14 11:50:02'),
(39, 'Lago dos Cisnes', '', 1, '2026-05-14 11:50:06'),
(40, 'Jardim dos Cisnes', '', 1, '2026-05-14 11:50:10'),
(41, 'Lago Interno', '', 1, '2026-05-14 11:50:15'),
(42, 'Jardim interno', '', 1, '2026-05-14 11:50:22'),
(43, 'Estacionamento Triangular', '', 1, '2026-05-14 11:50:29'),
(44, 'Estacionamento Retangular', '', 1, '2026-05-14 11:50:33'),
(45, 'Estacionamento Motos', '', 1, '2026-05-14 11:50:37'),
(46, 'Estacionamento Bolsão', '', 1, '2026-05-14 11:50:41'),
(47, 'Cancelas de entrada', '', 1, '2026-05-14 11:50:46'),
(48, 'Cancelas de Saídas', '', 1, '2026-05-14 11:50:50'),
(49, 'Portão pedestre', '', 1, '2026-05-14 11:50:54'),
(50, 'Abrigo ônibus', '', 1, '2026-05-14 11:50:58'),
(51, 'Retaguarda DOCA', '', 1, '2026-05-14 11:51:02'),
(52, 'Casa de Máquinas – Ar Cond. Praça', '', 1, '2026-05-14 11:51:09'),
(53, 'Laje Técnica – Corredor ADM', '', 1, '2026-05-14 11:51:14'),
(54, 'Laje Técnica – Praça', '', 1, '2026-05-14 11:51:19'),
(55, 'Sala de Operações', '', 1, '2026-05-14 11:51:24'),
(56, 'CFTV', '', 1, '2026-05-14 11:51:28'),
(57, 'Antílopes', '', 1, '2026-05-14 11:51:32'),
(58, 'Cisnes', '', 1, '2026-05-14 11:51:37'),
(59, 'Emus', '', 1, '2026-05-14 11:51:41'),
(60, 'Sala das Bandejas', '', 1, '2026-06-03 11:25:07'),
(61, 'Vestuário Masculino', '', 1, '2026-06-03 11:25:12'),
(62, 'Vestuário Feminino', '', 1, '2026-06-03 11:25:16'),
(63, 'Refeitório', '', 1, '2026-06-03 11:25:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `identity_hash` char(64) NOT NULL,
  `ip_hash` char(64) NOT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `identity_hash`, `ip_hash`, `successful`, `attempted_at`) VALUES
(1, '2274bbbd34b259c7e02dba2ec9dfacb4d4ced23593e523ccc2a216bf989ec22e', 'cb595da864c20b359c36ad734438af6d934f50e9fb7cc5fc843a2d1b9c793bde', 1, '2026-07-15 22:36:49'),
(2, '7ecc76c708c0131bae24d3129d2d969ca554075315a62a3d38e9ea0657dd4ebe', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-16 08:39:07'),
(3, '7ecc76c708c0131bae24d3129d2d969ca554075315a62a3d38e9ea0657dd4ebe', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-17 08:24:17'),
(4, '493df31697ed2f0d98c0f97e6e36e2e699c4cf0649e7ba25faa5055ac5320bcf', 'b3b02e032302f24ac8dc62f4b1b8a7e4bdb914a3bb772d6d68d953bda5db0a75', 0, '2026-07-17 17:01:45'),
(5, '493df31697ed2f0d98c0f97e6e36e2e699c4cf0649e7ba25faa5055ac5320bcf', 'b3b02e032302f24ac8dc62f4b1b8a7e4bdb914a3bb772d6d68d953bda5db0a75', 0, '2026-07-17 17:02:02'),
(6, '493df31697ed2f0d98c0f97e6e36e2e699c4cf0649e7ba25faa5055ac5320bcf', 'b3b02e032302f24ac8dc62f4b1b8a7e4bdb914a3bb772d6d68d953bda5db0a75', 0, '2026-07-17 17:02:13'),
(7, '493df31697ed2f0d98c0f97e6e36e2e699c4cf0649e7ba25faa5055ac5320bcf', 'b3b02e032302f24ac8dc62f4b1b8a7e4bdb914a3bb772d6d68d953bda5db0a75', 0, '2026-07-17 17:02:25'),
(8, '493df31697ed2f0d98c0f97e6e36e2e699c4cf0649e7ba25faa5055ac5320bcf', 'b3b02e032302f24ac8dc62f4b1b8a7e4bdb914a3bb772d6d68d953bda5db0a75', 0, '2026-07-17 17:04:34'),
(9, '7ecc76c708c0131bae24d3129d2d969ca554075315a62a3d38e9ea0657dd4ebe', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-20 09:09:58'),
(10, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-20 09:11:51'),
(11, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-20 14:44:10'),
(12, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-20 15:57:31'),
(13, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 07:06:27'),
(14, '7ecc76c708c0131bae24d3129d2d969ca554075315a62a3d38e9ea0657dd4ebe', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 08:11:41'),
(15, '10cf21dfa7b1aea82c55974545da86e86704a1444f18c2fa20001337e9cfcfdb', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 10:18:35'),
(16, '2274bbbd34b259c7e02dba2ec9dfacb4d4ced23593e523ccc2a216bf989ec22e', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 10:39:37'),
(17, '2274bbbd34b259c7e02dba2ec9dfacb4d4ced23593e523ccc2a216bf989ec22e', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 10:40:46'),
(18, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 14:04:10'),
(19, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 15:19:17'),
(20, '10cf21dfa7b1aea82c55974545da86e86704a1444f18c2fa20001337e9cfcfdb', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 15:47:09'),
(21, '10cf21dfa7b1aea82c55974545da86e86704a1444f18c2fa20001337e9cfcfdb', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-21 17:00:24'),
(22, '7ecc76c708c0131bae24d3129d2d969ca554075315a62a3d38e9ea0657dd4ebe', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-22 08:23:00'),
(23, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-22 13:24:04'),
(24, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-22 15:34:47'),
(25, '7ecc76c708c0131bae24d3129d2d969ca554075315a62a3d38e9ea0657dd4ebe', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-22 16:42:04'),
(26, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-22 16:42:46'),
(27, '2274bbbd34b259c7e02dba2ec9dfacb4d4ced23593e523ccc2a216bf989ec22e', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-22 17:11:03'),
(28, '828abc2da7e6b8f6968006a5fb2f5e9f3b034e3005cf1ec326350160705fd7f8', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-23 07:05:29'),
(29, '10cf21dfa7b1aea82c55974545da86e86704a1444f18c2fa20001337e9cfcfdb', 'dd4810eba66f6e9d75357d7dad52e781d379a59880f2798ecc73c626cd2d6bfa', 1, '2026-07-23 07:11:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `manutencoes_preventivas`
--

CREATE TABLE `manutencoes_preventivas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `descricao` text DEFAULT NULL,
  `local_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `agente_id` int(11) DEFAULT NULL,
  `prioridade` varchar(20) NOT NULL DEFAULT 'MEDIA',
  `intervalo_dias` int(11) NOT NULL DEFAULT 30,
  `proxima_execucao` date NOT NULL,
  `ultimo_gerado_em` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `manutencoes_preventivas`
--

INSERT INTO `manutencoes_preventivas` (`id`, `titulo`, `descricao`, `local_id`, `categoria_id`, `agente_id`, `prioridade`, `intervalo_dias`, `proxima_execucao`, `ultimo_gerado_em`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(1, 'Limpeza Subestação', 'Limpeza geral da subestação', 17, 8, 9, 'MEDIA', 30, '2026-06-18', '2026-05-19 16:36:25', 1, 2, '2026-05-19 15:57:14', '2026-05-19 16:36:25'),
(2, 'Exaustores Banheiros', 'Limpeza dos Exaustores: WC Fem. Mall, WC Masc. Mall, WC Família, Fraldário, WC Fem. Praça, WC Mas. Praça, Vest. Fem e Vest. Masc.', 4, 8, 4, 'MEDIA', 30, '2026-07-03', '2026-06-03 12:43:01', 1, 2, '2026-06-03 12:43:01', '2026-06-03 12:44:35'),
(3, 'Limpeza Locomotiva', 'Limpeza da Locomotiva', 40, 8, 16, 'MEDIA', 30, '2026-07-03', '2026-06-03 12:44:21', 1, 2, '2026-06-03 12:44:21', '2026-06-03 12:44:21'),
(4, 'Limpeza Retangular', 'Lavar com a maquina toda área da retangular.\r\nTer atenção próximo do quiosque Fondue di Chocolate.', 38, 8, 22, 'MEDIA', 30, '2026-07-03', '2026-06-03 12:50:24', 1, 2, '2026-06-03 12:50:24', '2026-06-03 12:50:24'),
(5, 'limpeza Praça de Alimentação', 'Lavagem da praça de alimentação com a máquina.', 1, 8, 22, 'MEDIA', 30, '2026-07-03', '2026-06-03 12:51:41', 1, 2, '2026-06-03 12:51:41', '2026-06-03 12:51:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `materiais_os`
--

CREATE TABLE `materiais_os` (
  `id` int(11) NOT NULL,
  `ordem_servico_id` int(11) NOT NULL,
  `nome_material` varchar(150) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `unidade` varchar(30) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `materiais_os`
--

INSERT INTO `materiais_os` (`id`, `ordem_servico_id`, `nome_material`, `quantidade`, `unidade`, `observacao`, `criado_em`) VALUES
(1, 9, 'Tinta Piso', 1.00, '1', 'Precisamos pintar o piso.', '2026-05-14 15:23:06'),
(2, 21, 'Lâmpada NITROLUX 9W E27 6500K', 10.00, '10', 'Solicitar orçamento conforme informações', '2026-05-19 07:37:05'),
(3, 43, 'Fechadura La Fonte', 3.00, '03', '', '2026-05-22 07:40:40'),
(4, 69, 'Lâmpada Slim 120cm 36w 6500k', 20.00, 'UND', 'Substituição nos corredores de serviço + DOCA.', '2026-06-01 09:23:29'),
(5, 72, 'Cabo de Rede Cat-6', 25.00, 'M', 'Instalação do telão da copa!', '2026-06-02 10:10:49'),
(6, 72, 'RJ-45', 2.00, 'Und', 'Instalação do telão da copa!', '2026-06-02 10:12:22'),
(7, 104, 'Bucha Drywall nº 02 com Parafusos', 4.00, '4', 'Foi preciso 04 Bucha Drywall nº 02 para a realização do serviço.', '2026-06-09 09:14:16'),
(8, 70, 'Disco de Corte para Serra Mármore - Cortar Porcelanato', 5.00, 'UND', 'Material será usado no acabamento da instalação das válvulas de descargas', '2026-06-16 07:28:58'),
(9, 70, 'Colher de Pedreiro', 1.00, 'UND', 'Material será usado no acabamento da instalação das válvulas de descargas', '2026-06-16 07:30:11'),
(10, 70, 'Desempenadeira dentada para argamassa', 1.00, 'UND', 'Material será usado no acabamento da instalação das válvulas de descargas', '2026-06-16 07:31:45'),
(11, 122, 'Lixa ferro nº 100', 10.00, '', '', '2026-06-25 08:10:16'),
(12, 122, 'Lixa ferro nº 150', 10.00, '', '', '2026-06-25 08:10:35'),
(13, 122, 'Lixa ferro p/ Lixadeira nº100', 5.00, '', '', '2026-06-25 08:10:49'),
(14, 122, 'Cola Almasuper Marceneiro', 2.00, '', '', '2026-06-25 08:11:11'),
(15, 122, 'Fita crepe larga', 3.00, '', '', '2026-06-25 08:11:28'),
(16, 122, 'Tinta Alto brilho Areia Iquine Dialine Seca Rápido 3L', 3.00, '', '', '2026-06-25 08:12:00'),
(17, 122, 'Thinner Iquine 900ml', 2.00, '', '', '2026-06-25 08:12:30'),
(18, 124, 'Lixa madeira nº 150', 10.00, '', '', '2026-06-25 15:39:24'),
(19, 124, 'Lixa madeira nº 100', 10.00, '', '', '2026-06-25 15:39:45'),
(20, 124, 'Lixa Ferro p/ Lixadeira nº150', 5.00, '', '', '2026-06-25 15:40:08'),
(21, 124, 'Thinner Iquine 900ml', 1.00, '', '', '2026-06-25 15:40:26'),
(22, 127, 'Disco de Corte para Serra Mármore - para ferro', 2.00, 'UND', 'Os mesmos serão usados para a extração dos parafusos antigos', '2026-07-21 07:12:56'),
(23, 127, 'Broca Nº 10 - para concreto', 2.00, '', 'A mesma será usada para a instalação dos tachões.', '2026-07-21 07:14:50'),
(24, 127, 'Linha de pedreiro', 1.00, '', 'Instalação dos Tachões', '2026-07-21 07:16:00'),
(25, 127, 'Cola para fixação de Tachões - Balde 5 Kg', 2.00, '', 'Instalação dos Tachões', '2026-07-21 07:26:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_estoque`
--

CREATE TABLE `movimentacoes_estoque` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `ordem_servico_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` varchar(30) NOT NULL,
  `quantidade` decimal(12,2) NOT NULL,
  `custo_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordem_agentes`
--

CREATE TABLE `ordem_agentes` (
  `id` int(11) NOT NULL,
  `ordem_servico_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `papel` enum('PRINCIPAL','AUXILIAR') NOT NULL DEFAULT 'AUXILIAR',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ordem_agentes`
--

INSERT INTO `ordem_agentes` (`id`, `ordem_servico_id`, `usuario_id`, `papel`, `criado_por`, `criado_em`) VALUES
(1, 4, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(2, 12, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(3, 16, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(4, 20, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(5, 21, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(6, 33, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(7, 34, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(8, 46, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(9, 47, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(10, 53, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(11, 54, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(12, 59, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(13, 60, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(15, 70, 3, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(17, 3, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(18, 7, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(19, 10, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(20, 18, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(21, 29, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(22, 30, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(23, 31, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(24, 48, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(25, 49, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(26, 55, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(27, 62, 4, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(28, 5, 5, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(29, 8, 5, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(30, 15, 5, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(31, 17, 5, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(32, 25, 5, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(33, 28, 5, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(34, 51, 5, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(35, 6, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(36, 14, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(37, 19, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(38, 24, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(39, 35, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(40, 56, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(41, 57, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(42, 63, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(43, 64, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(44, 65, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(45, 66, 6, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(47, 9, 8, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(48, 11, 8, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(49, 13, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(50, 22, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(51, 26, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(52, 27, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(53, 42, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(54, 45, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(55, 50, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(56, 52, 9, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(57, 23, 10, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(58, 32, 12, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(59, 36, 12, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(60, 37, 12, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(61, 38, 12, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(62, 39, 12, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(65, 67, 12, 'PRINCIPAL', NULL, '2026-06-02 10:21:01'),
(160, 72, 3, 'PRINCIPAL', 1, '2026-06-02 10:38:55'),
(161, 72, 6, 'AUXILIAR', 1, '2026-06-02 10:38:55'),
(166, 73, 9, 'PRINCIPAL', NULL, '2026-06-02 10:42:47'),
(238, 75, 4, 'PRINCIPAL', NULL, '2026-06-02 11:35:20'),
(264, 43, 3, 'PRINCIPAL', 2, '2026-06-02 11:37:29'),
(273, 74, 3, 'PRINCIPAL', 2, '2026-06-02 11:38:07'),
(322, 44, 8, 'PRINCIPAL', 2, '2026-06-02 11:45:39'),
(323, 44, 6, 'AUXILIAR', 2, '2026-06-02 11:45:39'),
(352, 76, 9, 'PRINCIPAL', 2, '2026-06-02 12:25:31'),
(353, 76, 6, 'AUXILIAR', 2, '2026-06-02 12:25:31'),
(445, 78, 9, 'PRINCIPAL', 2, '2026-06-02 13:52:10'),
(612, 86, 4, 'PRINCIPAL', NULL, '2026-06-02 16:16:10'),
(626, 68, 6, 'PRINCIPAL', 2, '2026-06-03 07:12:49'),
(627, 68, 8, 'AUXILIAR', 2, '2026-06-03 07:12:49'),
(645, 69, 5, 'PRINCIPAL', 2, '2026-06-03 07:19:19'),
(748, 88, 3, 'PRINCIPAL', NULL, '2026-06-03 12:33:26'),
(764, 87, 16, 'PRINCIPAL', 2, '2026-06-03 12:36:20'),
(765, 87, 19, 'AUXILIAR', 2, '2026-06-03 12:36:20'),
(777, 89, 4, 'PRINCIPAL', NULL, '2026-06-03 12:43:01'),
(787, 91, 22, 'PRINCIPAL', NULL, '2026-06-03 12:50:24'),
(789, 92, 22, 'PRINCIPAL', NULL, '2026-06-03 12:51:41'),
(841, 93, 3, 'PRINCIPAL', NULL, '2026-06-03 15:53:49'),
(873, 94, 6, 'PRINCIPAL', NULL, '2026-06-03 16:11:45'),
(888, 95, 4, 'PRINCIPAL', NULL, '2026-06-03 16:23:21'),
(929, 96, 10, 'PRINCIPAL', NULL, '2026-06-04 08:42:00'),
(973, 97, 9, 'PRINCIPAL', NULL, '2026-06-04 15:59:29'),
(987, 98, 9, 'PRINCIPAL', NULL, '2026-06-04 16:05:03'),
(1094, 100, 4, 'PRINCIPAL', NULL, '2026-06-05 15:32:03'),
(1145, 101, 9, 'PRINCIPAL', NULL, '2026-06-08 07:47:31'),
(1152, 102, 6, 'PRINCIPAL', NULL, '2026-06-09 07:49:10'),
(1158, 103, 9, 'PRINCIPAL', NULL, '2026-06-09 07:53:09'),
(1225, 104, 9, 'PRINCIPAL', NULL, '2026-06-09 09:02:05'),
(1387, 105, 9, 'PRINCIPAL', 2, '2026-06-10 14:13:52'),
(1427, 106, 6, 'PRINCIPAL', 2, '2026-06-11 11:18:31'),
(1450, 99, 12, 'PRINCIPAL', 2, '2026-06-11 11:45:41'),
(1451, 99, 3, 'AUXILIAR', 2, '2026-06-11 11:45:41'),
(1462, 107, 4, 'PRINCIPAL', NULL, '2026-06-11 11:49:43'),
(1516, 108, 3, 'PRINCIPAL', NULL, '2026-06-11 16:19:57'),
(1556, 109, 6, 'PRINCIPAL', 2, '2026-06-11 16:37:39'),
(1577, 110, 8, 'PRINCIPAL', 2, '2026-06-11 17:01:07'),
(1578, 110, 6, 'AUXILIAR', 2, '2026-06-11 17:01:07'),
(1620, 111, 8, 'PRINCIPAL', NULL, '2026-06-12 10:51:09'),
(1709, 112, 9, 'PRINCIPAL', NULL, '2026-06-15 10:29:16'),
(1716, 113, 3, 'PRINCIPAL', NULL, '2026-06-15 10:53:24'),
(1741, 114, 6, 'PRINCIPAL', NULL, '2026-06-15 14:20:44'),
(2035, 116, 10, 'PRINCIPAL', NULL, '2026-06-17 16:45:33'),
(2056, 117, 4, 'PRINCIPAL', NULL, '2026-06-18 07:08:43'),
(2102, 115, 9, 'PRINCIPAL', 2, '2026-06-18 07:47:49'),
(2103, 115, 3, 'AUXILIAR', 2, '2026-06-18 07:47:49'),
(2152, 118, 19, 'PRINCIPAL', 2, '2026-06-18 12:09:53'),
(2153, 118, 16, 'AUXILIAR', 2, '2026-06-18 12:09:53'),
(2214, 90, 16, 'PRINCIPAL', 2, '2026-06-18 16:09:34'),
(2215, 90, 19, 'AUXILIAR', 2, '2026-06-18 16:09:34'),
(2254, 120, 16, 'PRINCIPAL', 2, '2026-06-19 07:23:54'),
(2255, 120, 19, 'AUXILIAR', 2, '2026-06-19 07:23:54'),
(2279, 119, 9, 'PRINCIPAL', 2, '2026-06-19 07:31:40'),
(2539, 122, 4, 'PRINCIPAL', NULL, '2026-06-22 09:23:11'),
(2597, 123, 4, 'PRINCIPAL', NULL, '2026-06-25 08:05:42'),
(2646, 124, 5, 'PRINCIPAL', NULL, '2026-06-25 15:35:48'),
(2670, 125, 4, 'PRINCIPAL', NULL, '2026-06-25 16:21:07'),
(2762, 126, 3, 'PRINCIPAL', NULL, '2026-06-30 10:24:52'),
(2796, 121, 3, 'PRINCIPAL', 2, '2026-06-30 13:23:56'),
(2806, 41, 3, 'PRINCIPAL', 2, '2026-06-30 13:26:06'),
(2807, 41, 6, 'AUXILIAR', 2, '2026-06-30 13:26:06'),
(2816, 40, 12, 'PRINCIPAL', 2, '2026-06-30 13:44:30'),
(3376, 127, 4, 'PRINCIPAL', 31, '2026-07-20 09:30:39'),
(3397, 128, 6, 'PRINCIPAL', NULL, '2026-07-21 07:08:19'),
(3529, 129, 8, 'PRINCIPAL', 2, '2026-07-21 09:36:51'),
(3530, 129, 6, 'AUXILIAR', 2, '2026-07-21 09:36:51'),
(3552, 130, 10, 'PRINCIPAL', NULL, '2026-07-21 14:07:11'),
(3622, 131, 9, 'PRINCIPAL', NULL, '2026-07-23 07:24:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordem_patrimonios`
--

CREATE TABLE `ordem_patrimonios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ordem_id` int(11) NOT NULL,
  `patrimonio_externo_id` varchar(190) DEFAULT NULL,
  `codigo_barras` varchar(190) DEFAULT NULL,
  `numero_patrimonio` varchar(190) DEFAULT NULL,
  `descricao_snapshot` varchar(255) DEFAULT NULL,
  `marca_snapshot` varchar(120) DEFAULT NULL,
  `modelo_snapshot` varchar(120) DEFAULT NULL,
  `serie_snapshot` varchar(190) DEFAULT NULL,
  `setor_snapshot` varchar(190) DEFAULT NULL,
  `localizacao_snapshot` varchar(255) DEFAULT NULL,
  `origem` varchar(50) NOT NULL DEFAULT 'BANCO_EXTERNO',
  `consultado_por` int(11) NOT NULL,
  `consultado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_por` int(11) DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  `desvinculado_por` int(11) DEFAULT NULL,
  `desvinculado_em` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordens_servico`
--

CREATE TABLE `ordens_servico` (
  `id` int(11) NOT NULL,
  `numero_os` varchar(30) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `local_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `prioridade` enum('BAIXA','MEDIA','ALTA','CRITICA') NOT NULL,
  `status` enum('ABERTA','ATRIBUIDA','EM_ANDAMENTO','PAUSADA','AGUARDANDO_MATERIAL','CONCLUIDA_PELO_AGENTE','REPROVADA','ENCERRADA') DEFAULT 'ABERTA',
  `solicitante_id` int(11) NOT NULL,
  `gestor_id` int(11) NOT NULL,
  `agente_id` int(11) DEFAULT NULL,
  `prazo` datetime DEFAULT NULL,
  `valor_previsto_orcamento` decimal(10,2) DEFAULT NULL,
  `semestre_previsto` tinyint(4) DEFAULT NULL,
  `ano_previsto` year(4) DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `data_encerramento` datetime DEFAULT NULL,
  `aprovado_por` int(11) DEFAULT NULL,
  `observacao_aprovacao` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sla_horas` int(11) DEFAULT NULL,
  `sla_vencimento` datetime DEFAULT NULL,
  `origem` varchar(30) NOT NULL DEFAULT 'MANUAL',
  `preventiva_id` int(11) DEFAULT NULL,
  `custo_materiais` decimal(12,2) NOT NULL DEFAULT 0.00,
  `solicitacao_encerramento` tinyint(1) DEFAULT 0,
  `solicitacao_encerramento_data` datetime DEFAULT NULL,
  `solicitacao_encerramento_obs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `ordens_servico`
--

INSERT INTO `ordens_servico` (`id`, `numero_os`, `titulo`, `descricao`, `local_id`, `categoria_id`, `prioridade`, `status`, `solicitante_id`, `gestor_id`, `agente_id`, `prazo`, `valor_previsto_orcamento`, `semestre_previsto`, `ano_previsto`, `data_inicio`, `data_conclusao`, `data_encerramento`, `aprovado_por`, `observacao_aprovacao`, `criado_em`, `atualizado_em`, `sla_horas`, `sla_vencimento`, `origem`, `preventiva_id`, `custo_materiais`, `solicitacao_encerramento`, `solicitacao_encerramento_data`, `solicitacao_encerramento_obs`) VALUES
(3, 'OS-20260512-0001', 'Pintura dos pilares do mall', '- Verificar se os pilares precisa de retoque de massa.\r\n- Pintura dos pilares, iniciando pela retangular, corredor C&A.', 3, 5, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-13 00:00:00', NULL, NULL, NULL, '2026-05-14 07:27:32', '2026-06-08 07:30:13', '2026-06-08 07:30:13', 2, '', '2026-05-12 14:33:26', '2026-06-08 07:30:13', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(4, 'OS-20260512-0002', 'Iluminação', 'Masc. Praça - 01 Unidade\r\nFem. Praça - 02 Unidades\r\nMasc. Mall - 01 Unidade\r\nFem. Mall - 02 Unidades\r\nFraldário - 04 Unidades\r\nWc Família - 01 Unidade\r\nFem. Mall 03 unidade - Luminárias diferentes', 4, 1, 'ALTA', 'ENCERRADA', 2, 2, 3, '2026-05-12 22:00:00', NULL, NULL, NULL, '2026-05-14 16:36:16', NULL, '2026-05-18 10:39:09', 2, '', '2026-05-12 14:54:43', '2026-05-18 10:39:09', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(5, 'OS-20260513-0001', 'Troca de Lâmpada - Sala de Máquinas - Ar Praça de Alimentação', 'Substituição da lâmpada da sala de maquinas, próxima a porta de entrada.', 6, 1, 'BAIXA', 'ENCERRADA', 2, 2, 5, '2026-05-13 00:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-14 14:54:10', 2, '- Foram trocadas 02 (duas) Lâmpadas Nitrolux 9W', '2026-05-13 14:56:52', '2026-05-14 14:54:54', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(6, 'OS-20260513-0002', 'Capinação - Jardim dos Cisnes', 'Capinação do jardim dos cisnes', 8, 9, 'ALTA', 'ENCERRADA', 2, 2, 6, '2026-05-15 15:00:00', NULL, NULL, NULL, '2026-05-14 07:26:40', NULL, '2026-05-14 15:52:36', 2, '', '2026-05-13 15:13:15', '2026-05-14 15:52:36', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(7, 'OS-20260513-0003', 'Instalação da fonte do AP', 'Instalação da fonte do AP corredor do Guichê do estacionamento', 9, 10, 'MEDIA', 'ENCERRADA', 1, 1, 4, '2026-05-13 00:00:00', NULL, NULL, NULL, NULL, '2026-05-19 10:28:38', '2026-05-19 10:28:38', 1, '', '2026-05-13 15:40:46', '2026-05-19 10:28:38', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(8, 'OS-20260513-0004', 'Retirada de roteador ', 'Retirada de roteador do espaço cliente onde ira se instalar driftpark.', 3, 10, 'BAIXA', 'ENCERRADA', 1, 1, 5, '2026-05-13 22:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-14 15:08:41', 1, 'Retirada de roteador do espaço cliente onde ira se instalar driftpark.', '2026-05-13 15:43:27', '2026-05-14 15:08:41', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(9, 'OS-20260514-0001', 'Limpeza Sala de Maquinas', '- Realizar a limpeza da área para recebimento da pintura.', 52, 8, 'MEDIA', 'ENCERRADA', 2, 2, 8, '2026-05-14 16:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-14 15:53:21', 2, '', '2026-05-14 15:04:39', '2026-05-14 15:53:21', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(10, 'OS-20260514-0002', 'Infiltração Calha', 'Infiltração no corredor de serviço ADM próximo a entrada de serviço C&A / LASA, supostamente pela calha. ', 9, 7, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-14 16:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-14 16:05:49', 2, '', '2026-05-14 15:56:30', '2026-05-14 16:05:49', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(11, 'OS-20260514-0003', 'Lavagem do ACM interno', 'Lavagem do ACM interno - Corredor Mall', 35, 8, 'MEDIA', 'ENCERRADA', 2, 2, 8, '2026-07-17 10:00:00', NULL, NULL, NULL, '2026-05-14 16:38:46', '2026-07-17 09:01:03', '2026-07-17 09:01:03', 31, '', '2026-05-14 16:29:22', '2026-07-17 09:01:03', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(12, 'OS-20260514-0004', 'Estrutura solta', 'Chumbar estrutura da saída de pedestre. Serviço deverá ser realizado na noite do Sábado para o domingo, dando tempo para a secagem do concreto.', 49, 10, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-29 07:00:00', NULL, NULL, NULL, NULL, '2026-05-28 09:28:47', '2026-05-28 09:34:30', 2, '', '2026-05-14 16:34:17', '2026-05-28 09:34:30', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(13, 'OS-20260515-0001', 'Limpeza Subestação', 'Limpeza geral da Subestação', 17, 8, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-05-20 00:00:00', NULL, NULL, NULL, '2026-05-15 07:24:59', NULL, '2026-05-18 11:07:39', 2, '', '2026-05-15 07:19:49', '2026-05-18 11:07:39', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(14, 'OS-20260515-0002', 'Poda de galhos', 'Poda dos galhos das TECAS em frente a TROPICAL.', 42, 7, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-15 15:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-15 11:50:33', 2, '', '2026-05-15 11:38:47', '2026-05-15 11:50:33', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(15, 'OS-20260515-0003', 'substituição de Lâmpada', 'Foi substituída 02 (duas) lâmpadas NITROLUX 9W', 11, 1, 'MEDIA', 'ENCERRADA', 2, 2, 5, '2026-05-13 15:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:57:48', 2, '', '2026-05-15 11:52:48', '2026-05-18 10:57:48', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(16, 'OS-20260518-0001', 'Assento sanitário', 'Substituição do assento sanitário 01 (um) do vestuário feminino.', 4, 7, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-18 10:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:38:25', 2, '', '2026-05-18 09:20:00', '2026-05-18 10:38:25', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(17, 'OS-20260518-0002', 'Limpeza Gerador', 'Limpeza na parte externa do gerador ', 17, 8, 'MEDIA', 'ENCERRADA', 2, 2, 5, '2026-05-15 11:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:57:21', 2, '', '2026-05-18 10:41:15', '2026-05-18 10:57:21', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(18, 'OS-20260518-0003', 'Exaustores ', 'Limpeza dos exaustores', 27, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-19 05:00:00', NULL, NULL, NULL, NULL, NULL, '2026-05-19 07:26:37', 2, '', '2026-05-18 15:13:01', '2026-05-19 07:26:37', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(19, 'OS-20260518-0004', 'Capinação', 'Capinação frente do Shopping, entrada principal dos veículos, sentido entrada de pedestre.', 50, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2025-05-18 00:00:00', NULL, NULL, NULL, NULL, '2026-05-19 11:52:41', '2026-05-19 11:52:41', 2, '', '2026-05-18 15:21:23', '2026-05-19 11:52:41', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(20, 'OS-20260518-0005', 'Iluminação', 'Substituição lâmpadas laje técnica', 53, 7, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-18 18:30:00', NULL, NULL, NULL, NULL, '2026-05-19 11:51:13', '2026-05-19 11:51:13', 2, '', '2026-05-18 16:04:51', '2026-05-19 11:51:13', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(21, 'OS-20260518-0006', 'Iluminação', 'Substituição lâmpadas laje técnica', 54, 7, 'MEDIA', 'PAUSADA', 2, 2, 3, '2026-06-30 15:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-18 16:07:15', '2026-06-30 10:08:25', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(22, 'OS-20260518-0007', 'Impermeabilização clara-boia', 'Substituir manta asfáltica da lateral da clara- boia.', 54, 16, 'MEDIA', 'EM_ANDAMENTO', 2, 2, 9, '2026-06-30 18:00:00', NULL, NULL, NULL, '2026-05-19 12:05:04', NULL, NULL, NULL, NULL, '2026-05-18 16:38:10', '2026-06-15 08:27:52', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(23, 'OS-20260518-0008', 'Preparação de terra - Grama Amendoim', 'Preparação da terra para o plantio das mudas de grama amendoim', 40, 14, 'MEDIA', 'ENCERRADA', 2, 2, 10, '2026-05-20 16:00:00', NULL, NULL, NULL, '2026-05-19 12:04:54', '2026-05-21 08:15:20', '2026-05-21 08:15:20', 2, '', '2026-05-18 16:57:14', '2026-05-21 08:15:20', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(24, 'OS-20260519-0001', 'Capinação', 'Capinação ilhas cancelas de saídas', 43, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-06-19 16:00:00', NULL, NULL, NULL, '2026-05-19 12:04:44', '2026-05-22 09:45:39', '2026-05-22 09:45:39', 2, '', '2026-05-19 07:30:56', '2026-05-22 09:45:39', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(25, 'OS-20260519-0002', 'Mesas Praça de Alimentação', 'Aperto dos parafusos das mesas da praça.', 1, 15, 'MEDIA', 'ENCERRADA', 2, 2, 5, '2026-05-20 06:00:00', NULL, NULL, NULL, NULL, '2026-05-20 07:36:16', '2026-05-20 07:36:16', 2, '', '2026-05-19 12:17:09', '2026-05-20 07:36:16', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(26, 'OS-20260519-0003', 'Ar condicionado Praça TR 10', 'Em visita técnico da empresa CG Climatização, foi identificado baixa pressão de gás na linha que liga as duas máquinas. \r\nA empresa foi de enviar uma proposta para avaliação e possível aprovação do serviço.', 1, 4, 'ALTA', 'ENCERRADA', 2, 2, 9, '2026-05-22 16:00:00', NULL, NULL, NULL, NULL, '2026-05-21 14:56:11', '2026-05-21 14:56:11', 2, '', '2026-05-19 15:49:42', '2026-05-21 14:56:11', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(27, 'OS-20260519-0004', '[PREVENTIVA] Limpeza Subestação', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLimpeza geral da subestação', 17, 8, 'MEDIA', 'ENCERRADA', 1, 1, 9, '2026-06-16 12:00:00', NULL, NULL, NULL, NULL, '2026-06-18 15:46:09', '2026-06-18 15:46:09', 2, 'Limpeza Realizada.\r\nPróxima programada para o dia 14.07.2026.', '2026-05-19 16:36:25', '2026-06-18 15:46:09', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(28, 'OS-20260520-0001', 'Falta de energia - Abastecimento de combustível', 'Devido a falta de energia no dia 19.05.2026, foi solicitado o apoio a usina para o abastecimento do gerador.\r\nFoi abastecido 162 litros no tanque do gerador e 240 litros no tanque reserva, totalizando 402 litros de diesel.', 17, 7, 'MEDIA', 'ENCERRADA', 2, 2, 5, '2026-05-19 21:00:00', NULL, NULL, NULL, NULL, '2026-05-20 07:57:09', '2026-05-20 07:57:09', 2, '', '2026-05-20 07:44:58', '2026-05-20 07:57:09', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(29, 'OS-20260520-0002', 'Exaustores', 'Limpeza das saídas dos exautores', 25, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-21 10:00:00', NULL, NULL, NULL, NULL, '2026-05-21 08:24:13', '2026-05-21 08:24:13', 2, '', '2026-05-20 09:07:41', '2026-05-21 08:24:13', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(30, 'OS-20260520-0003', 'Exaustores', 'Limpeza das saídas dos exaustores', 28, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-20 10:00:00', NULL, NULL, NULL, NULL, '2026-05-20 09:13:53', '2026-05-20 09:13:53', 2, '', '2026-05-20 09:09:47', '2026-05-20 09:13:53', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(31, 'OS-20260520-0004', 'Exaustores', 'Limpeza das saídas dos exaustores', 29, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-25 10:00:00', NULL, NULL, NULL, NULL, '2026-05-26 09:33:06', '2026-05-26 09:33:06', 2, '', '2026-05-20 09:17:05', '2026-05-26 09:33:06', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(32, 'OS-20260520-0005', 'Alinhamento de  PAVER', 'Alinhamento dos PAVER -  Caixas de gorduras estacionamento próximo as cancelas de saídas + Carregador de Elétricos.', 37, 11, 'MEDIA', 'PAUSADA', 2, 2, 12, '2026-06-30 17:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-20 13:15:32', '2026-06-18 07:47:12', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(33, 'OS-20260520-0006', 'Infiltração - Preditiva', 'Aplicação de PU na descida da tubulação dos ar condicionados.', 54, 4, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-16 10:00:00', NULL, NULL, NULL, NULL, '2026-05-20 15:20:36', '2026-05-20 15:20:36', 2, '', '2026-05-20 15:12:42', '2026-05-20 15:20:36', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(34, 'OS-20260520-0007', 'Infiltração - ADM - Área próxima a QUALIDADE', 'Aplicação de PU na descida dos tubos do ar condicionado do ADM SC - Ar condicionado 24 mil Btus', 53, 16, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-20 16:00:00', NULL, NULL, NULL, NULL, '2026-05-20 15:30:24', '2026-05-20 15:30:24', 2, '', '2026-05-20 15:29:21', '2026-05-20 15:30:24', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(35, 'OS-20260521-0001', 'Plantação de grama amendoim', 'Plantio de 2.560 mudas de grama amendoim no jardim do LAGO MALL.', 42, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-22 16:00:00', NULL, NULL, NULL, NULL, '2026-05-22 16:03:50', '2026-05-22 16:03:50', 2, '', '2026-05-21 11:32:40', '2026-05-22 16:03:50', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(36, 'OS-20260521-0002', 'Alagamento no Mall - Ampliação das calha de piso', 'Nos dias de chuvas intensas, as canaletas de piso em frente ao quiosque Turbo Kids e em frente da Mich Store não suporta e causa alagamentos.', 3, 10, 'MEDIA', 'PAUSADA', 2, 2, 12, '2026-06-30 16:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 12:30:36', '2026-05-22 09:03:47', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(37, 'OS-20260521-0003', 'Sistema de descargas', 'Instalação de válvulas de descargas', 20, 2, 'ALTA', 'PAUSADA', 2, 2, 12, '2026-06-30 16:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 12:38:29', '2026-05-22 09:03:34', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(38, 'OS-20260521-0004', 'Sistema de descargas', 'Instalação de válvulas de descargas', 28, 2, 'ALTA', 'PAUSADA', 2, 2, 12, '2026-06-30 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 12:42:35', '2026-05-22 09:03:15', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(39, 'OS-20260521-0005', 'Sistema de descargas', 'Instalação de válvulas de descargas', 25, 2, 'MEDIA', 'PAUSADA', 2, 2, 12, '2026-06-30 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 12:44:47', '2026-05-22 09:03:02', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(40, 'OS-20260521-0006', 'Sistema de descargas - Vestuário Masculino', 'Instalação de válvulas de descargas', 4, 2, 'ALTA', 'ATRIBUIDA', 2, 2, 12, '2026-06-30 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 12:46:52', '2026-06-30 13:44:30', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(41, 'OS-20260521-0007', 'Sistema de descargas - Vestuário Feminino', 'Sistema de descargas', 4, 2, 'ALTA', 'ENCERRADA', 2, 2, 3, '2026-06-30 00:00:00', NULL, NULL, NULL, NULL, '2026-06-30 13:26:13', '2026-06-30 13:26:13', 2, '', '2026-05-21 12:47:33', '2026-06-30 13:26:13', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(42, 'OS-20260522-0001', 'Limpeza sala do marketing', 'Limpeza e arrumação da sala do marketing', 5, 8, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-05-21 16:00:00', NULL, NULL, NULL, NULL, '2026-05-22 07:31:52', '2026-05-22 07:31:52', 2, '', '2026-05-22 07:17:28', '2026-05-22 07:31:52', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(43, 'OS-20260522-0002', 'Fechaduras - Operações / CFTV', 'Substituição de três fechaduras, nas salas de operações e CFTV.', 56, 7, 'MEDIA', 'ATRIBUIDA', 2, 2, 3, '2026-06-19 16:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-22 07:36:44', '2026-06-15 08:02:51', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(44, 'OS-20260522-0003', 'Limpeza fachada', 'Limpeza da Fachada externa - Parede dos letreiros externo - C&A, Uninassau, Americanas e a parede da Faculdade na Retangular.', 36, 8, 'MEDIA', 'ENCERRADA', 2, 2, 8, '2026-06-15 16:00:00', NULL, NULL, NULL, NULL, '2026-06-10 14:22:11', '2026-06-10 14:22:11', 2, '', '2026-05-22 07:44:12', '2026-06-10 14:22:11', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(45, 'OS-20260522-0004', 'Recuperação piso Parquinho', 'Substituir tábua do piso da casa do parquinho', 39, 15, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-05-23 10:00:00', NULL, NULL, NULL, NULL, '2026-05-25 10:27:58', '2026-05-25 10:27:58', 2, '', '2026-05-22 10:04:45', '2026-05-25 10:27:58', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(46, 'OS-20260522-0005', 'Troca de baterias do Nobreak da cancela de entrada', 'Troca das baterias da cancela de entrada.', 47, 10, 'CRITICA', 'ENCERRADA', 1, 1, 3, '2026-05-22 08:00:00', NULL, NULL, NULL, NULL, '2026-05-22 15:33:38', '2026-05-22 15:33:38', 1, 'Foram feitas as trocas e devidos testes!', '2026-05-22 15:33:05', '2026-05-22 15:39:50', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(47, 'OS-20260522-0006', 'Troca / instalação das câmeras das cancelas de saída', 'Instalação das câmeras da cancela de saída e troca da queimada.', 48, 10, 'MEDIA', 'ENCERRADA', 1, 1, 3, '2026-05-22 10:00:00', NULL, NULL, NULL, NULL, '2026-05-22 15:39:29', '2026-05-22 15:39:29', 1, 'Troca feita testes executados', '2026-05-22 15:39:14', '2026-05-22 15:39:29', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(48, 'OS-20260525-0001', 'Exaustores', 'Limpeza do sistema de exaustão', 20, 2, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-24 07:00:00', NULL, NULL, NULL, NULL, '2026-05-25 10:34:47', '2026-05-25 10:34:47', 2, '', '2026-05-25 10:32:54', '2026-05-25 10:34:47', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(49, 'OS-20260525-0002', 'Exaustores', 'Limpeza do sistema de exaustão', 19, 2, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-24 07:00:00', NULL, NULL, NULL, NULL, '2026-05-25 10:36:53', '2026-05-25 10:36:53', 2, '', '2026-05-25 10:35:46', '2026-05-25 10:36:53', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(50, 'OS-20260525-0003', 'Ar condicionado Praça TR 15', 'Ar condicionado da Praça (TR15) apresentou problema, compressor ficou entrando e saindo.\r\nFoi observado que a mesma estava congelada.\r\nAbrimos cotações com a CG Climatização e JM Engenharia.', 54, 4, 'ALTA', 'ENCERRADA', 2, 2, 9, '2026-05-29 16:00:00', NULL, NULL, NULL, NULL, '2026-05-27 07:56:54', '2026-05-27 07:56:54', 2, '', '2026-05-25 14:13:11', '2026-05-27 07:56:54', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(51, 'OS-20260525-0004', 'Pintura do ATM', 'Apoio do agente de manutenção no desligamento da energia e da internet, pois o ATM será pintado por um terceiro.\r\nLevar o mesmo para DOCA. \r\nApós a pintura, colocar no lugar e ligar o mesmo.', 37, 5, 'MEDIA', 'ENCERRADA', 2, 2, 5, '2026-05-27 14:00:00', NULL, NULL, NULL, NULL, '2026-05-27 07:26:54', '2026-05-27 07:26:54', 2, '', '2026-05-25 15:11:16', '2026-05-27 07:26:54', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(52, 'OS-20260525-0005', 'Troca / instalação de câmera na cancela de entrada', 'Troca da Câmera da cancela de entrada', 47, 14, 'BAIXA', 'ENCERRADA', 1, 1, 9, '2026-05-25 16:20:00', NULL, NULL, NULL, NULL, '2026-05-25 16:15:53', '2026-05-25 16:16:15', 1, 'troca feita', '2026-05-25 16:15:34', '2026-05-25 16:16:15', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(53, 'OS-20260526-0001', 'Infiltração - Preditiva', 'Infiltração pelos dutos dos ar condicionados.\r\nSerá aplicado PU nas descidas das tubulações reforçando pontos que estão causando a infiltração.', 54, 16, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-28 16:00:00', NULL, NULL, NULL, NULL, '2026-05-27 07:43:03', '2026-05-27 07:43:03', 2, '', '2026-05-26 10:15:23', '2026-05-27 07:43:03', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(54, 'OS-20260526-0002', 'Evento odontológico - Praça de Alimentação', 'Retirada das mesas redondas da praça de alimentação.\r\nA retirada deverá ser realizada antes da abertura do Shopping.\r\nO evento acontecerá das 18hs às 22hs.', 1, 10, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-28 09:00:00', NULL, NULL, NULL, NULL, '2026-05-28 09:21:54', '2026-05-28 09:21:54', 2, '', '2026-05-26 12:33:17', '2026-05-28 09:21:54', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(55, 'OS-20260526-0003', 'Evento odontológico - Praça de Alimentação', 'Montagem das mesas redondas da praça de alimentação.', 1, 10, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-29 07:00:00', NULL, NULL, NULL, NULL, '2026-05-29 07:26:50', '2026-05-29 07:26:50', 2, '', '2026-05-26 12:58:46', '2026-05-29 07:26:50', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(56, 'OS-20260526-0004', 'Capinação', 'Capinação acesso de pedestre até cancela de saída.\r\nO serviço foi realizado nos dias 25 e 26.05.2026.', 2, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-26 17:00:00', NULL, NULL, NULL, NULL, '2026-05-26 15:55:14', '2026-05-26 15:55:14', 2, '', '2026-05-26 15:44:08', '2026-05-26 15:55:14', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(57, 'OS-20260527-0001', 'Capinação', 'Capinação e limpeza do gradil.', 16, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-29 16:00:00', NULL, NULL, NULL, '2026-05-27 08:07:51', '2026-05-29 07:28:56', '2026-05-29 07:28:56', 2, '', '2026-05-27 08:01:43', '2026-05-29 07:28:56', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(58, 'OS-20260527-0002', 'Troca de baterias do Nobreak da subestação', 'Troca das baterias da subestação.', 17, 6, 'ALTA', 'ENCERRADA', 1, 1, NULL, '2026-06-05 22:00:00', NULL, NULL, NULL, NULL, '2026-06-18 10:57:24', '2026-06-18 10:57:24', 2, '', '2026-05-27 08:04:35', '2026-06-18 10:57:24', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(59, 'OS-20260527-0003', 'Substituição das TVs', 'Substituição das TVs: Sala de Reunião 02 com Sala de Reunião 03.', 23, 7, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-05-26 18:00:00', NULL, NULL, NULL, NULL, '2026-05-27 08:57:45', '2026-05-27 08:57:45', 2, '', '2026-05-27 08:54:45', '2026-05-27 08:57:45', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(60, 'OS-20260527-0004', 'Medidores de energia', 'Medidores de energia com INVERSÃO DE FASES - Loja 1000 - Loja 1001.', 54, 1, 'MEDIA', 'ATRIBUIDA', 2, 2, 3, '2026-07-15 16:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-27 15:45:48', '2026-06-29 07:34:41', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(61, 'OS-20260528-0001', '[CHAMADO T260528-D97B1E] Camera Speed do Bolsão', 'Chamado encaminhado pelo sistema de Tickets para o GEOP.\n\nChamado: T260528-D97B1E\nSolicitante: Thiago Gomes Heráclio\nE-mail: operacoes@shoppingcarpina.com.br\nTelefone: 81992076708\nEmpresa/setor: -\nPrioridade no chamado: Alta\n\nObservacao do encaminhamento:\nCâmera do bolsão sem funcionar SpeedDome, favor direcionar agente para manuntenção.\n\nDescricao original:\nCâmera do bolsão sem funcionar SpeedDome\n\nLink do chamado no painel:\nhttps://tickets.shoppingcarpina.com.br/ver_chamado.php?id=52', 56, 6, 'ALTA', 'ENCERRADA', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-02 11:21:48', '2026-06-02 11:21:48', 1, '', '2026-05-28 09:18:29', '2026-06-02 11:21:48', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(62, 'OS-20260528-0002', 'Caixa Fluvial', 'Recuperação da tampa da caixa passagem fluvial do corredor de serviço - Praça de Alimentação', 18, 10, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-05-30 18:34:57', NULL, NULL, NULL, NULL, '2026-05-29 07:28:13', '2026-05-29 07:28:13', 2, '', '2026-05-28 15:34:57', '2026-05-29 07:28:13', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(63, 'OS-20260528-0003', 'Capinação', 'Capinação do jardim das cancelas de saídas', 48, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-28 17:00:00', NULL, NULL, NULL, NULL, '2026-05-28 16:55:50', '2026-05-28 16:55:50', 2, '', '2026-05-28 16:52:13', '2026-05-28 16:55:50', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(64, 'OS-20260529-0001', 'Varrição do lago', 'Varrição do fundo do lago.\r\nRetirada de material do fundo, como coquinhos das palmeiras, seixos e outros.\r\nCobertura da manta com areia.', 41, 8, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-29 16:00:00', NULL, NULL, NULL, NULL, '2026-06-01 07:18:44', '2026-06-01 07:18:44', 2, '', '2026-05-29 07:21:06', '2026-06-01 07:18:44', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(65, 'OS-20260601-0001', 'Capinação', 'Capinação na parte externa do Shopping (cancelas de saídas).', 48, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-29 16:00:00', NULL, NULL, NULL, NULL, '2026-06-01 07:26:15', '2026-06-01 07:26:15', 2, '', '2026-06-01 07:24:03', '2026-06-01 07:26:15', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(66, 'OS-20260601-0002', 'Capinação', 'Capinação interna - Entrada de pedestre.', 37, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-05-30 11:00:00', NULL, NULL, NULL, NULL, '2026-06-01 07:30:56', '2026-06-01 07:30:56', 2, '', '2026-06-01 07:28:29', '2026-06-01 07:30:56', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(67, 'OS-20260601-0003', 'Projeto de Incêndio - Shopping Carpina', 'Revisar Projeto de Incêndio - Shopping Carpina', 5, 7, 'MEDIA', 'PAUSADA', 2, 2, 12, '2026-06-30 16:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-01 07:33:39', '2026-06-03 07:31:09', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(68, 'OS-20260601-0004', 'Varrição do lago', 'Varrição do fundo do Lago + Limpeza da Lâmpadas aquáticas\r\nSegunda- Feira: Samuel\r\nQuarta- Feira: Maob Vieira\r\nSexta- Feira:', 42, 8, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-06-05 10:00:00', NULL, NULL, NULL, NULL, '2026-06-03 16:06:51', '2026-06-03 16:06:51', 2, '- Varremos o Lago hoje com o Colaborador Moab Vieira.', '2026-06-01 09:13:56', '2026-06-03 16:06:51', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(69, 'OS-20260601-0005', 'Iluminação', 'Substituição das lâmpadas Slim 120 cm 36w 6500k nos corredores de serviço + DOCA.\r\nInformar a quantidade usada e registrar com fotos antes e depois e enviar para o Gestor de Operações.\r\nUsar o telefone da Central para registrar as fotos.', 16, 7, 'MEDIA', 'ENCERRADA', 2, 2, 5, '2026-06-05 15:00:00', NULL, NULL, NULL, NULL, '2026-06-03 07:19:53', '2026-06-03 07:19:53', 2, '', '2026-06-01 09:21:00', '2026-06-03 07:19:53', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(70, 'OS-20260601-0006', 'Revestimento do Sala das Bandejas + Cabine Vestuário feminino', 'Aplicação do Revestimento  na sala das bandejas + cabine do vestuário feminino.', 4, 10, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-06-22 17:00:00', NULL, NULL, NULL, NULL, '2026-06-25 07:16:47', '2026-06-25 07:16:47', 2, 'Serviço finalizado.', '2026-06-01 13:01:34', '2026-06-25 07:16:47', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(72, 'OS-20260602-0001', 'Passar cabo de rede para o local onde ficará o telão', 'Passar cabo de rede para o telão dos jogos da copa, puxando o cabo do local onde se encontra o projetor para a tomada próximo à o ACM do cinema.\r\n\r\nSolicitar ajuda de Samuel, para mobilização da plataforma e execução da passagem do cabo!', 1, 10, 'ALTA', 'ENCERRADA', 1, 1, 3, '2026-06-03 19:00:00', NULL, NULL, NULL, NULL, '2026-06-03 10:17:51', '2026-06-03 10:17:51', 1, 'Cabo no local indicado. Finalizado antes da abertura do Shopping.', '2026-06-02 10:08:19', '2026-06-03 10:17:51', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(73, 'OS-20260602-0002', 'Verificar nobreaks', '- Verificar quais nobreaks estão ligando;\r\n- Os que estiverem ligando fazer a troca da bateria (solicitar bateria ao TI);\r\n- Os que não tiverem baterias ver a especificação técnica para aquisição.', 55, 14, 'BAIXA', 'ENCERRADA', 1, 1, 9, '2026-06-04 19:00:00', NULL, NULL, NULL, NULL, '2026-06-02 14:00:16', '2026-06-02 14:00:16', 1, 'Ficamos com 3 nobreaks funcionando! ', '2026-06-02 10:42:47', '2026-06-02 14:00:16', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(74, 'OS-20260602-0003', 'Speed Dome Bolsão', 'Equipamento: Câmera Speed Dome\r\nLocalização: Bolsão\r\nProblema observado: Perda de sinal\r\n\r\nLink do chamado no painel:\r\nhttps://tickets.shoppingcarpina.com.br/ver_chamado.php?id=52', 2, 7, 'MEDIA', 'ATRIBUIDA', 1, 2, 3, '2026-06-30 22:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-02 11:22:05', '2026-07-21 08:12:10', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(75, 'OS-20260602-0004', 'Pintura corredor de Serviço', 'Pintura do corredor de serviço do Guichê de estacionamento.', 30, 5, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-02 07:00:00', NULL, NULL, NULL, NULL, '2026-06-02 11:40:46', '2026-06-02 11:40:46', 2, '', '2026-06-02 11:35:20', '2026-06-02 11:40:46', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(76, 'OS-20260602-0005', 'Obstrução linha central de esgoto - Loja Bobs', 'Esgoto entupido - Loja BOBS', 9, 7, 'ALTA', 'ENCERRADA', 2, 2, 9, '2026-06-02 12:30:00', NULL, NULL, NULL, NULL, '2026-06-02 12:25:42', '2026-06-02 12:25:42', 2, '', '2026-06-02 12:19:22', '2026-06-02 12:25:42', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(78, 'OS-20260602-0007', 'Troca de baterias do Nobreak Sala de arquivo', 'Troca de baterias do Nobreak Sala de arquivo, visto que temos 2 para colocar em funcionamento!', 51, 6, 'MEDIA', 'ENCERRADA', 1, 2, 9, '2026-06-05 22:00:00', NULL, NULL, NULL, NULL, '2026-06-17 16:00:16', '2026-06-17 16:00:16', 2, '', '2026-06-02 13:20:25', '2026-06-17 16:00:16', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(86, 'OS-20260602-0008', 'Dutos Ar condicionados Praça', 'Limpeza dos dutos de saída dos ar condicionados.', 1, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-16 07:00:00', NULL, NULL, NULL, NULL, '2026-06-16 07:23:20', '2026-06-16 07:23:20', 2, 'Limpeza realizada.\r\nPróxima limpeza programada para o dia 16.07.2026.', '2026-06-02 16:16:10', '2026-06-16 07:23:20', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(87, 'OS-20260603-0001', 'Limpeza Locomotiva', 'Limpeza da LOCOMOTIVA', 40, 8, 'MEDIA', 'ENCERRADA', 2, 2, 16, '2026-06-02 17:00:00', NULL, NULL, NULL, NULL, '2026-06-03 12:36:26', '2026-06-03 12:36:26', 2, '', '2026-06-03 10:33:42', '2026-06-03 12:36:26', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(88, 'OS-20260603-0002', 'Ar condicionado Praça TR 10 - Escravo', 'Foi identificado no dia 01 (segunda- Feira), uma condensação na máquina TR 10 - Escravo.\r\nAbrimos um chamado com a empresa CG Climatização.', 1, 4, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-06-03 16:00:00', NULL, NULL, NULL, NULL, '2026-06-03 15:52:08', '2026-06-03 15:52:08', 2, '', '2026-06-03 12:33:25', '2026-06-03 15:52:08', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(89, 'OS-20260603-0003', '[PREVENTIVA] Exaustores Banheiros', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLimpeza dos Exaustores: WC Fem. Mall, WC Masc. Mall, WC Família, Fraldário, WC Fem. Praça, WC Mas. Praça, Vest. Fem e Vest. Masc.', 4, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-07-15 15:43:00', NULL, NULL, NULL, NULL, '2026-07-09 11:15:02', '2026-07-09 11:15:02', 31, '', '2026-06-03 12:43:01', '2026-07-09 11:15:02', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(90, 'OS-20260603-0004', '[PREVENTIVA] Limpeza Locomotiva', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLimpeza da Locomotiva', 40, 8, 'MEDIA', 'ENCERRADA', 2, 2, 16, '2026-06-18 15:44:00', NULL, NULL, NULL, NULL, '2026-06-19 07:19:19', '2026-06-19 07:19:19', 2, 'Limpeza da Locomotiva realizada.\r\nProgramada a próxima para o dia 26.06.2026.', '2026-06-03 12:44:21', '2026-06-19 07:19:19', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(91, 'OS-20260603-0005', '[PREVENTIVA] Limpeza Retangular', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLavar com a maquina toda área da retangular.\r\nTer atenção próximo do quiosque Fondue di Chocolate.', 38, 8, 'MEDIA', 'ENCERRADA', 2, 2, 22, '2026-07-01 15:50:00', NULL, NULL, NULL, NULL, '2026-07-14 10:24:30', '2026-07-14 10:24:30', 31, '', '2026-06-03 12:50:24', '2026-07-14 10:24:30', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(92, 'OS-20260603-0006', '[PREVENTIVA] limpeza Praça de Alimentação', 'Manutencao preventiva gerada automaticamente.\r\n\r\nLavagem da praça de alimentação com a máquina.', 1, 8, 'MEDIA', 'ENCERRADA', 2, 2, 22, '2026-06-25 15:51:00', NULL, NULL, NULL, NULL, '2026-06-29 07:51:29', '2026-06-29 07:51:29', 2, 'Limpeza realizada.\r\nPróxima programada para o dia 05.07.2026', '2026-06-03 12:51:41', '2026-06-29 07:51:29', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(93, 'OS-20260603-0007', 'Iluminação', 'Substituição de 02 (duas) lâmpada Slim 120cm 36w 6500k.', 63, 17, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-06-03 17:00:00', NULL, NULL, NULL, NULL, '2026-06-03 15:55:45', '2026-06-03 15:55:45', 2, '', '2026-06-03 15:53:49', '2026-06-03 15:55:45', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(94, 'OS-20260603-0008', 'Poda de Arvores - Cajueiro Frente do Shopping', 'Poda do cajueiro frente das cancelas de saídas do Shopping.', 48, 7, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-06-05 16:00:00', NULL, NULL, NULL, '2026-06-03 16:12:54', '2026-06-04 16:45:49', '2026-06-04 16:45:49', 2, '', '2026-06-03 16:11:45', '2026-06-04 16:45:49', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(95, 'OS-20260603-0009', 'Exaustores', 'Limpeza das exaustores da praça de alimentação', 1, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-15 07:00:00', NULL, NULL, NULL, '2026-06-03 16:25:46', '2026-06-15 08:06:38', '2026-06-15 08:06:38', 2, '', '2026-06-03 16:23:21', '2026-06-15 08:06:38', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(96, 'OS-20260604-0001', 'Poda de Palmeira', 'Retirada de 01 (uma) Palmeira, a mesma está com o troco doente e apresenta risco de queda.', 42, 7, 'MEDIA', 'ENCERRADA', 2, 2, 10, '2026-06-04 10:00:00', NULL, NULL, NULL, NULL, '2026-06-04 08:44:41', '2026-06-04 08:44:41', 2, '', '2026-06-04 08:42:00', '2026-06-04 08:44:41', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(97, 'OS-20260604-0002', 'Iluminação', 'Substituição de 02 (duas) lâmpadas PAR30 12w 960Lumes 2700K Branca Morna.', 19, 17, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-06-04 16:00:00', NULL, NULL, NULL, NULL, '2026-06-04 16:01:03', '2026-06-04 16:01:03', 2, '', '2026-06-04 15:59:29', '2026-06-04 16:01:03', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(98, 'OS-20260604-0003', 'Iluminação', 'Substituição de 02 (duas) lâmpadas PAR30 12w 960Lumes 2700K Branca Morna.', 20, 17, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-06-04 16:00:00', NULL, NULL, NULL, NULL, '2026-06-04 16:18:24', '2026-06-04 16:18:24', 2, '', '2026-06-04 16:05:03', '2026-06-04 16:18:24', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(99, 'OS-20260605-0001', 'Instalação das conexões hidráulicas - Válvulas de descargas', 'Instalação de 05 (cinco) Válvulas de descargas no banheiro feminino do mall.', 27, 2, 'MEDIA', 'ENCERRADA', 2, 2, 12, '2026-06-11 16:00:00', NULL, NULL, NULL, NULL, '2026-06-11 11:45:57', '2026-06-11 11:45:57', 2, '', '2026-06-05 07:17:43', '2026-06-11 11:45:57', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(100, 'OS-20260605-0002', 'limpeza Fachada - Preditiva', 'Limpeza e manutenção da Fachada da PREDITIVA', 34, 8, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-12 07:00:00', NULL, NULL, NULL, '2026-06-05 15:34:29', '2026-06-12 09:11:13', '2026-06-12 09:11:13', 2, '', '2026-06-05 15:32:03', '2026-06-12 09:11:13', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(101, 'OS-20260608-0001', 'Infiltração - Loja C&A', 'Infiltração na Loja C&A.', 53, 16, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-06-11 15:00:00', NULL, NULL, NULL, NULL, '2026-06-10 11:47:02', '2026-06-10 11:47:02', 2, '', '2026-06-08 07:47:31', '2026-06-10 11:47:02', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(102, 'OS-20260609-0001', 'Capinação - Jardim dos Cisnes', 'Limpeza e Capinação do Jardim dos Cisnes', 40, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-06-10 16:00:00', NULL, NULL, NULL, NULL, '2026-06-10 10:57:24', '2026-06-10 10:57:24', 2, '', '2026-06-09 07:49:09', '2026-06-10 10:57:24', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(103, 'OS-20260609-0002', 'Climatização', 'Maquina condensando - TR 10', 1, 4, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-06-10 12:59:00', NULL, NULL, NULL, NULL, '2026-06-10 11:46:29', '2026-06-10 11:46:29', 2, '', '2026-06-09 07:53:08', '2026-06-10 11:46:29', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(104, 'OS-20260609-0003', 'Instalação de Lousa de Vidro - Sala Diretoria', 'Instalação de uma Lousa na sala da diretoria - 110cm de altura (inicio da Lousa) alinhado com o eixo do ar condicionado da sala.', 5, 7, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-06-08 18:00:00', NULL, NULL, NULL, NULL, '2026-06-09 09:04:35', '2026-06-17 13:20:47', 2, '', '2026-06-09 09:02:04', '2026-06-17 13:20:47', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(105, 'OS-20260610-0001', 'Gerador Oscilando', 'Gerador apresentando oscilação.\r\nFoi solicitado a presença da COGERA.', 17, 1, 'ALTA', 'ENCERRADA', 2, 2, 9, '2026-06-18 17:00:00', NULL, NULL, NULL, NULL, '2026-06-18 15:40:31', '2026-06-18 15:40:31', 2, 'A empresa responsável pela manutenção esteve no local a inspeção e aproveitou para realizar a manutenção mensal.  \r\n', '2026-06-10 14:08:51', '2026-06-18 15:40:31', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(106, 'OS-20260610-0002', 'Caixas de Gorduras', 'Acompanhar a empresa Desentupidora Pernambuco na limpeza e manutenção das caixas de gorduras - Estacionamento, Corredor Adm, DOCA e E.T.E.', 16, 8, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-06-11 12:00:00', NULL, NULL, NULL, NULL, '2026-06-11 11:27:24', '2026-06-11 11:27:24', 2, '', '2026-06-10 16:33:28', '2026-06-11 11:27:24', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(107, 'OS-20260611-0001', 'Substituição da Haste da Cancela', 'Substituição da haste da cancela de saída.', 48, 7, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-10 06:00:00', NULL, NULL, NULL, NULL, '2026-06-11 11:52:59', '2026-06-11 11:52:59', 2, 'Substituição realizada!', '2026-06-11 11:49:43', '2026-06-11 11:52:59', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(108, 'OS-20260611-0002', 'Sistema de Incêndio - Visita técnica - Magnus', 'Manutenção no Sistema de Incêndio do SC.', 56, 7, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-06-11 17:00:00', NULL, NULL, NULL, NULL, '2026-06-11 16:28:23', '2026-06-11 16:28:23', 2, '', '2026-06-11 16:19:56', '2026-06-11 16:28:23', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(109, 'OS-20260611-0003', 'Capinação', 'Limpeza e capinação do Estacionamento Bolsão', 46, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-06-12 17:00:00', NULL, NULL, NULL, NULL, '2026-06-15 08:27:27', '2026-06-15 08:27:27', 2, '', '2026-06-11 16:31:16', '2026-06-15 08:27:27', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(110, 'OS-20260611-0004', 'limpeza do ACM Externo', 'Limpeza da Fachada externa - Triangular sentido cancela de saída.', 37, 8, 'MEDIA', 'ENCERRADA', 2, 2, 8, '2026-06-30 16:00:00', NULL, NULL, NULL, NULL, '2026-07-17 09:00:25', '2026-07-17 09:00:25', 31, '', '2026-06-11 16:53:00', '2026-07-17 09:00:25', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(111, 'OS-20260612-0001', 'Varrição do lago', 'Varrição do fundo do lago, limpeza das iluminarias e recolhimento de resíduos.', 41, 8, 'MEDIA', 'ENCERRADA', 2, 2, 8, '2026-06-12 11:00:00', NULL, NULL, NULL, NULL, '2026-06-12 11:04:36', '2026-06-12 11:04:36', 2, '', '2026-06-12 10:51:09', '2026-06-12 11:04:36', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(112, 'OS-20260615-0001', 'Ar condicionado - CG Climatização', 'Ar condicionado do Fraldário quando ligado, esta disparando o disjuntor.\r\nA empresa responsável CG Climatização já foi informada e o chamado aberto para verificar a causa.', 29, 4, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-06-17 17:00:00', NULL, NULL, NULL, NULL, '2026-06-16 12:28:52', '2026-06-16 12:28:52', 2, 'Máquina funcionando normalmente.\r\nA mesma está dividindo uma FASE com o exaustor, isso pode ter ocasionado o disparo do disjuntor, porém não podemos afirmar essa situação.\r\nFicaremos observando o funcionamento de ambos.', '2026-06-15 10:29:16', '2026-06-16 12:28:52', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(113, 'OS-20260615-0002', 'Instalação de lousas - Salas de Reuniões', 'Instalação de 02 (duas) lousas nas salas de reunião: Sala 01 (um) e Sala 03 (Três).', 24, 7, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-06-15 12:00:00', NULL, NULL, NULL, NULL, '2026-06-15 10:59:47', '2026-06-17 13:21:29', 2, '', '2026-06-15 10:53:24', '2026-06-17 13:21:29', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(114, 'OS-20260615-0003', 'Capinação', 'Capinação da Ilha do estacionamento - Sentido Cancela de saída.', 37, 14, 'MEDIA', 'ENCERRADA', 2, 2, 6, '2026-06-20 17:00:00', NULL, NULL, NULL, '2026-06-19 08:20:28', '2026-06-22 07:41:20', '2026-06-22 07:41:20', 2, 'Limpeza realizada \r\n', '2026-06-15 14:20:44', '2026-06-22 07:41:20', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(115, 'OS-20260617-0001', 'Manutenção Ar condicionado - Expresso Cidadão', 'Manutenção preventiva dos ar condicionados do Expresso Cidadão.\r\nA manutenção é de responsabilidade da JM Engenharia (JUCA).\r\nOs agente de manutenção do Shopping apenas acompanha.', 5, 4, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-06-27 22:30:00', NULL, NULL, NULL, '2026-06-18 08:15:24', '2026-06-22 09:09:56', '2026-06-22 09:09:56', 2, '', '2026-06-17 06:56:45', '2026-06-22 09:09:56', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(116, 'OS-20260617-0002', 'Aplicação de Terra tratada + Torta - Grama Amendoim', 'Aplicação de terra tratada e torta na mudas de grama amendoim.', 42, 14, 'MEDIA', 'ENCERRADA', 2, 2, 10, '2026-06-19 17:00:00', NULL, NULL, NULL, '2026-06-19 09:19:24', '2026-06-19 15:46:48', '2026-06-19 15:46:48', 2, 'Realizada aplicação de terra adubada nas gramas amendoim.\r\nPróxima aplicação programada para o dia 16.07.2026', '2026-06-17 16:45:33', '2026-06-19 15:46:48', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(117, 'OS-20260618-0001', 'Retoques Praça de Alimentação', 'Retorques em paredes e teto do praça de alimentação.', 1, 5, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-22 07:00:00', NULL, NULL, NULL, NULL, '2026-06-18 07:46:22', '2026-06-18 07:46:22', 2, 'Os retoques foram realizados!\r\nUsamos tinta (Branco) que temos em estoque.', '2026-06-18 07:08:43', '2026-06-18 07:46:22', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(118, 'OS-20260618-0002', 'limpeza Pátio de Evento São João', 'Lavagem do Pátio de Evento São João \r\nUsar a enceradeira para retirada do material mas pesado', 31, 8, 'MEDIA', 'ENCERRADA', 2, 2, 19, '2026-06-18 12:00:00', NULL, NULL, NULL, NULL, '2026-06-18 12:10:07', '2026-06-18 12:10:07', 2, '', '2026-06-18 12:04:06', '2026-06-18 12:10:07', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(119, 'OS-20260618-0003', 'Iluminação', 'Iluminação queimada próximo ao guichê do estacionamento.', 30, 17, 'MEDIA', 'EM_ANDAMENTO', 2, 2, 9, '2026-07-22 16:00:00', NULL, NULL, NULL, '2026-06-29 08:05:42', NULL, NULL, NULL, NULL, '2026-06-18 16:13:17', '2026-06-30 10:03:38', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(120, 'OS-20260619-0001', 'Balizadores - Triangular e Retangular', 'Limpeza dos balizadores Entrada Triangular e Retangular.', 37, 8, 'MEDIA', 'ENCERRADA', 2, 2, 16, '2026-06-18 16:00:00', NULL, NULL, NULL, NULL, '2026-06-19 07:26:43', '2026-06-19 07:26:43', 2, 'Realizada a limpeza dos balizadores.\r\nPróxima programada para o dia 26.06.2026.', '2026-06-19 07:23:46', '2026-06-19 07:26:43', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(121, 'OS-20260622-0001', 'Instalação das conexões hidráulicas - Válvulas de descargas', 'Instalação de 02 (duas) Válvulas de descargas no vestuário feminino.', 62, 2, 'MEDIA', 'ATRIBUIDA', 2, 2, 3, '2026-07-01 17:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 09:13:47', '2026-06-30 13:23:56', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(122, 'OS-20260622-0002', 'Pintura e restauração das cadeiras da praça de alimentação', 'Pintura e restauração das cadeiras da praça de alimentação', 1, 5, 'MEDIA', 'EM_ANDAMENTO', 2, 2, 4, '2026-07-31 07:00:00', NULL, NULL, NULL, '2026-07-14 08:47:21', NULL, NULL, NULL, NULL, '2026-06-22 09:23:10', '2026-07-14 08:47:21', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(123, 'OS-20260625-0001', 'Pintura corredor de Serviço 24h', 'Retoque de pintura das paredes do corredor do guichê de pagamento do estacionamento.', 30, 5, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-22 07:00:00', NULL, NULL, NULL, NULL, '2026-06-25 08:09:23', '2026-06-25 08:09:23', 2, 'Retoque concluído!', '2026-06-25 08:05:42', '2026-06-25 08:09:23', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(124, 'OS-20260625-0002', 'Pintura e restauração dos Bancos do Mall, Vestuários e Lixeira do Mall.', 'Pintura e restauração dos bancos do Mall + Vestuários + Lixeira do Mall.\r\nEnvernizar a madeira e pintar a estrutura de ferro na cor branca.', 31, 5, 'MEDIA', 'EM_ANDAMENTO', 2, 2, 5, '2026-07-31 07:00:00', NULL, NULL, NULL, '2026-07-09 10:57:20', NULL, NULL, NULL, NULL, '2026-06-25 15:35:47', '2026-07-21 08:44:30', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(125, 'OS-20260625-0003', 'Pintura dos puxadores das lixeiras da praça.', 'Pintura dos puxadores das lixeiras da praça.', 1, 5, 'MEDIA', 'ENCERRADA', 2, 2, 4, '2026-06-26 07:00:00', NULL, NULL, NULL, '2026-06-25 16:21:37', '2026-07-21 08:11:09', '2026-07-21 08:11:09', 2, '', '2026-06-25 16:21:07', '2026-07-21 08:11:09', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(126, 'OS-20260630-0001', 'Manutenção Ar condicionado - CG Climatização', 'Manutenção preventiva mensal - Limpeza dos filtros', 5, 4, 'MEDIA', 'ENCERRADA', 2, 2, 3, '2026-07-15 19:00:00', NULL, NULL, NULL, NULL, '2026-07-21 08:03:24', '2026-07-21 08:03:24', 2, '', '2026-06-30 10:24:52', '2026-07-21 08:03:24', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(127, 'OS-20260709-0001', 'Recolocação de tachas de sinalização', 'Recolocação de tachas de sinalização antes da entrada da triangular no piso do estaacionamento. \r\n\r\nAs peças estão guardadas na sala de arquivo, por favor acionar o agente de manutenção para ver quais os equipamentos necessarios para recolação.', 2, 11, 'MEDIA', 'AGUARDANDO_MATERIAL', 30, 30, 4, '2026-08-15 07:00:00', NULL, NULL, NULL, '2026-07-10 08:19:58', NULL, NULL, NULL, NULL, '2026-07-09 14:44:20', '2026-07-21 07:29:27', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(128, 'OS-20260721-0001', 'Capinação', 'Capinação do estacionamento - Iniciando pelas cancelas de Entrada.', 2, 14, 'MEDIA', 'ATRIBUIDA', 2, 2, 6, '2026-07-24 15:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-21 07:08:18', '2026-07-21 07:08:18', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(129, 'OS-20260721-0002', 'limpeza do ACM Esterno', 'limpeza do ACM Esterno - Iniciando pela entrada retangular', 2, 8, 'MEDIA', 'EM_ANDAMENTO', 2, 2, 8, '2026-08-30 09:00:00', NULL, NULL, NULL, '2026-07-21 09:36:59', NULL, NULL, NULL, NULL, '2026-07-21 09:30:33', '2026-07-21 09:36:59', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(130, 'OS-20260721-0003', 'Poda de galhos - Nins e Aroeiras', 'Poda das Aroeiras e do Nins, mantendo as plantas organizadas e bonitas.', 40, 14, 'MEDIA', 'EM_ANDAMENTO', 2, 2, 10, '2026-07-24 15:00:00', NULL, NULL, NULL, '2026-07-22 13:48:43', NULL, NULL, NULL, NULL, '2026-07-21 14:07:10', '2026-07-22 13:48:43', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL),
(131, 'OS-20260723-0001', 'Hidrante  Área DOCA - Vazamento', 'Vazamento no hidrante.\r\nVerificar se tem algo obstruindo as \"borrachas\" isolantes e se há rompimento das mesmas.', 13, 2, 'MEDIA', 'ENCERRADA', 2, 2, 9, '2026-07-22 14:00:00', NULL, NULL, NULL, NULL, '2026-07-23 07:27:55', '2026-07-23 07:27:55', 2, 'Foi verificado o rompimento do Oring de vedação.\r\nFizemos a substituição do mesmo, sanando o vazamento.', '2026-07-23 07:24:40', '2026-07-23 07:27:55', NULL, NULL, 'MANUAL', NULL, 0.00, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_fotos_publicas`
--

CREATE TABLE `os_fotos_publicas` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `os_sequences`
--

CREATE TABLE `os_sequences` (
  `sequence_date` date NOT NULL,
  `last_value` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `selector` char(24) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `patrimonio_consultas_rate`
--

CREATE TABLE `patrimonio_consultas_rate` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `consultado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `patrimonio_consultas_rate`
--

INSERT INTO `patrimonio_consultas_rate` (`id`, `usuario_id`, `consultado_em`) VALUES
(1, 1, '2026-07-15 21:55:44'),
(2, 1, '2026-07-15 21:55:45'),
(3, 1, '2026-07-15 21:55:45'),
(4, 1, '2026-07-15 22:07:47'),
(5, 1, '2026-07-15 22:07:53'),
(6, 1, '2026-07-15 22:08:00'),
(7, 1, '2026-07-15 22:08:04'),
(8, 1, '2026-07-15 22:08:06'),
(9, 1, '2026-07-15 22:08:37'),
(10, 1, '2026-07-15 22:08:39'),
(11, 1, '2026-07-15 22:08:39'),
(12, 1, '2026-07-15 22:08:45'),
(13, 1, '2026-07-15 22:08:51'),
(14, 1, '2026-07-15 22:08:52'),
(15, 1, '2026-07-15 22:08:53'),
(16, 1, '2026-07-15 22:08:57'),
(17, 1, '2026-07-15 22:11:00'),
(18, 1, '2026-07-15 22:11:02'),
(19, 1, '2026-07-15 22:11:04'),
(20, 1, '2026-07-15 22:19:50'),
(21, 1, '2026-07-15 22:19:51'),
(22, 1, '2026-07-15 22:20:08'),
(23, 1, '2026-07-15 22:20:16'),
(24, 1, '2026-07-15 22:20:21'),
(25, 1, '2026-07-15 22:20:25'),
(26, 1, '2026-07-15 22:22:30'),
(27, 1, '2026-07-15 22:22:35'),
(28, 1, '2026-07-15 22:23:03'),
(29, 1, '2026-07-15 22:23:06'),
(30, 1, '2026-07-15 22:26:50'),
(31, 1, '2026-07-15 22:31:58'),
(32, 1, '2026-07-15 22:32:29'),
(33, 1, '2026-07-15 22:32:40'),
(34, 1, '2026-07-15 22:37:00'),
(35, 1, '2026-07-15 22:37:20'),
(36, 1, '2026-07-15 22:37:30'),
(37, 1, '2026-07-15 22:37:35'),
(38, 1, '2026-07-15 22:37:51'),
(39, 1, '2026-07-15 22:38:03'),
(40, 1, '2026-07-15 22:40:33'),
(41, 1, '2026-07-15 22:40:45'),
(42, 1, '2026-07-15 22:41:01'),
(43, 1, '2026-07-15 22:41:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfil_permissoes`
--

CREATE TABLE `perfil_permissoes` (
  `perfil_id` int(10) UNSIGNED NOT NULL,
  `permissao_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perfil_permissoes`
--

INSERT INTO `perfil_permissoes` (`perfil_id`, `permissao_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(2, 1),
(2, 2),
(2, 3),
(2, 5),
(2, 6),
(2, 7),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 16),
(2, 17),
(2, 18),
(2, 20),
(2, 21),
(2, 23),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(3, 1),
(3, 4),
(3, 7),
(3, 8),
(3, 20),
(3, 23),
(3, 24),
(3, 27),
(4, 1),
(4, 19),
(4, 20),
(5, 1),
(5, 3),
(5, 12),
(5, 13),
(5, 20),
(6, 1),
(6, 2),
(6, 3),
(6, 5),
(6, 6),
(6, 7),
(6, 8),
(6, 12),
(6, 16),
(6, 17),
(6, 18),
(6, 20),
(6, 23),
(6, 24),
(6, 25),
(6, 26),
(6, 27),
(7, 1),
(7, 2),
(7, 3),
(7, 4),
(7, 5),
(7, 6),
(7, 7),
(7, 8),
(7, 9),
(7, 10),
(7, 11),
(7, 12),
(7, 13),
(7, 14),
(7, 15),
(7, 16),
(7, 17),
(7, 18),
(7, 19),
(7, 21),
(7, 23),
(7, 24),
(7, 25),
(7, 26),
(7, 27),
(7, 29);

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(80) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `administrativo` tinyint(1) NOT NULL DEFAULT 0,
  `sistema` tinyint(1) NOT NULL DEFAULT 0,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perfis`
--

INSERT INTO `perfis` (`id`, `codigo`, `nome`, `descricao`, `ativo`, `administrativo`, `sistema`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(1, 'TI', 'TI', 'Administracao integral', 1, 1, 1, NULL, '2026-07-15 21:28:13', '2026-07-15 21:31:34'),
(2, 'GESTOR', 'Gestor', 'Gestao operacional', 1, 0, 1, NULL, '2026-07-15 21:28:13', NULL),
(3, 'AGENTE', 'Agente', 'Execucao de ordens vinculadas', 1, 0, 1, NULL, '2026-07-15 21:28:13', NULL),
(4, 'CFTV', 'CFTV', 'Operacao CFTV', 1, 0, 1, NULL, '2026-07-15 21:28:13', NULL),
(5, 'DIRETOR_EXECUTIVO', 'Diretor executivo', 'Visao executiva', 1, 0, 1, NULL, '2026-07-15 21:28:13', NULL),
(6, 'COORDENADOR_OPERACOES_OBRAS', 'Coordenador de operacoes e obras', 'Coordenacao operacional', 1, 0, 1, NULL, '2026-07-15 21:28:13', NULL),
(7, 'GESTOR_SINDICO', 'Gestor / Sindico', 'Gestor / Sindico', 1, 0, 0, 1, '2026-07-15 21:30:21', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(80) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `permissoes`
--

INSERT INTO `permissoes` (`id`, `codigo`, `nome`, `descricao`) VALUES
(1, 'dashboard.visualizar', 'Visualizar dashboard', NULL),
(2, 'os.criar', 'Criar OS', NULL),
(3, 'os.visualizar_todas', 'Visualizar todas as OS', NULL),
(4, 'os.visualizar_vinculadas', 'Visualizar OS vinculadas', NULL),
(5, 'os.editar', 'Editar OS', NULL),
(6, 'os.atribuir', 'Atribuir agentes', NULL),
(7, 'os.alterar_status', 'Alterar status', NULL),
(8, 'os.concluir', 'Concluir OS', NULL),
(9, 'os.encerrar', 'Encerrar OS', NULL),
(10, 'os.reabrir', 'Reabrir OS', NULL),
(11, 'token.gerar', 'Gerar token', NULL),
(12, 'relatorios.visualizar', 'Visualizar relatorios', NULL),
(13, 'relatorios.exportar', 'Exportar relatorios', NULL),
(14, 'usuarios.gerenciar', 'Gerenciar usuarios', NULL),
(15, 'perfis.gerenciar', 'Gerenciar perfis', NULL),
(16, 'estoque.gerenciar', 'Gerenciar estoque', NULL),
(17, 'preventivas.gerenciar', 'Gerenciar preventivas', NULL),
(18, 'cronograma.gerenciar', 'Gerenciar cronograma', NULL),
(19, 'cftv.acessar', 'Acessar CFTV', NULL),
(20, 'chat.acessar', 'Acessar chat', NULL),
(21, 'auditoria.visualizar', 'Visualizar auditoria', NULL),
(22, 'configuracoes.administrar', 'Administrar configuracoes', NULL),
(23, 'patrimonio.consultar', 'Consultar patrimonio externo', NULL),
(24, 'patrimonio.vincular', 'Vincular patrimonio a OS', NULL),
(25, 'patrimonio.desvincular', 'Desvincular patrimonio', NULL),
(26, 'patrimonio.atualizar_snapshot', 'Atualizar snapshot', NULL),
(27, 'patrimonio.visualizar_historico', 'Visualizar historico do patrimonio', NULL),
(28, 'patrimonio.testar_conexao', 'Testar conexao externa', NULL),
(29, 'patrimonio.configurar', 'Configurar integracao de patrimonio', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` varchar(64) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `schema_migrations`
--

INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES
('001_security_foundation', '2026-07-15 21:28:13'),
('002_dynamic_rbac_notifications', '2026-07-15 21:28:13'),
('003_external_assets', '2026-07-15 21:28:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `setores`
--

CREATE TABLE `setores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `setores`
--

INSERT INTO `setores` (`id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 'TI', 'Tecnologia da informação', 1, '2026-05-05 10:40:36'),
(2, 'Operações', 'Gestão de operações', 1, '2026-05-05 10:40:36'),
(3, 'Manutenção', 'Equipe de manutenção', 1, '2026-05-05 10:40:36'),
(4, 'Engenharia', '', 1, '2026-05-13 13:49:32'),
(5, 'Jardinagem', '', 1, '2026-05-19 09:55:27'),
(6, 'Limpeza', '', 1, '2026-05-19 09:55:31'),
(7, 'Brigadista', '', 1, '2026-06-03 11:24:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tokens_reabertura_os`
--

CREATE TABLE `tokens_reabertura_os` (
  `id` int(11) NOT NULL,
  `ordem_servico_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `gerado_por` int(11) NOT NULL,
  `usado_por` int(11) DEFAULT NULL,
  `usado_em` datetime DEFAULT NULL,
  `expira_em` datetime NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `solicitacao_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tokens_reabertura_os`
--

INSERT INTO `tokens_reabertura_os` (`id`, `ordem_servico_id`, `token_hash`, `gerado_por`, `usado_por`, `usado_em`, `expira_em`, `criado_em`, `solicitacao_id`) VALUES
(1, 7, '$2y$10$e1H6GT5e2qa8jvURw8anD.4qFz8wLwTVIboocJ/z8ygc6wbL7wTvK', 1, 7, '2026-05-19 10:28:25', '2026-05-20 10:28:09', '2026-05-19 10:28:09', NULL),
(2, 12, '$2y$10$nSrOQ8OBGGgZSAtINvhA7eDFwWeAxqGIBeOY8WVgVgu366xRLiI1m', 1, 2, '2026-05-28 09:34:13', '2026-05-29 09:32:54', '2026-05-28 09:32:54', NULL),
(3, 104, '$2y$10$ed8TznTgXmom6d4LJnzlQuVY8FLDRzKUevo9qkyuXtZ0QHCA30dWa', 1, 2, '2026-06-09 09:13:31', '2026-06-10 09:12:58', '2026-06-09 09:12:58', NULL),
(4, 104, '$2y$10$PGAEI6sEl..5nvpRFGV.quhKHYfW7facH.TxHFaemwxpr5RDXik7G', 1, 1, '2026-06-17 13:17:35', '2026-06-15 13:11:51', '2026-06-14 13:11:51', NULL),
(5, 104, '$2y$10$pMUHigyiPUmsJ.k4y6ulCOhBiQbXreItd309GzlzoD.53hAchNc1i', 1, 2, '2026-06-17 13:20:43', '2026-06-18 13:17:35', '2026-06-17 13:17:35', NULL),
(6, 113, '$2y$10$lVXZ60BCe19w52eKvFKgUuBcQhyxyxTOwsYOkHeR7SUkFiof6ytvW', 1, 2, '2026-06-17 13:21:25', '2026-06-18 13:21:09', '2026-06-17 13:21:09', NULL),
(7, 113, '$2y$10$kYvSkUPvGGMjrZr6Jri6OeC.8Als3D7noUCioiHFQyaq75DEZVuue', 1, 31, '2026-07-09 10:58:39', '2026-06-20 14:22:56', '2026-06-19 14:22:56', NULL),
(8, 113, '$2y$10$qNB6TDigSDq3LRAKUksphOjp0klxs52Vitsz95459TJZ8AfUaADVO', 31, 1, '2026-07-15 13:13:52', '2026-07-10 10:58:39', '2026-07-09 10:58:39', NULL),
(9, 104, '$2y$10$HaoBf827joXJv0HV5m62w.DPCDyrhYrBwNRmCk1.aCAnQKX1MEoTq', 31, NULL, NULL, '2026-07-10 10:58:46', '2026-07-09 10:58:46', NULL),
(10, 113, '$2y$10$eK2qF06ywBfqCzuBK0ao7.2QTDk3h2ZiJthPs.P9YIr5cmZQkGUty', 1, NULL, NULL, '2026-07-16 13:13:52', '2026-07-15 13:13:52', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `token_reabertura_solicitacoes`
--

CREATE TABLE `token_reabertura_solicitacoes` (
  `id` int(11) NOT NULL,
  `ordem_servico_id` int(11) NOT NULL,
  `solicitado_por` int(11) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDENTE',
  `atendido_por` int(11) DEFAULT NULL,
  `atendido_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `resolvida_em` datetime DEFAULT NULL,
  `resolvida_por` int(11) DEFAULT NULL,
  `token_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `token_reabertura_solicitacoes`
--

INSERT INTO `token_reabertura_solicitacoes` (`id`, `ordem_servico_id`, `solicitado_por`, `motivo`, `status`, `atendido_por`, `atendido_em`, `criado_em`, `resolvida_em`, `resolvida_por`, `token_id`) VALUES
(1, 104, 2, 'Solicitação de token para reabertura da OS.', 'PENDENTE', NULL, NULL, '2026-06-09 09:11:00', NULL, NULL, NULL),
(2, 113, 2, 'Solicitação de token para reabertura da OS.', 'PENDENTE', NULL, NULL, '2026-06-17 13:19:20', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `perfil` varchar(80) NOT NULL,
  `perfil_id` int(10) UNSIGNED DEFAULT NULL,
  `setor_id` int(11) DEFAULT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `status` enum('ATIVO','INATIVO') DEFAULT 'ATIVO',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `senha_alterada_em` datetime DEFAULT NULL,
  `forcar_troca_senha` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `cargo`, `perfil`, `perfil_id`, `setor_id`, `senha_hash`, `status`, `criado_por`, `criado_em`, `atualizado_em`, `senha_alterada_em`, `forcar_troca_senha`) VALUES
(1, 'Julio Orlando da Silva', 'ti@shoppingcarpina.com.br', '81989328045', 'TI', 'TI', 1, 1, '$2y$10$3DFyZ.Cv2oInmnDn1M/2geiUi9B0S.bPz/esT.1t/UylahLgvvc5a', 'ATIVO', NULL, '2026-05-05 10:40:36', '2026-07-15 21:28:13', NULL, 0),
(2, 'Thiago Gomes Heráclio', 'operacoes@shoppingcarpina.com.br', '81992076708', 'Gestor de Operações', 'GESTOR', 2, 2, '$2y$10$/bphJ1Y6VnhyzlliT82cKOcHx3LNBhGPTtUM1pjC5WkcXHSJg/aHG', 'ATIVO', 1, '2026-05-05 13:56:06', '2026-07-15 21:28:13', NULL, 0),
(3, 'Alexandre Jose da Silva', 'alexandrejoelma7@gmail.com', '8199168-1319', 'Agente de Manutenção', 'AGENTE', 3, 3, '$2y$10$M5uL2C7IwdpwukRf8T9VXOjV9JnkumHaQLmHLRxLMwI7QZVwZngYG', 'ATIVO', 1, '2026-05-12 15:13:40', '2026-07-15 21:28:13', NULL, 0),
(4, 'Wilson Lourenço da Silva', 'ws4540294@gmail.com', '8199772-9096', 'Agente de Manutenção', 'AGENTE', 3, 3, '$2y$10$lpx0ImPhsnSdRJR5xRM5uOFN5lldnk0BsKbFEpSlsyxxABobIa5AS', 'ATIVO', 1, '2026-05-12 15:14:51', '2026-07-15 21:28:13', NULL, 0),
(5, 'Andson Luiz de Barros', 'andsonluizdebarros@hotmail.com', '8198903-1244', 'Agente de Manutenção', 'AGENTE', 3, 3, '$2y$10$748G6mAJBuxJR1m5EgpoiuN7UsY/br4VhWbICJE2XT14jKodsEGea', 'ATIVO', 1, '2026-05-12 15:15:59', '2026-07-15 21:28:13', NULL, 0),
(6, 'Samuel Jose da Paz', 'ssamueljose05@gmail.com', '8199452-3406', 'Agente de Manutenção', 'AGENTE', 3, 3, '$2y$10$lQdSXdFTVyXpYw4t37VTxuSAqU7WRvVUyRuhmXPwzDVaQxTOKjseW', 'ATIVO', 1, '2026-05-12 15:17:12', '2026-07-15 21:28:13', NULL, 0),
(7, 'Marcênio', 'marceniochaves1@petribudesenvolvimento.com.br', '5581 991125111', 'Engenharia', 'COORDENADOR_OPERACOES_OBRAS', 6, 4, '$2y$10$sukZwMRAg8lFmW36QecuXOr87BgY395UY6buc8kT/yOF./9sSM1j6', 'INATIVO', 1, '2026-05-13 13:33:06', '2026-07-15 21:28:13', NULL, 0),
(8, 'Moab Vieira Dos Santos', 'moabvieira72@gmail.com', '81 99388-2482', 'Auxiliar de Serviços Gerais', 'AGENTE', 3, 3, '$2y$10$QXQys0FLH7rR1lqEDV8.3.R9piFbGzKrawrNZ2qlcQRfhy415Ldwi', 'ATIVO', 1, '2026-05-14 11:53:48', '2026-07-15 21:28:13', NULL, 0),
(9, 'Tarciso Carneiro da Silva', 'tarcisocdasilva@gmail.com', '81 99630-3637', 'Agente de Manutenção', 'AGENTE', 3, 3, '$2y$10$Vp6xRsPa0VOaSkSoSjDlSOTq8Ax89lghyUE5cwK8JrxXdYvlaMIni', 'ATIVO', 1, '2026-05-14 11:55:03', '2026-07-15 21:28:13', NULL, 0),
(10, 'Lucrecio Luís da silva', 'lucreciosememail@gmail.com', '81991835479', 'Jardineiro', 'AGENTE', 3, 5, '$2y$10$06fKPAaPiSED/Z6wgzPQ..E16O3sbrbb/Yb1OwwGKPCDbAvSFzQyC', 'ATIVO', 1, '2026-05-18 16:53:15', '2026-07-15 21:28:13', NULL, 0),
(11, 'Raphael Campos', 'raphaelcampos@petribudesenvolvimento.com.br', '81 99173-2607', 'Diretor Executivo', 'DIRETOR_EXECUTIVO', 5, NULL, '$2y$10$x2g3GDZ20LhOb9.BwdBC/.c..E.mRz409.8YohdK6/dK2rT8bm5AS', 'ATIVO', 1, '2026-05-21 15:58:56', '2026-07-15 21:28:13', NULL, 0),
(12, 'Marcênio Chaves', 'marceniochaves@petribudesenvolvimento.com.br', '81 99112-5111', 'Engenharia', 'COORDENADOR_OPERACOES_OBRAS', 6, 4, '$2y$10$aJuy.bF4YWVpA3gHh8MTgenodAjUQgkvubsTv2jxTGZ4RN3mCF/xu', 'ATIVO', 1, '2026-05-21 16:03:17', '2026-07-15 21:28:13', NULL, 0),
(14, 'Glaybson Bruno Lourenço Feliciano da Silva', 'chapadao88@gmail.com', '', 'Operador de CFTV', 'CFTV', 4, 2, '$2y$10$epHbpYbLbITInM/fIb08zePo92AzxWjHozj0olnxNXIwlP4CbYP3a', 'ATIVO', 1, '2026-05-25 11:33:40', '2026-07-15 21:28:13', NULL, 1),
(15, 'ALESSANDRO OLIVEIRA DA SILVA', 'atricolor20003@gmail.com', '81989097910', 'Operador de CFTV', 'CFTV', 4, 2, '$2y$10$iqJel/UTDnoQ5jU6MHJclud4bQfJpI6YybN3SPmfEPqIAnICjviaq', 'ATIVO', 1, '2026-05-26 13:56:50', '2026-07-15 21:28:13', '2026-05-26 15:01:36', 0),
(16, 'Geovanny Cristhian', 'sememail@gmail.com', '81818151611', 'ASG', 'AGENTE', 3, 6, '$2y$10$b73iMOvWSzDiE/5.smA3IO1IUSb2naPKY/qC8Aa5CSrHsBMf/LMbu', 'ATIVO', 1, '2026-06-03 11:18:11', '2026-07-15 21:28:13', NULL, 0),
(18, 'Laudicéia da Silva', 'sememai1l@gmail.com', '451115646541', 'ASG', 'AGENTE', 3, 6, '$2y$10$TPvU/W9miYtee6JYGx7wSuBQGz27twENGQw1smY8pQj8n5Nlf6HAW', 'ATIVO', 1, '2026-06-03 11:18:48', '2026-07-15 21:28:13', NULL, 0),
(19, 'Leonilton Barbosa', 'sememail2@gmail.com', '5445451212', 'ASG', 'AGENTE', 3, 6, '$2y$10$QqbjlKZc7W68C4H/uGTU0.7KH12V8jcmVhCteRntTSceS9UjnceLm', 'ATIVO', 1, '2026-06-03 11:21:36', '2026-07-15 21:28:13', NULL, 0),
(20, 'Emanair Patrícia ', 'sememail3@gmail.com', 'adadafasd', 'ASG', 'AGENTE', 3, 6, '$2y$10$7nvvadloM/cVHzwDq.KVj.mvS7m3vMyueWHFEVBR56SlDEzslHvn.', 'ATIVO', 1, '2026-06-03 11:22:06', '2026-07-15 21:28:13', NULL, 0),
(21, 'Gabriel Manoel', 'sememail4@gmail.com', '841212313', 'ASG', 'AGENTE', 3, 6, '$2y$10$ZsaIH3mIsOCdvG4bnsbPkOA1Irt5zBMppbT9tT7wzMSlkR.KM29Oe', 'ATIVO', 1, '2026-06-03 11:22:54', '2026-07-15 21:28:13', NULL, 0),
(22, 'Heleno Augusto', 'sememail5@gmail.com', '3423424', 'ASG', 'AGENTE', 3, 6, '$2y$10$LLAzn5LnoRD3WX239/MzmuO1ItnWmpXqKXCIETcaJ8XknO.yGdA7G', 'ATIVO', 1, '2026-06-03 11:23:20', '2026-07-15 21:28:13', NULL, 0),
(24, 'Deivson Felipe', 'sememail6@gmail.com', '5654413132', 'ASG', 'AGENTE', 3, 6, '$2y$10$oNCJwuVW/ikjP3VLGsjszux0oJP1VLdz/4cMTcu.2maHfnAxTJesG', 'ATIVO', 1, '2026-06-03 11:23:50', '2026-07-15 21:28:13', NULL, 0),
(26, 'Ricardo Rocha', 'sememail7@gmail.com', '48476544', 'ASG', 'AGENTE', 3, 6, '$2y$10$z3i67/0VGtCCcDPF9JY63.pVpb1dEhR7qWaOs4f6mtroLUFFCvhc6', 'ATIVO', 1, '2026-06-03 11:24:22', '2026-07-15 21:28:13', NULL, 0),
(27, 'Adriana Ribeiro', 'sememail8@gmail.com', 'adasdad', 'BC', 'TI', 1, 7, '$2y$10$2kTd6TAuB6pMQvVSgTfRtO1sdDo.8GKqF0z/k60kOSFSy69BUbyae', 'ATIVO', 1, '2026-06-03 11:26:15', '2026-07-15 21:28:13', NULL, 0),
(29, 'Daniel França', 'sememail9@gmail.com', 'asdasdda', 'BC', 'TI', 1, 7, '$2y$10$woEw2ihiIz8h02.c7GULiO9Ps41YB9uJYy0BHufVpZpkXID9u2WpS', 'ATIVO', 1, '2026-06-03 11:26:45', '2026-07-15 21:28:13', NULL, 0),
(30, 'Leonardo Vidal', 'leonardovidal@petribudesenvolvimento.com.br', '819992940198', 'Gestor/Sindico', 'GESTOR_SINDICO', 7, NULL, '$2y$10$0TH8MSAXRpS/ya85A7fDc.fEBrEUyQJMSFvAC0PL4fDpRjT46WzDy', 'ATIVO', 1, '2026-06-17 13:13:24', '2026-07-15 21:31:58', NULL, 0),
(31, 'Rubens Augusto Mendes da Silva', 'rubensmendes@shoppingcarpina.com.br', '81  99743-7439', 'Auditoria', 'TI', 1, 1, '$2y$10$XHsQG8/NLTvoM.85jGVrPufiS6h/KPgb63E5ua3GaxPL/3TC6tDWO', 'ATIVO', 1, '2026-06-19 14:18:49', '2026-07-15 21:28:13', NULL, 0),
(32, 'Rubens Augusto Mendes da Silva', 'compras@shoppingcarpina.com.br', '81997437439', 'AUDITORIA', 'GESTOR', 2, NULL, '$2y$10$OShwfyIJOHWrNcmx4k4Hr.w1yZYWoLJM9qjiq5rijpIRoutFhfIzW', 'INATIVO', NULL, '2026-07-07 15:26:51', '2026-07-15 21:28:13', NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_convites`
--

CREATE TABLE `usuario_convites` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `gerado_por` int(11) NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `invalidado_em` datetime DEFAULT NULL,
  `expira_em` datetime NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_uso` varchar(64) DEFAULT NULL,
  `user_agent_uso` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario_convites`
--

INSERT INTO `usuario_convites` (`id`, `usuario_id`, `token_hash`, `gerado_por`, `usado_em`, `invalidado_em`, `expira_em`, `criado_em`, `ip_uso`, `user_agent_uso`) VALUES
(1, 14, '$2y$10$EatagUZOuW0jRzqm.dH/beSDA.0683UMU9HuvTMRbkIFEX4pxP5Qy', 1, NULL, NULL, '2026-05-28 11:33:40', '2026-05-25 11:33:40', NULL, NULL),
(2, 15, '$2y$10$I93Xd2pkHlplkmjtIMcn2O10Q2RBrzXb1kU3Y1jvZOvHAYSc2bVPm', 1, '2026-05-26 15:01:36', NULL, '2026-05-29 13:56:50', '2026-05-26 13:56:50', '138.219.243.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `anexos_os`
--
ALTER TABLE `anexos_os`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_servico_id` (`ordem_servico_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `auditoria_eventos`
--
ALTER TABLE `auditoria_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entidade` (`entidade`,`entidade_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_criado_em` (`criado_em`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cftv_liberacao_confirmacoes`
--
ALTER TABLE `cftv_liberacao_confirmacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cftv_confirmacao_relatorio` (`relatorio_id`,`liberacao_id`),
  ADD KEY `idx_relatorio` (`relatorio_id`),
  ADD KEY `idx_liberacao` (`liberacao_id`),
  ADD KEY `idx_status_confirmacao` (`status_confirmacao`);

--
-- Índices de tabela `cftv_liberacoes`
--
ALTER TABLE `cftv_liberacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_acesso` (`data_acesso`),
  ADD KEY `idx_cpf` (`cpf`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_criado_por` (`criado_por`);

--
-- Índices de tabela `cftv_observacoes`
--
ALTER TABLE `cftv_observacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_relatorio` (`relatorio_id`),
  ADD KEY `idx_equipe` (`equipe`),
  ADD KEY `idx_classificacao` (`classificacao`);

--
-- Índices de tabela `cftv_ocorrencias`
--
ALTER TABLE `cftv_ocorrencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_relatorio` (`relatorio_id`),
  ADD KEY `idx_nivel` (`nivel`),
  ADD KEY `idx_horario` (`horario`);

--
-- Índices de tabela `cftv_relatorios`
--
ALTER TABLE `cftv_relatorios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cftv_relatorio_periodo` (`data_plantao`,`operador_id`,`turno`),
  ADD KEY `idx_data_plantao` (`data_plantao`),
  ADD KEY `idx_operador` (`operador_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `cftv_tokens_edicao`
--
ALTER TABLE `cftv_tokens_edicao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_relatorio` (`relatorio_id`),
  ADD KEY `idx_expira` (`expira_em`),
  ADD KEY `idx_usado` (`usado_em`);

--
-- Índices de tabela `chat_conversas`
--
ALTER TABLE `chat_conversas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chat_par` (`usuario_1_id`,`usuario_2_id`),
  ADD KEY `idx_chat_u1` (`usuario_1_id`),
  ADD KEY `idx_chat_u2` (`usuario_2_id`),
  ADD KEY `idx_chat_atualizado` (`atualizado_em`);

--
-- Índices de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_msg_conversa` (`conversa_id`,`id`),
  ADD KEY `idx_chat_msg_dest_lida` (`destinatario_id`,`lida_em`),
  ADD KEY `idx_chat_msg_criado` (`criado_em`);

--
-- Índices de tabela `cronograma_agentes`
--
ALTER TABLE `cronograma_agentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agente_id` (`agente_id`),
  ADD KEY `gestor_id` (`gestor_id`),
  ADD KEY `local_id` (`local_id`);

--
-- Índices de tabela `estoque_materiais`
--
ALTER TABLE `estoque_materiais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_estoque_nome` (`nome`);

--
-- Índices de tabela `gestor_tokens`
--
ALTER TABLE `gestor_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`);

--
-- Índices de tabela `historico_os`
--
ALTER TABLE `historico_os`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_servico_id` (`ordem_servico_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `locais`
--
ALTER TABLE `locais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_identity_time` (`identity_hash`,`attempted_at`),
  ADD KEY `idx_login_ip_time` (`ip_hash`,`attempted_at`);

--
-- Índices de tabela `manutencoes_preventivas`
--
ALTER TABLE `manutencoes_preventivas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proxima_execucao` (`proxima_execucao`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `materiais_os`
--
ALTER TABLE `materiais_os`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ordem_servico_id` (`ordem_servico_id`);

--
-- Índices de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_material` (`material_id`),
  ADD KEY `idx_os` (`ordem_servico_id`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Índices de tabela `ordem_agentes`
--
ALTER TABLE `ordem_agentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ordem_usuario` (`ordem_servico_id`,`usuario_id`),
  ADD KEY `idx_ordem` (`ordem_servico_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_papel` (`papel`);

--
-- Índices de tabela `ordem_patrimonios`
--
ALTER TABLE `ordem_patrimonios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_op_ordem_ativo` (`ordem_id`,`ativo`),
  ADD KEY `idx_op_externo` (`patrimonio_externo_id`),
  ADD KEY `idx_op_codigo` (`codigo_barras`),
  ADD KEY `idx_op_numero` (`numero_patrimonio`),
  ADD KEY `fk_op_consultado` (`consultado_por`);

--
-- Índices de tabela `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_os` (`numero_os`),
  ADD KEY `local_id` (`local_id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `solicitante_id` (`solicitante_id`),
  ADD KEY `gestor_id` (`gestor_id`),
  ADD KEY `agente_id` (`agente_id`),
  ADD KEY `aprovado_por` (`aprovado_por`);

--
-- Índices de tabela `os_fotos_publicas`
--
ALTER TABLE `os_fotos_publicas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `os_sequences`
--
ALTER TABLE `os_sequences`
  ADD PRIMARY KEY (`sequence_date`);

--
-- Índices de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_password_reset_selector` (`selector`),
  ADD KEY `idx_password_reset_user` (`usuario_id`,`expires_at`);

--
-- Índices de tabela `patrimonio_consultas_rate`
--
ALTER TABLE `patrimonio_consultas_rate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pcr_usuario_data` (`usuario_id`,`consultado_em`);

--
-- Índices de tabela `perfil_permissoes`
--
ALTER TABLE `perfil_permissoes`
  ADD PRIMARY KEY (`perfil_id`,`permissao_id`),
  ADD KEY `fk_pp_permissao` (`permissao_id`);

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_perfis_codigo` (`codigo`),
  ADD UNIQUE KEY `uq_perfis_nome` (`nome`);

--
-- Índices de tabela `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissoes_codigo` (`codigo`);

--
-- Índices de tabela `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Índices de tabela `setores`
--
ALTER TABLE `setores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tokens_reabertura_os`
--
ALTER TABLE `tokens_reabertura_os`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token_solicitacao` (`solicitacao_id`),
  ADD KEY `idx_ordem_servico_id` (`ordem_servico_id`),
  ADD KEY `idx_expira_em` (`expira_em`),
  ADD KEY `idx_usado_em` (`usado_em`);

--
-- Índices de tabela `token_reabertura_solicitacoes`
--
ALTER TABLE `token_reabertura_solicitacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_ordem_servico_id` (`ordem_servico_id`),
  ADD KEY `idx_criado_em` (`criado_em`),
  ADD KEY `idx_trs_status` (`status`,`criado_em`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_setores` (`setor_id`),
  ADD KEY `fk_usuarios_criado_por` (`criado_por`),
  ADD KEY `idx_usuarios_perfil_id` (`perfil_id`);

--
-- Índices de tabela `usuario_convites`
--
ALTER TABLE `usuario_convites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_expira` (`expira_em`),
  ADD KEY `idx_usado` (`usado_em`),
  ADD KEY `idx_invalidado` (`invalidado_em`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anexos_os`
--
ALTER TABLE `anexos_os`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=605;

--
-- AUTO_INCREMENT de tabela `auditoria_eventos`
--
ALTER TABLE `auditoria_eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1190;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `cftv_liberacao_confirmacoes`
--
ALTER TABLE `cftv_liberacao_confirmacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cftv_liberacoes`
--
ALTER TABLE `cftv_liberacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cftv_observacoes`
--
ALTER TABLE `cftv_observacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `cftv_ocorrencias`
--
ALTER TABLE `cftv_ocorrencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `cftv_relatorios`
--
ALTER TABLE `cftv_relatorios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `cftv_tokens_edicao`
--
ALTER TABLE `cftv_tokens_edicao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chat_conversas`
--
ALTER TABLE `chat_conversas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `cronograma_agentes`
--
ALTER TABLE `cronograma_agentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estoque_materiais`
--
ALTER TABLE `estoque_materiais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `gestor_tokens`
--
ALTER TABLE `gestor_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `historico_os`
--
ALTER TABLE `historico_os`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1194;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `manutencoes_preventivas`
--
ALTER TABLE `manutencoes_preventivas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `materiais_os`
--
ALTER TABLE `materiais_os`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ordem_agentes`
--
ALTER TABLE `ordem_agentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3634;

--
-- AUTO_INCREMENT de tabela `ordem_patrimonios`
--
ALTER TABLE `ordem_patrimonios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ordens_servico`
--
ALTER TABLE `ordens_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT de tabela `os_fotos_publicas`
--
ALTER TABLE `os_fotos_publicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `patrimonio_consultas_rate`
--
ALTER TABLE `patrimonio_consultas_rate`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `tokens_reabertura_os`
--
ALTER TABLE `tokens_reabertura_os`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `token_reabertura_solicitacoes`
--
ALTER TABLE `token_reabertura_solicitacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `usuario_convites`
--
ALTER TABLE `usuario_convites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `anexos_os`
--
ALTER TABLE `anexos_os`
  ADD CONSTRAINT `anexos_os_ibfk_1` FOREIGN KEY (`ordem_servico_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anexos_os_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `cronograma_agentes`
--
ALTER TABLE `cronograma_agentes`
  ADD CONSTRAINT `cronograma_agentes_ibfk_1` FOREIGN KEY (`agente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `cronograma_agentes_ibfk_2` FOREIGN KEY (`gestor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `cronograma_agentes_ibfk_3` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`);

--
-- Restrições para tabelas `historico_os`
--
ALTER TABLE `historico_os`
  ADD CONSTRAINT `historico_os_ibfk_1` FOREIGN KEY (`ordem_servico_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_os_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `materiais_os`
--
ALTER TABLE `materiais_os`
  ADD CONSTRAINT `materiais_os_ibfk_1` FOREIGN KEY (`ordem_servico_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ordem_patrimonios`
--
ALTER TABLE `ordem_patrimonios`
  ADD CONSTRAINT `fk_op_consultado` FOREIGN KEY (`consultado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_op_ordem` FOREIGN KEY (`ordem_id`) REFERENCES `ordens_servico` (`id`);

--
-- Restrições para tabelas `ordens_servico`
--
ALTER TABLE `ordens_servico`
  ADD CONSTRAINT `ordens_servico_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`),
  ADD CONSTRAINT `ordens_servico_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `ordens_servico_ibfk_3` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ordens_servico_ibfk_4` FOREIGN KEY (`gestor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ordens_servico_ibfk_5` FOREIGN KEY (`agente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ordens_servico_ibfk_6` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `perfil_permissoes`
--
ALTER TABLE `perfil_permissoes`
  ADD CONSTRAINT `fk_pp_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pp_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_usuarios_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`),
  ADD CONSTRAINT `fk_usuarios_setores` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Estruturas GEOP modernizadas

-- GEOP 001: estabilização e segurança (MySQL 5.7+/MariaDB 10.3+)
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(64) NOT NULL PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_numero_sequences (
  ano SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  ultimo_numero BIGINT UNSIGNED NOT NULL DEFAULT 0,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO os_numero_sequences (ano, ultimo_numero)
SELECT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(numero_os, '-', 2), '-', -1) AS UNSIGNED),
       MAX(CAST(SUBSTRING_INDEX(numero_os, '-', -1) AS UNSIGNED))
FROM ordens_servico
WHERE numero_os REGEXP '^OS-[0-9]{4}-[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(numero_os, '-', 2), '-', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE ultimo_numero = GREATEST(ultimo_numero, VALUES(ultimo_numero));

UPDATE ordens_servico SET prioridade = 'CRITICA' WHERE prioridade = 'URGENTE';

CREATE TABLE IF NOT EXISTS perfis (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(60) NOT NULL,
  nome VARCHAR(100) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_perfis_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(100) NOT NULL,
  nome VARCHAR(120) NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_permissoes_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfil_permissoes (
  perfil_id INT UNSIGNED NOT NULL,
  permissao_id INT UNSIGNED NOT NULL,
  concedida_por INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (perfil_id, permissao_id),
  KEY idx_pp_permissao (permissao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO perfis (codigo,nome) VALUES
('TI','TI'),('DIRETORIA','Diretoria'),('GESTOR','Gestor'),('GESTOR_SINDICO','Gestor síndico'),
('CFTV','CFTV'),('AGENTE','Agente'),('COORDENADOR_OPERACOES_OBRAS','Coordenador de operações e obras')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), ativo=1;

INSERT INTO permissoes (codigo,nome,descricao) VALUES
('os.ver','Visualizar ordens','Visualizar ordens'),
('os.criar','Criar ordens','Criar ordens'),
('os.editar','Editar ordens','Editar ordens'),
('os.executar','Executar ordem atribuída','Executar ordem atribuída'),
('os.reabrir','Reabrir ordens','Reabrir ordens'),
('os.aprovar','Aprovar conclusão','Aprovar conclusão'),
('relatorios.ver','Visualizar relatórios','Visualizar relatórios'),
('relatorios.exportar','Exportar relatórios','Exportar relatórios'),
('usuarios.gerenciar','Gerenciar usuários','Gerenciar usuários'),
('rbac.gerenciar','Gerenciar permissões','Gerenciar permissões'),
('catalogos.gerenciar','Gerenciar catálogos','Gerenciar catálogos'),
('estoque.ver','Visualizar estoque','Visualizar estoque'),
('estoque.movimentar','Movimentar estoque','Movimentar estoque'),
('estoque.negativo','Autorizar estoque negativo','Autorizar estoque negativo'),
('preventivas.gerenciar','Gerenciar preventivas','Gerenciar preventivas'),
('cftv.operar','Operar CFTV','Operar CFTV'),
('auditoria.ver','Visualizar auditoria','Visualizar auditoria'),
('aprovacoes.decidir','Decidir aprovações','Decidir aprovações')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao);

INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,p.id FROM perfis pf CROSS JOIN permissoes p WHERE pf.codigo='TI';
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,p.id FROM perfis pf JOIN permissoes p ON p.codigo IN
('os.ver','os.criar','os.editar','os.reabrir','os.aprovar','relatorios.ver','relatorios.exportar',
 'catalogos.gerenciar','estoque.ver','estoque.movimentar','preventivas.gerenciar','auditoria.ver','aprovacoes.decidir')
WHERE pf.codigo IN ('GESTOR','GESTOR_SINDICO','DIRETORIA');
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,p.id FROM perfis pf JOIN permissoes p ON p.codigo IN ('os.ver','os.executar')
WHERE pf.codigo='AGENTE';

DROP PROCEDURE IF EXISTS geop_add_column;
DELIMITER $$
CREATE PROCEDURE geop_add_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=p_table AND COLUMN_NAME=p_column
  ) THEN
    SET @ddl=CONCAT('ALTER TABLE `',p_table,'` ADD COLUMN `',p_column,'` ',p_definition);
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL geop_add_column('anexos_os','origem',"VARCHAR(30) NOT NULL DEFAULT 'AUTENTICADO'");
CALL geop_add_column('anexos_os','mime_type','VARCHAR(100) NULL');
CALL geop_add_column('anexos_os','tamanho_bytes','BIGINT UNSIGNED NULL');
CALL geop_add_column('anexos_os','sha256','CHAR(64) NULL');
CALL geop_add_column('anexos_os','ip_origem','VARCHAR(45) NULL');
CALL geop_add_column('anexos_os','fase',"VARCHAR(20) NOT NULL DEFAULT 'GERAL'");
CALL geop_add_column('chat_mensagens','lida_em','DATETIME NULL');
CALL geop_add_column('usuarios','senha_alterada_em','DATETIME NULL');
CALL geop_add_column('usuarios','forcar_troca_senha','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('usuarios','tentativas_login','SMALLINT UNSIGNED NOT NULL DEFAULT 0');
CALL geop_add_column('usuarios','bloqueado_ate','DATETIME NULL');
CALL geop_add_column('gestor_tokens','token_hash','CHAR(64) NULL');
CALL geop_add_column('usuario_convites','token_selector','CHAR(64) NULL');
CALL geop_add_column('auditoria_eventos','valores_anteriores','JSON NULL');
CALL geop_add_column('auditoria_eventos','valores_posteriores','JSON NULL');
CALL geop_add_column('auditoria_eventos','origem',"VARCHAR(30) NOT NULL DEFAULT 'WEB'");
CALL geop_add_column('auditoria_eventos','request_id','VARCHAR(64) NULL');

ALTER TABLE anexos_os MODIFY usuario_id INT NULL;

CREATE TABLE IF NOT EXISTS auditoria_eventos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  acao VARCHAR(80) NOT NULL,
  entidade VARCHAR(80) NOT NULL,
  entidade_id BIGINT NULL,
  valores_anteriores JSON NULL,
  valores_posteriores JSON NULL,
  ip VARCHAR(45) NULL,
  origem VARCHAR(30) NOT NULL DEFAULT 'WEB',
  request_id VARCHAR(64) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_auditoria_entidade (entidade,entidade_id),
  KEY idx_auditoria_usuario (usuario_id),
  KEY idx_auditoria_data (criado_em),
  KEY idx_auditoria_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tokens_seguros (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(40) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  entidade_id BIGINT NULL,
  usuario_id INT NULL,
  payload JSON NULL,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_token_hash (token_hash),
  KEY idx_token_tipo_entidade (tipo,entidade_id),
  KEY idx_token_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS geop_convert_utf8mb4;
DELIMITER $$
CREATE PROCEDURE geop_convert_utf8mb4()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE table_name_value VARCHAR(64);
  DECLARE tables_cursor CURSOR FOR
    SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1;
  OPEN tables_cursor;
  conversion_loop: LOOP
    FETCH tables_cursor INTO table_name_value;
    IF done=1 THEN LEAVE conversion_loop; END IF;
    SET @ddl=CONCAT('ALTER TABLE `',table_name_value,'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END LOOP;
  CLOSE tables_cursor;
END$$
DELIMITER ;
-- Execute CALL geop_convert_utf8mb4() somente depois do backup e da auditoria de mojibake.

INSERT INTO schema_migrations (version,applied_at) VALUES ('001_core_security',NOW())
ON DUPLICATE KEY UPDATE applied_at=applied_at;
-- GEOP 002: estruturas operacionais e 18 módulos
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, codigo_patrimonial VARCHAR(80) NOT NULL,
 qr_token_hash CHAR(64) NOT NULL, categoria_id INT NULL, marca VARCHAR(100), modelo VARCHAR(100),
 numero_serie VARCHAR(120), local_id INT NULL, unidade VARCHAR(100), fornecedor_id BIGINT NULL,
 garantia_ate DATE NULL, adquirido_em DATE NULL, estado_conservacao VARCHAR(40), latitude DECIMAL(10,7),
 longitude DECIMAL(10,7), dados_externos JSON NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_ativo_codigo(codigo_patrimonial), UNIQUE KEY uq_ativo_qr(qr_token_hash), KEY idx_ativo_local(local_id), KEY idx_ativo_categoria(categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativo_arquivos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(20) NOT NULL,
 caminho VARCHAR(255) NOT NULL, mime_type VARCHAR(100), sha256 CHAR(64), criado_por INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_arquivo_ativo(ativo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checklist_modelos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(160) NOT NULL, tipo_servico VARCHAR(60),
 categoria_id INT NULL, local_id INT NULL, equipe VARCHAR(100), tipo_manutencao VARCHAR(60),
 ativo TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS checklist_itens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, modelo_id BIGINT UNSIGNED NOT NULL, rotulo VARCHAR(255) NOT NULL,
 tipo ENUM('SIM_NAO','TEXTO','NUMERO','FOTO','ASSINATURA','SELECAO','DATA','MEDIDOR') NOT NULL,
 opcoes JSON NULL, obrigatorio TINYINT(1) NOT NULL DEFAULT 0, ordem SMALLINT NOT NULL DEFAULT 0, KEY idx_item_modelo(modelo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS checklist_respostas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, item_id BIGINT UNSIGNED NOT NULL,
 usuario_id INT NULL, valor_texto TEXT NULL, valor_numero DECIMAL(18,4) NULL, arquivo_id BIGINT NULL,
 respondido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_check_resposta(ordem_servico_id,item_id), KEY idx_check_usuario(usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aprovacoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo VARCHAR(40) NOT NULL, entidade VARCHAR(60) NOT NULL,
 entidade_id BIGINT NOT NULL, solicitante_id INT NOT NULL, aprovador_id INT NULL,
 status ENUM('PENDENTE','APROVADA','REPROVADA','CANCELADA') NOT NULL DEFAULT 'PENDENTE',
 observacao TEXT NULL, decidido_em DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_aprovacao_fila(status,tipo,created_at), KEY idx_aprovacao_entidade(entidade,entidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS aprovacao_historico (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, aprovacao_id BIGINT UNSIGNED NOT NULL, usuario_id INT NULL,
 status_anterior VARCHAR(20), status_novo VARCHAR(20) NOT NULL, observacao TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_aprov_hist(aprovacao_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conhecimento_artigos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, titulo VARCHAR(200) NOT NULL, categoria_id INT NULL, conteudo MEDIUMTEXT NOT NULL,
 links JSON NULL, versao VARCHAR(30) NOT NULL DEFAULT '1.0', responsavel_id INT NULL, revisar_em DATE NULL,
 status ENUM('RASCUNHO','PUBLICADO','INATIVO') NOT NULL DEFAULT 'RASCUNHO',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FULLTEXT KEY ft_artigo(titulo,conteudo), KEY idx_artigo_categoria(categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS equipe_disponibilidade (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
 tipo ENUM('JORNADA','ESCALA','FERIAS','FOLGA','AUSENCIA','AFASTAMENTO','PLANTAO') NOT NULL,
 inicio DATETIME NOT NULL, fim DATETIME NOT NULL, observacao VARCHAR(255), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_disp_usuario(usuario_id,inicio,fim), CHECK(fim>inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS usuario_especialidades (
 usuario_id INT NOT NULL, especialidade VARCHAR(100) NOT NULL, nivel TINYINT UNSIGNED DEFAULT 1,
 PRIMARY KEY(usuario_id,especialidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS passagens_turno (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, entrega_id INT NOT NULL, recebe_id INT NOT NULL,
 pendencias TEXT, ocorrencias_criticas TEXT, equipamentos_indisponiveis TEXT, servicos_andamento TEXT,
 itens_entregues TEXT, observacoes TEXT, lida_em DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_turno_recebe(recebe_id,lida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS terceiros (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo_pessoa ENUM('PF','PJ') NOT NULL, documento VARCHAR(20) NOT NULL,
 nome VARCHAR(180) NOT NULL, contatos JSON, contrato VARCHAR(120), vigencia_inicio DATE, vigencia_fim DATE,
 especialidade VARCHAR(160), situacao VARCHAR(30) NOT NULL DEFAULT 'ATIVO', avaliacao DECIMAL(3,2),
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_terceiro_documento(documento), KEY idx_terceiro_vigencia(vigencia_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS terceiro_documentos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, terceiro_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(80), caminho VARCHAR(255),
 vence_em DATE, sha256 CHAR(64), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_doc_vence(vence_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conclusao_assinaturas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, nome VARCHAR(180) NOT NULL,
 papel VARCHAR(60) NOT NULL, arquivo VARCHAR(255), aceite TINYINT(1) NOT NULL, justificativa TEXT,
 ip VARCHAR(45), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_assinatura_os(ordem_servico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recorrencias_os (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, semelhante_id INT NOT NULL,
 score DECIMAL(5,4) NOT NULL, criterios JSON NOT NULL, detectada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_recorrencia(ordem_servico_id,semelhante_id), KEY idx_rec_score(score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispositivos_iot (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identificador VARCHAR(100) NOT NULL, tipo VARCHAR(80) NOT NULL,
 local_id INT NULL, status VARCHAR(30) NOT NULL DEFAULT 'NOVO', ultima_comunicacao DATETIME NULL,
 bateria DECIMAL(5,2), limite_alerta JSON, api_key_hash CHAR(64) NOT NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_iot_identificador(identificador), UNIQUE KEY uq_iot_api(api_key_hash), KEY idx_iot_status(status,ultima_comunicacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS iot_leituras (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, dispositivo_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(80) NOT NULL,
 valor_numero DECIMAL(20,6), valor_texto VARCHAR(255), payload JSON, recebida_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_leitura_disp(dispositivo_id,recebida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativos_rede (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo ENUM('CAMERA','NVR','DVR','SWITCH','ACCESS_POINT','LINK','SERVIDOR') NOT NULL,
 nome VARCHAR(160) NOT NULL, endereco_ip VARCHAR(45), local_id INT NULL, criticidade VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
 falhas_para_alerta SMALLINT UNSIGNED NOT NULL DEFAULT 3, falhas_consecutivas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 status VARCHAR(30) NOT NULL DEFAULT 'DESCONHECIDO', ultima_verificacao DATETIME NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_rede_ip(endereco_ip), KEY idx_rede_monitor(status,ultima_verificacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ativo_rede_checks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_rede_id BIGINT UNSIGNED NOT NULL, sucesso TINYINT(1) NOT NULL,
 latencia_ms INT NULL, detalhe VARCHAR(255), verificado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_check_ativo(ativo_rede_id,verificado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, tipo VARCHAR(60) NOT NULL,
 titulo VARCHAR(180) NOT NULL, mensagem TEXT, url VARCHAR(255), lida_em DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_notificacao_usuario(usuario_id,lida_em,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_execucoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, comando VARCHAR(80) NOT NULL, status VARCHAR(20) NOT NULL,
 iniciado_em DATETIME NOT NULL, finalizado_em DATETIME NULL, resumo JSON NULL, erro TEXT NULL,
 KEY idx_cron_comando(comando,iniciado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL geop_add_column('ordens_servico','ativo_id','BIGINT UNSIGNED NULL');
CALL geop_add_column('ordens_servico','token_publico_hash','CHAR(64) NULL');
CALL geop_add_column('ordens_servico','latitude','DECIMAL(10,7) NULL');
CALL geop_add_column('ordens_servico','longitude','DECIMAL(10,7) NULL');
CALL geop_add_column('categorias','exige_foto_antes','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('categorias','exige_foto_durante','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('categorias','exige_foto_depois','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('materiais_os','produto_id','INT NULL');
CALL geop_add_column('movimentacoes_estoque','justificativa','TEXT NULL');
CALL geop_add_column('movimentacoes_estoque','aprovacao_id','BIGINT UNSIGNED NULL');

INSERT INTO schema_migrations (version,applied_at) VALUES ('002_operational_features',NOW())
ON DUPLICATE KEY UPDATE applied_at=applied_at;
