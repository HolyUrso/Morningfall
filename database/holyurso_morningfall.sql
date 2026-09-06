-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 06/09/2026 às 16:59
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `holyurso_morningfall`
--
CREATE DATABASE IF NOT EXISTS `holyurso_morningfall` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `holyurso_morningfall`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `activities`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE IF NOT EXISTS `activities` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `type` enum('live_bonus','collab','raid','event','video','viral_video','server_promo','referral','monthly_bonus','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `activity_date` date NOT NULL,
  `evidence_url` varchar(700) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_activities_streamer` (`streamer_id`),
  KEY `fk_activities_staff` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `activities`:
--   `created_by`
--       `staff_users` -> `id`
--   `streamer_id`
--       `streamers` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_commands`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `admin_commands`;
CREATE TABLE IF NOT EXISTS `admin_commands` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `command` varchar(100) NOT NULL,
  `description` varchar(500) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `command` (`command`),
  KEY `fk_admin_commands_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `admin_commands`:
--   `created_by`
--       `staff_users` -> `id`
--

--
-- Despejando dados para a tabela `admin_commands`
--

INSERT INTO `admin_commands` (`id`, `command`, `description`, `position`, `active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '/painel', 'Abre o Painel da Staff.', 1, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(2, '/tpm', 'Teleporta até a marcação do mapa.', 2, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(3, '/cds', 'Mostra a CDS da localização atual.', 3, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(4, '/nc', 'Ativa ou desativa o Noclip e Invisibilidade.', 4, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(5, '/pedpainel', 'Painel de Peds (Acesso Restrito).', 5, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(6, '/dv', 'Guarda a carroça ou veículo utilizado.', 6, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(7, '/revive', 'Revive você mesmo.', 7, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(8, '/revive iddbabota', 'Revive outro jogador utilizando o ID.', 8, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(9, '/god', 'Enche fome, sede, vida e remove o stress.', 9, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(10, '/god iddbabota', 'Enche fome, sede, vida e remove o stress de outro jogador.', 10, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(11, '/tpto iddbabota', 'Teleporta você até o jogador informado.', 11, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(12, '/tptome iddbabota', 'Teleporta o jogador informado até você.', 12, 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_logs`
--
-- Criação: 06/09/2026 às 14:34
-- Última atualização: 06/09/2026 às 14:41
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `staff_name` varchar(150) NOT NULL,
  `affected_user_id` int(10) UNSIGNED DEFAULT NULL,
  `affected_user_name` varchar(150) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `category` varchar(60) NOT NULL,
  `details` text DEFAULT NULL,
  `before_data` longtext DEFAULT NULL,
  `after_data` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_user` (`affected_user_id`),
  KEY `idx_audit_staff` (`staff_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=664 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `audit_logs`:
--

--
-- Despejando dados para a tabela `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `staff_id`, `staff_name`, `affected_user_id`, `affected_user_name`, `action`, `category`, `details`, `before_data`, `after_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Mod Luci', 8, NULL, 'Staff excluída', 'Staff', 'Usuário Staff excluído permanentemente.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:18:02'),
(2, 1, 'Mod Luci', 6, NULL, 'Staff excluída', 'Staff', 'Usuário Staff excluído permanentemente.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:18:08'),
(3, 1, 'Mod Luci', 9, 'Jessy', 'Staff criada', 'Staff', 'Novo usuário Staff criado.', NULL, '{\"username\":\"jessy\",\"role\":\"master\"}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:19:16'),
(4, 1, 'Mod Luci', 9, 'Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:23:40'),
(5, 1, 'Mod Luci', 9, 'Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:38:33'),
(6, 1, 'Mod Luci', 9, 'Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:39:18'),
(7, 1, 'Mod Luci', 1, 'Mod Luci', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"luci\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:39:37'),
(8, 1, 'Mod Luci', 9, 'Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:41:41'),
(9, 1, 'Mod Luci', 9, 'Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:43:50'),
(10, 1, 'Mod Luci', 9, 'Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:56:01'),
(11, 1, 'Mod Luci', 12, 'Matheus', 'Staff criada', 'Staff', 'Novo usuário Staff criado.', NULL, '{\"username\":\"matheus\",\"role\":\"master\"}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 17:56:39'),
(12, 1, 'Mod Luci', 9, 'Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:06:02'),
(13, 1, 'Mod Luci', 12, 'Matheus', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"matheus\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:06:09'),
(14, 1, 'Mod Luci', 1, 'CEO Luci', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"luci\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:06:28'),
(15, 1, 'CEO Luci', 9, 'CEO Jessy', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"jessy\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:06:39'),
(16, 1, 'CEO Luci', 12, 'COO Matheus', 'Staff alterada', 'Staff', 'Dados/permissão da Staff alterados.', NULL, '{\"username\":\"matheus\",\"role\":\"master\",\"password_changed\":true}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:06:50'),
(17, 1, 'CEO Luci', 7, 'Teste', 'Cadastro de streamer', 'Usuário', 'Novo streamer cadastrado.', NULL, '{\"name\":\"Teste\",\"category\":\"novato\",\"platform\":\"Twitch\",\"joined_at\":\"2026-09-03\"}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:15:58'),
(18, 1, 'CEO Luci', 8, 'Teste', 'Cadastro de streamer', 'Usuário', 'Novo streamer cadastrado.', NULL, '{\"name\":\"Teste\",\"category\":\"novato\",\"platform\":\"Twitch\",\"joined_at\":\"2026-09-03\"}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:16:33'),
(19, 1, 'CEO Luci', 10, 'teste', 'Cadastro de streamer', 'Usuário', 'Novo streamer cadastrado.', NULL, '{\"name\":\"teste\",\"category\":\"novato\",\"platform\":\"Twitch\",\"joined_at\":\"2026-09-03\"}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-03 18:23:07'),
(20, 1, 'CEO Luci', 7, 'Teste', 'Alteração de streamer', 'Usuário', 'Perfil do streamer alterado.', '{\"name\":\"Teste\",\"discord\":\"207712204890439680\",\"platform\":\"Twitch\",\"channel_url\":\"\",\"category\":\"novato\",\"joined_at\":\"2026-09-03\",\"notes\":\"\"}', '{\"name\":\"Teste\",\"discord\":\"207712204890439680\",\"platform\":\"Twitch\",\"channel_url\":\"\",\"category\":\"novato\",\"joined_at\":\"2026-09-03\",\"notes\":\"\"}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 00:37:58'),
(21, 1, 'CEO Luci', 7, 'Teste', 'Alteração de streamer', 'Usuário', 'Perfil do streamer alterado.', '{\"name\":\"Teste\",\"discord\":\"207712204890439680\",\"platform\":\"Twitch\",\"channel_url\":\"\",\"category\":\"novato\",\"joined_at\":\"2026-09-03\",\"notes\":\"\"}', '{\"name\":\"Teste\",\"discord\":\"207712204890439680\",\"platform\":\"Twitch\",\"channel_url\":\"\",\"category\":\"novato\",\"joined_at\":\"2026-09-03\",\"notes\":\"\"}', '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 00:40:58'),
(22, 1, 'CEO Luci', 4, 'Ntsaca', 'Colaborador do site criado', 'Equipe', 'Novo colaborador cadastrado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 00:55:27'),
(23, 1, 'CEO Luci', 5, 'Aurora', 'Colaborador do site criado', 'Equipe', 'Novo colaborador cadastrado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 00:56:53'),
(24, 1, 'CEO Luci', 4, 'Ntsaca', 'Colaborador do site alterado', 'Equipe', 'Dados do colaborador alterados.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 00:57:30'),
(25, 1, 'CEO Luci', 4, 'Nysaca', 'Colaborador do site alterado', 'Equipe', 'Dados do colaborador alterados.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 00:58:10'),
(26, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública criada', 'Empresa', 'Empresa adicionada ao Portal.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:33:53'),
(27, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:38:56'),
(28, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:45:06'),
(29, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:46:53'),
(30, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:48:10'),
(31, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:50:44'),
(32, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:51:45'),
(33, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 02:52:53'),
(34, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 03:22:38'),
(35, 1, 'CEO Luci', 1, 'Saloon', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 03:23:25'),
(36, 1, 'CEO Luci', 1, 'Categoria', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 03:26:36'),
(37, 1, 'CEO Luci', 1, 'Categoria', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 03:33:32'),
(38, 1, 'CEO Luci', 0, 'Imagens', 'Pacote de imagens de precificação enviado', 'Precificação', 'Convertidas 1 imagem(ns) para WebP.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:02:23'),
(39, 1, 'CEO Luci', 1, 'Saloon', 'Categoria de precificação alterada', 'Precificação', 'Categoria alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:03:20'),
(40, 1, 'CEO Luci', 1, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:13'),
(41, 1, 'CEO Luci', 23, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:13'),
(42, 1, 'CEO Luci', 2, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:13'),
(43, 1, 'CEO Luci', 24, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:13'),
(44, 1, 'CEO Luci', 3, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:14'),
(45, 1, 'CEO Luci', 25, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:14'),
(46, 1, 'CEO Luci', 4, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:14'),
(47, 1, 'CEO Luci', 26, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:14'),
(48, 1, 'CEO Luci', 5, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:15'),
(49, 1, 'CEO Luci', 27, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:15'),
(50, 1, 'CEO Luci', 6, 'Piña Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:15'),
(51, 1, 'CEO Luci', 28, 'Piña Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:15'),
(52, 1, 'CEO Luci', 7, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:16'),
(53, 1, 'CEO Luci', 29, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:16'),
(54, 1, 'CEO Luci', 8, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:16'),
(55, 1, 'CEO Luci', 30, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:16'),
(56, 1, 'CEO Luci', 9, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:17'),
(57, 1, 'CEO Luci', 31, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:17'),
(58, 1, 'CEO Luci', 10, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:17'),
(59, 1, 'CEO Luci', 32, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:17'),
(60, 1, 'CEO Luci', 11, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:18'),
(61, 1, 'CEO Luci', 33, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:18'),
(62, 1, 'CEO Luci', 12, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:18'),
(63, 1, 'CEO Luci', 34, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:18'),
(64, 1, 'CEO Luci', 13, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:19'),
(65, 1, 'CEO Luci', 35, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:19'),
(66, 1, 'CEO Luci', 14, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:19'),
(67, 1, 'CEO Luci', 36, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:20'),
(68, 1, 'CEO Luci', 15, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:20'),
(69, 1, 'CEO Luci', 37, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:20'),
(70, 1, 'CEO Luci', 16, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:20'),
(71, 1, 'CEO Luci', 38, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:21'),
(72, 1, 'CEO Luci', 17, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:21'),
(73, 1, 'CEO Luci', 39, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:21'),
(74, 1, 'CEO Luci', 18, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:21'),
(75, 1, 'CEO Luci', 40, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:22'),
(76, 1, 'CEO Luci', 19, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:22'),
(77, 1, 'CEO Luci', 41, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:22'),
(78, 1, 'CEO Luci', 20, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:22'),
(79, 1, 'CEO Luci', 42, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:23'),
(80, 1, 'CEO Luci', 21, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:23'),
(81, 1, 'CEO Luci', 43, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:23'),
(82, 1, 'CEO Luci', 22, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:23'),
(83, 1, 'CEO Luci', 44, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:24'),
(84, 1, 'CEO Luci', 0, 'Imagens', 'Pacote de imagens de precificação enviado', 'Precificação', 'Convertidas 1 imagem(ns) para WebP.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:10:45'),
(85, 1, 'CEO Luci', 1, 'Whiskey', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:11:22'),
(86, 1, 'CEO Luci', 1, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:11:33'),
(87, 1, 'CEO Luci', 0, 'Imagens', 'Pacote de imagens de precificação enviado', 'Precificação', 'Convertidas 1 imagem(ns) para WebP.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:13:47'),
(88, 1, 'CEO Luci', 0, 'Imagens', 'Pacote de imagens de precificação enviado', 'Precificação', 'Convertidas 1 imagem(ns) para WebP.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:20:22'),
(89, 1, 'CEO Luci', 45, 'defull', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:21:12'),
(90, 1, 'CEO Luci', 45, 'defull', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado default.webp.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:21:17'),
(91, 1, 'CEO Luci', 46, 'defull', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:24:52'),
(92, 1, 'CEO Luci', 47, 'defull', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:24:54'),
(93, 1, 'CEO Luci', 48, 'defull', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:25:08'),
(94, 1, 'CEO Luci', 45, 'defull', 'Imagem automática de precificação', 'Precificação', 'Imagem vinculada: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:25:39'),
(95, 1, 'CEO Luci', 45, 'defull', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:25:56'),
(96, 1, 'CEO Luci', 46, 'defull', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:00'),
(97, 1, 'CEO Luci', 47, 'defull', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:03'),
(98, 1, 'CEO Luci', 48, 'defull', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:06'),
(99, 1, 'CEO Luci', 1, 'Whiskey', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:13'),
(100, 1, 'CEO Luci', 2, 'Gin Cereja', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:19'),
(101, 1, 'CEO Luci', 25, 'Bourbon', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:24'),
(102, 1, 'CEO Luci', 26, 'Brandy', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:30'),
(103, 1, 'CEO Luci', 28, 'Piña Colada', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:26:38'),
(104, 1, 'CEO Luci', 48, 'defull', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:29'),
(105, 1, 'CEO Luci', 23, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:29'),
(106, 1, 'CEO Luci', 24, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:30'),
(107, 1, 'CEO Luci', 3, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:30'),
(108, 1, 'CEO Luci', 4, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:30'),
(109, 1, 'CEO Luci', 5, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:30'),
(110, 1, 'CEO Luci', 27, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:31'),
(111, 1, 'CEO Luci', 6, 'Piña Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:31'),
(112, 1, 'CEO Luci', 7, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:31'),
(113, 1, 'CEO Luci', 29, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:31'),
(114, 1, 'CEO Luci', 8, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:32'),
(115, 1, 'CEO Luci', 30, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:32'),
(116, 1, 'CEO Luci', 9, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:32'),
(117, 1, 'CEO Luci', 31, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:32'),
(118, 1, 'CEO Luci', 10, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:33'),
(119, 1, 'CEO Luci', 32, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:33'),
(120, 1, 'CEO Luci', 11, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:33'),
(121, 1, 'CEO Luci', 33, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:33'),
(122, 1, 'CEO Luci', 12, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:34'),
(123, 1, 'CEO Luci', 34, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:34'),
(124, 1, 'CEO Luci', 13, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:35'),
(125, 1, 'CEO Luci', 35, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:35'),
(126, 1, 'CEO Luci', 14, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:35'),
(127, 1, 'CEO Luci', 36, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:36'),
(128, 1, 'CEO Luci', 15, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:36'),
(129, 1, 'CEO Luci', 37, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:36'),
(130, 1, 'CEO Luci', 16, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:36'),
(131, 1, 'CEO Luci', 38, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:37'),
(132, 1, 'CEO Luci', 17, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:37'),
(133, 1, 'CEO Luci', 39, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:37'),
(134, 1, 'CEO Luci', 48, 'defull', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:37'),
(135, 1, 'CEO Luci', 18, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:37'),
(136, 1, 'CEO Luci', 40, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:38'),
(137, 1, 'CEO Luci', 19, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:38'),
(138, 1, 'CEO Luci', 41, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:38'),
(139, 1, 'CEO Luci', 20, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:39'),
(140, 1, 'CEO Luci', 42, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:39'),
(141, 1, 'CEO Luci', 21, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:39'),
(142, 1, 'CEO Luci', 43, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:39'),
(143, 1, 'CEO Luci', 22, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:40'),
(144, 1, 'CEO Luci', 44, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:40'),
(145, 1, 'CEO Luci', 48, 'defull', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: default.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:40'),
(146, 1, 'CEO Luci', 1, 'Saloon', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 40 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:30:57');
INSERT INTO `audit_logs` (`id`, `staff_id`, `staff_name`, `affected_user_id`, `affected_user_name`, `action`, `category`, `details`, `before_data`, `after_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(147, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:31:39'),
(148, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:41:12'),
(149, 1, 'CEO Luci', 1, 'Saloon', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 44 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:43:00'),
(150, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:43:07'),
(151, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:50'),
(152, 1, 'CEO Luci', 93, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:53'),
(153, 1, 'CEO Luci', 115, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:53'),
(154, 1, 'CEO Luci', 94, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:53'),
(155, 1, 'CEO Luci', 116, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:54'),
(156, 1, 'CEO Luci', 95, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:54'),
(157, 1, 'CEO Luci', 117, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:54'),
(158, 1, 'CEO Luci', 96, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:54'),
(159, 1, 'CEO Luci', 118, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:55'),
(160, 1, 'CEO Luci', 97, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:55'),
(161, 1, 'CEO Luci', 119, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:55'),
(162, 1, 'CEO Luci', 98, 'Piña Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:55'),
(163, 1, 'CEO Luci', 120, 'Piña Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:56'),
(164, 1, 'CEO Luci', 99, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:56'),
(165, 1, 'CEO Luci', 121, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:56'),
(166, 1, 'CEO Luci', 100, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:56'),
(167, 1, 'CEO Luci', 122, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:57'),
(168, 1, 'CEO Luci', 101, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:57'),
(169, 1, 'CEO Luci', 123, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem não encontrada; usado fallback: defull.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:58'),
(170, 1, 'CEO Luci', 1, 'Saloon', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 44 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:45:58'),
(171, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:46:07'),
(172, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:49:00'),
(173, 1, 'CEO Luci', 1, 'Saloon', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 44 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:49:10'),
(174, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:49:21'),
(175, 1, 'CEO Luci', 181, 'Whiskey', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:49:43'),
(176, 1, 'CEO Luci', 1, 'Saloon', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 21 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:49:55'),
(177, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 22 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:50:12'),
(178, 1, 'CEO Luci', 221, 'Caldo Milho', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 04:53:00'),
(179, 1, 'CEO Luci', 221, 'Caldo Milho', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:03:53'),
(180, 1, 'CEO Luci', 221, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: caldo_milho.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:04:11'),
(181, 1, 'CEO Luci', 203, 'Whiskey', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:06:42'),
(182, 1, 'CEO Luci', 204, 'Gin Cereja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:09:01'),
(183, 1, 'CEO Luci', 205, 'Bourbon', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:10:31'),
(184, 1, 'CEO Luci', 206, 'Brandy', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:12:29'),
(185, 1, 'CEO Luci', 207, 'Vinho Tinto', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:14:52'),
(186, 1, 'CEO Luci', 208, 'Piña Colada', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:16:25'),
(187, 1, 'CEO Luci', 209, 'Hidromel', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:18:13'),
(188, 1, 'CEO Luci', 210, 'Cerveja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:20:00'),
(189, 1, 'CEO Luci', 211, 'Café', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:21:25'),
(190, 1, 'CEO Luci', 212, 'Suco de Laranja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:23:53'),
(191, 1, 'CEO Luci', 213, 'Suco de Uva', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:26:48'),
(192, 1, 'CEO Luci', 214, 'Suco de Limão', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:28:58'),
(193, 1, 'CEO Luci', 215, 'Suco de Framboeza', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:31:51'),
(194, 1, 'CEO Luci', 216, 'Prato Lá Cowboy', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:40:04'),
(195, 1, 'CEO Luci', 217, 'Picadinho de Carne', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:46:07'),
(196, 1, 'CEO Luci', 218, 'Fricassé de Frango', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:48:39'),
(197, 1, 'CEO Luci', 219, 'Prato Omelete', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:50:33'),
(198, 1, 'CEO Luci', 220, 'Mingau', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:52:34'),
(199, 1, 'CEO Luci', 222, 'Sopa Batata', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 05:53:59'),
(200, 1, 'CEO Luci', 223, 'Sopa Feijão', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:00:14'),
(201, 1, 'CEO Luci', 224, 'Sopa Verduras', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:01:11'),
(202, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:06:11'),
(203, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:06:41'),
(204, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:14:40'),
(205, 1, 'CEO Luci', 203, 'Whiskey', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:19:18'),
(206, 1, 'CEO Luci', 204, 'Gin Cereja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:26:11'),
(207, 1, 'CEO Luci', 205, 'Bourbon', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:27:44'),
(208, 1, 'CEO Luci', 206, 'Brandy', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:29:20'),
(209, 1, 'CEO Luci', 207, 'Vinho Tinto', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:31:26'),
(210, 1, 'CEO Luci', 208, 'Straberry Colada', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:34:45'),
(211, 1, 'CEO Luci', 208, 'Strawberry Colada', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:34:59'),
(212, 1, 'CEO Luci', 211, 'Café', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:38:30'),
(213, 1, 'CEO Luci', 212, 'Suco de Laranja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:39:49'),
(214, 1, 'CEO Luci', 214, 'Suco de Limão', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:41:17'),
(215, 1, 'CEO Luci', 214, 'Suco de Limão', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:43:56'),
(216, 1, 'CEO Luci', 214, 'Suco de Limão', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:44:19'),
(217, 1, 'CEO Luci', 214, 'Suco de Limão', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:45:12'),
(218, 1, 'CEO Luci', 213, 'Suco de Uva', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:46:47'),
(219, 1, 'CEO Luci', 209, 'Hidromel', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:51:52'),
(220, 1, 'CEO Luci', 210, 'Cerveja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:52:14'),
(221, 1, 'CEO Luci', 215, 'Suco de Framboeza', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:53:41'),
(222, 1, 'CEO Luci', 216, 'Prato Lá Cowboy', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:54:15'),
(223, 1, 'CEO Luci', 217, 'Picadinho de Carne', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:54:35'),
(224, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:54:56'),
(225, 1, 'CEO Luci', 218, 'Fricassé de Frango', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:55:30'),
(226, 1, 'CEO Luci', 219, 'Prato Omelete', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:55:45'),
(227, 1, 'CEO Luci', 220, 'Mingau', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:56:03'),
(228, 1, 'CEO Luci', 221, 'Caldo Milho', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:56:27'),
(229, 1, 'CEO Luci', 223, 'Sopa Feijão', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:57:06'),
(230, 1, 'CEO Luci', 224, 'Sopa Verduras', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:57:27'),
(231, 1, 'CEO Luci', 221, 'Caldo Milho', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 06:59:22'),
(232, 1, 'CEO Luci', 221, 'Caldo Milho', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:01:07'),
(233, 1, 'CEO Luci', 222, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_batata.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:01:56'),
(234, 1, 'CEO Luci', 226, 'Linguiça Acebolada', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:12:50'),
(235, 1, 'CEO Luci', 227, 'Frango Assado', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:21:08'),
(236, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:27:25'),
(237, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:27:57'),
(238, 1, 'CEO Luci', 227, 'Frango Assado', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:28:27'),
(239, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:29:01'),
(240, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:29:31'),
(241, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:32:49'),
(242, 1, 'CEO Luci', 226, 'Linguiça Acebolada', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:34:07'),
(243, 1, 'CEO Luci', 229, 'Carne Louca', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 07:49:22'),
(244, 1, 'CEO Luci', 230, 'Provolone Milanesa', 'Produto de precificação criado', 'Precificação', 'Produto criado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:13:15'),
(245, 1, 'CEO Luci', 216, 'Prato Lá Cowboy', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:14:09'),
(246, 1, 'CEO Luci', 217, 'Picadinho de Carne', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:14:40'),
(247, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:15:10'),
(248, 1, 'CEO Luci', 229, 'Carne Louca', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:15:49'),
(249, 1, 'CEO Luci', 227, 'Frango Assado', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:16:37'),
(250, 1, 'CEO Luci', 218, 'Fricassé de Frango', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:17:13'),
(251, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:18:22'),
(252, 1, 'CEO Luci', 226, 'Linguiça Acebolada', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:19:01'),
(253, 1, 'CEO Luci', 219, 'Prato Omelete', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:20:35'),
(254, 1, 'CEO Luci', 219, 'Prato Omelete', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:34'),
(255, 1, 'CEO Luci', 203, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: whiskey.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:43'),
(256, 1, 'CEO Luci', 204, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: gin_cereja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:43'),
(257, 1, 'CEO Luci', 205, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bourbon.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:43'),
(258, 1, 'CEO Luci', 206, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: brandy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:44'),
(259, 1, 'CEO Luci', 207, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: vinho_tinto.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:44'),
(260, 1, 'CEO Luci', 208, 'Strawberry Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: strawberry_colada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:44'),
(261, 1, 'CEO Luci', 209, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: hidromel.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:44'),
(262, 1, 'CEO Luci', 210, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cerveja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:45'),
(263, 1, 'CEO Luci', 211, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cafe.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:45'),
(264, 1, 'CEO Luci', 212, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_laranja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:45'),
(265, 1, 'CEO Luci', 213, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_uva.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:46'),
(266, 1, 'CEO Luci', 214, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_limao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:46'),
(267, 1, 'CEO Luci', 215, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_framboeza.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:46'),
(268, 1, 'CEO Luci', 216, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_la_cowboy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:46'),
(269, 1, 'CEO Luci', 217, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: picadinho_de_carne.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:47'),
(270, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bife_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:47'),
(271, 1, 'CEO Luci', 229, 'Carne Louca', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: carne_louca.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:47'),
(272, 1, 'CEO Luci', 227, 'Frango Assado', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: frango_assado.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:47'),
(273, 1, 'CEO Luci', 218, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: fricasse_de_frango.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:48'),
(274, 1, 'CEO Luci', 230, 'Provolone Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: provolone_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:48'),
(275, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: especial_da_marilene.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:48'),
(276, 1, 'CEO Luci', 226, 'Linguiça Acebolada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: linguica_acebolada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:49'),
(277, 1, 'CEO Luci', 219, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_omelete.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:49'),
(278, 1, 'CEO Luci', 220, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: mingau.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:49'),
(279, 1, 'CEO Luci', 221, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: caldo_milho.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:49'),
(280, 1, 'CEO Luci', 222, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_batata.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:50'),
(281, 1, 'CEO Luci', 223, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_feijao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:50'),
(282, 1, 'CEO Luci', 224, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_verduras.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:26:50'),
(283, 1, 'CEO Luci', 219, 'Prato Omelete', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:40'),
(284, 1, 'CEO Luci', 203, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: whiskey.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:55'),
(285, 1, 'CEO Luci', 204, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: gin_cereja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:55'),
(286, 1, 'CEO Luci', 205, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bourbon.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:56'),
(287, 1, 'CEO Luci', 206, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: brandy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:56'),
(288, 1, 'CEO Luci', 207, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: vinho_tinto.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:56'),
(289, 1, 'CEO Luci', 208, 'Strawberry Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: strawberry_colada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:57'),
(290, 1, 'CEO Luci', 209, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: hidromel.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:57'),
(291, 1, 'CEO Luci', 210, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cerveja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:57'),
(292, 1, 'CEO Luci', 211, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cafe.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:57'),
(293, 1, 'CEO Luci', 212, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_laranja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:58'),
(294, 1, 'CEO Luci', 213, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_uva.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:58'),
(295, 1, 'CEO Luci', 214, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_limao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:58'),
(296, 1, 'CEO Luci', 215, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_framboeza.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:58'),
(297, 1, 'CEO Luci', 216, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_la_cowboy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:59'),
(298, 1, 'CEO Luci', 217, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: picadinho_de_carne.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:59'),
(299, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bife_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:34:59');
INSERT INTO `audit_logs` (`id`, `staff_id`, `staff_name`, `affected_user_id`, `affected_user_name`, `action`, `category`, `details`, `before_data`, `after_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(300, 1, 'CEO Luci', 229, 'Carne Louca', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: carne_louca.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:00'),
(301, 1, 'CEO Luci', 227, 'Frango Assado', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: frango_assado.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:00'),
(302, 1, 'CEO Luci', 218, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: fricasse_de_frango.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:00'),
(303, 1, 'CEO Luci', 230, 'Provolone Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: provolone_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:01'),
(304, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: especial_da_marilene.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:01'),
(305, 1, 'CEO Luci', 226, 'Linguiça Acebolada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: linguica_acebolada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:02'),
(306, 1, 'CEO Luci', 219, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_omelete.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:02'),
(307, 1, 'CEO Luci', 220, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: mingau.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:02'),
(308, 1, 'CEO Luci', 221, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: caldo_milho.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:03'),
(309, 1, 'CEO Luci', 222, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_batata.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:03'),
(310, 1, 'CEO Luci', 223, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_feijao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:03'),
(311, 1, 'CEO Luci', 224, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_verduras.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:35:04'),
(312, 1, 'CEO Luci', 203, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: whiskey.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:00'),
(313, 1, 'CEO Luci', 204, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: gin_cereja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:00'),
(314, 1, 'CEO Luci', 205, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bourbon.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:00'),
(315, 1, 'CEO Luci', 206, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: brandy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:00'),
(316, 1, 'CEO Luci', 207, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: vinho_tinto.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:01'),
(317, 1, 'CEO Luci', 208, 'Strawberry Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: strawberry_colada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:01'),
(318, 1, 'CEO Luci', 209, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: hidromel.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:01'),
(319, 1, 'CEO Luci', 210, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cerveja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:02'),
(320, 1, 'CEO Luci', 211, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cafe.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:02'),
(321, 1, 'CEO Luci', 212, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_laranja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:02'),
(322, 1, 'CEO Luci', 213, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_uva.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:03'),
(323, 1, 'CEO Luci', 214, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_limao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:03'),
(324, 1, 'CEO Luci', 215, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_framboeza.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:03'),
(325, 1, 'CEO Luci', 216, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_la_cowboy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:04'),
(326, 1, 'CEO Luci', 217, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: picadinho_de_carne.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:04'),
(327, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bife_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:04'),
(328, 1, 'CEO Luci', 229, 'Carne Louca', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: carne_louca.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:05'),
(329, 1, 'CEO Luci', 227, 'Frango Assado', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: frango_assado.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:05'),
(330, 1, 'CEO Luci', 218, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: fricasse_de_frango.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:05'),
(331, 1, 'CEO Luci', 230, 'Provolone Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: provolone_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:06'),
(332, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: especial_da_marilene.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:06'),
(333, 1, 'CEO Luci', 226, 'Linguiça Acebolada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: linguica_acebolada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:07'),
(334, 1, 'CEO Luci', 219, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_omelete.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:07'),
(335, 1, 'CEO Luci', 220, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: mingau.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:07'),
(336, 1, 'CEO Luci', 221, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: caldo_milho.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:08'),
(337, 1, 'CEO Luci', 222, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_batata.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:08'),
(338, 1, 'CEO Luci', 223, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_feijao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:10'),
(339, 1, 'CEO Luci', 224, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_verduras.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:41:11'),
(340, 1, 'CEO Luci', 2, 'Fazenda', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:44:01'),
(341, 1, 'CEO Luci', 2, 'Fazenda', 'Categoria de precificação alterada', 'Precificação', 'Categoria alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:44:14'),
(342, 1, 'CEO Luci', 1, 'Saloon', 'Categoria de precificação alterada', 'Precificação', 'Categoria alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:44:20'),
(343, 1, 'CEO Luci', 2, 'Fazenda', 'Importação rápida de precificação', 'Precificação', 'Importados 18 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:46:48'),
(344, 1, 'CEO Luci', 2, 'Fazenda', 'Importação rápida de precificação', 'Precificação', 'Importados 18 produtos via TXT.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:24'),
(345, 1, 'CEO Luci', 203, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: whiskey.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:45'),
(346, 1, 'CEO Luci', 203, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: whiskey.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:49'),
(347, 1, 'CEO Luci', 204, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: gin_cereja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:50'),
(348, 1, 'CEO Luci', 205, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bourbon.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:50'),
(349, 1, 'CEO Luci', 206, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: brandy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:50'),
(350, 1, 'CEO Luci', 207, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: vinho_tinto.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:51'),
(351, 1, 'CEO Luci', 208, 'Strawberry Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: strawberry_colada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:51'),
(352, 1, 'CEO Luci', 209, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: hidromel.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:51'),
(353, 1, 'CEO Luci', 210, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cerveja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:51'),
(354, 1, 'CEO Luci', 211, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cafe.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:52'),
(355, 1, 'CEO Luci', 212, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_laranja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:52'),
(356, 1, 'CEO Luci', 213, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_uva.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:52'),
(357, 1, 'CEO Luci', 214, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_limao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:52'),
(358, 1, 'CEO Luci', 215, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_framboeza.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:53'),
(359, 1, 'CEO Luci', 216, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_la_cowboy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:53'),
(360, 1, 'CEO Luci', 217, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: picadinho_de_carne.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:53'),
(361, 1, 'CEO Luci', 228, 'Bife Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bife_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:53'),
(362, 1, 'CEO Luci', 229, 'Carne Louca', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: carne_louca.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:54'),
(363, 1, 'CEO Luci', 227, 'Frango Assado', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: frango_assado.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:54'),
(364, 1, 'CEO Luci', 218, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: fricasse_de_frango.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:54'),
(365, 1, 'CEO Luci', 230, 'Provolone Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: provolone_milanesa.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:55'),
(366, 1, 'CEO Luci', 225, 'Especial da Marilene', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: especial_da_marilene.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:55'),
(367, 1, 'CEO Luci', 226, 'Linguiça Acebolada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: linguica_acebolada.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:55'),
(368, 1, 'CEO Luci', 219, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_omelete.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:55'),
(369, 1, 'CEO Luci', 220, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: mingau.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:56'),
(370, 1, 'CEO Luci', 221, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: caldo_milho.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:56'),
(371, 1, 'CEO Luci', 222, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_batata.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:56'),
(372, 1, 'CEO Luci', 223, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_feijao.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:56'),
(373, 1, 'CEO Luci', 224, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_verduras.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 08:57:57'),
(374, 1, 'CEO Luci', 231, 'Álcool Artesanal', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:00:13'),
(375, 1, 'CEO Luci', 232, 'Farinha de Trigo', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:02:31'),
(376, 1, 'CEO Luci', 233, 'Amido de Milho', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:04:17'),
(377, 1, 'CEO Luci', 234, 'Torrões de Açucar', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:06:01'),
(378, 1, 'CEO Luci', 235, 'Pó de Café', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:07:46'),
(379, 1, 'CEO Luci', 236, 'Vinagre de Uva', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:09:27'),
(380, 1, 'CEO Luci', 237, 'Caixa de Ovos', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:11:43'),
(381, 1, 'CEO Luci', 238, 'Garrafa de Leite', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:20:40'),
(382, 1, 'CEO Luci', 239, 'Queijo', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:29:59'),
(383, 1, 'CEO Luci', 240, 'Levedura', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:31:32'),
(384, 1, 'CEO Luci', 241, 'Geleia de Cereja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:34:14'),
(385, 1, 'CEO Luci', 242, 'Melado de Cana', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:35:33'),
(386, 1, 'CEO Luci', 243, 'Fécula de Batata', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:42:37'),
(387, 1, 'CEO Luci', 244, 'Oleo Vegetal', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:44:11'),
(388, 1, 'CEO Luci', 245, 'Erva Medicinal', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:45:52'),
(389, 1, 'CEO Luci', 246, 'Tabaco Curado', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:47:24'),
(390, 1, 'CEO Luci', 247, 'Caixa de Verduras', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:48:56'),
(391, 1, 'CEO Luci', 248, 'Caixa de Animais', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:52:01'),
(392, 1, 'CEO Luci', 248, 'Caixa de Animais', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:52:57'),
(393, 1, 'CEO Luci', 248, 'Caixa de Animais', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:57:04'),
(394, 1, 'CEO Luci', 2, 'Fazenda', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 18 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:57:10'),
(395, 1, 'CEO Luci', 2, 'Fazenda', 'Importação rápida de precificação', 'Precificação', 'Importados 18 produtos via ZIP. Imagens novas: 18.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:57:31'),
(396, 1, 'CEO Luci', 1, 'Saloon', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 28 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:58:12'),
(397, 1, 'CEO Luci', 1, 'Saloon', 'Importação rápida de precificação', 'Precificação', 'Importados 28 produtos via ZIP. Imagens novas: 28.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 09:58:47'),
(398, 1, 'CEO Luci', 3, 'Doceria / Padaria', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:04:37'),
(399, 1, 'CEO Luci', 3, 'Doceria / Padaria', 'Importação rápida de precificação', 'Precificação', 'Importados 13 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:04:51'),
(400, 1, 'CEO Luci', 295, 'Chocolate em Barra Rústico', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:06:33'),
(401, 1, 'CEO Luci', 296, 'Arroz Doce', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:08:13'),
(402, 1, 'CEO Luci', 297, 'Bolo Brigadeiro', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:09:52'),
(403, 1, 'CEO Luci', 298, 'Bolo de Milho', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:11:21'),
(404, 1, 'CEO Luci', 299, 'Doce de Leite', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:12:47'),
(405, 1, 'CEO Luci', 300, 'Torta de Maça', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:14:21'),
(406, 1, 'CEO Luci', 301, 'Cheesecake Cereja', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:16:04'),
(407, 1, 'CEO Luci', 302, 'Bolinho de Carne', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:18:13'),
(408, 1, 'CEO Luci', 303, 'Coxinha', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:19:41'),
(409, 1, 'CEO Luci', 304, 'Bala de Caramelo', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:20:52'),
(410, 1, 'CEO Luci', 305, 'Acholatado', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:24:41'),
(411, 1, 'CEO Luci', 306, 'Café Cremoso', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:25:58'),
(412, 1, 'CEO Luci', 306, 'Café Cremoso', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:27:20'),
(413, 1, 'CEO Luci', 4, 'Ferraria', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:31:19'),
(414, 1, 'CEO Luci', 4, 'Ferraria', 'Importação rápida de precificação', 'Precificação', 'Importados 19 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:31:29'),
(415, 1, 'CEO Luci', 4, 'Ferraria', 'Categoria de precificação alterada', 'Precificação', 'Categoria alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:32:04'),
(416, 1, 'CEO Luci', 5, 'Açougueiro', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:32:17'),
(417, 1, 'CEO Luci', 6, 'Mineradora', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:34:43'),
(418, 1, 'CEO Luci', 6, 'Mineradora', 'Importação rápida de precificação', 'Precificação', 'Importados 14 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:34:54'),
(419, 1, 'CEO Luci', 7, 'Madeireira', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:37:15'),
(420, 1, 'CEO Luci', 7, 'Madeireira', 'Importação rápida de precificação', 'Precificação', 'Importados 12 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:37:25'),
(421, 1, 'CEO Luci', 8, 'Hospital', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:38:59'),
(422, 1, 'CEO Luci', 8, 'Hospital', 'Importação rápida de precificação', 'Precificação', 'Importados 4 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:39:11'),
(423, 1, 'CEO Luci', 9, 'Artesanato', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:39:56'),
(424, 1, 'CEO Luci', 8, 'Hospital', 'Categoria de precificação alterada', 'Precificação', 'Categoria alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:40:19'),
(425, 1, 'CEO Luci', 9, 'Artesanato', 'Importação rápida de precificação', 'Precificação', 'Importados 12 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:40:55'),
(426, 1, 'CEO Luci', 10, 'Veterinaria', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:42:13'),
(427, 1, 'CEO Luci', 10, 'Veterinaria', 'Importação rápida de precificação', 'Precificação', 'Importados 10 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:43:41'),
(428, 1, 'CEO Luci', 10, 'Veterinaria', 'Categoria de precificação alterada', 'Precificação', 'Categoria alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:44:00'),
(429, 1, 'CEO Luci', 6, 'Mineradora', 'Categoria de precificação alterada', 'Precificação', 'Categoria alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:44:08'),
(430, 1, 'CEO Luci', 11, 'Armaria', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:46:16'),
(431, 1, 'CEO Luci', 11, 'Armaria', 'Importação rápida de precificação', 'Precificação', 'Importados 34 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:46:27'),
(432, 1, 'CEO Luci', 12, 'Jornal', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:50:15'),
(433, 1, 'CEO Luci', 12, 'Jornal', 'Importação rápida de precificação', 'Precificação', 'Importados 10 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:50:23'),
(434, 1, 'CEO Luci', 13, 'Atelie', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:51:01'),
(435, 1, 'CEO Luci', 13, 'Atelie', 'Importação rápida de precificação', 'Precificação', 'Importados 8 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:52:03'),
(436, 1, 'CEO Luci', 14, 'Nativos', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:53:00'),
(437, 1, 'CEO Luci', 14, 'Nativos', 'Importação rápida de precificação', 'Precificação', 'Importados 4 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:53:13'),
(438, 1, 'CEO Luci', 14, 'Nativos', 'Todos os produtos de uma categoria foram excluídos', 'Precificação', 'Excluídos 4 produto(s).', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:54:01'),
(439, 1, 'CEO Luci', 14, 'Nativos', 'Importação rápida de precificação', 'Precificação', 'Importados 12 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:54:23'),
(440, 1, 'CEO Luci', 15, 'Tabacaria', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:54:41'),
(441, 1, 'CEO Luci', 15, 'Tabacaria', 'Importação rápida de precificação', 'Precificação', 'Importados 6 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:55:46'),
(442, 1, 'CEO Luci', 16, 'Estabulo', 'Categoria de precificação criada', 'Precificação', 'Categoria criada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:56:39'),
(443, 1, 'CEO Luci', 16, 'Estabulo', 'Importação rápida de precificação', 'Precificação', 'Importados 8 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:56:54'),
(444, 1, 'CEO Luci', 459, 'Ração Equina', 'Produto de precificação excluído', 'Precificação', 'Produto excluído.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 10:59:11'),
(445, 1, 'CEO Luci', 5, 'Açougueiro', 'Importação rápida de precificação', 'Precificação', 'Importados 9 produtos via TXT. Imagens novas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:04:10'),
(446, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:12:01'),
(447, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:15:03'),
(448, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 7; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:21:33'),
(449, 1, 'CEO Luci', 2, 'Saloon de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:29:57'),
(450, 1, 'CEO Luci', 3, 'Saloon de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:30:35'),
(451, 1, 'CEO Luci', 4, 'Saloon Van Horn', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:31:09');
INSERT INTO `audit_logs` (`id`, `staff_id`, `staff_name`, `affected_user_id`, `affected_user_name`, `action`, `category`, `details`, `before_data`, `after_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(452, 1, 'CEO Luci', 5, 'Saloon de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:31:31'),
(453, 1, 'CEO Luci', 6, 'Saloon de Armadillo', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:32:14'),
(454, 1, 'CEO Luci', 7, 'Saloon de Tumbleweed', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:32:44'),
(455, 1, 'CEO Luci', 8, 'Saloon de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:33:08'),
(456, 1, 'CEO Luci', 2, 'Saloon de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:34:34'),
(457, 1, 'CEO Luci', 5, 'Saloon de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:34:57'),
(458, 1, 'CEO Luci', 3, 'Saloon de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:35:40'),
(459, 1, 'CEO Luci', 4, 'Saloon Van Horn', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:36:01'),
(460, 1, 'CEO Luci', 6, 'Saloon de Armadillo', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:36:16'),
(461, 1, 'CEO Luci', 7, 'Saloon de Tumbleweed', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:36:36'),
(462, 1, 'CEO Luci', 8, 'Saloon de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:36:52'),
(463, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:37:55'),
(464, 1, 'CEO Luci', 5, 'Saloon de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:38:21'),
(465, 1, 'CEO Luci', 2, 'Saloon de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:38:47'),
(466, 1, 'CEO Luci', 1, 'Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:43:20'),
(467, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 6; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:44:26'),
(468, 1, 'CEO Luci', 10, 'Artesanato de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:45:14'),
(469, 1, 'CEO Luci', 12, 'Artesanato de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:45:44'),
(470, 1, 'CEO Luci', 11, 'Artesanato de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:46:08'),
(471, 1, 'CEO Luci', 9, 'Artesanato de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:46:34'),
(472, 1, 'CEO Luci', 13, 'Artesanato de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:47:00'),
(473, 1, 'CEO Luci', 14, 'Artesanato de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:47:21'),
(474, 1, 'CEO Luci', 9, 'Artesanato de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:51:34'),
(475, 1, 'CEO Luci', 1, 'Saloon Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:52:05'),
(476, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 1; atualizadas: 3.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:54:25'),
(477, 1, 'CEO Luci', 15, 'Padaria', 'Empresa pública excluída', 'Empresa', 'Empresa removida do Portal.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:55:01'),
(478, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 4; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:55:50'),
(479, 1, 'CEO Luci', 12, 'Artesanato de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:56:19'),
(480, 1, 'CEO Luci', 11, 'Artesanato de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:56:35'),
(481, 1, 'CEO Luci', 10, 'Artesanato de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:56:56'),
(482, 1, 'CEO Luci', 16, 'Doceria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:57:33'),
(483, 1, 'CEO Luci', 18, 'Doceria de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:57:54'),
(484, 1, 'CEO Luci', 17, 'Doceria de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:58:32'),
(485, 1, 'CEO Luci', 19, 'Doceria de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 11:58:49'),
(486, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 7; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:03:53'),
(487, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 6; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:09:02'),
(488, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 5; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:12:41'),
(489, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 6; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:16:05'),
(490, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 5; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:24:05'),
(491, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 5; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:27:36'),
(492, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 5; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:31:29'),
(493, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 3; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:34:49'),
(494, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 3; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:40:40'),
(495, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 7; atualizadas: 0.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:45:44'),
(496, 1, 'CEO Luci', 9, 'Artesanato de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:53:46'),
(497, 1, 'CEO Luci', 13, 'Artesanato de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:54:35'),
(498, 1, 'CEO Luci', 14, 'Artesanato de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:54:55'),
(499, 1, 'CEO Luci', 10, 'Artesanato de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:56:22'),
(500, 1, 'CEO Luci', 11, 'Artesanato de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:56:36'),
(501, 1, 'CEO Luci', 12, 'Artesanato de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:56:56'),
(502, 1, 'CEO Luci', 10, 'Artesanato de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:58:03'),
(503, 1, 'CEO Luci', 11, 'Artesanato de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:58:28'),
(504, 1, 'CEO Luci', 12, 'Artesanato de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:58:41'),
(505, 1, 'CEO Luci', 9, 'Artesanato de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:59:05'),
(506, 1, 'CEO Luci', 13, 'Artesanato de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:59:18'),
(507, 1, 'CEO Luci', 14, 'Artesanato de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 12:59:29'),
(508, 1, 'CEO Luci', 49, 'Armaria de Annesburg', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:03:43'),
(509, 1, 'CEO Luci', 51, 'Armaria de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:03:59'),
(510, 1, 'CEO Luci', 53, 'Armaria de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:04:16'),
(511, 1, 'CEO Luci', 52, 'Armaria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:04:43'),
(512, 1, 'CEO Luci', 50, 'Armaria de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:05:01'),
(513, 1, 'CEO Luci', 16, 'Doceria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:10:39'),
(514, 1, 'CEO Luci', 17, 'Doceria de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:10:50'),
(515, 1, 'CEO Luci', 18, 'Doceria de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:11:01'),
(516, 1, 'CEO Luci', 19, 'Doceria de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:11:14'),
(517, 1, 'CEO Luci', 21, 'Ferraria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:14:22'),
(518, 1, 'CEO Luci', 20, 'Ferraria de Annesburg', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:14:33'),
(519, 1, 'CEO Luci', 22, 'Ferraria de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:14:45'),
(520, 1, 'CEO Luci', 23, 'Ferraria de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:14:56'),
(521, 1, 'CEO Luci', 24, 'Ferraria de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:15:06'),
(522, 1, 'CEO Luci', 25, 'Ferraria de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:15:17'),
(523, 1, 'CEO Luci', 26, 'Ferraria de Armadilo', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:15:32'),
(524, 1, 'CEO Luci', 27, 'Açougue de Annesburg', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:18:05'),
(525, 1, 'CEO Luci', 28, 'Açougue de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:18:21'),
(526, 1, 'CEO Luci', 29, 'Açougue de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:18:33'),
(527, 1, 'CEO Luci', 32, 'Açougue de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:18:53'),
(528, 1, 'CEO Luci', 30, 'Açougue de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:21:38'),
(529, 1, 'CEO Luci', 31, 'Açougue de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:21:51'),
(530, 1, 'CEO Luci', 33, 'Mineradora de Annesburg', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:25:29'),
(531, 1, 'CEO Luci', 34, 'Mineradora de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:25:42'),
(532, 1, 'CEO Luci', 35, 'Mineradora de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:25:54'),
(533, 1, 'CEO Luci', 36, 'Mineradora de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:26:12'),
(534, 1, 'CEO Luci', 37, 'Mineradora de Armadilo', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:26:27'),
(535, 1, 'CEO Luci', 41, 'Mineradora de Emerald Ranch', 'Empresa pública excluída', 'Empresa', 'Empresa removida do Portal.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:27:07'),
(536, 1, 'CEO Luci', 42, 'Mineradora de Saint Denis', 'Empresa pública excluída', 'Empresa', 'Empresa removida do Portal.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:27:22'),
(537, 1, 'CEO Luci', 43, 'Mineradora de BlackWater', 'Empresa pública excluída', 'Empresa', 'Empresa removida do Portal.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:27:59'),
(538, 1, 'CEO Luci', 38, 'Madeireira de Annesburg', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:29:33'),
(539, 1, 'CEO Luci', 39, 'Madeireira de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:29:49'),
(540, 1, 'CEO Luci', 40, 'Madeireira de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:30:09'),
(541, 1, 'CEO Luci', 44, 'Veterinaria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:35:13'),
(542, 1, 'CEO Luci', 45, 'Veterinaria de Van Horn', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:35:27'),
(543, 1, 'CEO Luci', 46, 'Veterinaria de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:35:50'),
(544, 1, 'CEO Luci', 47, 'Veterinaria de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:36:29'),
(545, 1, 'CEO Luci', 48, 'Veterinaria de BlackWater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:36:47'),
(546, 1, 'CEO Luci', 54, 'Jornal de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:38:39'),
(547, 1, 'CEO Luci', 54, 'Jornal de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:39:54'),
(548, 1, 'CEO Luci', 55, 'Jornal de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:40:11'),
(549, 1, 'CEO Luci', 56, 'Jornal de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:40:26'),
(550, 1, 'CEO Luci', 57, 'Jornal de BlackWater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:40:42'),
(551, 1, 'CEO Luci', 58, 'Jornal de Annesburg', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:40:58'),
(552, 1, 'CEO Luci', 59, 'Ateliê Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:43:22'),
(553, 1, 'CEO Luci', 60, 'Ateliê Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:43:52'),
(554, 1, 'CEO Luci', 61, 'Ateliê Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:44:10'),
(555, 1, 'CEO Luci', 62, 'Tabacaria Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:49:49'),
(556, 1, 'CEO Luci', 63, 'Tabacaria BlackWater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:50:05'),
(557, 1, 'CEO Luci', 64, 'Tabacaria Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:50:26'),
(558, 1, 'CEO Luci', 67, 'Estábulo de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:51:44'),
(559, 1, 'CEO Luci', 66, 'Estábulo de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:52:19'),
(560, 1, 'CEO Luci', 71, 'Estábulo de Tumbleweed', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:53:17'),
(561, 1, 'CEO Luci', 68, 'Estábulo de Van Horn', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:53:33'),
(562, 1, 'CEO Luci', 65, 'Estábulo de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:56:23'),
(563, 1, 'CEO Luci', 67, 'Estábulo de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:56:36'),
(564, 1, 'CEO Luci', 69, 'Estábulo de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:56:55'),
(565, 1, 'CEO Luci', 70, 'Estábulo de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 13:57:12'),
(566, 1, 'CEO Luci', 295, 'Chocolate em Barra Rústico', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: chocolate_em_barra_rustico.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:02'),
(567, 1, 'CEO Luci', 296, 'Arroz Doce', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: arroz_doce.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:03'),
(568, 1, 'CEO Luci', 297, 'Bolo Brigadeiro', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bolo_brigadeiro.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:04'),
(569, 1, 'CEO Luci', 298, 'Bolo de Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bolo_de_milho.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:04'),
(570, 1, 'CEO Luci', 299, 'Doce de Leite', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: doce_de_leite.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:04'),
(571, 1, 'CEO Luci', 300, 'Torta de Maça', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: torta_de_maca.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:05'),
(572, 1, 'CEO Luci', 301, 'Cheesecake Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cheesecake_cereja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:05'),
(573, 1, 'CEO Luci', 302, 'Bolinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bolinho_de_carne.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:06'),
(574, 1, 'CEO Luci', 303, 'Coxinha', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: coxinha.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:06'),
(575, 1, 'CEO Luci', 304, 'Bala de Caramelo', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bala_de_caramelo.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:07'),
(576, 1, 'CEO Luci', 305, 'Acholatado', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: acholatado.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:05:07'),
(577, 1, 'CEO Luci', 295, 'Chocolate em Barra Rústico', 'Produto de precificação alterado', 'Precificação', 'Produto alterado.', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:17'),
(578, 1, 'CEO Luci', 295, 'Chocolate em Barra Rústico', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: chocolate_em_barra_rustico.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:28'),
(579, 1, 'CEO Luci', 296, 'Arroz Doce', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: arroz_doce.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:28'),
(580, 1, 'CEO Luci', 297, 'Bolo Brigadeiro', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bolo_brigadeiro.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:28'),
(581, 1, 'CEO Luci', 298, 'Bolo de Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bolo_de_milho.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:29'),
(582, 1, 'CEO Luci', 299, 'Doce de Leite', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: doce_de_leite.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:29'),
(583, 1, 'CEO Luci', 300, 'Torta de Maça', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: torta_de_maca.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:29'),
(584, 1, 'CEO Luci', 301, 'Cheesecake Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cheesecake_cereja.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:29'),
(585, 1, 'CEO Luci', 302, 'Bolinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bolinho_de_carne.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:30'),
(586, 1, 'CEO Luci', 303, 'Coxinha', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: coxinha.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:30'),
(587, 1, 'CEO Luci', 304, 'Bala de Caramelo', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bala_de_caramelo.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:30'),
(588, 1, 'CEO Luci', 305, 'Acholatado', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: acholatado.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:06:31'),
(589, 1, 'CEO Luci', 267, 'Whiskey', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: whiskey_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:00'),
(590, 1, 'CEO Luci', 268, 'Gin Cereja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: gin_cereja_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:00'),
(591, 1, 'CEO Luci', 269, 'Bourbon', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bourbon_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:01'),
(592, 1, 'CEO Luci', 270, 'Brandy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: brandy_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:01'),
(593, 1, 'CEO Luci', 271, 'Vinho Tinto', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: vinho_tinto_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:02'),
(594, 1, 'CEO Luci', 272, 'Strawberry Colada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: strawberry_colada_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:02'),
(595, 1, 'CEO Luci', 273, 'Hidromel', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: hidromel_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:03'),
(596, 1, 'CEO Luci', 274, 'Cerveja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cerveja_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:04'),
(597, 1, 'CEO Luci', 275, 'Café', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: cafe_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:04'),
(598, 1, 'CEO Luci', 276, 'Suco de Laranja', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_laranja_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:05'),
(599, 1, 'CEO Luci', 277, 'Suco de Uva', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_uva_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:05'),
(600, 1, 'CEO Luci', 278, 'Suco de Limão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_limao_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:06'),
(601, 1, 'CEO Luci', 279, 'Suco de Framboeza', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: suco_de_framboeza_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:06'),
(602, 1, 'CEO Luci', 280, 'Prato Lá Cowboy', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_la_cowboy.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:07'),
(603, 1, 'CEO Luci', 281, 'Picadinho de Carne', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: picadinho_de_carne.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:08'),
(604, 1, 'CEO Luci', 282, 'Bife Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: bife_milanesa_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:08'),
(605, 1, 'CEO Luci', 283, 'Carne Louca', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: carne_louca_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:09');
INSERT INTO `audit_logs` (`id`, `staff_id`, `staff_name`, `affected_user_id`, `affected_user_name`, `action`, `category`, `details`, `before_data`, `after_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(606, 1, 'CEO Luci', 284, 'Frango Assado', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: frango_assado_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:09'),
(607, 1, 'CEO Luci', 285, 'Fricassé de Frango', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: fricasse_de_frango.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:09'),
(608, 1, 'CEO Luci', 286, 'Provolone Milanesa', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: provolone_milanesa_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:10'),
(609, 1, 'CEO Luci', 287, 'Especial da Marilene', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: especial_da_marilene_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:10'),
(610, 1, 'CEO Luci', 288, 'Linguiça Acebolada', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: linguica_acebolada_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:12'),
(611, 1, 'CEO Luci', 289, 'Prato Omelete', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: prato_omelete_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:12'),
(612, 1, 'CEO Luci', 290, 'Mingau', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: mingau_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:13'),
(613, 1, 'CEO Luci', 291, 'Caldo Milho', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: caldo_milho_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:13'),
(614, 1, 'CEO Luci', 292, 'Sopa Batata', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_batata_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:14'),
(615, 1, 'CEO Luci', 293, 'Sopa Feijão', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_feijao_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:14'),
(616, 1, 'CEO Luci', 294, 'Sopa Verduras', 'Imagem automática de precificação', 'Precificação', 'Imagem encontrada: sopa_verduras_2.webp', NULL, NULL, '186.230.114.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-04 14:22:15'),
(617, 1, 'CEO Luci', 0, 'Importação TXT', 'Importação em massa de empresas', 'Empresa', 'Criadas: 8; atualizadas: 0.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:50:25'),
(618, 1, 'CEO Luci', 76, 'Ferrovia de Annesburg', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:51:21'),
(619, 1, 'CEO Luci', 79, 'Ferrovia de Armadillo', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:51:40'),
(620, 1, 'CEO Luci', 72, 'Ferrovia de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:54:16'),
(621, 1, 'CEO Luci', 73, 'Ferrovia de Emerald', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:55:02'),
(622, 1, 'CEO Luci', 74, 'Ferrovia de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:55:31'),
(623, 1, 'CEO Luci', 75, 'Ferrovia de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:55:49'),
(624, 1, 'CEO Luci', 77, 'Ferrovia de Van Horn', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:56:10'),
(625, 1, 'CEO Luci', 78, 'Ferrovia de Riggs', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:56:29'),
(626, 1, 'CEO Luci', 1, 'Saloon Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:57:35'),
(627, 1, 'CEO Luci', 2, 'Saloon de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:57:48'),
(628, 1, 'CEO Luci', 5, 'Saloon de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:58:03'),
(629, 1, 'CEO Luci', 10, 'Artesanato de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:58:27'),
(630, 1, 'CEO Luci', 11, 'Artesanato de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:58:41'),
(631, 1, 'CEO Luci', 12, 'Artesanato de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:58:55'),
(632, 1, 'CEO Luci', 16, 'Doceria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:59:09'),
(633, 1, 'CEO Luci', 17, 'Doceria de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:59:22'),
(634, 1, 'CEO Luci', 18, 'Doceria de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:59:38'),
(635, 1, 'CEO Luci', 19, 'Doceria de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 12:59:54'),
(636, 1, 'CEO Luci', 30, 'Açougue de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:00:41'),
(637, 1, 'CEO Luci', 31, 'Açougue de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:00:54'),
(638, 1, 'CEO Luci', 44, 'Veterinaria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:01:12'),
(639, 1, 'CEO Luci', 45, 'Veterinaria de Van Horn', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:01:24'),
(640, 1, 'CEO Luci', 46, 'Veterinaria de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:01:39'),
(641, 1, 'CEO Luci', 47, 'Veterinaria de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:01:51'),
(642, 1, 'CEO Luci', 48, 'Veterinaria de BlackWater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:02:06'),
(643, 1, 'CEO Luci', 50, 'Armaria de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:02:24'),
(644, 1, 'CEO Luci', 52, 'Armaria de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:02:39'),
(645, 1, 'CEO Luci', 59, 'Ateliê Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:03:09'),
(646, 1, 'CEO Luci', 61, 'Ateliê Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:03:22'),
(647, 1, 'CEO Luci', 65, 'Estábulo de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:03:49'),
(648, 1, 'CEO Luci', 67, 'Estábulo de Emerald Ranch', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:04:07'),
(649, 1, 'CEO Luci', 69, 'Estábulo de Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:04:29'),
(650, 1, 'CEO Luci', 70, 'Estábulo de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:04:36'),
(651, 1, 'CEO Luci', 3, 'Saloon de Rhodes', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:08:27'),
(652, 1, 'CEO Luci', 4, 'Saloon Van Horn', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:08:55'),
(653, 1, 'CEO Luci', 6, 'Saloon de Armadillo', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:08:59'),
(654, 1, 'CEO Luci', 7, 'Saloon de Tumbleweed', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:09:13'),
(655, 1, 'CEO Luci', 8, 'Saloon de Strawberry', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:09:19'),
(656, 1, 'CEO Luci', 2, 'Saloon de Valentine', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:10:23'),
(657, 1, 'CEO Luci', 1, 'Saloon Saint Denis', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:10:29'),
(658, 1, 'CEO Luci', 5, 'Saloon de Blackwater', 'Empresa pública alterada', 'Empresa', 'Empresa do Portal alterada.', NULL, NULL, '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 13:10:35'),
(659, 1, 'CEO Luci', 11, 'Carou', 'Cadastro de streamer', 'Usuário', 'Novo streamer cadastrado.', NULL, '{\"name\":\"Carou\",\"category\":\"novato\",\"platform\":\"Twitch\",\"joined_at\":\"2026-09-05\"}', '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 20:07:24'),
(660, 1, 'CEO Luci', 11, 'Carou', 'VOD aprovada', 'VOD', 'VOD aprovada pela Staff.', '{\"status\":\"pending\",\"duration_minutes\":247}', '{\"status\":\"approved\",\"duration_minutes\":247,\"points\":0}', '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 20:11:24'),
(661, 1, 'CEO Luci', 11, 'Carou', 'Alteração de streamer', 'Usuário', 'Perfil do streamer alterado.', '{\"name\":\"Carou\",\"discord\":\"carou\",\"platform\":\"Twitch\",\"channel_url\":\"https://www.twitch.tv/car0ul\",\"category\":\"novato\",\"joined_at\":\"2026-09-05\",\"notes\":\"\"}', '{\"name\":\"Carou\",\"discord\":\"carou\",\"platform\":\"Twitch\",\"channel_url\":\"https://www.twitch.tv/car0ul\",\"category\":\"oficial\",\"joined_at\":\"2026-09-05\",\"notes\":\"\"}', '177.140.31.28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-05 20:17:27'),
(662, 1, 'CEO Luci', 11, 'Carou', 'Alteração de streamer', 'Usuário', 'Perfil do streamer alterado.', '{\"name\":\"Carou\",\"discord\":\"carou\",\"platform\":\"Twitch\",\"channel_url\":\"https://www.twitch.tv/car0ul\",\"category\":\"oficial\",\"joined_at\":\"2026-09-05\",\"notes\":\"\"}', '{\"name\":\"Carou\",\"discord\":\"carou\",\"platform\":\"Twitch\",\"channel_url\":\"https://www.twitch.tv/car0ul\",\"category\":\"oficial\",\"joined_at\":\"2026-09-05\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-06 14:41:09'),
(663, 1, 'CEO Luci', 11, 'Carou', 'Alteração de streamer', 'Usuário', 'Perfil do streamer alterado.', '{\"name\":\"Carou\",\"discord\":\"carou\",\"platform\":\"Twitch\",\"channel_url\":\"https://www.twitch.tv/car0ul\",\"category\":\"oficial\",\"joined_at\":\"2026-09-05\",\"notes\":\"\"}', '{\"name\":\"Carou\",\"discord\":\"carou\",\"platform\":\"Twitch\",\"channel_url\":\"https://www.twitch.tv/car0ul\",\"category\":\"oficial\",\"joined_at\":\"2026-09-05\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0 (Edition std-2)', '2026-09-06 14:41:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `content_submissions`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `content_submissions`;
CREATE TABLE IF NOT EXISTS `content_submissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `content_type` varchar(40) NOT NULL,
  `url` varchar(700) NOT NULL,
  `submission_date` date NOT NULL,
  `collab` tinyint(1) NOT NULL DEFAULT 0,
  `collab_streamer_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `vod_id` bigint(20) UNSIGNED DEFAULT NULL,
  `duration_minutes` int(10) UNSIGNED DEFAULT NULL,
  `points_awarded` int(11) NOT NULL DEFAULT 0,
  `staff_notes` text DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_submission_streamer_status` (`streamer_id`,`status`),
  KEY `idx_submission_date` (`streamer_id`,`submission_date`),
  KEY `collab_streamer_id` (`collab_streamer_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `vod_id` (`vod_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `content_submissions`:
--   `streamer_id`
--       `streamers` -> `id`
--   `collab_streamer_id`
--       `streamers` -> `id`
--   `reviewed_by`
--       `staff_users` -> `id`
--   `vod_id`
--       `vods` -> `id`
--

--
-- Despejando dados para a tabela `content_submissions`
--

INSERT INTO `content_submissions` (`id`, `streamer_id`, `content_type`, `url`, `submission_date`, `collab`, `collab_streamer_id`, `status`, `vod_id`, `duration_minutes`, `points_awarded`, `staff_notes`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(34, 11, 'vod', 'https://www.twitch.tv/videos/2863757349', '2026-09-05', 0, NULL, 'approved', 15, 247, 0, '', 1, '2026-09-05 13:11:24', '2026-09-05 20:10:56', '2026-09-05 20:11:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `content_submission_images`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `content_submission_images`;
CREATE TABLE IF NOT EXISTS `content_submission_images` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_submission_images_submission` (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `content_submission_images`:
--   `submission_id`
--       `content_submissions` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `live_monitor_events`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `live_monitor_events`;
CREATE TABLE IF NOT EXISTS `live_monitor_events` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `level` enum('info','warning','error') NOT NULL DEFAULT 'info',
  `message` varchar(1000) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_monitor_events` (`channel_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `live_monitor_events`:
--   `channel_id`
--       `streamer_channels` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `live_monitor_logs`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `live_monitor_logs`;
CREATE TABLE IF NOT EXISTS `live_monitor_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `streamer_id` int(11) DEFAULT NULL,
  `platform` varchar(20) DEFAULT NULL,
  `searched_at` datetime DEFAULT NULL,
  `live_found` tinyint(1) DEFAULT NULL,
  `live_title` varchar(255) DEFAULT NULL,
  `result` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `live_monitor_logs`:
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `live_monitor_sessions`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `live_monitor_sessions`;
CREATE TABLE IF NOT EXISTS `live_monitor_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel_id` bigint(20) UNSIGNED NOT NULL,
  `platform_stream_id` varchar(160) DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `last_seen_at` datetime NOT NULL,
  `title` varchar(500) DEFAULT NULL,
  `category` varchar(180) DEFAULT NULL,
  `status` enum('live','completed','ignored','error') NOT NULL DEFAULT 'live',
  `vod_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_monitor_stream` (`channel_id`,`platform_stream_id`),
  KEY `idx_monitor_open` (`channel_id`,`status`),
  KEY `vod_id` (`vod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `live_monitor_sessions`:
--   `channel_id`
--       `streamer_channels` -> `id`
--   `vod_id`
--       `vods` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `live_monitor_settings`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `live_monitor_settings`;
CREATE TABLE IF NOT EXISTS `live_monitor_settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `live_monitor_settings`:
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `live_monitor_titles`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `live_monitor_titles`;
CREATE TABLE IF NOT EXISTS `live_monitor_titles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `live_monitor_titles`:
--

--
-- Despejando dados para a tabela `live_monitor_titles`
--

INSERT INTO `live_monitor_titles` (`id`, `title`, `active`, `created_at`) VALUES
(1, '[ Carmesim RP ]', 1, '2026-09-06 05:51:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `penalties`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `penalties`;
CREATE TABLE IF NOT EXISTS `penalties` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `penalty_date` date NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_penalties_streamer` (`streamer_id`),
  KEY `fk_penalties_staff` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `penalties`:
--   `created_by`
--       `staff_users` -> `id`
--   `streamer_id`
--       `streamers` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `player_commands`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `player_commands`;
CREATE TABLE IF NOT EXISTS `player_commands` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `command` varchar(150) NOT NULL,
  `description` varchar(500) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `command` (`command`),
  KEY `idx_player_commands_active` (`active`),
  KEY `idx_player_commands_position` (`position`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `player_commands`:
--

--
-- Despejando dados para a tabela `player_commands`
--

INSERT INTO `player_commands` (`id`, `name`, `command`, `description`, `position`, `active`, `created_by`, `created_at`, `updated_at`) VALUES
(21, 'Algemar', '/cuff', 'Algema outro jogador, permitindo realizar a contenção durante uma abordagem ou procedimento da Cavalaria.', 1, 1, NULL, '2026-09-03 20:16:00', '2026-09-06 06:10:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `point_transactions`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `point_transactions`;
CREATE TABLE IF NOT EXISTS `point_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `type` enum('earn','penalty','adjustment','redemption_refund') NOT NULL,
  `description` varchar(255) NOT NULL,
  `points` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_points_staff` (`created_by`),
  KEY `idx_points_streamer_date` (`streamer_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `point_transactions`:
--   `created_by`
--       `staff_users` -> `id`
--   `streamer_id`
--       `streamers` -> `id`
--

--
-- Despejando dados para a tabela `point_transactions`
--

INSERT INTO `point_transactions` (`id`, `streamer_id`, `type`, `description`, `points`, `reference_type`, `reference_id`, `created_by`, `created_at`) VALUES
(25, 11, 'earn', 'VOD / Live - 05/09/2026', 4, 'vod', 15, 1, '2026-09-05 20:11:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pricing_categories`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `pricing_categories`;
CREATE TABLE IF NOT EXISTS `pricing_categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_pricing_categories_position` (`active`,`position`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `pricing_categories`:
--

--
-- Despejando dados para a tabela `pricing_categories`
--

INSERT INTO `pricing_categories` (`id`, `name`, `position`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Saloon', 1, 1, '2026-09-04 03:23:25', '2026-09-04 08:44:20'),
(2, 'Fazenda', 2, 1, '2026-09-04 08:44:01', '2026-09-04 08:44:14'),
(3, 'Doceria / Padaria', 3, 1, '2026-09-04 10:04:37', '2026-09-04 10:04:37'),
(4, 'Ferraria', 5, 1, '2026-09-04 10:31:19', '2026-09-04 10:32:04'),
(5, 'Açougueiro', 4, 1, '2026-09-04 10:32:17', '2026-09-04 10:32:17'),
(6, 'Mineradora', 10, 1, '2026-09-04 10:34:43', '2026-09-04 10:44:08'),
(7, 'Madeireira', 6, 1, '2026-09-04 10:37:15', '2026-09-04 10:37:15'),
(8, 'Hospital', 8, 1, '2026-09-04 10:38:59', '2026-09-04 10:40:19'),
(9, 'Artesanato', 7, 1, '2026-09-04 10:39:56', '2026-09-04 10:39:56'),
(10, 'Veterinaria', 9, 1, '2026-09-04 10:42:13', '2026-09-04 10:44:00'),
(11, 'Armaria', 11, 1, '2026-09-04 10:46:16', '2026-09-04 10:46:16'),
(12, 'Jornal', 12, 1, '2026-09-04 10:50:15', '2026-09-04 10:50:15'),
(13, 'Atelie', 13, 1, '2026-09-04 10:51:01', '2026-09-04 10:51:01'),
(14, 'Nativos', 14, 1, '2026-09-04 10:53:00', '2026-09-04 10:53:00'),
(15, 'Tabacaria', 15, 1, '2026-09-04 10:54:41', '2026-09-04 10:54:41'),
(16, 'Estabulo', 16, 1, '2026-09-04 10:56:39', '2026-09-04 10:56:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pricing_products`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `pricing_products`;
CREATE TABLE IF NOT EXISTS `pricing_products` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `item_name` varchar(180) NOT NULL,
  `price_min` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_max` decimal(12,2) NOT NULL DEFAULT 0.00,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pricing_products_category` (`category_id`,`active`,`position`,`item_name`)
) ENGINE=InnoDB AUTO_INCREMENT=470 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `pricing_products`:
--   `category_id`
--       `pricing_categories` -> `id`
--

--
-- Despejando dados para a tabela `pricing_products`
--

INSERT INTO `pricing_products` (`id`, `category_id`, `photo`, `item_name`, `price_min`, `price_max`, `position`, `active`, `created_at`, `updated_at`) VALUES
(249, 2, 'Alcool_Artesanal.webp', 'Álcool Artesanal', 1.17, 1.33, 0, 1, '2026-09-04 09:57:26', '2026-09-04 09:57:26'),
(250, 2, 'Farinha_de_Trigo.webp', 'Farinha de Trigo', 0.98, 1.12, 1, 1, '2026-09-04 09:57:27', '2026-09-04 09:57:27'),
(251, 2, 'Amido_de_Milho.webp', 'Amido de Milho', 0.98, 1.12, 2, 1, '2026-09-04 09:57:27', '2026-09-04 09:57:27'),
(252, 2, 'Torroes_de_Acucar.webp', 'Torrões de Açucar', 0.93, 1.07, 3, 1, '2026-09-04 09:57:27', '2026-09-04 09:57:27'),
(253, 2, 'Po_de_Cafe.webp', 'Pó de Café', 0.78, 0.89, 4, 1, '2026-09-04 09:57:27', '2026-09-04 09:57:27'),
(254, 2, 'Vinagre_de_Uva.webp', 'Vinagre de Uva', 0.80, 0.92, 5, 1, '2026-09-04 09:57:28', '2026-09-04 09:57:28'),
(255, 2, 'Caixa_de_Ovos.webp', 'Caixa de Ovos', 7.18, 8.20, 6, 1, '2026-09-04 09:57:28', '2026-09-04 09:57:28'),
(256, 2, 'Garrafa_de_Leite.webp', 'Garrafa de Leite', 0.79, 0.90, 7, 1, '2026-09-04 09:57:28', '2026-09-04 09:57:28'),
(257, 2, 'Queijo.webp', 'Queijo', 1.16, 1.33, 8, 1, '2026-09-04 09:57:28', '2026-09-04 09:57:28'),
(258, 2, 'Levedura.webp', 'Levedura', 0.63, 0.72, 9, 1, '2026-09-04 09:57:29', '2026-09-04 09:57:29'),
(259, 2, 'Geleia_de_Cereja.webp', 'Geleia de Cereja', 1.32, 1.51, 10, 1, '2026-09-04 09:57:29', '2026-09-04 09:57:29'),
(260, 2, 'Melado_de_Cana.webp', 'Melado de Cana', 1.56, 1.78, 11, 1, '2026-09-04 09:57:29', '2026-09-04 09:57:29'),
(261, 2, 'Fecula_de_Batata.webp', 'Fécula de Batata', 1.09, 1.24, 12, 1, '2026-09-04 09:57:29', '2026-09-04 09:57:29'),
(262, 2, 'Oleo_Vegetal.webp', 'Oleo Vegetal', 0.85, 0.97, 13, 1, '2026-09-04 09:57:30', '2026-09-04 09:57:30'),
(263, 2, 'Erva_Medicinal.webp', 'Erva Medicinal', 1.18, 1.35, 14, 1, '2026-09-04 09:57:30', '2026-09-04 09:57:30'),
(264, 2, 'Tabaco_Curado.webp', 'Tabaco Curado', 0.58, 0.67, 15, 1, '2026-09-04 09:57:30', '2026-09-04 09:57:30'),
(265, 2, 'Caixa_de_Verduras.webp', 'Caixa de Verduras', 4.55, 5.20, 16, 1, '2026-09-04 09:57:30', '2026-09-04 09:57:30'),
(266, 2, 'Caixa_de_Animais_2.webp', 'Caixa de Animais', 4.55, 5.20, 17, 1, '2026-09-04 09:57:31', '2026-09-04 09:57:31'),
(267, 1, 'whiskey_2.webp', 'Whiskey', 4.87, 5.57, 0, 1, '2026-09-04 09:58:39', '2026-09-04 14:22:00'),
(268, 1, 'gin_cereja_2.webp', 'Gin Cereja', 4.52, 5.16, 1, 1, '2026-09-04 09:58:39', '2026-09-04 14:22:00'),
(269, 1, 'bourbon_2.webp', 'Bourbon', 3.45, 3.94, 2, 1, '2026-09-04 09:58:40', '2026-09-04 14:22:01'),
(270, 1, 'brandy_2.webp', 'Brandy', 3.30, 3.77, 3, 1, '2026-09-04 09:58:40', '2026-09-04 14:22:01'),
(271, 1, 'vinho_tinto_2.webp', 'Vinho Tinto', 3.01, 3.44, 4, 1, '2026-09-04 09:58:40', '2026-09-04 14:22:02'),
(272, 1, 'strawberry_colada_2.webp', 'Strawberry Colada', 3.58, 4.09, 5, 1, '2026-09-04 09:58:40', '2026-09-04 14:22:02'),
(273, 1, 'hidromel_2.webp', 'Hidromel', 2.27, 2.59, 6, 1, '2026-09-04 09:58:40', '2026-09-04 14:22:03'),
(274, 1, 'cerveja_2.webp', 'Cerveja', 2.14, 2.44, 7, 1, '2026-09-04 09:58:41', '2026-09-04 14:22:04'),
(275, 1, 'cafe_2.webp', 'Café', 2.17, 2.48, 8, 1, '2026-09-04 09:58:41', '2026-09-04 14:22:04'),
(276, 1, 'suco_de_laranja_2.webp', 'Suco de Laranja', 2.24, 2.56, 9, 1, '2026-09-04 09:58:41', '2026-09-04 14:22:05'),
(277, 1, 'suco_de_uva_2.webp', 'Suco de Uva', 2.24, 2.56, 10, 1, '2026-09-04 09:58:42', '2026-09-04 14:22:05'),
(278, 1, 'suco_de_limao_2.webp', 'Suco de Limão', 2.24, 2.56, 11, 1, '2026-09-04 09:58:42', '2026-09-04 14:22:06'),
(279, 1, 'suco_de_framboeza_2.webp', 'Suco de Framboeza', 2.24, 2.56, 12, 1, '2026-09-04 09:58:42', '2026-09-04 14:22:06'),
(280, 1, 'prato_la_cowboy.webp', 'Prato Lá Cowboy', 3.97, 4.54, 13, 1, '2026-09-04 09:58:43', '2026-09-04 14:22:07'),
(281, 1, 'picadinho_de_carne.webp', 'Picadinho de Carne', 3.96, 4.52, 14, 1, '2026-09-04 09:58:43', '2026-09-04 14:22:08'),
(282, 1, 'bife_milanesa_2.webp', 'Bife Milanesa', 3.97, 4.54, 15, 1, '2026-09-04 09:58:43', '2026-09-04 14:22:08'),
(283, 1, 'carne_louca_2.webp', 'Carne Louca', 3.94, 4.50, 16, 1, '2026-09-04 09:58:44', '2026-09-04 14:22:09'),
(284, 1, 'frango_assado_2.webp', 'Frango Assado', 3.96, 4.52, 17, 1, '2026-09-04 09:58:44', '2026-09-04 14:22:09'),
(285, 1, 'fricasse_de_frango.webp', 'Fricassé de Frango', 3.94, 4.50, 18, 1, '2026-09-04 09:58:44', '2026-09-04 14:22:09'),
(286, 1, 'provolone_milanesa_2.webp', 'Provolone Milanesa', 3.90, 4.46, 19, 1, '2026-09-04 09:58:45', '2026-09-04 14:22:10'),
(287, 1, 'especial_da_marilene_2.webp', 'Especial da Marilene', 3.92, 4.48, 20, 1, '2026-09-04 09:58:45', '2026-09-04 14:22:10'),
(288, 1, 'linguica_acebolada_2.webp', 'Linguiça Acebolada', 4.00, 4.57, 21, 1, '2026-09-04 09:58:45', '2026-09-04 14:22:12'),
(289, 1, 'prato_omelete_2.webp', 'Prato Omelete', 3.97, 4.54, 22, 1, '2026-09-04 09:58:46', '2026-09-04 14:22:12'),
(290, 1, 'mingau_2.webp', 'Mingau', 1.80, 2.05, 23, 1, '2026-09-04 09:58:46', '2026-09-04 14:22:13'),
(291, 1, 'caldo_milho_2.webp', 'Caldo Milho', 1.47, 1.68, 24, 1, '2026-09-04 09:58:46', '2026-09-04 14:22:13'),
(292, 1, 'sopa_batata_2.webp', 'Sopa Batata', 1.47, 1.68, 25, 1, '2026-09-04 09:58:46', '2026-09-04 14:22:14'),
(293, 1, 'sopa_feijao_2.webp', 'Sopa Feijão', 1.47, 1.68, 26, 1, '2026-09-04 09:58:47', '2026-09-04 14:22:14'),
(294, 1, 'sopa_verduras_2.webp', 'Sopa Verduras', 1.47, 1.68, 27, 1, '2026-09-04 09:58:47', '2026-09-04 14:22:15'),
(295, 3, 'chocolate_em_barra_rustico.webp', 'Chocolate em Barra Rústico', 1.10, 1.26, 0, 1, '2026-09-04 10:04:51', '2026-09-04 14:06:28'),
(296, 3, 'arroz_doce.webp', 'Arroz Doce', 1.65, 1.89, 1, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:03'),
(297, 3, 'bolo_brigadeiro.webp', 'Bolo Brigadeiro', 1.96, 2.24, 2, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:04'),
(298, 3, 'bolo_de_milho.webp', 'Bolo de Milho', 1.90, 2.18, 3, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:04'),
(299, 3, 'doce_de_leite.webp', 'Doce de Leite', 1.90, 2.17, 4, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:04'),
(300, 3, 'torta_de_maca.webp', 'Torta de Maça', 1.90, 2.18, 5, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:05'),
(301, 3, 'cheesecake_cereja.webp', 'Cheesecake Cereja', 2.20, 2.52, 6, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:05'),
(302, 3, 'bolinho_de_carne.webp', 'Bolinho de Carne', 2.26, 2.59, 7, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:06'),
(303, 3, 'coxinha.webp', 'Coxinha', 2.66, 3.04, 8, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:06'),
(304, 3, 'bala_de_caramelo.webp', 'Bala de Caramelo', 2.18, 2.49, 9, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:07'),
(305, 3, 'acholatado.webp', 'Acholatado', 2.10, 2.40, 10, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:07'),
(306, 3, 'Cafe_Cremoso_2.webp', 'Café Cremoso', 2.28, 2.61, 11, 1, '2026-09-04 10:04:51', '2026-09-04 10:27:20'),
(307, 3, 'default.webp', 'Café Extraforte', 3.57, 4.09, 12, 1, '2026-09-04 10:04:51', '2026-09-04 14:05:08'),
(308, 4, 'defull.webp', 'Faca', 2.79, 3.18, 0, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:29'),
(309, 4, 'defull.webp', 'Machadinha', 4.93, 5.64, 1, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:29'),
(310, 4, 'defull.webp', 'Cápsula de Munição', 0.61, 0.70, 2, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:30'),
(311, 4, 'defull.webp', 'Molas', 2.44, 2.79, 3, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:30'),
(312, 4, 'defull.webp', 'Gatilhos', 3.45, 3.95, 4, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:30'),
(313, 4, 'defull.webp', 'Cano Simples', 4.17, 4.77, 5, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:30'),
(314, 4, 'defull.webp', 'Camera de Ignição', 4.04, 4.62, 6, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:31'),
(315, 4, 'defull.webp', 'Rebites', 1.48, 1.69, 7, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:31'),
(316, 4, 'defull.webp', 'Dobradiças', 1.49, 1.70, 8, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:31'),
(317, 4, 'defull.webp', 'Martelo', 3.00, 3.43, 9, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:31'),
(318, 4, 'defull.webp', 'Picareta', 4.10, 4.69, 10, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:32'),
(319, 4, 'defull.webp', 'Pa', 3.25, 3.71, 11, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:32'),
(320, 4, 'defull.webp', 'Machado', 4.12, 4.71, 12, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:32'),
(321, 4, 'defull.webp', 'Ancinho', 2.16, 2.47, 13, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:33'),
(322, 4, 'defull.webp', 'Moedor', 0.55, 0.63, 14, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:33'),
(323, 4, 'defull.webp', 'Prego', 1.03, 1.18, 15, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:33'),
(324, 4, 'defull.webp', 'Ferradura', 4.38, 5.01, 16, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:33'),
(325, 4, 'defull.webp', 'Ferradura Dourada', 7.05, 8.06, 17, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:34'),
(326, 4, 'defull.webp', 'Serrote', 1.77, 2.03, 18, 1, '2026-09-04 10:31:29', '2026-09-04 11:04:34'),
(327, 6, 'defull.webp', 'Minerio Salino', 0.26, 0.30, 0, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:53'),
(328, 6, 'defull.webp', 'Cascalho', 0.14, 0.16, 1, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:53'),
(329, 6, 'defull.webp', 'Carvão', 0.27, 0.31, 2, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:53'),
(330, 6, 'defull.webp', 'Pólvora Refinada', 0.50, 0.57, 3, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:54'),
(331, 6, 'defull.webp', 'Garrafa de Vidro', 0.26, 0.30, 4, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:54'),
(332, 6, 'defull.webp', 'Tubo Metalico', 1.65, 1.89, 5, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:54'),
(333, 6, 'defull.webp', 'Lingote Cobre', 1.02, 1.17, 6, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:55'),
(334, 6, 'defull.webp', 'Lingote Ferro', 1.13, 1.29, 7, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:55'),
(335, 6, 'defull.webp', 'Lingote Prata', 1.27, 1.45, 8, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:55'),
(336, 6, 'defull.webp', 'Lingote Ouro', 1.34, 1.53, 9, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:55'),
(337, 6, 'defull.webp', 'Lingote Aço', 1.46, 1.67, 10, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:56'),
(338, 6, 'defull.webp', 'Seringa', 0.26, 0.30, 11, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:56'),
(339, 6, 'defull.webp', 'Chumbo', 1.24, 1.41, 12, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:56'),
(340, 6, 'defull.webp', 'Minerio de Silica', 0.39, 0.44, 13, 1, '2026-09-04 10:34:54', '2026-09-04 11:05:57'),
(341, 7, 'defull.webp', 'Tabua Processada', 0.51, 0.59, 0, 1, '2026-09-04 10:37:25', '2026-09-04 11:04:58'),
(342, 7, 'defull.webp', 'Tabua Cilindrica', 2.30, 2.63, 1, 1, '2026-09-04 10:37:25', '2026-09-04 11:04:58'),
(343, 7, 'defull.webp', 'Caixa Grande', 4.49, 5.13, 2, 1, '2026-09-04 10:37:25', '2026-09-04 11:04:59'),
(344, 7, 'defull.webp', 'Caixa Simples', 0.47, 0.54, 3, 1, '2026-09-04 10:37:25', '2026-09-04 11:04:59'),
(345, 7, 'defull.webp', 'Lenha', 0.34, 0.39, 4, 1, '2026-09-04 10:37:25', '2026-09-04 11:04:59'),
(346, 7, 'defull.webp', 'Palha', 0.46, 0.53, 5, 1, '2026-09-04 10:37:25', '2026-09-04 11:04:59'),
(347, 7, 'defull.webp', 'Seiva Processada', 1.16, 1.32, 6, 1, '2026-09-04 10:37:25', '2026-09-04 11:05:00'),
(348, 7, 'defull.webp', 'Papel Processado', 0.65, 0.74, 7, 1, '2026-09-04 10:37:25', '2026-09-04 11:05:00'),
(349, 7, 'defull.webp', 'Papel Refinado', 0.71, 0.82, 8, 1, '2026-09-04 10:37:25', '2026-09-04 11:05:00'),
(350, 7, 'defull.webp', 'Laço', 4.62, 5.28, 9, 1, '2026-09-04 10:37:25', '2026-09-04 11:05:00'),
(351, 7, 'defull.webp', 'Laço Reforçado', 5.85, 6.68, 10, 1, '2026-09-04 10:37:25', '2026-09-04 11:05:01'),
(352, 7, 'defull.webp', 'Fibras', 0.26, 0.30, 11, 1, '2026-09-04 10:37:25', '2026-09-04 11:05:01'),
(353, 8, 'defull.webp', 'Remédio', 3.17, 3.62, 0, 1, '2026-09-04 10:39:11', '2026-09-04 11:05:25'),
(354, 8, 'defull.webp', 'Bandagem', 1.72, 1.96, 1, 1, '2026-09-04 10:39:11', '2026-09-04 11:05:25'),
(355, 8, 'defull.webp', 'Seringa Médica', 15.49, 17.70, 2, 1, '2026-09-04 10:39:11', '2026-09-04 11:05:26'),
(356, 8, 'defull.webp', 'Antidoto de Veneno', 2.05, 2.34, 3, 1, '2026-09-04 10:39:11', '2026-09-04 11:05:26'),
(357, 9, 'defull.webp', 'Mochila', 7.53, 8.60, 0, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:11'),
(358, 9, 'defull.webp', 'Balde de Madeira', 3.42, 3.90, 1, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:11'),
(359, 9, 'defull.webp', 'Cantil', 3.58, 4.09, 2, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:12'),
(360, 9, 'defull.webp', 'Linha de Algodão', 0.88, 1.00, 3, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:12'),
(361, 9, 'defull.webp', 'Mapa de Bolso', 6.37, 7.28, 4, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:12'),
(362, 9, 'defull.webp', 'Pigmento Artesanal', 2.21, 2.52, 5, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:13'),
(363, 9, 'defull.webp', 'Vara de Pesca', 12.02, 13.74, 6, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:13'),
(364, 9, 'defull.webp', 'Isca de Pesca Artesanal', 0.41, 0.47, 7, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:13'),
(365, 9, 'defull.webp', 'Verniz Artesanal', 1.34, 1.53, 8, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:13'),
(366, 9, 'defull.webp', 'Seda Fina', 3.00, 3.43, 9, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:14'),
(367, 9, 'defull.webp', 'Carvão de Combustão', 3.46, 3.96, 10, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:14'),
(368, 9, 'defull.webp', 'Pano', 1.40, 1.60, 11, 1, '2026-09-04 10:40:55', '2026-09-04 11:05:14'),
(369, 10, 'defull.webp', 'Bezerro', 9.00, 12.00, 0, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:43'),
(370, 10, 'defull.webp', 'Bezerra', 9.00, 12.00, 1, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:43'),
(371, 10, 'defull.webp', 'Leitão', 9.00, 12.00, 2, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:43'),
(372, 10, 'defull.webp', 'Leitoa', 9.00, 12.00, 3, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:44'),
(373, 10, 'defull.webp', 'Pintinho Macho', 9.00, 12.00, 4, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:44'),
(374, 10, 'defull.webp', 'Pintinho Fêmea', 9.00, 12.00, 5, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:44'),
(375, 10, 'defull.webp', 'Cordeiro', 9.00, 12.00, 6, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:45'),
(376, 10, 'defull.webp', 'Cabrito', 9.00, 12.00, 7, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:45'),
(377, 10, 'defull.webp', 'Ração Animal', 2.28, 2.61, 8, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:45'),
(378, 10, 'defull.webp', 'Vacina Veterinaria', 2.89, 3.30, 9, 1, '2026-09-04 10:43:41', '2026-09-04 11:05:45'),
(379, 11, 'defull.webp', 'Repetidora Winchester', 329.75, 376.86, 0, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:27'),
(380, 11, 'defull.webp', 'Repetidora Evans', 329.75, 376.86, 1, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:27'),
(381, 11, 'defull.webp', 'Repetidora Carabina', 226.33, 258.66, 2, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:27'),
(382, 11, 'defull.webp', 'Repetidora Henry', 211.68, 241.92, 3, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:27'),
(383, 11, 'defull.webp', 'Rifle de Ferrolho', 567.67, 648.76, 4, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:28'),
(384, 11, 'defull.webp', 'Rifle Springfield', 403.59, 461.24, 5, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:28'),
(385, 11, 'defull.webp', 'Revólver Lemat', 266.86, 304.98, 6, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:28'),
(386, 11, 'defull.webp', 'Revólver Schofield', 179.27, 204.88, 7, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:29'),
(387, 11, 'defull.webp', 'Double Action', 188.76, 215.72, 8, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:29'),
(388, 11, 'defull.webp', 'Mexicano', 123.88, 141.58, 9, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:29'),
(389, 11, 'defull.webp', 'M1899', 328.86, 375.84, 10, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:29'),
(390, 11, 'defull.webp', 'Mauser', 286.55, 327.48, 11, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:30'),
(391, 11, 'defull.webp', 'Semi-Auto', 205.14, 234.44, 12, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:30'),
(392, 11, 'defull.webp', 'Volcanic', 221.81, 253.50, 13, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:30'),
(393, 11, 'defull.webp', 'DoubleBarrel', 342.21, 391.10, 14, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:30'),
(394, 11, 'defull.webp', 'Munição de Pistola', 6.44, 7.36, 15, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:31'),
(395, 11, 'defull.webp', 'Munição de Repetidora', 7.44, 8.50, 16, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:31'),
(396, 11, 'defull.webp', 'Munição de Revólver', 3.95, 4.51, 17, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:31'),
(397, 11, 'defull.webp', 'Munição de Rifle', 9.66, 11.04, 18, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:32'),
(398, 11, 'defull.webp', 'Munição de Shotgun', 10.89, 12.44, 19, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:32'),
(399, 11, 'defull.webp', 'Repetição Expressa', 31.68, 36.20, 20, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:32'),
(400, 11, 'defull.webp', 'Revólver Expressa', 25.69, 29.36, 21, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:32'),
(401, 11, 'defull.webp', 'Pistola Expressa', 22.75, 26.00, 22, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:33'),
(402, 11, 'defull.webp', 'Rifle Expressa', 48.93, 55.92, 23, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:33'),
(403, 11, 'defull.webp', 'Repetição Velocidade', 32.10, 36.68, 24, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:33'),
(404, 11, 'defull.webp', 'Revólver Velocidade', 26.11, 29.84, 25, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:34'),
(405, 11, 'defull.webp', 'Pistola Velocidade', 23.03, 26.32, 26, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:34'),
(406, 11, 'defull.webp', 'Rifle Velocidade', 49.77, 56.88, 27, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:34'),
(407, 11, 'defull.webp', 'Repetição Ponto Dividido', 32.10, 36.68, 28, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:34'),
(408, 11, 'defull.webp', 'Revólver Ponto Dividido', 26.11, 29.84, 29, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:56'),
(409, 11, 'defull.webp', 'Pistola Ponto Dividido', 23.03, 26.32, 30, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:57'),
(410, 11, 'defull.webp', 'Rifle Ponto Dividido', 49.77, 56.88, 31, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:58'),
(411, 11, 'defull.webp', 'Pano para Armas', 13.65, 15.60, 32, 1, '2026-09-04 10:46:27', '2026-09-04 11:06:59'),
(412, 11, 'defull.webp', 'Personalização de Arma', 75.00, 150.00, 33, 1, '2026-09-04 10:46:27', '2026-09-04 11:07:00'),
(413, 12, 'defull.webp', 'Jornal do Condado', 3.42, 3.91, 0, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:15'),
(414, 12, 'defull.webp', 'Cartaz', 3.79, 4.33, 1, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:15'),
(415, 12, 'defull.webp', 'Câmera Fotográfica', 33.43, 38.20, 2, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:15'),
(416, 12, 'defull.webp', 'Manual de Cruza', 1779.23, 2033.40, 3, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:16'),
(417, 12, 'defull.webp', 'Blueprint de Pistola', 40.71, 46.52, 4, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:16'),
(418, 12, 'defull.webp', 'Blueprint de Repetidora', 73.01, 83.44, 5, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:16'),
(419, 12, 'defull.webp', 'Blueprint de Revolver', 36.61, 41.84, 6, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:16'),
(420, 12, 'defull.webp', 'Blueprint de Rifle', 82.04, 93.76, 7, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:17'),
(421, 12, 'defull.webp', 'Lanterna de Davy', 45.41, 51.90, 8, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:17'),
(422, 12, 'defull.webp', 'Lampião a Gás', 16.29, 18.62, 9, 1, '2026-09-04 10:50:23', '2026-09-04 11:07:17'),
(423, 13, 'defull.webp', 'Espelho Gourmet', 12.36, 14.12, 0, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:27'),
(424, 13, 'defull.webp', 'Blush Gourmet', 2.37, 2.70, 1, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:27'),
(425, 13, 'defull.webp', 'Delineador Gourmet', 2.37, 2.70, 2, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:28'),
(426, 13, 'defull.webp', 'Batom Gourmet', 2.37, 2.70, 3, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:28'),
(427, 13, 'defull.webp', 'Sombra Gourmet', 2.37, 2.70, 4, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:28'),
(428, 13, 'defull.webp', 'Pomada Capilar', 2.37, 2.70, 5, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:28'),
(429, 13, 'defull.webp', 'Outfit Pronto', 75.00, 90.00, 6, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:29'),
(430, 13, 'defull.webp', 'Toalha', 5.26, 6.01, 7, 1, '2026-09-04 10:52:03', '2026-09-04 11:07:29'),
(435, 14, 'defull.webp', 'Arco Simples', 20.37, 23.28, 0, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:37'),
(436, 14, 'defull.webp', 'Arco Melhorado', 47.67, 54.48, 1, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:37'),
(437, 14, 'defull.webp', 'F. Incendiária', 2.37, 2.70, 2, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:37'),
(438, 14, 'defull.webp', 'F. Venenosa', 2.37, 2.70, 3, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:38'),
(439, 14, 'defull.webp', 'Flecha', 2.37, 2.70, 4, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:38'),
(440, 14, 'defull.webp', 'Boleadeira', 2.37, 2.70, 5, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:38'),
(441, 14, 'defull.webp', 'Charuto', 75.00, 90.00, 6, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:38'),
(442, 14, 'defull.webp', 'Elixir Tribal', 2.63, 3.00, 7, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:39'),
(443, 14, 'defull.webp', 'Tomahawk', 4.41, 5.04, 8, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:39'),
(444, 14, 'defull.webp', 'Fogueira', 1.05, 1.20, 9, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:39'),
(445, 14, 'defull.webp', 'Linha de Fibra', 3.92, 4.48, 10, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:40'),
(446, 14, 'defull.webp', 'Madeira Tratada', 2.87, 3.28, 11, 1, '2026-09-04 10:54:23', '2026-09-04 11:07:40'),
(447, 15, 'defull.webp', 'Charuto', 3.64, 4.16, 0, 1, '2026-09-04 10:55:46', '2026-09-04 11:07:48'),
(448, 15, 'defull.webp', 'Cachimbo', 8.24, 9.42, 1, 1, '2026-09-04 10:55:46', '2026-09-04 11:07:48'),
(449, 15, 'defull.webp', 'Cigarro', 2.14, 2.44, 2, 1, '2026-09-04 10:55:46', '2026-09-04 11:07:49'),
(450, 15, 'defull.webp', 'Goma de Mascar', 1.89, 2.16, 3, 1, '2026-09-04 10:55:46', '2026-09-04 11:07:49'),
(451, 15, 'defull.webp', 'Fumo', 2.05, 2.34, 4, 1, '2026-09-04 10:55:46', '2026-09-04 11:07:49'),
(452, 15, 'defull.webp', 'Isqueiro', 1.45, 1.66, 5, 1, '2026-09-04 10:55:46', '2026-09-04 11:07:49'),
(453, 16, 'defull.webp', 'Revitalizador Equino', 9.98, 11.40, 0, 1, '2026-09-04 10:56:54', '2026-09-04 11:02:24'),
(454, 16, 'defull.webp', 'Ração Campestre', 2.24, 2.56, 1, 1, '2026-09-04 10:56:54', '2026-09-04 11:02:24'),
(455, 16, 'defull.webp', 'Escovinha Equina', 3.57, 4.08, 2, 1, '2026-09-04 10:56:54', '2026-09-04 11:02:25'),
(456, 16, 'defull.webp', 'Troca de Ferradura Ferro', 28.16, 30.51, 3, 1, '2026-09-04 10:56:54', '2026-09-04 11:02:25'),
(457, 16, 'defull.webp', 'Troca de Ferradura Ouro', 28.16, 30.51, 4, 1, '2026-09-04 10:56:54', '2026-09-04 11:02:25'),
(458, 16, 'defull.webp', 'Treinamento', 150.00, 300.00, 5, 1, '2026-09-04 10:56:54', '2026-09-04 11:02:26'),
(460, 16, 'defull.webp', 'Remédio Equino', 8.24, 9.42, 7, 1, '2026-09-04 10:56:54', '2026-09-04 11:02:26'),
(461, 5, 'default.webp', 'Corte Nobre', 0.56, 0.64, 0, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:34'),
(462, 5, 'default.webp', 'Corte Seleto', 0.53, 0.60, 1, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:34'),
(463, 5, 'default.webp', 'Linguiça Premium', 0.48, 0.55, 2, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:34'),
(464, 5, 'default.webp', 'Corte de Ave', 0.35, 0.40, 3, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:34'),
(465, 5, 'default.webp', 'Sebo Animal', 0.55, 0.63, 4, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:35'),
(466, 5, 'default.webp', 'Tendão', 0.68, 0.78, 5, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:35'),
(467, 5, 'default.webp', 'Sal', 0.88, 1.01, 6, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:35'),
(468, 5, 'default.webp', 'Cutelo', 4.78, 5.47, 7, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:36'),
(469, 5, 'default.webp', 'Couro Curtido', 0.88, 1.00, 8, 1, '2026-09-04 11:04:10', '2026-09-04 11:25:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `prizes`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `prizes`;
CREATE TABLE IF NOT EXISTS `prizes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `points_novato` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `points_oficial` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `points_afiliado` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `prizes`:
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `raffles`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `raffles`;
CREATE TABLE IF NOT EXISTS `raffles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `prize` varchar(255) NOT NULL,
  `draw_date` date DEFAULT NULL,
  `draw_time` time DEFAULT NULL,
  `winners_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `entry_points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `eligibility_text` varchar(500) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `drawn_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_raffles_active` (`active`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `raffles`:
--   `created_by`
--       `staff_users` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `raffle_entries`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `raffle_entries`;
CREATE TABLE IF NOT EXISTS `raffle_entries` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `raffle_id` bigint(20) UNSIGNED NOT NULL,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `eligibility_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `month_reference` char(7) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_raffle_streamer` (`raffle_id`,`streamer_id`),
  KEY `idx_raffle_entries_raffle` (`raffle_id`),
  KEY `streamer_id` (`streamer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `raffle_entries`:
--   `raffle_id`
--       `raffles` -> `id`
--   `streamer_id`
--       `streamers` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `raffle_winners`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `raffle_winners`;
CREATE TABLE IF NOT EXISTS `raffle_winners` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `raffle_id` bigint(20) UNSIGNED NOT NULL,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `draw_number` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `drawn_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_raffle_winner` (`raffle_id`,`streamer_id`),
  KEY `streamer_id` (`streamer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `raffle_winners`:
--   `raffle_id`
--       `raffles` -> `id`
--   `streamer_id`
--       `streamers` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `redemptions`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `redemptions`;
CREATE TABLE IF NOT EXISTS `redemptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `prize_id` int(10) UNSIGNED NOT NULL,
  `streamer_category` enum('novato','oficial','afiliado') NOT NULL,
  `points_spent` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `delivered_by` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `staff_notes` text DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_redemptions_streamer` (`streamer_id`),
  KEY `fk_redemptions_prize` (`prize_id`),
  KEY `fk_redemptions_approved` (`approved_by`),
  KEY `fk_redemptions_delivered` (`delivered_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `redemptions`:
--   `approved_by`
--       `staff_users` -> `id`
--   `delivered_by`
--       `staff_users` -> `id`
--   `prize_id`
--       `prizes` -> `id`
--   `streamer_id`
--       `streamers` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `settings`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `settings`:
--

--
-- Despejando dados para a tabela `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(47, 'live_points_per_hour', '1', 'Pontos por hora completa de VOD/Live', '2026-09-03 17:16:21'),
(48, 'live_max_points', '10', 'Limite máximo de pontos de VOD/Live por dia', '2026-09-03 17:16:21'),
(49, 'collab_raid_points', '3', 'Pontos por Collab/Raid com outro streamer do Condado', '2026-09-03 17:16:21'),
(50, 'social_common_points', '3', 'Pontos por Link Comum', '2026-09-03 17:16:21'),
(51, 'social_humor_points', '20', 'Pontos por Vídeo Humorístico', '2026-09-03 17:16:21'),
(52, 'social_news_points', '10', 'Pontos por Vídeo de Novidades', '2026-09-03 17:16:21'),
(53, 'weekly_social_points_limit', '30', 'Limite semanal de pontos por conteúdos de redes sociais', '2026-09-03 17:16:21'),
(54, 'weekly_live_days_target', '3', 'Dias distintos com live aprovados na semana para atingir a meta', '2026-09-03 17:16:21'),
(55, 'weekly_live_days_bonus', '20', 'Bônus por atingir a meta semanal de dias com live', '2026-09-03 17:16:21'),
(56, 'weekly_live_hours_target', '10', 'Horas completas de live aprovadas na semana para atingir a meta', '2026-09-03 17:16:21'),
(57, 'weekly_live_hours_bonus', '30', 'Bônus por atingir a meta semanal de horas com live', '2026-09-03 17:16:21'),
(58, 'monthly_live_days_target', '20', 'Dias distintos com live aprovados no mês para atingir a meta', '2026-09-03 17:16:21'),
(59, 'monthly_live_days_bonus', '50', 'Bônus por atingir a meta mensal de dias com live', '2026-09-03 17:16:21'),
(60, 'proof_retention_days', '45', 'Quantidade de dias para manter os comprovantes de VOD antes da exclusão automática', '2026-09-03 17:16:21'),
(61, 'redemption_cooldown_days', '7', 'Quantidade de dias entre resgates aprovados do mesmo streamer', '2026-09-03 17:16:21'),
(62, 'streamer_logo', 'assets/uploads/streamer/logo_20260903141316_4426f5a8.png', 'Logo da Área do Streamer', '2026-09-03 18:13:16'),
(63, 'streamer_background', 'assets/uploads/streamer/background_20260903141009_ffba8a98.png', 'Background da Área do Streamer', '2026-09-03 18:10:09'),
(65, 'vod_daily_points_migrated', '1', 'VODs existentes recalculadas pela regra diária', '2026-09-03 18:23:55'),
(66, 'discord_ranking_key', '4684a07da552d6e32e9042220052a594b8d361e7eba54677', 'Chave privada do endpoint de atualização do ranking Discord.', '2026-09-05 19:41:18'),
(67, 'discord_ranking_webhook', 'https://discord.com/api/webhooks/1545882035590135839/hGk8JfVH1TwY6j7fF_WCOT1fUdVaR4Xbb0ZzWjpxFRWu1E4gNqEGSAaCYApYhls0F-9I', 'Webhook do ranking público Carmesim Creators.', '2026-09-05 19:43:32'),
(68, 'discord_ranking_message_id', '', 'ID da mensagem do ranking no Discord.', '2026-09-05 19:43:32'),
(69, 'live_monitor_enabled', '0', 'Ativa o monitoramento automático de lives', '2026-09-06 04:43:30'),
(70, 'live_monitor_interval_minutes', '5', 'Intervalo planejado do cron do Live Monitor', '2026-09-06 04:43:30'),
(71, 'live_monitor_title_rule', '[Carmesim RP]', 'Trecho obrigatório no título da live', '2026-09-06 04:43:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `site_companies`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `site_companies`;
CREATE TABLE IF NOT EXISTS `site_companies` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `photo` varchar(500) DEFAULT NULL,
  `category` varchar(120) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` varchar(700) DEFAULT NULL,
  `responsible` varchar(180) DEFAULT NULL,
  `link_url` varchar(1000) DEFAULT NULL,
  `link_text` varchar(160) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_companies_active_position` (`active`,`position`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `site_companies`:
--

--
-- Despejando dados para a tabela `site_companies`
--

INSERT INTO `site_companies` (`id`, `photo`, `category`, `name`, `description`, `responsible`, `link_url`, `link_text`, `available`, `position`, `active`, `created_at`, `updated_at`) VALUES
(1, 'empresa_b4eff70338ee7c4f.webp', 'Saloon', 'Saloon Saint Denis', 'Luxuoso Salon no Centro de Saint Denis\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 0, 1, '2026-09-04 02:33:53', '2026-09-05 13:10:29'),
(2, 'empresa_91aee473e3ae9bc2.webp', 'Saloon', 'Saloon de Valentine', 'Ponto de encontro tradicional de Valentine, frequentado por moradores e viajantes.\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 1, 1, '2026-09-04 11:21:33', '2026-09-05 13:10:23'),
(3, 'empresa_60ee21e111663c7d.webp', 'Saloon', 'Saloon de Rhodes', 'Saloon movimentado de Rhodes, ideal para bebidas, encontros e conversas.\r\nRequerimento: 4 Pessoas para assumir', NULL, NULL, NULL, 1, 2, 1, '2026-09-04 11:21:33', '2026-09-05 13:08:27'),
(4, 'empresa_2b4c2a2069a38828.webp', 'Saloon', 'Saloon Van Horn', 'Saloon rústico de Van Horn, frequentado por viajantes e trabalhadores.\r\nRequerimento: 4 Pessoas para assumir', NULL, NULL, NULL, 1, 3, 1, '2026-09-04 11:21:33', '2026-09-05 13:08:55'),
(5, 'empresa_3b59bf2aab15c90d.webp', 'Saloon', 'Saloon de Blackwater', 'Ponto de encontro de moradores, comerciantes e viajantes de Blackwater.\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 4, 1, '2026-09-04 11:21:33', '2026-09-05 13:10:35'),
(6, 'empresa_26593dab5d1b561d.webp', 'Saloon', 'Saloon de Armadillo', 'Refúgio para moradores e viajantes no árido território de New Austin.\r\nRequerimento: 3 Pessoas para assumir', NULL, NULL, NULL, 1, 5, 1, '2026-09-04 11:21:33', '2026-09-05 13:08:59'),
(7, 'empresa_35e7123b10d3e9c6.webp', 'Saloon', 'Saloon de Tumbleweed', 'Saloon rústico de Tumbleweed, frequentado por viajantes e moradores.\r\nRequerimento: 3 Pessoas para assumir', NULL, NULL, NULL, 1, 6, 1, '2026-09-04 11:21:33', '2026-09-05 13:09:13'),
(8, 'empresa_a3b5c84654aea091.webp', 'Saloon', 'Saloon de Strawberry', 'Saloon tranquilo de Strawberry, ideal para descanso, bebidas e encontros.\r\nRequerimento: 4 Pessoas para assumir', NULL, NULL, NULL, 1, 7, 1, '2026-09-04 11:21:33', '2026-09-05 13:09:19'),
(9, 'empresa_c621d9e75405e8e4.webp', 'Artesanato', 'Artesanato de Rhodes', 'Oficina tradicional de Rhodes, onde moradores e viajantes encontram produtos artesanais e trabalhos manuais.\r\nRequerimento: 5 Pessoas', NULL, NULL, NULL, 1, 8, 1, '2026-09-04 11:44:26', '2026-09-04 12:59:05'),
(10, 'empresa_150af5a69efe4a2c.webp', 'Artesanato', 'Artesanato de Saint Denis', 'Oficina movimentada de Saint Denis, conhecida pela variedade de trabalhos e produtos artesanais.\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 9, 1, '2026-09-04 11:44:26', '2026-09-05 12:58:27'),
(11, 'empresa_faa230841ef3f558.webp', 'Artesanato', 'Artesanato de Blackwater', 'Espaço de produção artesanal de Blackwater, atendendo moradores e comerciantes da região.\r\nRequerimento Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 10, 1, '2026-09-04 11:44:26', '2026-09-05 12:58:41'),
(12, 'empresa_4623ce2473cac84b.webp', 'Artesanato', 'Artesanato de Valentine', 'Oficina artesanal de Valentine, voltada à produção e comércio de itens feitos à mão.\r\nRequerimento Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 11, 1, '2026-09-04 11:44:26', '2026-09-05 12:58:55'),
(13, 'empresa_422113e86836e568.webp', 'Artesanato', 'Artesanato de Strawberry', 'Pequena oficina artesanal de Strawberry, com produção voltada aos moradores e visitantes.\r\nRequerimento: 4 Pessoas', NULL, NULL, NULL, 1, 12, 1, '2026-09-04 11:44:26', '2026-09-04 12:59:18'),
(14, 'empresa_05e5e03d5de8058f.webp', 'Artesanato', 'Artesanato de Emerald Ranch', 'Oficina artesanal de Emerald Ranch, atendendo a comunidade local com trabalhos e produtos artesanais.\r\nRequerimento: 5 Pessoas', NULL, NULL, NULL, 1, 13, 1, '2026-09-04 11:44:26', '2026-09-04 12:59:29'),
(16, 'empresa_c5303c5f8b6c03af.webp', 'Doceria&Padaria', 'Doceria de Saint Denis', 'Doceria tradicional de Saint Denis, oferecendo doces, pães e produtos artesanais para moradores e visitantes.\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 14, 1, '2026-09-04 11:55:50', '2026-09-05 12:59:09'),
(17, 'empresa_1530a270b91bfb68.webp', 'Doceria&Padaria', 'Doceria de Blackwater', 'Doceria de Blackwater, conhecida por seus doces, pães e produtos frescos.\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 15, 1, '2026-09-04 11:55:50', '2026-09-05 12:59:22'),
(18, 'empresa_396c944002e0d3ed.webp', 'Doceria&Padaria', 'Doceria de Valentine', 'Pequena doceria de Valentine, oferecendo doces, pães e produtos para moradores e viajantes.\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 16, 1, '2026-09-04 11:55:50', '2026-09-05 12:59:38'),
(19, 'empresa_d2de748b81511cb4.webp', 'Doceria&Padaria', 'Doceria de Emerald Ranch', 'Doceria e padaria de Emerald Ranch, atendendo a comunidade local com produtos frescos e artesanais.\r\nRequerimento: Donate Via CentralCard', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 17, 1, '2026-09-04 11:55:50', '2026-09-05 12:59:54'),
(20, 'empresa_dda9b54179409265.webp', 'Ferraria', 'Ferraria de Annesburg', 'Ferraria de Annesburg voltada à produção, manutenção e comércio de equipamentos de metal. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 18, 1, '2026-09-04 12:03:53', '2026-09-04 13:14:33'),
(21, 'empresa_e329610219f8a724.webp', 'Ferraria', 'Ferraria de Saint Denis', 'Ferraria de Saint Denis especializada em trabalhos de metal, ferramentas e equipamentos. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 19, 1, '2026-09-04 12:03:53', '2026-09-04 13:14:22'),
(22, 'empresa_1b44e2d04c9f0ef7.webp', 'Ferraria', 'Ferraria de Blackwater', 'Ferraria de Blackwater dedicada à produção e manutenção de ferramentas e equipamentos. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 20, 1, '2026-09-04 12:03:53', '2026-09-04 13:14:45'),
(23, 'empresa_d266a4d3114cec1f.webp', 'Ferraria', 'Ferraria de Valentine', 'Ferraria de Valentine voltada aos trabalhos de metal, ferramentas e equipamentos para a região. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 21, 1, '2026-09-04 12:03:53', '2026-09-04 13:14:56'),
(24, 'empresa_48157af9ea0501a6.webp', 'Ferraria', 'Ferraria de Strawberry', 'Ferraria de Strawberry responsável pela produção e manutenção de ferramentas e equipamentos. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 22, 1, '2026-09-04 12:03:53', '2026-09-04 13:15:06'),
(25, 'empresa_e4e772d63959ed03.webp', 'Ferraria', 'Ferraria de Rhodes', 'Ferraria de Rhodes dedicada aos serviços de metalurgia, ferramentas e equipamentos. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 23, 1, '2026-09-04 12:03:53', '2026-09-04 13:15:17'),
(26, 'empresa_44d96c0a35142dc9.webp', 'Ferraria', 'Ferraria de Armadilo', 'Ferraria de Armadilo voltada à produção e manutenção de ferramentas e equipamentos no território de New Austin. Requerimento: 3 Pessoas', NULL, NULL, NULL, 1, 24, 1, '2026-09-04 12:03:53', '2026-09-04 13:15:32'),
(27, 'empresa_a451907078408493.webp', 'Açougue', 'Açougue de Annesburg', 'Açougue de Annesburg dedicado ao comércio e preparo de carnes. Requerimento: 4 Pessoas', NULL, NULL, NULL, 1, 25, 1, '2026-09-04 12:09:02', '2026-09-04 13:18:05'),
(28, 'empresa_c64eec22e1f6921c.webp', 'Açougue', 'Açougue de Saint Denis', 'Açougue de Saint Denis dedicado ao comércio e preparo de carnes. Requerimento: 4 Pessoas', NULL, NULL, NULL, 1, 26, 1, '2026-09-04 12:09:02', '2026-09-04 13:18:21'),
(29, 'empresa_7284a533ad40e630.webp', 'Açougue', 'Açougue de Blackwater', 'Açougue de Blackwater dedicado ao comércio e preparo de carnes. Requerimento: 4 Pessoas', NULL, NULL, NULL, 1, 27, 1, '2026-09-04 12:09:02', '2026-09-04 13:18:33'),
(30, 'empresa_3d2661777a5e8aa0.webp', 'Açougue', 'Açougue de Valentine', 'Açougue de Valentine dedicado ao comércio e preparo de carnes. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 28, 1, '2026-09-04 12:09:02', '2026-09-05 13:00:41'),
(31, 'empresa_acd4c29a0b7bf82a.webp', 'Açougue', 'Açougue de Strawberry', 'Açougue de Strawberry dedicado ao comércio e preparo de carnes. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 29, 1, '2026-09-04 12:09:02', '2026-09-05 13:00:54'),
(32, 'empresa_ee93cb9429328432.webp', 'Açougue', 'Açougue de Rhodes', 'Açougue de Rhodes dedicado ao comércio e preparo de carnes. Requerimento: 4 Pessoas', NULL, NULL, NULL, 1, 30, 1, '2026-09-04 12:09:02', '2026-09-04 13:18:53'),
(33, 'empresa_9b710a180f1db90d.webp', 'Mineradora', 'Mineradora de Annesburg', 'Mineradora de Annesburg dedicada à extração e produção de recursos minerais. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 31, 1, '2026-09-04 12:12:41', '2026-09-04 13:25:29'),
(34, 'empresa_1b4befec64ddf262.webp', 'Mineradora', 'Mineradora de Rhodes', 'Mineradora de Rhodes dedicada à extração e produção de recursos minerais. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 32, 1, '2026-09-04 12:12:41', '2026-09-04 13:25:42'),
(35, 'empresa_ddd3b39371c5fee2.webp', 'Mineradora', 'Mineradora de Blackwater', 'Mineradora de Blackwater dedicada à extração e produção de recursos minerais. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 33, 1, '2026-09-04 12:12:41', '2026-09-04 13:25:54'),
(36, 'empresa_56a5bd8828529a91.webp', 'Mineradora', 'Mineradora de Emerald Ranch', 'Mineradora de Emerald Ranch dedicada à extração e produção de recursos minerais. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 34, 1, '2026-09-04 12:12:41', '2026-09-04 13:26:12'),
(37, 'empresa_2e063bcd4845eea6.webp', 'Mineradora', 'Mineradora de Armadilo', 'Mineradora de Armadilo dedicada à extração e produção de recursos minerais. Requerimento: 3 Pessoas', NULL, NULL, NULL, 1, 35, 1, '2026-09-04 12:12:41', '2026-09-04 13:26:27'),
(38, 'empresa_d9fa4a7fe7f30def.webp', 'Madeireira', 'Madeireira de Annesburg', 'Madeireira de Annesburg dedicada ao corte, processamento e comércio de madeira. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 36, 1, '2026-09-04 12:16:05', '2026-09-04 13:29:33'),
(39, 'empresa_3dcced581fe15032.webp', 'Madeireira', 'Madeireira de Rhodes', 'Madeireira de Rhodes dedicada ao corte, processamento e comércio de madeira. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 37, 1, '2026-09-04 12:16:05', '2026-09-04 13:29:49'),
(40, 'empresa_681c2dde8bbbe2b6.webp', 'Madeireira', 'Madeireira de Valentine', 'Madeireira de Valentine dedicada ao corte, processamento e comércio de madeira. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 38, 1, '2026-09-04 12:16:05', '2026-09-04 13:30:09'),
(44, 'empresa_842521f3a29f37b7.webp', 'Veterinaria', 'Veterinaria de Saint Denis', 'Veterinaria de Saint Denis dedicada ao cuidado e atendimento de animais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 42, 1, '2026-09-04 12:24:05', '2026-09-05 13:01:12'),
(45, 'empresa_e0dadd9d35fa4208.webp', 'Veterinaria', 'Veterinaria de Van Horn', 'Veterinaria de Van Horn dedicada ao cuidado e atendimento de animais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 43, 1, '2026-09-04 12:24:05', '2026-09-05 13:01:24'),
(46, 'empresa_f1a7b3fa34f09392.webp', 'Veterinaria', 'Veterinaria de Emerald Ranch', 'Veterinaria de Emerald Ranch dedicada ao cuidado e atendimento de animais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 44, 1, '2026-09-04 12:24:05', '2026-09-05 13:01:39'),
(47, 'empresa_6a69d16e672bddd4.webp', 'Veterinaria', 'Veterinaria de Strawberry', 'Veterinaria de Strawberry dedicada ao cuidado e atendimento de animais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 45, 1, '2026-09-04 12:24:05', '2026-09-05 13:01:51'),
(48, 'empresa_7cd9451b97e8fc5d.webp', 'Veterinaria', 'Veterinaria de BlackWater', 'Veterinaria de BlackWater dedicada ao cuidado e atendimento de animais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 46, 1, '2026-09-04 12:24:05', '2026-09-05 13:02:06'),
(49, 'empresa_67b857cee56c8c90.webp', 'Armaria', 'Armaria de Annesburg', 'Armaria de Annesburg especializada na venda e manutenção de armas e equipamentos. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 47, 1, '2026-09-04 12:27:36', '2026-09-04 13:03:43'),
(50, 'empresa_8fdf9e1ad08ccf0b.webp', 'Armaria', 'Armaria de Valentine', 'Armaria de Valentine especializada na venda e manutenção de armas e equipamentos. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 48, 1, '2026-09-04 12:27:36', '2026-09-05 13:02:24'),
(51, 'empresa_23c7f1290712cc94.webp', 'Armaria', 'Armaria de Rhodes', 'Armaria de Rhodes especializada na venda e manutenção de armas e equipamentos. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 49, 1, '2026-09-04 12:27:36', '2026-09-04 13:03:59'),
(52, 'empresa_94e7359ed42266d1.webp', 'Armaria', 'Armaria de Saint Denis', 'Armaria de Saint Denis especializada na venda e manutenção de armas e equipamentos. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 50, 1, '2026-09-04 12:27:36', '2026-09-05 13:02:39'),
(53, 'empresa_af4bb3216a36e084.webp', 'Armaria', 'Armaria de Strawberry', 'Armaria de Strawberry especializada na venda e manutenção de armas e equipamentos. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 51, 1, '2026-09-04 12:27:36', '2026-09-04 13:04:16'),
(54, 'empresa_06a6d5ef4ed29fc2.webp', 'Jornal', 'Jornal de Valentine', 'Jornal de Valentine responsável pela divulgação de notícias e acontecimentos da região. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 52, 1, '2026-09-04 12:31:29', '2026-09-04 13:39:54'),
(55, 'empresa_a5ca300ad5298e39.webp', 'Jornal', 'Jornal de Saint Denis', 'Jornal de Saint Denis responsável pela divulgação de notícias e acontecimentos da região. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 53, 1, '2026-09-04 12:31:29', '2026-09-04 13:40:11'),
(56, 'empresa_c853df600cc21a59.webp', 'Jornal', 'Jornal de Rhodes', 'Jornal de Rhodes responsável pela divulgação de notícias e acontecimentos da região. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 54, 1, '2026-09-04 12:31:29', '2026-09-04 13:40:26'),
(57, 'empresa_ff63864db9dc9da4.webp', 'Jornal', 'Jornal de BlackWater', 'Jornal de BlackWater responsável pela divulgação de notícias e acontecimentos da região. Requerimento: 5 Pessoas', NULL, NULL, NULL, 1, 55, 1, '2026-09-04 12:31:29', '2026-09-04 13:40:42'),
(58, 'empresa_93df3877010fbb24.webp', 'Jornal', 'Jornal de Annesburg', 'Jornal de Annesburg responsável pela divulgação de notícias e acontecimentos da região. Requerimento: 3 Pessoas', NULL, NULL, NULL, 1, 56, 1, '2026-09-04 12:31:29', '2026-09-04 13:40:58'),
(59, 'empresa_13291bf4243ea2eb.webp', 'Ateliê', 'Ateliê Strawberry', 'Ateliê de Strawberry dedicado à produção e comércio de peças e trabalhos artesanais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 57, 1, '2026-09-04 12:34:49', '2026-09-05 13:03:09'),
(60, 'empresa_a1230983ce7c14ae.webp', 'Ateliê', 'Ateliê Saint Denis', 'Ateliê de Saint Denis dedicado à produção e comércio de peças e trabalhos artesanais. Requerimento: Donate via CentralCart', NULL, NULL, NULL, 1, 58, 1, '2026-09-04 12:34:49', '2026-09-04 13:43:52'),
(61, 'empresa_b9124337d7d66d1e.webp', 'Ateliê', 'Ateliê Emerald Ranch', 'Ateliê de Emerald Ranch dedicado à produção e comércio de peças e trabalhos artesanais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 59, 1, '2026-09-04 12:34:49', '2026-09-05 13:03:22'),
(62, 'empresa_b95c539d2381a5ca.webp', 'Tabacaria', 'Tabacaria Rhodes', 'Tabacaria de Rhodes dedicada ao comércio de tabaco e produtos relacionados. Requerimento: 3 Pessoas', NULL, NULL, NULL, 1, 60, 1, '2026-09-04 12:40:40', '2026-09-04 13:49:49'),
(63, 'empresa_65d0ecacc493d803.webp', 'Tabacaria', 'Tabacaria BlackWater', 'Tabacaria de BlackWater dedicada ao comércio de tabaco e produtos relacionados. Requerimento: 3 Pessoas', NULL, NULL, NULL, 1, 61, 1, '2026-09-04 12:40:40', '2026-09-04 13:50:05'),
(64, 'empresa_46f15eae6ebec1e8.webp', 'Tabacaria', 'Tabacaria Emerald Ranch', 'Tabacaria de Emerald Ranch dedicada ao comércio de tabaco e produtos relacionados. Requerimento: 3 Pessoas', NULL, NULL, NULL, 1, 62, 1, '2026-09-04 12:40:40', '2026-09-04 13:50:26'),
(65, 'empresa_194c7be6b0e1034e.webp', 'Estábulo', 'Estábulo de Valentine', 'Estábulo de Valentine responsável pelo cuidado, hospedagem e comércio de cavalos. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 63, 1, '2026-09-04 12:45:44', '2026-09-05 13:03:49'),
(66, 'empresa_2200d5bf9dec1f18.webp', 'Estábulo', 'Estábulo de Strawberry', 'Estábulo de Strawberry responsável pelo cuidado, hospedagem e comércio de cavalos. Requerimento: 4 Pessoas', NULL, NULL, NULL, 1, 64, 1, '2026-09-04 12:45:44', '2026-09-04 13:52:19'),
(67, 'empresa_0177d96288d6d7f5.webp', 'Estábulo', 'Estábulo de Emerald Ranch', 'Estábulo de Emerald Ranch responsável pelo cuidado, hospedagem e comércio de cavalos. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 65, 1, '2026-09-04 12:45:44', '2026-09-05 13:04:07'),
(68, 'empresa_073fe01669275019.webp', 'Estábulo', 'Estábulo de Van Horn', 'Estábulo de Van Horn responsável pelo cuidado, hospedagem e comércio de cavalos. Requerimento: 4 Pessoas', NULL, NULL, NULL, 1, 66, 1, '2026-09-04 12:45:44', '2026-09-04 13:53:33'),
(69, 'empresa_105799d8f0b4b3ec.webp', 'Estábulo', 'Estábulo de Saint Denis', 'Estábulo de Saint Denis responsável pelo cuidado, hospedagem e comércio de cavalos. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 67, 1, '2026-09-04 12:45:44', '2026-09-05 13:04:29'),
(70, 'empresa_9c9520ac5d55978e.webp', 'Estábulo', 'Estábulo de Blackwater', 'Estábulo de Blackwater responsável pelo cuidado, hospedagem e comércio de cavalos. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 68, 1, '2026-09-04 12:45:44', '2026-09-05 13:04:36'),
(71, 'empresa_a4f23dee0267b0fc.webp', 'Estábulo', 'Estábulo de Tumbleweed', 'Estábulo de Tumbleweed responsável pelo cuidado, hospedagem e comércio de cavalos. Requerimento: 4 Pessoas', NULL, NULL, NULL, 1, 69, 1, '2026-09-04 12:45:44', '2026-09-04 13:53:17'),
(72, 'empresa_17a1008032c70781.webp', 'Ferrovia', 'Ferrovia de Valentine', 'Ferrovia Valentine responsável pelo transporte de passageiros, cargas e correspondências, conectando Valentine às demais regiões do território. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 70, 1, '2026-09-05 12:50:25', '2026-09-05 12:54:16'),
(73, 'empresa_9f2f232005c9457a.webp', 'Ferrovia', 'Ferrovia de Emerald', 'Ferrovia Emerald responsável pelo transporte de cargas, animais, produtos agrícolas e suprimentos, conectando as áreas rurais aos principais centros comerciais. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 71, 1, '2026-09-05 12:50:25', '2026-09-05 12:55:02'),
(74, 'empresa_980fcd4cd6b692a2.webp', 'Ferrovia', 'Ferrovia de Rhodes', 'Ferrovia Rhodes responsável pelo transporte de passageiros, mercadorias e produtos agrícolas, mantendo a ligação comercial entre Rhodes e as demais regiões. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 72, 1, '2026-09-05 12:50:25', '2026-09-05 12:55:31'),
(75, 'empresa_44c3016acfd26b7b.webp', 'Ferrovia', 'Ferrovia de Saint Denis', 'Ferrovia Saint Denis responsável pelo transporte de passageiros, cargas comerciais e matérias-primas, conectando Saint Denis aos principais centros ferroviários do território. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 73, 1, '2026-09-05 12:50:25', '2026-09-05 12:55:49'),
(76, 'empresa_44e75c3c5ac9d691.webp', 'Ferrovia', 'Ferrovia de Annesburg', 'Ferrovia Annesburg responsável pelo transporte de carvão, minérios, ferramentas e recursos provenientes das áreas de mineração, abastecendo as regiões industriais. Requerimento: 6 Pessoas', NULL, NULL, NULL, 1, 74, 1, '2026-09-05 12:50:25', '2026-09-05 12:51:21'),
(77, 'empresa_17a756b8da505abc.webp', 'Ferrovia', 'Ferrovia de Van Horn', 'Ferrovia Van Horn responsável pelo transporte de passageiros e mercadorias, realizando a conexão entre o comércio portuário de Van Horn e as demais rotas ferroviárias. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 75, 1, '2026-09-05 12:50:25', '2026-09-05 12:56:10'),
(78, 'empresa_284e50e3d3cc7414.webp', 'Ferrovia', 'Ferrovia de Riggs', 'Ferrovia Riggs responsável pelo transporte de passageiros, cargas e correspondências, servindo como importante ponto de conexão entre West Elizabeth e as demais regiões. Requerimento: Donate via CentralCart', NULL, 'https://condadocarmesim.centralcart.ai', 'CentralCart Carmesim', 1, 76, 1, '2026-09-05 12:50:25', '2026-09-05 12:56:29'),
(79, 'empresa_8188886e0252479e.webp', 'Ferrovia', 'Ferrovia de Armadillo', 'Ferrovia Armadillo responsável pelo transporte de passageiros, correspondências, suprimentos e mercadorias através das regiões áridas do oeste. Requerimento: 6 Pessoas', NULL, NULL, NULL, 1, 77, 1, '2026-09-05 12:50:25', '2026-09-05 12:51:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `site_team_members`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `site_team_members`;
CREATE TABLE IF NOT EXISTS `site_team_members` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `role_title` varchar(120) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `discord_id` varchar(32) DEFAULT NULL,
  `discord_avatar` varchar(700) DEFAULT NULL,
  `member_type` enum('leadership','collaborator') NOT NULL DEFAULT 'collaborator',
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_team_type_position` (`member_type`,`position`,`active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `site_team_members`:
--

--
-- Despejando dados para a tabela `site_team_members`
--

INSERT INTO `site_team_members` (`id`, `name`, `role_title`, `description`, `discord_id`, `discord_avatar`, `member_type`, `position`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Jessy', 'CEO', 'Idealizadora do Condado Carmesim', '519870847310102540', 'https://cdn.discordapp.com/avatars/519870847310102540/0882b806f2c82399c990639224b0fbe4.png?size=256', 'leadership', 1, 1, '2026-09-04 00:52:51', '2026-09-04 00:53:42'),
(2, 'Luci', 'CEO', 'Idealizador do Condado Carmesim', '207712204890439680', 'https://cdn.discordapp.com/avatars/207712204890439680/bf103cfd7e4b93e69857e1cd6b34de4a.png?size=256', 'leadership', 2, 1, '2026-09-04 00:52:51', '2026-09-04 00:53:57'),
(3, 'Matheus', 'COO', 'Parte da liderança e construção do projeto', '389970494763302912', 'https://cdn.discordapp.com/avatars/389970494763302912/949d2e425565dd6c5913d239c8cc691b.png?size=256', 'leadership', 3, 1, '2026-09-04 00:52:51', '2026-09-04 00:54:22'),
(4, 'Nysaca', 'Head of Marketing', 'Gerencia o Marketing', '1070361044117114910', 'https://cdn.discordapp.com/avatars/1070361044117114910/a_e603f091d7312c1dce540b3b790dcc96.gif?size=256', 'collaborator', 1, 1, '2026-09-04 00:55:27', '2026-09-04 00:58:10'),
(5, 'Aurora', 'Head Creators', 'Gerenciadora dos Creators', '1456649161288974468', 'https://cdn.discordapp.com/avatars/1456649161288974468/ea47de6f65dbfce2ead306f5a415bf43.png?size=256', 'collaborator', 1, 1, '2026-09-04 00:56:53', '2026-09-04 00:56:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `staff_commands_settings`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `staff_commands_settings`;
CREATE TABLE IF NOT EXISTS `staff_commands_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `top_name` varchar(100) NOT NULL DEFAULT 'NEVADA STAFF',
  `eyebrow_name` varchar(150) NOT NULL DEFAULT 'NEVADA ROLEPLAY · STAFF',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `staff_commands_settings`:
--

--
-- Despejando dados para a tabela `staff_commands_settings`
--

INSERT INTO `staff_commands_settings` (`id`, `top_name`, `eyebrow_name`, `updated_at`) VALUES
(1, 'CARMESIM STAFF', 'CARMESIM ROLEPLAY · STAFF', '2026-09-03 18:07:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `staff_coordinates`
--
-- Criação: 06/09/2026 às 14:35
--

DROP TABLE IF EXISTS `staff_coordinates`;
CREATE TABLE IF NOT EXISTS `staff_coordinates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `subcategory` varchar(100) NOT NULL DEFAULT 'Geral',
  `icon` varchar(20) NOT NULL DEFAULT '?',
  `name` varchar(180) NOT NULL,
  `cds` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_staff_coordinates_created_by` (`created_by`),
  KEY `idx_staff_coordinates_category` (`category`),
  KEY `idx_staff_coordinates_active` (`active`),
  KEY `idx_staff_coordinates_name` (`name`),
  KEY `idx_staff_coordinates_subcategory` (`subcategory`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `staff_coordinates`:
--   `created_by`
--       `staff_users` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `staff_coordinate_categories`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `staff_coordinate_categories`;
CREATE TABLE IF NOT EXISTS `staff_coordinate_categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(20) NOT NULL DEFAULT '?',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `fk_staff_coord_categories_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=349 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `staff_coordinate_categories`:
--   `created_by`
--       `staff_users` -> `id`
--

--
-- Despejando dados para a tabela `staff_coordinate_categories`
--

INSERT INTO `staff_coordinate_categories` (`id`, `name`, `icon`, `active`, `created_by`, `created_at`, `updated_at`) VALUES
(343, 'Empresas', '🏢', 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(344, 'Fazendas', '🌾', 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(345, 'Casas', '🏠', 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(346, 'Grupos', '👥', 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(347, 'Sobrenatural', '👻', 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(348, 'Xerifado', '⭐', 1, 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `staff_coordinate_seed_state`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `staff_coordinate_seed_state`;
CREATE TABLE IF NOT EXISTS `staff_coordinate_seed_state` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `initialized_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `staff_coordinate_seed_state`:
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `staff_coordinate_subcategories`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `staff_coordinate_subcategories`;
CREATE TABLE IF NOT EXISTS `staff_coordinate_subcategories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(20) NOT NULL DEFAULT '?',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coord_subcategory` (`category_id`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=873 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `staff_coordinate_subcategories`:
--   `category_id`
--       `staff_coordinate_categories` -> `id`
--

--
-- Despejando dados para a tabela `staff_coordinate_subcategories`
--

INSERT INTO `staff_coordinate_subcategories` (`id`, `category_id`, `name`, `icon`, `active`, `created_at`, `updated_at`) VALUES
(858, 343, 'Saloons', '🍺', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(859, 343, 'Ferrarias', '🔨', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(860, 343, 'Tabacarias', '🚬', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(861, 343, 'Artesanatos', '🎨', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(862, 343, 'Armarias', '🔫', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(863, 343, 'Ateliês', '✂️', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(864, 343, 'Perfumarias', '🌹', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(865, 343, 'Jornais', '📰', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(866, 343, 'Geral', '📍', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(867, 344, 'Geral', '📍', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(868, 345, 'Geral', '📍', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(869, 346, 'Geral', '📍', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(870, 347, 'Geral', '📍', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(871, 348, 'Cavalaria', '🐎', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31'),
(872, 348, 'Geral', '📍', 1, '2026-09-03 18:07:31', '2026-09-03 18:07:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `staff_users`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `staff_users`;
CREATE TABLE IF NOT EXISTS `staff_users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `username` varchar(80) NOT NULL,
  `discord_id` varchar(32) DEFAULT NULL,
  `discord_avatar` varchar(700) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('master','staff') NOT NULL DEFAULT 'staff',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `staff_users`:
--

--
-- Despejando dados para a tabela `staff_users`
--

INSERT INTO `staff_users` (`id`, `name`, `username`, `discord_id`, `discord_avatar`, `password_hash`, `role`, `active`, `created_at`) VALUES
(1, 'CEO Luci', 'luci', '207712204890439680', 'https://cdn.discordapp.com/avatars/207712204890439680/bf103cfd7e4b93e69857e1cd6b34de4a.png?size=256', '$2y$10$P1Ff/8/qm53ubq/RRyQ6E.zqu2.fTlYF8wqk0h3m6RAr5eMIZCPmW', 'master', 1, '2026-08-18 16:41:06'),
(9, 'CEO Jessy', 'jessy', '519870847310102540', 'https://cdn.discordapp.com/avatars/519870847310102540/0882b806f2c82399c990639224b0fbe4.png?size=256', '$2y$10$KZrJJVG9A.lh27OxjbemaeYX1AIh0g.cpZ634TZthMqROGxDrvfEO', 'master', 1, '2026-09-03 17:19:16'),
(12, 'COO Matheus', 'matheus', '389970494763302912', 'https://cdn.discordapp.com/avatars/389970494763302912/949d2e425565dd6c5913d239c8cc691b.png?size=256', '$2y$10$1cBfhTzntQrVImf2h3cwRujIDlUnFFIq0YHCpjl3QhzQDGyvhaiXK', 'master', 1, '2026-09-03 17:56:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `streamers`
--
-- Criação: 06/09/2026 às 14:34
-- Última atualização: 06/09/2026 às 14:41
--

DROP TABLE IF EXISTS `streamers`;
CREATE TABLE IF NOT EXISTS `streamers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `discord` varchar(120) DEFAULT NULL,
  `discord_id` varchar(32) DEFAULT NULL,
  `discord_avatar` varchar(700) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `channel_url` varchar(500) DEFAULT NULL,
  `category` enum('novato','oficial','afiliado') NOT NULL DEFAULT 'novato',
  `access_code` varchar(100) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `joined_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_code` (`access_code`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `streamers`:
--

--
-- Despejando dados para a tabela `streamers`
--

INSERT INTO `streamers` (`id`, `name`, `discord`, `discord_id`, `discord_avatar`, `platform`, `channel_url`, `category`, `access_code`, `points`, `active`, `joined_at`, `notes`, `webhook_url`, `created_at`, `updated_at`) VALUES
(7, 'Teste', '207712204890439680', '207712204890439680', 'https://cdn.discordapp.com/avatars/207712204890439680/bf103cfd7e4b93e69857e1cd6b34de4a.png?size=256', 'Twitch', '', 'novato', '5AF3F8B3', 0, 1, '2026-09-03', '', NULL, '2026-09-03 18:15:58', '2026-09-03 18:15:58'),
(8, 'Teste', '207712204890439680', '207712204890439680', 'https://cdn.discordapp.com/avatars/207712204890439680/bf103cfd7e4b93e69857e1cd6b34de4a.png?size=256', 'Twitch', 'https://www.twitch.tv/morningfall', 'novato', '61RWAYRR', 0, 1, '2026-09-03', '', NULL, '2026-09-03 18:16:33', '2026-09-03 18:16:33'),
(10, 'teste', '207712204890439680', '207712204890439680', 'https://cdn.discordapp.com/avatars/207712204890439680/bf103cfd7e4b93e69857e1cd6b34de4a.png?size=256', 'Twitch', 'https://www.twitch.tv/morningfall', 'novato', '30ICWKB2', 0, 1, '2026-09-03', '', NULL, '2026-09-03 18:23:07', '2026-09-03 18:23:07'),
(11, 'Carou', 'carou', '1167944586937761857', 'https://cdn.discordapp.com/avatars/1167944586937761857/f40f6958f2fd199533dab5ee908ba8eb.png?size=256', 'Twitch', 'https://www.twitch.tv/car0ul', 'oficial', 'CNBSDBUG', 4, 1, '2026-09-05', '', NULL, '2026-09-05 20:07:24', '2026-09-06 14:41:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `streamer_channels`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `streamer_channels`;
CREATE TABLE IF NOT EXISTS `streamer_channels` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `platform` enum('twitch','youtube') NOT NULL,
  `channel_url` varchar(700) NOT NULL,
  `channel_login` varchar(160) DEFAULT NULL,
  `external_id` varchar(160) DEFAULT NULL,
  `display_name` varchar(180) DEFAULT NULL,
  `required_category` varchar(180) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_synced_at` datetime DEFAULT NULL,
  `last_error` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_streamer_platform` (`streamer_id`,`platform`),
  KEY `idx_channels_monitor` (`platform`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `streamer_channels`:
--   `streamer_id`
--       `streamers` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `streamer_platforms`
--
-- Criação: 06/09/2026 às 14:39
-- Última atualização: 06/09/2026 às 14:39
--

DROP TABLE IF EXISTS `streamer_platforms`;
CREATE TABLE IF NOT EXISTS `streamer_platforms` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `platform` enum('twitch','youtube','kick','tiktok') NOT NULL,
  `username` varchar(150) DEFAULT NULL,
  `channel_url` varchar(255) DEFAULT NULL,
  `channel_id` varchar(255) DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `followers` int(10) UNSIGNED DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) DEFAULT 1,
  `last_sync` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_streamer_platform` (`streamer_id`,`platform`),
  KEY `idx_streamer` (`streamer_id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_platform_username` (`platform`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONAMENTOS PARA TABELAS `streamer_platforms`:
--   `streamer_id`
--       `streamers` -> `id`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `vods`
--
-- Criação: 06/09/2026 às 14:34
--

DROP TABLE IF EXISTS `vods`;
CREATE TABLE IF NOT EXISTS `vods` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `streamer_id` int(10) UNSIGNED NOT NULL,
  `url` varchar(700) NOT NULL,
  `vod_date` date NOT NULL,
  `duration_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `points_awarded` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vod_streamer` (`streamer_id`,`vod_date`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONAMENTOS PARA TABELAS `vods`:
--   `streamer_id`
--       `streamers` -> `id`
--

--
-- Despejando dados para a tabela `vods`
--

INSERT INTO `vods` (`id`, `streamer_id`, `url`, `vod_date`, `duration_minutes`, `points_awarded`, `created_at`) VALUES
(15, 11, 'https://www.twitch.tv/videos/2863757349', '2026-09-05', 247, 4, '2026-09-05 20:11:24');

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `fk_activities_staff` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activities_streamer` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `admin_commands`
--
ALTER TABLE `admin_commands`
  ADD CONSTRAINT `fk_admin_commands_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `content_submissions`
--
ALTER TABLE `content_submissions`
  ADD CONSTRAINT `content_submissions_ibfk_1` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_submissions_ibfk_2` FOREIGN KEY (`collab_streamer_id`) REFERENCES `streamers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `content_submissions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `content_submissions_ibfk_4` FOREIGN KEY (`vod_id`) REFERENCES `vods` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `content_submission_images`
--
ALTER TABLE `content_submission_images`
  ADD CONSTRAINT `content_submission_images_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `content_submissions` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `live_monitor_events`
--
ALTER TABLE `live_monitor_events`
  ADD CONSTRAINT `fk_live_monitor_events_channel` FOREIGN KEY (`channel_id`) REFERENCES `streamer_channels` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `live_monitor_sessions`
--
ALTER TABLE `live_monitor_sessions`
  ADD CONSTRAINT `fk_live_monitor_sessions_channel` FOREIGN KEY (`channel_id`) REFERENCES `streamer_channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_live_monitor_sessions_vod` FOREIGN KEY (`vod_id`) REFERENCES `vods` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `penalties`
--
ALTER TABLE `penalties`
  ADD CONSTRAINT `fk_penalties_staff` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_penalties_streamer` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD CONSTRAINT `fk_points_staff` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_points_streamer` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pricing_products`
--
ALTER TABLE `pricing_products`
  ADD CONSTRAINT `pricing_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `pricing_categories` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `raffles`
--
ALTER TABLE `raffles`
  ADD CONSTRAINT `raffles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `raffle_entries`
--
ALTER TABLE `raffle_entries`
  ADD CONSTRAINT `raffle_entries_ibfk_1` FOREIGN KEY (`raffle_id`) REFERENCES `raffles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raffle_entries_ibfk_2` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `raffle_winners`
--
ALTER TABLE `raffle_winners`
  ADD CONSTRAINT `raffle_winners_ibfk_1` FOREIGN KEY (`raffle_id`) REFERENCES `raffles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raffle_winners_ibfk_2` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `redemptions`
--
ALTER TABLE `redemptions`
  ADD CONSTRAINT `fk_redemptions_approved` FOREIGN KEY (`approved_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_redemptions_delivered` FOREIGN KEY (`delivered_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_redemptions_prize` FOREIGN KEY (`prize_id`) REFERENCES `prizes` (`id`),
  ADD CONSTRAINT `fk_redemptions_streamer` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`);

--
-- Restrições para tabelas `staff_coordinates`
--
ALTER TABLE `staff_coordinates`
  ADD CONSTRAINT `fk_staff_coordinates_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `staff_coordinate_categories`
--
ALTER TABLE `staff_coordinate_categories`
  ADD CONSTRAINT `fk_staff_coord_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `staff_coordinate_subcategories`
--
ALTER TABLE `staff_coordinate_subcategories`
  ADD CONSTRAINT `fk_staff_coord_subcategories_category` FOREIGN KEY (`category_id`) REFERENCES `staff_coordinate_categories` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `streamer_channels`
--
ALTER TABLE `streamer_channels`
  ADD CONSTRAINT `fk_streamer_channels_streamer` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `streamer_platforms`
--
ALTER TABLE `streamer_platforms`
  ADD CONSTRAINT `fk_streamer_platforms_streamer` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `vods`
--
ALTER TABLE `vods`
  ADD CONSTRAINT `fk_vods_streamer` FOREIGN KEY (`streamer_id`) REFERENCES `streamers` (`id`) ON DELETE CASCADE;


--
-- Metadata
--
USE `phpmyadmin`;

--
-- Metadata para tabela activities
--

--
-- Metadata para tabela admin_commands
--

--
-- Metadata para tabela audit_logs
--

--
-- Metadata para tabela content_submissions
--

--
-- Metadata para tabela content_submission_images
--

--
-- Metadata para tabela live_monitor_events
--

--
-- Metadata para tabela live_monitor_logs
--

--
-- Metadata para tabela live_monitor_sessions
--

--
-- Metadata para tabela live_monitor_settings
--

--
-- Metadata para tabela live_monitor_titles
--

--
-- Metadata para tabela penalties
--

--
-- Metadata para tabela player_commands
--

--
-- Metadata para tabela point_transactions
--

--
-- Metadata para tabela pricing_categories
--

--
-- Metadata para tabela pricing_products
--

--
-- Metadata para tabela prizes
--

--
-- Metadata para tabela raffles
--

--
-- Metadata para tabela raffle_entries
--

--
-- Metadata para tabela raffle_winners
--

--
-- Metadata para tabela redemptions
--

--
-- Metadata para tabela settings
--

--
-- Metadata para tabela site_companies
--

--
-- Metadata para tabela site_team_members
--

--
-- Metadata para tabela staff_commands_settings
--

--
-- Metadata para tabela staff_coordinates
--

--
-- Metadata para tabela staff_coordinate_categories
--

--
-- Metadata para tabela staff_coordinate_seed_state
--

--
-- Metadata para tabela staff_coordinate_subcategories
--

--
-- Metadata para tabela staff_users
--

--
-- Metadata para tabela streamers
--

--
-- Metadata para tabela streamer_channels
--

--
-- Metadata para tabela streamer_platforms
--

--
-- Metadata para tabela vods
--

--
-- Metadata para o banco de dados holyurso_morningfall
--
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
