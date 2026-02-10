-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 09/02/2026 às 19:48
-- Versão do servidor: 10.9.1-MariaDB
-- Versão do PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `dev_pyrosoft`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_cron_events`
--

DROP TABLE IF EXISTS `tb_cron_events`;
CREATE TABLE `tb_cron_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hook` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `args` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL,
  `recurrence` int(10) UNSIGNED DEFAULT NULL,
  `mode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tb_cron_events`
--

INSERT INTO `tb_cron_events` (`id`, `slug`, `hook`, `args`, `timestamp`, `recurrence`, `mode`, `created_at`) VALUES
(2371, 'Clean up expired tokens', 'token_cleanup_expired', '[]', 1769990344, 60, NULL, '2026-02-01 20:58:04'),
(2372, 'Verify system key', 'verify_system_key', '[]', 1769990584, 300, NULL, '2026-02-01 20:58:04'),
(2373, 'Clean temp uploads', 'clean_temp_uploads', '[]', 1770076684, 86400, NULL, '2026-02-01 20:58:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_cruds`
--

DROP TABLE IF EXISTS `tb_cruds`;
CREATE TABLE `tb_cruds` (
  `id` int(11) NOT NULL,
  `piece_name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `type_crud` varchar(10) NOT NULL,
  `attributes` varchar(50) DEFAULT NULL,
  `div_attributes` varchar(200) DEFAULT NULL,
  `form_method` varchar(10) DEFAULT NULL,
  `form_action` varchar(500) DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `submits_count` int(11) DEFAULT 0,
  `table_crud` varchar(20) DEFAULT NULL,
  `foreign_key` varchar(20) DEFAULT NULL,
  `form_settings` varchar(500) DEFAULT NULL,
  `list_settings` varchar(500) DEFAULT NULL,
  `limit_results` varchar(11) DEFAULT NULL,
  `related_to` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '''table''',
  `crud_id` varchar(11) DEFAULT NULL,
  `crud_panel` text DEFAULT NULL,
  `pages_list` text DEFAULT NULL,
  `custom_urls` text DEFAULT NULL,
  `result_page` varchar(11) DEFAULT NULL,
  `permission_type` varchar(15) NOT NULL DEFAULT '''except_these''',
  `login_required` int(11) DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `tb_cruds`
--

INSERT INTO `tb_cruds` (`id`, `piece_name`, `slug`, `type_crud`, `attributes`, `div_attributes`, `form_method`, `form_action`, `views_count`, `submits_count`, `table_crud`, `foreign_key`, `form_settings`, `list_settings`, `limit_results`, `related_to`, `crud_id`, `crud_panel`, `pages_list`, `custom_urls`, `result_page`, `permission_type`, `login_required`, `status_id`, `created_at`, `updated_at`) VALUES
(176, 'Edit User', 'main-edit-user', 'update', '', '', 'POST', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"without_reload\":\"1\",\"view_mode\":\"default\",\"container\":\"1\",\"steps_form\":{\"save_between_steps\":\"1\",\"one_step_at_a_time\":\"1\",\"show_progess\":\"1\",\"show_steps\":\"1\",\"progess_style\":\"progress_steps_detailed\",\"progress_color\":\"secondary\",\"button_name_send\":\"Enviar\"},\"delay\":\"\"}', '{\"limit_results\":\"\"}', '', 'table', '182', '{\"show_panel\":\"1\"}', '[]', '[]', '', '', 1, 1, '2025-01-21 01:42:16', '2026-02-09 16:48:14'),
(177, 'List Users', 'main-list-users', 'list', '', '', '', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"view_mode\":\"default\",\"steps_form\":{\"progess_style\":\"progress_bar\",\"progress_color\":\"\",\"button_name_send\":\"\"},\"delay\":\"\"}', '{\"0\":\"show_id\",\"1\":\"data_table\",\"2\":\"data_table_async\",\"limit_results\":\"\"}', '', 'table', '182', '{\"show_panel\":\"1\"}', '[]', '[]', '', '', 1, 1, '2025-01-21 01:42:17', '2026-02-09 16:48:14'),
(179, 'Create User', 'main-create-user', 'insert', '', '', 'POST', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"without_reload\":\"1\",\"view_mode\":\"default\",\"steps_form\":{\"progess_style\":\"\",\"progress_color\":\"\",\"button_name_send\":\"\"},\"delay\":\"\"}', '{\"limit_results\":\"\"}', '', 'table', '182', '{\"show_panel\":\"1\"}', '[]', '[]', '', '', 0, 1, '2025-02-12 01:36:22', '2026-02-09 16:41:27'),
(182, 'User Master', 'main-user-master', 'master', '', '', '', '[]', 0, 0, 'tb_users', 'user_id', '[]', '[]', '', 'table', '', '[]', '{\"list_pg\":\"63\",\"insert\":{\"mode\":\"page\",\"page\":\"310\",\"piece\":\"179\"},\"update\":{\"mode\":\"page\",\"page\":\"77\",\"piece\":\"176\"},\"view\":{\"mode\":\"modal\",\"page\":\"84\",\"piece\":\"231\"}}', '[]', '', 'only_these', 0, 1, '2025-03-29 06:37:49', '2026-02-09 16:42:18'),
(231, 'View User', 'main-view-user', 'view', '', '', 'POST', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"view_mode\":\"default\",\"steps_form\":{\"progess_style\":\"\",\"progress_color\":\"\",\"button_name_send\":\"\"},\"delay\":\"\"}', '{\"0\":\"show_id\",\"1\":\"show_list_pg\",\"2\":\"data_table\",\"limit_results\":\"\"}', '', 'table', '182', '{\"show_panel\":\"1\"}', '[]', '[]', '', '', 0, 1, '2025-07-22 01:30:15', '2026-02-01 21:55:25'),
(239, 'General Settings', 'general-settings', 'master', '', '', '', '[]', 0, 0, 'tb_info', '', '[]', '[]', NULL, 'table', NULL, '[]', '{\"list_pg\":\"\",\"insert\":{\"mode\":\"page\",\"page\":\"\",\"piece\":\"\"},\"update\":{\"mode\":\"modal\",\"page\":\"\",\"piece\":\"\"},\"view\":{\"mode\":\"page\",\"page\":\"\",\"piece\":\"\"}}', '[]', '', 'only_these', 0, 1, '2025-09-21 05:38:35', '2026-02-09 16:42:29'),
(240, 'Branding Settings', 'branding-settings', 'update', '', '', '', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"without_reload\":\"1\",\"view_mode\":\"tabs_form\",\"steps_form\":{\"progess_style\":\"\",\"progress_color\":\"\",\"button_name_send\":\"\"},\"delay\":\"\"}', '{\"limit_results\":\"\"}', NULL, 'system_info', '239', '[]', '[]', '[]', '', '', 1, 1, '2025-09-30 01:50:13', '2026-02-09 16:48:14'),
(242, 'Business Settings', 'business-settings', 'update', NULL, '', '', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"without_reload\":\"1\",\"view_mode\":\"tabs_form\",\"steps_form\":{\"progess_style\":\"\",\"progress_color\":\"\",\"button_name_send\":\"\"},\"delay\":\"\"}', '{\"limit_results\":\"\"}', '', 'system_info', '239', '[]', '[]', '[]', '', '', 1, 1, '2025-10-05 04:59:54', '2026-02-09 16:48:14'),
(251, 'Maintenance Settings', 'maintenance-settings', 'update', '', '', '', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"without_reload\":\"1\",\"view_mode\":\"tabs_form\",\"steps_form\":{\"progess_style\":\"\",\"progress_color\":\"\",\"button_name_send\":\"\"},\"delay\":\"\"}', '{\"limit_results\":\"\"}', NULL, 'system_info', '239', '[]', '[]', '[]', '', '', 0, 4, '2025-10-07 00:33:56', '2026-02-09 16:48:14'),
(254, '[Summary] List Users', 'main-summary-list-users', 'list', '', '', '', '{\"type\":\"api\",\"action\":\"form-processor\"}', 0, 0, '', '', '{\"view_mode\":\"default\",\"steps_form\":{\"progess_style\":\"progress_bar\",\"progress_color\":\"\",\"button_name_send\":\"\"},\"delay\":\"\"}', '{\"limit_results\":\"5\"}', '', 'table', '182', '{\"show_panel\":\"1\",\"0\":\"show_name\",\"1\":\"minimize_actions\"}', '[]', '[]', '', '', 1, 1, '2026-02-01 02:52:41', '2026-02-09 16:48:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_cruds_fields`
--

DROP TABLE IF EXISTS `tb_cruds_fields`;
CREATE TABLE `tb_cruds_fields` (
  `id` int(11) NOT NULL,
  `type_field` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscribers_only` char(1) DEFAULT NULL,
  `view_in_list` varchar(11) DEFAULT NULL,
  `order_reg` int(11) DEFAULT NULL,
  `is_model` int(11) DEFAULT NULL,
  `crud_id` int(11) DEFAULT NULL,
  `status_id` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `tb_cruds_fields`
--

INSERT INTO `tb_cruds_fields` (`id`, `type_field`, `name`, `settings`, `subscribers_only`, `view_in_list`, `order_reg`, `is_model`, `crud_id`, `status_id`) VALUES
(228, 'basic', 'first_name', '{\"label\":\"Nome\",\"type\":\"text\",\"input_id\":\"nome\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 1, NULL, 176, '1'),
(229, 'basic', 'login', '{\"label\":\"Nickname\",\"type\":\"text\",\"input_id\":\"login\",\"attachment\":\"{\\\"prepend\\\":\\\"@\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 3, NULL, 176, '1'),
(232, 'basic', 'first_name', '{\"label\":\"Nome\",\"type\":\"text\",\"input_id\":\"nome\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '1', 1, NULL, 177, '1'),
(233, 'basic', 'login', '{\"label\":\"Nickname\",\"type\":\"text\",\"input_id\":\"login\",\"attachment\":\"{\\\"prepend\\\":\\\"@\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '1', 2, NULL, 177, '1'),
(242, 'submit_button', 'process-form', '{\"Value\":\"Enviar\",\"class\":\"btn btn-primary\",\"allow_schedule\":\"1\",\"input_id\":\"process-form\",\"Query\":\"\"}', '', '', 9, NULL, 176, '1'),
(252, 'basic', 'last_name', '{\"label\":\"Sobrenome\",\"type\":\"text\",\"input_id\":\"nome[singular]\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 2, NULL, 176, '1'),
(258, 'status_selector', 'status_id', '{\"function_proccess\":\"user_status\",\"Value\":\"1\",\"input_id\":\"status_id\",\"Query\":\"\"}', '', '', 8, NULL, 176, '1'),
(264, 'hr', '', '{\"Query\":\"\"}', '', '', 6, NULL, 176, '1'),
(268, 'basic', 'first_name', '{\"label\":\"Nome\",\"type\":\"text\",\"input_id\":\"nome\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 1, 0, 179, '1'),
(269, 'basic', 'login', '{\"label\":\"Nickname\",\"type\":\"text\",\"input_id\":\"login\",\"attachment\":\"{\\\"prepend\\\":\\\"@\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 3, 0, 179, '1'),
(270, 'basic', 'password', '{\"label\":\"Senha\",\"type\":\"password\",\"input_id\":\"senha\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 4, 0, 179, '1'),
(272, 'submit_button', 'process-form', '{\"Value\":\"Enviar\",\"class\":\"btn btn-primary\",\"allow_schedule\":\"1\",\"input_id\":\"process-form\",\"Query\":\"\"}', '', '', 6, 0, 179, '1'),
(273, 'basic', 'last_name', '{\"label\":\"Sobrenome\",\"type\":\"text\",\"input_id\":\"nome[singular]\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 2, 0, 179, '1'),
(275, 'status_selector', 'status_id', '{\"function_proccess\":\"general_status\",\"input_id\":\"status_id\",\"Query\":\"\"}', '', '', 5, 0, 179, '1'),
(283, 'basic', 'email', '{\"label\":\"E-mail\",\"type\":\"email\",\"input_id\":\"email\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '1', 3, NULL, 177, '1'),
(285, 'basic', 'email', '{\"label\":\"E-mail\",\"type\":\"email\",\"input_id\":\"email\",\"function_proccess\":\"auto_fill_name_by_cpf({email})\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"@email.com\\\"}\",\"Required\":\"1\",\"run_before_action\":\"1\",\"unique_key\":\"1\",\"Query\":\"\"}', '', '', 4, NULL, 176, '1'),
(319, 'selection_type', 'role_id[]', '{\"label\":\"Roles\",\"type\":\"checkbox\",\"variation\":\"balloons\",\"run_after_action\":\"1\",\"function_proccess\":\"edit_user_role_assignments({register_id}, {role_id})\",\"function_view\":\"user_roles_to_string\",\"options_resolver\":\"get_roles_by_user_id()\",\"Query\":\"\"}', '', '1', 7, NULL, 176, '1'),
(341, 'status_selector', 'status_id', '{\"function_proccess\":\"user_status\",\"input_id\":\"status_id\",\"Query\":\"\"}', '', '1', 4, NULL, 177, '1'),
(342, 'basic', 'first_name', '{\"label\":\"Nome\",\"type\":\"text\",\"input_id\":\"nome\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '1', 1, NULL, 231, '1'),
(343, 'basic', 'login', '{\"label\":\"Nickname\",\"type\":\"text\",\"input_id\":\"login\",\"attachment\":\"{\\\"prepend\\\":\\\"@\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 4, NULL, 231, '1'),
(346, 'submit_button', 'process-form', '{\"Value\":\"Enviar\",\"class\":\"btn btn-primary\",\"input_id\":\"process-form\",\"Query\":\"\"}', '', '', 7, NULL, 231, '1'),
(347, 'basic', 'last_name', '{\"label\":\"Sobrenome\",\"type\":\"text\",\"input_id\":\"nome[singular]\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '1', 2, NULL, 231, '1'),
(349, 'status_selector', 'status_id', '{\"function_proccess\":\"user_status\",\"Value\":\"1\",\"input_id\":\"status_id\",\"Query\":\"\"}', '', '', 6, NULL, 231, '1'),
(354, 'basic', 'email', '{\"label\":\"E-mail\",\"type\":\"email\",\"input_id\":\"email\",\"function_proccess\":\"auto_fill_name_by_cpf({email}, {register_id})\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"@email.com\\\"}\",\"Required\":\"1\",\"run_before_action\":\"1\",\"Query\":\"\"}', '', '', 3, NULL, 231, '1'),
(361, 'selection_type', 'role_id[]', '{\"label\":\"Roles\",\"type\":\"checkbox\",\"variation\":\"inline\",\"run_after_action\":\"1\",\"function_proccess\":\"edit_user_role_assignments({register_id}, {role_id})\",\"function_view\":\"user_roles_to_string({register_id})\",\"options_resolver\":\"get_roles_by_user_id()\",\"Query\":\"\"}', '', '1', 5, NULL, 231, '1'),
(442, 'selection_type', 'role_id[]', '{\"label\":\"Roles\",\"type\":\"checkbox\",\"variation\":\"inline\",\"run_after_action\":\"1\",\"function_proccess\":\"edit_user_role_assignments({register_id}, {role_id})\",\"function_view\":\"user_roles_to_string({register_id})\",\"options_resolver\":\"get_roles_by_user_id()\",\"Query\":\"\"}', '', '', 5, NULL, 177, '1'),
(451, 'divider', '', '{\"title\":\"Branding\",\"Query\":\"\"}', '', '', 1, NULL, 240, '1'),
(452, 'basic', 'name', '{\"depth\":\"1\",\"label\":\"Name\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 2, NULL, 240, '1'),
(453, 'basic', 'short_name', '{\"depth\":\"1\",\"label\":\"Short Name\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 3, NULL, 240, '1'),
(454, 'basic', 'title', '{\"depth\":\"1\",\"label\":\"Page title\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 4, NULL, 240, '1'),
(455, 'upload', 'favicon', '{\"depth\":\"1\",\"label\":\"Favicon\",\"Src\":\"brand\",\"accepted_extensions\":\"image\\/png, image\\/jpeg, image\\/pjpeg, image\\/gif, image\\/webp\",\"final_name\":\"favicon.png\",\"type\":\"images\",\"Required\":\"1\",\"visibility\":\"public\",\"image_size\":\"48x48\",\"Query\":\"\"}', '', '', 5, NULL, 240, '1'),
(456, 'shortcode', '', '{\"depth\":\"1\",\"content\":\"<h4>Brand colors<\\/h4>\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 6, NULL, 240, '1'),
(457, 'basic', 'brand_colors[primary]', '{\"depth\":\"1\",\"label\":\"Primary color\",\"type\":\"color\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-md-6 col-xl-3\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 7, NULL, 240, '1'),
(458, 'basic', 'brand_colors[secondary]', '{\"depth\":\"1\",\"label\":\"Secondary color\",\"type\":\"color\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-md-6 col-xl-3\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 8, NULL, 240, '1'),
(459, 'basic', 'brand_colors[tertiary]', '{\"depth\":\"1\",\"label\":\"Tertiary color\",\"type\":\"color\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-md-6 col-xl-3\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 9, NULL, 240, '1'),
(460, 'basic', 'brand_colors[quaternary]', '{\"depth\":\"1\",\"label\":\"Quaternary color\",\"type\":\"color\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-md-6 col-xl-3\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 10, NULL, 240, '1'),
(463, 'shortcode', '', '{\"depth\":\"1\",\"content\":\"<h4>Main URL<\\/h4>\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 11, NULL, 240, '1'),
(464, 'basic', 'main_page[title]', '{\"depth\":\"1\",\"label\":\"Page title\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 12, NULL, 240, '1'),
(465, 'selection_type', 'main_page[slug]', '{\"depth\":\"1\",\"label\":\"Page URL\",\"type\":\"search\",\"variation\":\"original\",\"Required\":\"1\",\"options_resolver\":\"get_pages_for_select(\\\"slug\\\")\",\"Query\":\"\"}', '', '', 13, NULL, 240, '1'),
(473, 'divider', '', '{\"title\":\"Contact\",\"Query\":\"\"}', '', '', 14, NULL, 240, '1'),
(475, 'divider', '', '{\"title\":\"SEO\",\"Query\":\"\"}', '', '', 26, NULL, 240, '1'),
(476, 'seo_form', 'seo', '{\"depth\":\"1\",\"mode\":\"common\",\"Query\":\"\"}', '', '', 27, NULL, 240, '1'),
(477, 'field_repeater', 'contact', '{\"depth\":\"1\",\"label\":\"Concatcts\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 15, NULL, 240, '1'),
(478, 'basic', 'contact_type', '{\"depth\":\"2\",\"label\":\"Contact type\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 16, NULL, 240, '1'),
(479, 'basic', 'phone', '{\"depth\":\"2\",\"label\":\"Phone\",\"type\":\"text\",\"class\":\"mask-phone\",\"input_id\":\"telephone\",\"function_proccess\":\"clean_number\",\"function_view\":\"format_phone\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '1', 17, NULL, 240, '1'),
(480, 'basic', 'email', '{\"depth\":\"2\",\"label\":\"E-mail\",\"type\":\"email\",\"input_id\":\"email\",\"function_proccess\":\"auto_fill_name_by_cpf({email})\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"@email.com\\\"}\",\"Required\":\"1\",\"run_before_action\":\"1\",\"Query\":\"\"}', '', '', 18, NULL, 240, '1'),
(481, 'basic', 'country', '{\"depth\":\"2\",\"label\":\"Country\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-sm-6 col-md-3\",\"Query\":\"\"}', '', '', 19, NULL, 240, '1'),
(482, 'basic', 'language', '{\"depth\":\"2\",\"label\":\"Language\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-sm-6 col-md-3 col-xl-3\",\"Query\":\"\"}', '', '', 20, NULL, 240, '1'),
(483, 'field_repeater', 'social_media', '{\"depth\":\"1\",\"label\":\"Social medias\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 22, NULL, 240, '1'),
(484, 'basic', 'name', '{\"depth\":\"2\",\"label\":\"Name\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 23, NULL, 240, '1'),
(485, 'basic', 'icon', '{\"depth\":\"2\",\"label\":\"Icon\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 24, NULL, 240, '1'),
(486, 'basic', 'url', '{\"depth\":\"2\",\"label\":\"URL\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 25, NULL, 240, '1'),
(488, 'hr', '', '{\"depth\":\"1\",\"Query\":\"\"}', '', '', 21, NULL, 240, '1'),
(503, 'divider', '', '{\"title\":\"Business\",\"Query\":\"\"}', '', '', 1, 0, 242, '1'),
(504, 'basic', 'taxID', '{\"depth\":\"1\",\"label\":\"Tax  ID\",\"type\":\"text\",\"class\":\"mask-cnpj\",\"function_proccess\":\"clean_number\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-md-6 col-xl-3\",\"Query\":\"\"}', '', '', 2, 0, 242, '1'),
(505, 'basic', 'price_range', '{\"depth\":\"1\",\"label\":\"Price range\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 3, 0, 242, '1'),
(506, 'field_repeater', 'about_business[services]', '{\"depth\":\"1\",\"label\":\"List your services\",\"storage_mode\":\"json\",\"table\":\"tb_info\",\"add_btn_title\":\"Adicionar servi\\u00e7o\",\"Query\":\"\"}', '', '', 5, 0, 242, '1'),
(507, 'basic', 'name', '{\"depth\":\"2\",\"label\":\"Name\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 6, 0, 242, '1'),
(508, 'basic', 'description', '{\"depth\":\"2\",\"label\":\"Description\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 7, 0, 242, '1'),
(509, 'selection_type', 'about_business[type]', '{\"depth\":\"1\",\"label\":\"Type\",\"type\":\"select\",\"variation\":\"original\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"local_business\\\",\\\"display\\\":\\\"Local business\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"Organization\\\",\\\"display\\\":\\\"Organization\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 4, 0, 242, '1'),
(511, 'address_form', 'address', '{\"depth\":\"1\",\"label\":\"Endere\\u00e7o\",\"function_proccess\":\"1\",\"Query\":\"\"}', '', '', 13, 0, 242, '1'),
(526, 'shortcode', '', '{\"depth\":\"1\",\"content\":\"<h4>Address<\\/h4>\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 12, 0, 242, '1'),
(527, 'divider', '', '{\"title\":\"Local\",\"Query\":\"\"}', '', '', 8, NULL, 242, '1'),
(528, 'shortcode', '', '{\"depth\":\"1\",\"content\":\"<h4>Geo<\\/h4>\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 9, NULL, 242, '1'),
(529, 'basic', 'geo[latitude]', '{\"depth\":\"1\",\"label\":\"Laditude\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 10, NULL, 242, '1'),
(530, 'basic', 'geo[longitude]', '{\"depth\":\"1\",\"label\":\"Longitude\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 11, NULL, 242, '1'),
(531, 'divider', '', '{\"title\":\"Organization\",\"Query\":\"\"}', '', '', 43, NULL, 242, '1'),
(532, 'divider', '', '{\"title\":\"Founding\",\"Query\":\"\"}', '', '', 14, NULL, 242, '1'),
(533, 'basic', 'founding[date]', '{\"depth\":\"1\",\"label\":\"Date\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 16, NULL, 242, '1'),
(534, 'basic', 'founding[location]', '{\"depth\":\"1\",\"label\":\"Location\",\"type\":\"text\",\"Alert\":\"Use ISO-8601\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 15, NULL, 242, '1'),
(535, 'field_repeater', 'founding[founder]', '{\"depth\":\"1\",\"label\":\"Founders\",\"storage_mode\":\"json\",\"is_orderable\":\"1\",\"Query\":\"\"}', '', '', 17, NULL, 242, '1'),
(536, 'basic', 'name', '{\"depth\":\"2\",\"label\":\"Name\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 18, NULL, 242, '1'),
(537, 'basic', 'job_title', '{\"depth\":\"2\",\"label\":\"Job Title\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 19, NULL, 242, '1'),
(538, 'basic', 'url', '{\"depth\":\"2\",\"label\":\"URL\",\"type\":\"url\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 20, NULL, 242, '1'),
(539, 'basic', 'organization_chart[parent]', '{\"depth\":\"1\",\"label\":\"Parent\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 44, NULL, 242, '1'),
(544, 'divider', '', '{\"title\":\"Opening\",\"Query\":\"\"}', '', '', 21, NULL, 242, '1'),
(545, 'field_repeater', 'organization_chart[opening_hours][Monday]', '{\"depth\":\"1\",\"label\":\"Monday\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 22, NULL, 242, '1'),
(546, 'field_repeater', 'organization_chart[opening_hours][Tuesday]', '{\"depth\":\"1\",\"label\":\"Tuesday\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 25, NULL, 242, '1'),
(547, 'field_repeater', 'organization_chart[opening_hours][Wednesday]', '{\"depth\":\"1\",\"label\":\"Wednesday\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 28, NULL, 242, '1'),
(548, 'field_repeater', 'organization_chart[opening_hours][Thursday]', '{\"depth\":\"1\",\"label\":\"Thursday\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 31, NULL, 242, '1'),
(549, 'field_repeater', 'organization_chart[opening_hours][Friday]', '{\"depth\":\"1\",\"label\":\"Friday\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 34, NULL, 242, '1'),
(550, 'field_repeater', 'organization_chart[opening_hours][Saturday]', '{\"depth\":\"1\",\"label\":\"Saturday\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 37, NULL, 242, '1'),
(551, 'field_repeater', 'organization_chart[opening_hours][Sunday]', '{\"depth\":\"1\",\"label\":\"Sunday\",\"storage_mode\":\"json\",\"Query\":\"\"}', '', '', 40, NULL, 242, '1'),
(552, 'basic', 'opens', '{\"depth\":\"2\",\"label\":\"Opens\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 23, NULL, 242, '1'),
(553, 'basic', 'closes', '{\"depth\":\"2\",\"label\":\"Closes\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 24, NULL, 242, '1'),
(554, 'basic', 'opens', '{\"depth\":\"2\",\"label\":\"Opens\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 26, NULL, 242, '1'),
(555, 'basic', 'closes', '{\"depth\":\"2\",\"label\":\"Closes\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 27, NULL, 242, '1'),
(556, 'basic', 'opens', '{\"depth\":\"2\",\"label\":\"Opens\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 29, NULL, 242, '1'),
(557, 'basic', 'closes', '{\"depth\":\"2\",\"label\":\"Closes\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 30, NULL, 242, '1'),
(558, 'basic', 'opens', '{\"depth\":\"2\",\"label\":\"Opens\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 32, NULL, 242, '1'),
(559, 'basic', 'closes', '{\"depth\":\"2\",\"label\":\"Closes\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 33, NULL, 242, '1'),
(560, 'basic', 'opens', '{\"depth\":\"2\",\"label\":\"Opens\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 35, NULL, 242, '1'),
(561, 'basic', 'closes', '{\"depth\":\"2\",\"label\":\"Closes\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 36, NULL, 242, '1'),
(562, 'basic', 'opens', '{\"depth\":\"2\",\"label\":\"Opens\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 38, NULL, 242, '1'),
(563, 'basic', 'closes', '{\"depth\":\"2\",\"label\":\"Closes\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 39, NULL, 242, '1'),
(564, 'basic', 'opens', '{\"depth\":\"2\",\"label\":\"Opens\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 41, NULL, 242, '1'),
(565, 'basic', 'closes', '{\"depth\":\"2\",\"label\":\"Closes\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 42, NULL, 242, '1'),
(566, 'divider', '', '{\"title\":\"E-mail\",\"Query\":\"\"}', '', '', 14, NULL, 251, '1'),
(567, 'selection_type', 'email_config[enable_sending]', '{\"depth\":\"1\",\"label\":\"Enable sending\",\"type\":\"radio\",\"variation\":\"inline\",\"Required\":\"1\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Yes\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"0\\\",\\\"display\\\":\\\"No\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 15, NULL, 251, '1'),
(568, 'selection_type', 'email_config[plugin]', '{\"depth\":\"1\",\"label\":\"Provider\",\"type\":\"select\",\"variation\":\"original\",\"options_resolver\":\"$email_providers\",\"Query\":\"\"}', '', '', 16, NULL, 251, '1'),
(569, 'divider', '', '{\"title\":\"Access\",\"Query\":\"\"}', '', '', 17, NULL, 251, '1'),
(570, 'selection_type', 'login_settings[password_must]', '{\"depth\":\"1\",\"label\":\"Password must has\",\"type\":\"switch\",\"variation\":\"original\",\"size\":\"col-md-6\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Numbers\\\",\\\"name\\\":\\\"login_settings[password_must][has_number]\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Upper\\\",\\\"name\\\":\\\"login_settings[password_must][has_upper]\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"3\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Special characters\\\",\\\"name\\\":\\\"login_settings[password_must][has_special_characters]\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 20, NULL, 251, '1'),
(571, 'basic', 'login_settings[password_must][length][min]', '{\"depth\":\"1\",\"label\":\"Min\",\"type\":\"number\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-sm-6 col-md-3\",\"Required\":\"1\",\"Query\":\"\"}', '', '', 21, NULL, 251, '1'),
(572, 'basic', 'login_settings[password_must][length][max]', '{\"depth\":\"1\",\"label\":\"Max\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-sm-6 col-md-3\",\"Query\":\"\"}', '', '', 22, NULL, 251, '1'),
(573, 'shortcode', '', '{\"depth\":\"1\",\"content\":\"<h4>Password<\\/h4>\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 19, NULL, 251, '1'),
(574, 'hr', '', '{\"depth\":\"1\",\"Query\":\"\"}', '', '', 24, NULL, 251, '1'),
(575, 'selection_type', 'login_settings[who_changes_password]', '{\"depth\":\"1\",\"label\":\"Who can change password\",\"type\":\"radio\",\"variation\":\"inline\",\"Required\":\"1\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"only_admin\\\",\\\"display\\\":\\\"Only admin\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"anyone\\\",\\\"display\\\":\\\"Anyone\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 23, NULL, 251, '1'),
(576, 'selection_type', 'login_settings[recaptcha_login]', '{\"depth\":\"1\",\"label\":\"Enable reCaptcha in login page\",\"type\":\"radio\",\"variation\":\"inline\",\"Required\":\"1\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Yes\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"0\\\",\\\"display\\\":\\\"No\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 18, NULL, 251, '1'),
(577, 'selection_type', 'login_settings[login_social][]', '{\"depth\":\"1\",\"label\":\"Login social\",\"type\":\"checkbox\",\"variation\":\"inline\",\"size\":\"col-md-12 col-xl-12\",\"Alert\":\"It requires plugins\",\"options_resolver\":\"$login_social\",\"Query\":\"\"}', '', '', 27, NULL, 251, '1'),
(579, 'shortcode', '', '{\"depth\":\"1\",\"content\":\"<h4>Register page<\\/h4>\",\"size\":\"col-xl-12\",\"Query\":\"\"}', '', '', 25, NULL, 251, '1'),
(580, 'selection_type', 'login_settings[register_page][active]', '{\"depth\":\"1\",\"label\":\"Active\",\"type\":\"radio\",\"variation\":\"inline\",\"Required\":\"1\",\"size\":\"col-md-6 col-xl-3\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Yes\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"0\\\",\\\"display\\\":\\\"No\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 29, NULL, 251, '1'),
(581, 'selection_type', 'login_settings[register_page][slug]', '{\"depth\":\"1\",\"label\":\"Register page\",\"type\":\"search\",\"variation\":\"original\",\"Required\":\"1\",\"size\":\"col-md-6 col-xl\",\"function_proccess\":\"get_pages_for_select\",\"options_resolver\":\"get_pages_for_select(\\\"slug\\\")\",\"Query\":\"\"}', '', '', 28, NULL, 251, '1'),
(582, 'selection_type', 'login_settings[register_page][login_after_register]', '{\"depth\":\"1\",\"label\":\"Login after register\",\"type\":\"radio\",\"variation\":\"inline\",\"Required\":\"1\",\"size\":\"col-md-6 col-xl-3\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Yes\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"0\\\",\\\"display\\\":\\\"No\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 30, NULL, 251, '1'),
(583, 'selection_type', 'lowest_role[]', '{\"depth\":\"1\",\"label\":\"Lowest role(s)\",\"type\":\"checkbox\",\"variation\":\"balloons\",\"size\":\"col-xl-12\",\"Query\":\"SELECT id as value, name as display FROM tb_user_roles\"}', '', '', 26, NULL, 251, '1'),
(584, 'divider', '', '{\"title\":\"Maintence\",\"Query\":\"\"}', '', '', 7, NULL, 251, '1'),
(585, 'selection_type', 'block_system', '{\"depth\":\"1\",\"label\":\"Block system\",\"type\":\"radio\",\"variation\":\"inline\",\"Required\":\"1\",\"Alert\":\"Only developers can login.\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Yes\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"0\\\",\\\"display\\\":\\\"No\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 8, NULL, 251, '1'),
(586, 'selection_type', 'is_localhost', '{\"depth\":\"1\",\"label\":\"Is localhost\",\"type\":\"radio\",\"variation\":\"inline\",\"Required\":\"1\",\"Options\":\"{\\\"1\\\":{\\\"value\\\":\\\"1\\\",\\\"display\\\":\\\"Yes\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"},\\\"2\\\":{\\\"value\\\":\\\"0\\\",\\\"display\\\":\\\"No\\\",\\\"name\\\":\\\"\\\",\\\"description\\\":\\\"\\\",\\\"highlight\\\":\\\"\\\",\\\"small\\\":\\\"\\\",\\\"attributes\\\":\\\"\\\"}}\",\"Query\":\"\"}', '', '', 9, NULL, 251, '1'),
(587, 'selection_type', 'activated_plugins[]', '{\"depth\":\"1\",\"label\":\"Active plugins\",\"type\":\"switch\",\"variation\":\"original\",\"options_resolver\":\"list_plugins(true)\",\"Query\":\"\"}', '', '', 10, NULL, 251, '1'),
(588, 'divider', '', '{\"title\":\"General\",\"Query\":\"\"}', '', '', 1, NULL, 251, '1'),
(589, 'basic', 'country', '{\"depth\":\"1\",\"label\":\"Country\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 2, NULL, 251, '1'),
(590, 'basic', 'lang', '{\"depth\":\"1\",\"label\":\"Lang\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 3, NULL, 251, '1'),
(591, 'basic', 'timezone', '{\"depth\":\"1\",\"label\":\"Timezone\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 4, NULL, 251, '1'),
(592, 'basic', 'charset', '{\"depth\":\"1\",\"label\":\"Charset\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 5, NULL, 251, '1'),
(593, 'basic', 'viewport', '{\"depth\":\"1\",\"label\":\"Viewport\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 6, NULL, 251, '1'),
(594, 'hr', '', '{\"depth\":\"1\",\"Query\":\"\"}', '', '', 12, NULL, 251, '1'),
(595, 'basic', 'developer[author]', '{\"depth\":\"1\",\"label\":\"Developer\",\"type\":\"text\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"size\":\"col-md-6\",\"Query\":\"\"}', '', '', 13, NULL, 251, '1'),
(597, 'submit_button', 'process-form', '{\"Value\":\"Enviar\",\"class\":\"btn btn-st\",\"allow_schedule\":\"1\",\"input_id\":\"process-form\",\"Query\":\"\"}', '', '', 28, NULL, 240, '1'),
(598, 'submit_button', 'process-form', '{\"Value\":\"Enviar\",\"class\":\"btn btn-st\",\"input_id\":\"process-form\",\"Query\":\"\"}', '', '', 45, NULL, 242, '1'),
(599, 'submit_button', 'process-form', '{\"Value\":\"Enviar\",\"class\":\"btn btn-st\",\"allow_schedule\":\"1\",\"input_id\":\"process-form\",\"Query\":\"\"}', '', '', 31, NULL, 251, '1'),
(600, 'copy', 'system-password', '{\"depth\":\"1\",\"label\":\"System password\",\"type\":\"password\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '', 11, NULL, 251, '1'),
(655, 'password', 'password', '{\"label\":\"Senha\",\"type\":\"default\",\"Query\":\"\"}', '', '', 5, NULL, 176, '1'),
(657, 'basic', 'first_name', '{\"label\":\"Nome\",\"type\":\"text\",\"input_id\":\"nome\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Required\":\"1\",\"Query\":\"\"}', '', '1', 1, NULL, 254, '1'),
(659, 'basic', 'email', '{\"label\":\"E-mail\",\"type\":\"email\",\"input_id\":\"email\",\"attachment\":\"{\\\"prepend\\\":\\\"\\\",\\\"append\\\":\\\"\\\"}\",\"Query\":\"\"}', '', '1', 2, NULL, 254, '1'),
(660, 'status_selector', 'status_id', '{\"function_proccess\":\"user_status\",\"input_id\":\"status_id\",\"Query\":\"\"}', '', '1', 3, NULL, 254, '1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_info`
--

DROP TABLE IF EXISTS `tb_info`;
CREATE TABLE `tb_info` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `option_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `option_value` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `autoload` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1',
  `type` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tb_info`
--

INSERT INTO `tb_info` (`id`, `option_name`, `option_value`, `autoload`, `type`) VALUES
(1, 'name', 'PyroSoft', '1', 'info'),
(2, 'short_name', 'PyroSoft', '1', 'info'),
(3, 'title', 'PyroSoft', '1', 'info'),
(4, 'about_business', '', '1', 'info'),
(5, 'contact', '[]', '1', 'info'),
(6, 'social_media', '[]', '1', 'info'),
(7, 'address', '', '1', 'info'),
(8, 'geo', '', '1', 'info'),
(9, 'organization_chart', '', '1', 'info'),
(10, 'founding', '', '1', 'info'),
(11, 'price_range', '', '1', 'info'),
(12, 'taxID', '', '1', 'info'),
(14, 'email', 'example@email.com', '1', 'info'),
(19, 'favicon', 'favicon.png', '1', 'info'),
(29, 'brand_colors', '{\"primary\":\"#f6bb1b\",\"secondary\":\"#c77a00\",\"tertiary\":\"#f8f8f8\",\"quaternary\":\"#121212\"}', '1', 'info'),
(30, 'system-password', 'd9b1d7db4cd6e70935368a1efb10e377', NULL, 'config'),
(31, 'lang', 'pt-BR', '1', 'config'),
(33, 'charset', 'UTF-8', '1', 'config'),
(34, 'timezone', 'GMT+3', '1', 'config'),
(35, 'country', 'BR', '1', 'config'),
(36, 'viewport', 'width=device-width, initial-scale=1, shrink-to-fit=no', '1', 'config'),
(37, 'block_system', '0', '1', 'config'),
(38, 'developer', '{\"author\":\"EUPHORIA SYSTEMS\"}', '1', 'config'),
(39, 'main_page', '{\"title\":\"Início\",\"slug\":\"home\"}', '1', 'config'),
(40, 'seo', '{\"keywords\":\"\",\"image\":\"[]\",\"robots\":\"\",\"type\":\"\",\"description\":\"\",\"author\":\"\"}', '1', 'config'),
(41, 'activated_plugins', '', '1', 'config'),
(42, 'ep-secret-key', 'ep-sk-', NULL, 'config'),
(43, 'ep-public-key', 'ep-pk-', '1', 'config'),
(44, 'is_localhost', '1', '1', 'config'),
(45, 'email_config', '{\"enable_sending\":\"1\",\"plugin\":\"\"}', '1', 'config'),
(46, 'login_settings', '{\"recaptcha_login\":\"0\",\"password_must\":{\"has_number\":\"1\",\"has_upper\":\"1\",\"has_special_characters\":\"1\",\"length\":{\"min\":\"8\",\"max\":\"16\"}},\"who_changes_password\":\"only_admin\",\"register_page\":{\"slug\":\"home\",\"active\":\"0\",\"login_after_register\":\"1\"}}', '1', 'config'),
(47, 'lowest_role', '[\"3\"]', '1', 'config'),
(48, 'base_url', 'https://localhost/PyroSoft/development', '1', 'config');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_menus`
--

DROP TABLE IF EXISTS `tb_menus`;
CREATE TABLE `tb_menus` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `attributes` varchar(200) DEFAULT NULL,
  `icon` varchar(25) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `order_reg` int(11) DEFAULT NULL,
  `depth` int(2) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  `function_view` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `style` varchar(10) DEFAULT NULL,
  `which_users` varchar(15) DEFAULT NULL,
  `status_id` varchar(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `tb_menus`
--

INSERT INTO `tb_menus` (`id`, `title`, `attributes`, `icon`, `url`, `order_reg`, `depth`, `parent_id`, `function_view`, `type`, `style`, `which_users`, `status_id`, `menu_id`, `created_at`, `updated_at`) VALUES
(153, '[app] Main Menu', NULL, NULL, 'app-main-menu', NULL, 0, NULL, NULL, 'list', NULL, NULL, '1', NULL, '2025-03-13 03:38:42', '2026-01-28 03:05:48'),
(270, '[admin] Main Menu', NULL, NULL, 'admin-main-menu', NULL, 0, NULL, NULL, 'list', NULL, NULL, '1', NULL, '2025-03-18 05:49:24', '2026-01-28 03:06:26'),
(272, 'Dashboard', NULL, 'fas fa-home', '47', 2, 1, 293, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-18 06:17:54', '2026-01-28 03:06:26'),
(274, 'List Users', NULL, ' ', '63', 4, 2, 322, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-18 06:17:54', '2026-01-28 03:06:26'),
(275, 'Permissions', NULL, ' ', '61', 6, 2, 322, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-18 06:17:54', '2026-01-28 03:06:26'),
(277, 'Site', NULL, 'fas fa-home', '1', 7, 1, 293, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-18 06:17:54', '2026-01-28 03:06:26'),
(279, 'Manage CRUDs', NULL, ' ', '179', 9, 2, 321, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-18 06:17:54', '2026-01-28 03:06:26'),
(280, 'List Pages', NULL, ' ', '49', 10, 2, 321, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-18 06:17:54', '2026-01-28 03:06:26'),
(281, 'Manage Menus', NULL, ' ', '311', 11, 2, 321, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-18 06:17:54', '2026-01-28 03:06:26'),
(293, 'Basic', NULL, '', '', 1, 0, 0, '', 'groups', 'generic', 'everyone', NULL, 270, '2025-03-19 02:25:51', '2026-01-28 03:06:26'),
(294, 'Roles', NULL, '', '60', 5, 2, 322, '', 'page', 'generic', 'everyone', NULL, 270, '2025-03-19 05:12:09', '2026-01-28 03:06:26'),
(315, '[app] Footer', NULL, NULL, 'app-footer', NULL, 0, NULL, NULL, 'list', NULL, NULL, '1', NULL, '2025-03-20 03:22:32', '2026-01-28 03:05:10'),
(321, 'Settings', NULL, 'fas fa-cogs', '', 8, 1, 293, '', 'groups', 'generic', 'everyone', NULL, 270, '2025-04-15 05:52:05', '2026-01-28 03:06:26'),
(322, 'Users', NULL, 'fas fa-users', '', 3, 1, 293, '', 'groups', 'generic', 'everyone', NULL, 270, '2025-04-15 05:57:55', '2026-01-28 03:06:26'),
(324, 'Scheduled Events', NULL, 'fas fa-clock', '314', 14, 1, 293, '', 'page', 'generic', 'everyone', NULL, 270, '2025-07-31 07:14:08', '2026-01-28 03:06:26'),
(336, 'System Settings', NULL, '', '316', 12, 2, 321, '', 'page', 'generic', 'everyone', NULL, 270, '2025-10-02 19:19:48', '2026-01-28 03:06:26'),
(337, 'Plugins', NULL, '', '318', 13, 2, 321, '', 'page', 'generic', 'everyone', NULL, 270, '2025-12-04 01:30:52', '2026-01-28 03:06:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_pages`
--

DROP TABLE IF EXISTS `tb_pages`;
CREATE TABLE `tb_pages` (
  `id` int(11) NOT NULL,
  `slug` varchar(220) DEFAULT NULL,
  `seo` longtext DEFAULT NULL,
  `title` varchar(220) DEFAULT NULL,
  `access_count` int(11) DEFAULT 0,
  `page_settings` longtext DEFAULT NULL,
  `page_type` varchar(15) DEFAULT NULL,
  `is_public` int(11) DEFAULT 0,
  `page_template` varchar(150) DEFAULT NULL,
  `parent_page_id` int(11) DEFAULT NULL,
  `page_area` varchar(10) DEFAULT NULL,
  `custom_urls` text DEFAULT NULL,
  `permission_type` varchar(15) NOT NULL DEFAULT '''except_these''',
  `status_id` int(11) DEFAULT 2,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `tb_pages`
--

INSERT INTO `tb_pages` (`id`, `slug`, `seo`, `title`, `access_count`, `page_settings`, `page_type`, `is_public`, `page_template`, `parent_page_id`, `page_area`, `custom_urls`, `permission_type`, `status_id`, `created_at`, `updated_at`) VALUES
(1, 'home', NULL, 'Welcome to PyroSoft', 0, '{\"navbar\":{\"format\":\"none\",\"style\":\"transparent-scroll\"},\"footer\":{\"format\":\"none\"}}', 'essential', 1, 'this-system/ep-areas/app/home.php', 0, 'app', '', 'except_these', 1, '2018-02-23 00:00:00', '2026-02-02 21:00:59'),
(12, 'login', NULL, 'Login', 0, '{\"navbar\":{\"format\":\"medium\",\"style\":\"transparent-absolute\"},\"footer\":{\"format\":\"none\"}}', 'essential', 1, 'this-system/ep-areas/app/login.php', 0, 'app', '', 'except_these', 1, '2021-01-14 14:28:03', '2026-01-28 05:10:13'),
(47, 'administration', NULL, 'Administration', 0, '{\"navbar\":{\"format\":\"full\",\"style\":\"fixed\"},\"footer\":{\"format\":\"full\"}}', 'essential', 0, 'this-system/ep-areas/admin/admin.php', 0, 'admin', '', 'only_these', 1, '2021-02-08 17:23:56', '2026-02-09 16:48:14'),
(49, 'list-pages', NULL, 'List Pages', 0, '[]', 'not_essential', 0, 'ep-includes/features/page-crud-management-system/custom-pages/list-pages.php', 0, 'admin', '', 'only_these', 1, '2021-02-08 17:42:26', '2026-02-09 16:48:14'),
(60, 'role-management', NULL, 'Role Management', 0, '[]', 'not_essential', 0, 'ep-includes/features/roles-management/custom-pages/roles-management.php', 0, 'admin', '', 'only_these', 1, '2021-02-09 02:31:58', '2026-02-09 16:48:14'),
(61, 'manage-permissions', NULL, 'Manage Permissions', 0, '[]', 'not_essential', 0, 'ep-includes/features/permissions-management/custom-pages/permissions-management.php', 60, 'admin', '', 'only_these', 1, '2021-02-09 02:32:59', '2026-02-01 21:55:25'),
(63, 'list-users', NULL, 'List Users', 0, '[]', 'not_essential', 0, 'this-system/ep-areas/admin/common.php', 0, 'admin', '', 'only_these', 1, '2021-02-09 02:40:21', '2026-02-09 16:48:14'),
(77, 'edit-user', NULL, 'Edit User', 0, '[]', 'not_essential', 0, 'this-system/ep-areas/admin/common.php', 63, 'admin', '', 'only_these', 1, '2021-02-09 15:36:25', '2026-02-09 16:48:14'),
(84, 'view-user', NULL, 'View User', 0, '[]', 'not_essential', 0, 'this-system/ep-areas/admin/common.php', 63, 'admin', '', 'only_these', 1, '2021-02-10 00:21:51', '2026-02-01 21:55:25'),
(179, 'crud-management', NULL, 'Manage CRUDs', 0, '[]', 'not_essential', 0, 'ep-includes/features/page-crud-management-system/custom-pages/crud-management.php', 0, 'admin', '', 'only_these', 1, '2022-10-13 21:32:38', '2026-02-09 16:48:14'),
(310, 'create-user', NULL, 'Create User', 0, '[]', 'not_essential', 0, 'this-system/ep-areas/admin/common.php', 63, 'admin', '', 'only_these', 1, '2025-02-12 02:15:52', '2026-02-01 21:55:25'),
(311, 'menu-management', NULL, 'Menu Management', 0, '[]', 'not_essential', 0, 'ep-includes/features/menu-management/custom-pages/menu-management.php', 0, 'admin', '', 'only_these', 1, '2025-03-13 03:43:15', '2026-02-09 16:48:14'),
(314, 'scheduled-events', NULL, 'Scheduled Events', 0, '[]', 'not_essential', 0, 'this-system/ep-areas/admin/cron-management.php', 0, 'admin', '', 'only_these', 1, '2025-07-31 07:05:35', '2026-02-01 21:55:25'),
(315, 'page-manager', NULL, 'Page Manager', 0, '[]', 'not_essential', 0, 'ep-includes/features/page-crud-management-system/custom-pages/page-management.php', 49, 'admin', '', 'only_these', 1, '2025-09-18 21:48:45', '2026-02-09 16:48:14'),
(316, 'system-settings', NULL, 'System Settings', 0, '[]', 'not_essential', 0, 'this-system/ep-areas/admin/common.php', 0, 'admin', '', 'only_these', 1, '2025-10-02 18:34:07', '2026-02-09 16:48:14'),
(318, 'plugin-management', NULL, 'Plugin Management', 0, '[]', 'not_essential', 0, 'ep-includes/features/plugins-management/custom-pages/plugins-management.php', 0, 'admin', '', 'only_these', 1, '2025-12-03 03:38:00', '2026-02-09 16:48:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_page_content`
--

DROP TABLE IF EXISTS `tb_page_content`;
CREATE TABLE `tb_page_content` (
  `id` int(11) NOT NULL,
  `TypeModule` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settings` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contents` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscribers_only` char(1) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `page_id` int(11) DEFAULT NULL,
  `crud_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_reg` int(11) DEFAULT NULL,
  `is_model` int(11) DEFAULT NULL,
  `status_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tb_page_content`
--

INSERT INTO `tb_page_content` (`id`, `TypeModule`, `settings`, `contents`, `subscribers_only`, `page_id`, `crud_id`, `order_reg`, `is_model`, `status_id`) VALUES
(103, 'crud', '{\"class\":\"col-12 module pt-0\",\"crud_id\":\"179\",\"background\":\"{\\\"effect\\\":\\\"\\\",\\\"color\\\":\\\"\\\",\\\"image\\\":null,\\\"mode\\\":\\\"\\\"}\",\"image_folder\":\"modules\"}', '', '', 310, '179', 1, 0, '1'),
(138, 'crud', '{\"crud_id\":\"176\",\"background\":\"{\\\"effect\\\":\\\"\\\",\\\"color\\\":\\\"\\\",\\\"image\\\":null,\\\"mode\\\":\\\"\\\"}\",\"image_folder\":\"modules\"}', '', '', 77, '176', 1, NULL, '1'),
(147, 'crud', '{\"crud_id\":\"231\",\"background\":\"{\\\"effect\\\":\\\"\\\",\\\"color\\\":\\\"\\\",\\\"image\\\":null,\\\"mode\\\":\\\"\\\"}\",\"image_folder\":\"modules\"}', '', '', 84, '231', 1, NULL, '1'),
(170, 'crud', '{\"crud_id\":\"240\",\"background\":\"{\\\"effect\\\":\\\"\\\",\\\"color\\\":\\\"\\\",\\\"image\\\":null,\\\"mode\\\":\\\"\\\"}\",\"image_folder\":\"modules\"}', '', '', 316, '240', 1, NULL, '1'),
(171, 'crud', '{\"crud_id\":\"242\",\"background\":\"{\\\"effect\\\":\\\"\\\",\\\"color\\\":\\\"\\\",\\\"image\\\":null,\\\"mode\\\":\\\"\\\"}\",\"image_folder\":\"modules\"}', '', '', 316, '242', 3, NULL, '1'),
(173, 'crud', '{\"crud_id\":\"251\",\"background\":\"{\\\"effect\\\":\\\"\\\",\\\"color\\\":\\\"\\\",\\\"image\\\":null,\\\"mode\\\":\\\"\\\"}\",\"image_folder\":\"modules\"}', '', '', 316, '251', 2, NULL, '1'),
(268, 'crud', '{\"class\":\"col-12 module pt-0\",\"crud_id\":\"177\",\"background\":\"{\\\"effect\\\":\\\"\\\",\\\"color\\\":\\\"\\\",\\\"image\\\":null,\\\"mode\\\":\\\"\\\"}\",\"image_folder\":\"modules\"}', '', '', 63, '177', 1, NULL, '1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_tokens`
--

DROP TABLE IF EXISTS `tb_tokens`;
CREATE TABLE `tb_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '''available''',
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `resource_id` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_users`
--

DROP TABLE IF EXISTS `tb_users`;
CREATE TABLE `tb_users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(250) DEFAULT NULL,
  `last_name` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL,
  `status_id` int(11) DEFAULT 2,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `tb_users`
--

INSERT INTO `tb_users` (`id`, `first_name`, `last_name`, `email`, `login`, `password`, `status_id`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'PyroSoft', 'example@email.com', 'admin', '$argon2id$v=19$m=65536,t=4,p=1$dENqeVU3Z2RqaTdlRlhkOQ$75ZREv/7J2RbTWpH80dyDaoNFuHdktqL9CN6pghj4wk', 1, '2015-09-19 00:00:00', '2026-02-08 17:57:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_user_roles`
--

DROP TABLE IF EXISTS `tb_user_roles`;
CREATE TABLE `tb_user_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `order_reg` int(10) DEFAULT NULL,
  `redirect_page_id` int(11) DEFAULT NULL,
  `type` varchar(11) DEFAULT 'role',
  `status_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `tb_user_roles`
--

INSERT INTO `tb_user_roles` (`id`, `name`, `slug`, `order_reg`, `redirect_page_id`, `type`, `status_id`, `created_at`, `updated_at`) VALUES
(1, 'Developer', 'developer', 1, 47, 'role', 1, '2025-03-26 04:35:23', '2026-02-01 19:29:23'),
(2, 'Administrator', 'administrator', 2, 47, 'role', 1, '2025-03-26 04:35:23', '2026-02-01 19:29:23'),
(3, 'Client', 'client', 7, 2, 'role', 1, '2025-03-26 04:35:47', '2026-02-01 19:29:23'),
(6, 'Visitor', 'visitor', 8, 1, 'role', 1, '2025-03-26 04:37:26', '2026-02-01 19:29:23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_user_role_assignments`
--

DROP TABLE IF EXISTS `tb_user_role_assignments`;
CREATE TABLE `tb_user_role_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tb_user_role_assignments`
--

INSERT INTO `tb_user_role_assignments` (`id`, `user_id`, `role_id`) VALUES
(588, 1, 2),
(706, 1, 1),
(757, 1, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_user_role_permissions`
--

DROP TABLE IF EXISTS `tb_user_role_permissions`;
CREATE TABLE `tb_user_role_permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `allowed` int(1) DEFAULT NULL,
  `permission_type` varchar(15) DEFAULT NULL,
  `action_trigger` varchar(10) DEFAULT NULL,
  `crud_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `tb_user_role_permissions`
--

INSERT INTO `tb_user_role_permissions` (`id`, `name`, `slug`, `allowed`, `permission_type`, `action_trigger`, `crud_id`, `page_id`, `permission_id`, `role_id`) VALUES
(297, 'Manage System Information', 'update-system-informations', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(298, NULL, NULL, 1, NULL, '0', NULL, NULL, 297, 1),
(299, NULL, NULL, 0, NULL, '0', NULL, NULL, 297, 2),
(300, NULL, NULL, 0, NULL, '0', NULL, NULL, 297, 3),
(303, NULL, NULL, 0, NULL, '0', NULL, NULL, 297, 6),
(306, 'Manage Pages', 'manage-pages', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(307, NULL, NULL, 1, NULL, '0', NULL, NULL, 306, 1),
(308, NULL, NULL, 0, NULL, '0', NULL, NULL, 306, 2),
(309, NULL, NULL, 0, NULL, '0', NULL, NULL, 306, 3),
(312, NULL, NULL, 0, NULL, '0', NULL, NULL, 306, 6),
(315, 'Manage CRUDs', 'manage-cruds', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(316, NULL, NULL, 1, NULL, '0', NULL, NULL, 315, 1),
(317, NULL, NULL, 0, NULL, '0', NULL, NULL, 315, 2),
(318, NULL, NULL, 0, NULL, '0', NULL, NULL, 315, 3),
(321, NULL, NULL, 0, NULL, '0', NULL, NULL, 315, 6),
(324, 'Manage Permissions', 'manage-permissions', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(325, NULL, NULL, 1, NULL, '0', NULL, NULL, 324, 1),
(326, NULL, NULL, 0, NULL, '0', NULL, NULL, 324, 2),
(327, NULL, NULL, 0, NULL, '0', NULL, NULL, 324, 3),
(330, NULL, NULL, 0, NULL, '0', NULL, NULL, 324, 6),
(333, 'Change System Password', 'change-system-password', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(334, NULL, NULL, 1, NULL, '0', NULL, NULL, 333, 1),
(335, NULL, NULL, 0, NULL, '0', NULL, NULL, 333, 2),
(336, NULL, NULL, 0, NULL, '0', NULL, NULL, 333, 3),
(339, NULL, NULL, 0, NULL, '0', NULL, NULL, 333, 6),
(9686, 'Manage Scheduled Events', 'manage-schedules-events', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(9687, NULL, NULL, 1, NULL, '0', NULL, NULL, 9686, 1),
(9688, NULL, NULL, 0, NULL, '0', NULL, NULL, 9686, 2),
(9689, NULL, NULL, 0, NULL, '0', NULL, NULL, 9686, 3),
(9692, NULL, NULL, 0, NULL, '0', NULL, NULL, 9686, 6),
(33879, NULL, NULL, 1, NULL, 'insert', 0, NULL, NULL, 1),
(33880, NULL, NULL, 0, NULL, 'insert', 0, NULL, NULL, 2),
(33881, NULL, NULL, 0, NULL, 'insert', 0, NULL, NULL, 3),
(33884, NULL, NULL, 0, NULL, 'insert', 0, NULL, NULL, 6),
(33887, NULL, NULL, 1, NULL, 'update', 0, NULL, NULL, 1),
(33888, NULL, NULL, 0, NULL, 'update', 0, NULL, NULL, 2),
(33889, NULL, NULL, 0, NULL, 'update', 0, NULL, NULL, 3),
(33892, NULL, NULL, 0, NULL, 'update', 0, NULL, NULL, 6),
(33895, NULL, NULL, 1, NULL, 'view', 0, NULL, NULL, 1),
(33896, NULL, NULL, 0, NULL, 'view', 0, NULL, NULL, 2),
(33897, NULL, NULL, 0, NULL, 'view', 0, NULL, NULL, 3),
(33900, NULL, NULL, 0, NULL, 'view', 0, NULL, NULL, 6),
(33903, NULL, NULL, 1, NULL, 'delete', 0, NULL, NULL, 1),
(33904, NULL, NULL, 0, NULL, 'delete', 0, NULL, NULL, 2),
(33905, NULL, NULL, 0, NULL, 'delete', 0, NULL, NULL, 3),
(33908, NULL, NULL, 0, NULL, 'delete', 0, NULL, NULL, 6),
(33911, NULL, NULL, 1, NULL, 'duplicate', 0, NULL, NULL, 1),
(33912, NULL, NULL, 0, NULL, 'duplicate', 0, NULL, NULL, 2),
(33913, NULL, NULL, 0, NULL, 'duplicate', 0, NULL, NULL, 3),
(33916, NULL, NULL, 0, NULL, 'duplicate', 0, NULL, NULL, 6),
(40943, NULL, NULL, 0, NULL, '0', NULL, 12, NULL, 1),
(40944, NULL, NULL, 0, NULL, '0', NULL, 12, NULL, 2),
(40945, NULL, NULL, 0, NULL, '0', NULL, 12, NULL, 3),
(40948, NULL, NULL, 0, NULL, '0', NULL, 12, NULL, 6),
(47855, NULL, NULL, 0, NULL, 'insert', 242, NULL, NULL, 1),
(47856, NULL, NULL, 0, NULL, 'insert', 242, NULL, NULL, 2),
(47857, NULL, NULL, 0, NULL, 'insert', 242, NULL, NULL, 3),
(47860, NULL, NULL, 0, NULL, 'insert', 242, NULL, NULL, 6),
(47863, NULL, NULL, 0, NULL, 'update', 242, NULL, NULL, 1),
(47864, NULL, NULL, 0, NULL, 'update', 242, NULL, NULL, 2),
(47865, NULL, NULL, 0, NULL, 'update', 242, NULL, NULL, 3),
(47868, NULL, NULL, 0, NULL, 'update', 242, NULL, NULL, 6),
(47871, NULL, NULL, 0, NULL, 'view', 242, NULL, NULL, 1),
(47872, NULL, NULL, 0, NULL, 'view', 242, NULL, NULL, 2),
(47873, NULL, NULL, 0, NULL, 'view', 242, NULL, NULL, 3),
(47876, NULL, NULL, 0, NULL, 'view', 242, NULL, NULL, 6),
(47879, NULL, NULL, 0, NULL, 'delete', 242, NULL, NULL, 1),
(47880, NULL, NULL, 0, NULL, 'delete', 242, NULL, NULL, 2),
(47881, NULL, NULL, 0, NULL, 'delete', 242, NULL, NULL, 3),
(47884, NULL, NULL, 0, NULL, 'delete', 242, NULL, NULL, 6),
(47887, NULL, NULL, 0, NULL, 'duplicate', 242, NULL, NULL, 1),
(47888, NULL, NULL, 0, NULL, 'duplicate', 242, NULL, NULL, 2),
(47889, NULL, NULL, 0, NULL, 'duplicate', 242, NULL, NULL, 3),
(47892, NULL, NULL, 0, NULL, 'duplicate', 242, NULL, NULL, 6),
(50784, 'Gerencia plugins', 'manage-plugins', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(50825, NULL, NULL, 1, NULL, '0', NULL, NULL, 50784, 1),
(50826, NULL, NULL, 0, NULL, '0', NULL, NULL, 50784, 2),
(50827, NULL, NULL, 0, NULL, '0', NULL, NULL, 50784, 3),
(50830, NULL, NULL, 0, NULL, '0', NULL, NULL, 50784, 6),
(53008, NULL, NULL, 1, NULL, '0', NULL, 310, NULL, 1),
(53009, NULL, NULL, 1, NULL, '0', NULL, 310, NULL, 2),
(53010, NULL, NULL, 0, NULL, '0', NULL, 310, NULL, 3),
(53013, NULL, NULL, 0, NULL, '0', NULL, 310, NULL, 6),
(53040, NULL, NULL, 1, NULL, '0', NULL, 314, NULL, 1),
(53041, NULL, NULL, 0, NULL, '0', NULL, 314, NULL, 2),
(53042, NULL, NULL, 0, NULL, '0', NULL, 314, NULL, 3),
(53045, NULL, NULL, 0, NULL, '0', NULL, 314, NULL, 6),
(53056, NULL, NULL, 1, NULL, '0', NULL, 316, NULL, 1),
(53057, NULL, NULL, 0, NULL, '0', NULL, 316, NULL, 2),
(53058, NULL, NULL, 0, NULL, '0', NULL, 316, NULL, 3),
(53061, NULL, NULL, 0, NULL, '0', NULL, 316, NULL, 6),
(53072, NULL, NULL, 1, NULL, '0', NULL, 49, NULL, 1),
(53073, NULL, NULL, 0, NULL, '0', NULL, 49, NULL, 2),
(53074, NULL, NULL, 0, NULL, '0', NULL, 49, NULL, 3),
(53077, NULL, NULL, 0, NULL, '0', NULL, 49, NULL, 6),
(53104, NULL, NULL, 1, NULL, '0', NULL, 77, NULL, 1),
(53105, NULL, NULL, 1, NULL, '0', NULL, 77, NULL, 2),
(53106, NULL, NULL, 0, NULL, '0', NULL, 77, NULL, 3),
(53109, NULL, NULL, 0, NULL, '0', NULL, 77, NULL, 6),
(53120, NULL, NULL, 1, NULL, '0', NULL, 84, NULL, 1),
(53121, NULL, NULL, 1, NULL, '0', NULL, 84, NULL, 2),
(53122, NULL, NULL, 0, NULL, '0', NULL, 84, NULL, 3),
(53125, NULL, NULL, 0, NULL, '0', NULL, 84, NULL, 6),
(58391, 'Manage feature metrics', 'manage-feature-metrics', NULL, 'only_these', 'custom', NULL, NULL, NULL, NULL),
(58408, NULL, NULL, 1, NULL, '0', NULL, NULL, 58391, 1),
(58409, NULL, NULL, 0, NULL, '0', NULL, NULL, 58391, 2),
(58410, NULL, NULL, 0, NULL, '0', NULL, NULL, 58391, 3),
(58413, NULL, NULL, 0, NULL, '0', NULL, NULL, 58391, 6),
(59460, NULL, NULL, 1, NULL, '0', NULL, 47, NULL, 1),
(59461, NULL, NULL, 1, NULL, '0', NULL, 47, NULL, 2),
(59462, NULL, NULL, 0, NULL, '0', NULL, 47, NULL, 3),
(59463, NULL, NULL, 0, NULL, '0', NULL, 47, NULL, 6),
(59472, NULL, NULL, 1, NULL, '0', NULL, 61, NULL, 1),
(59473, NULL, NULL, 1, NULL, '0', NULL, 61, NULL, 2),
(59474, NULL, NULL, 0, NULL, '0', NULL, 61, NULL, 3),
(59475, NULL, NULL, 0, NULL, '0', NULL, 61, NULL, 6),
(59476, NULL, NULL, 1, NULL, '0', NULL, 318, NULL, 1),
(59477, NULL, NULL, 0, NULL, '0', NULL, 318, NULL, 2),
(59478, NULL, NULL, 0, NULL, '0', NULL, 318, NULL, 3),
(59479, NULL, NULL, 0, NULL, '0', NULL, 318, NULL, 6),
(59484, NULL, NULL, 1, NULL, '0', NULL, 315, NULL, 1),
(59485, NULL, NULL, 0, NULL, '0', NULL, 315, NULL, 2),
(59486, NULL, NULL, 0, NULL, '0', NULL, 315, NULL, 3),
(59487, NULL, NULL, 0, NULL, '0', NULL, 315, NULL, 6),
(59488, NULL, NULL, 1, NULL, '0', NULL, 60, NULL, 1),
(59489, NULL, NULL, 1, NULL, '0', NULL, 60, NULL, 2),
(59490, NULL, NULL, 0, NULL, '0', NULL, 60, NULL, 3),
(59491, NULL, NULL, 0, NULL, '0', NULL, 60, NULL, 6),
(59492, NULL, NULL, 1, NULL, '0', NULL, 179, NULL, 1),
(59493, NULL, NULL, 0, NULL, '0', NULL, 179, NULL, 2),
(59494, NULL, NULL, 0, NULL, '0', NULL, 179, NULL, 3),
(59495, NULL, NULL, 0, NULL, '0', NULL, 179, NULL, 6),
(59496, NULL, NULL, 1, NULL, '0', NULL, 311, NULL, 1),
(59497, NULL, NULL, 0, NULL, '0', NULL, 311, NULL, 2),
(59498, NULL, NULL, 0, NULL, '0', NULL, 311, NULL, 3),
(59499, NULL, NULL, 0, NULL, '0', NULL, 311, NULL, 6),
(59504, NULL, NULL, 0, NULL, 'insert', 251, NULL, NULL, 1),
(59505, NULL, NULL, 0, NULL, 'insert', 251, NULL, NULL, 2),
(59506, NULL, NULL, 0, NULL, 'insert', 251, NULL, NULL, 3),
(59507, NULL, NULL, 0, NULL, 'insert', 251, NULL, NULL, 6),
(59508, NULL, NULL, 0, NULL, 'update', 251, NULL, NULL, 1),
(59509, NULL, NULL, 0, NULL, 'update', 251, NULL, NULL, 2),
(59510, NULL, NULL, 0, NULL, 'update', 251, NULL, NULL, 3),
(59511, NULL, NULL, 0, NULL, 'update', 251, NULL, NULL, 6),
(59512, NULL, NULL, 0, NULL, 'view', 251, NULL, NULL, 1),
(59513, NULL, NULL, 0, NULL, 'view', 251, NULL, NULL, 2),
(59514, NULL, NULL, 0, NULL, 'view', 251, NULL, NULL, 3),
(59515, NULL, NULL, 0, NULL, 'view', 251, NULL, NULL, 6),
(59516, NULL, NULL, 0, NULL, 'delete', 251, NULL, NULL, 1),
(59517, NULL, NULL, 0, NULL, 'delete', 251, NULL, NULL, 2),
(59518, NULL, NULL, 0, NULL, 'delete', 251, NULL, NULL, 3),
(59519, NULL, NULL, 0, NULL, 'delete', 251, NULL, NULL, 6),
(59520, NULL, NULL, 0, NULL, 'duplicate', 251, NULL, NULL, 1),
(59521, NULL, NULL, 0, NULL, 'duplicate', 251, NULL, NULL, 2),
(59522, NULL, NULL, 0, NULL, 'duplicate', 251, NULL, NULL, 3),
(59523, NULL, NULL, 0, NULL, 'duplicate', 251, NULL, NULL, 6),
(59536, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, 1),
(59537, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, 2),
(59538, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, 3),
(59539, NULL, NULL, 0, NULL, '0', NULL, 1, NULL, 6),
(59560, NULL, NULL, 0, NULL, 'insert', 240, NULL, NULL, 1),
(59561, NULL, NULL, 0, NULL, 'insert', 240, NULL, NULL, 2),
(59562, NULL, NULL, 0, NULL, 'insert', 240, NULL, NULL, 3),
(59563, NULL, NULL, 0, NULL, 'insert', 240, NULL, NULL, 6),
(59564, NULL, NULL, 0, NULL, 'update', 240, NULL, NULL, 1),
(59565, NULL, NULL, 0, NULL, 'update', 240, NULL, NULL, 2),
(59566, NULL, NULL, 0, NULL, 'update', 240, NULL, NULL, 3),
(59567, NULL, NULL, 0, NULL, 'update', 240, NULL, NULL, 6),
(59568, NULL, NULL, 0, NULL, 'view', 240, NULL, NULL, 1),
(59569, NULL, NULL, 0, NULL, 'view', 240, NULL, NULL, 2),
(59570, NULL, NULL, 0, NULL, 'view', 240, NULL, NULL, 3),
(59571, NULL, NULL, 0, NULL, 'view', 240, NULL, NULL, 6),
(59572, NULL, NULL, 0, NULL, 'delete', 240, NULL, NULL, 1),
(59573, NULL, NULL, 0, NULL, 'delete', 240, NULL, NULL, 2),
(59574, NULL, NULL, 0, NULL, 'delete', 240, NULL, NULL, 3),
(59575, NULL, NULL, 0, NULL, 'delete', 240, NULL, NULL, 6),
(59576, NULL, NULL, 0, NULL, 'duplicate', 240, NULL, NULL, 1),
(59577, NULL, NULL, 0, NULL, 'duplicate', 240, NULL, NULL, 2),
(59578, NULL, NULL, 0, NULL, 'duplicate', 240, NULL, NULL, 3),
(59579, NULL, NULL, 0, NULL, 'duplicate', 240, NULL, NULL, 6),
(59808, NULL, NULL, 1, NULL, '0', NULL, 63, NULL, 1),
(59809, NULL, NULL, 1, NULL, '0', NULL, 63, NULL, 2),
(59810, NULL, NULL, 0, NULL, '0', NULL, 63, NULL, 3),
(59811, NULL, NULL, 0, NULL, '0', NULL, 63, NULL, 6),
(60012, NULL, NULL, 0, NULL, 'insert', 231, NULL, NULL, 1),
(60013, NULL, NULL, 0, NULL, 'insert', 231, NULL, NULL, 2),
(60014, NULL, NULL, 0, NULL, 'insert', 231, NULL, NULL, 3),
(60015, NULL, NULL, 0, NULL, 'insert', 231, NULL, NULL, 6),
(60016, NULL, NULL, 0, NULL, 'update', 231, NULL, NULL, 1),
(60017, NULL, NULL, 0, NULL, 'update', 231, NULL, NULL, 2),
(60018, NULL, NULL, 0, NULL, 'update', 231, NULL, NULL, 3),
(60019, NULL, NULL, 0, NULL, 'update', 231, NULL, NULL, 6),
(60020, NULL, NULL, 0, NULL, 'view', 231, NULL, NULL, 1),
(60021, NULL, NULL, 0, NULL, 'view', 231, NULL, NULL, 2),
(60022, NULL, NULL, 0, NULL, 'view', 231, NULL, NULL, 3),
(60023, NULL, NULL, 0, NULL, 'view', 231, NULL, NULL, 6),
(60024, NULL, NULL, 0, NULL, 'delete', 231, NULL, NULL, 1),
(60025, NULL, NULL, 0, NULL, 'delete', 231, NULL, NULL, 2),
(60026, NULL, NULL, 0, NULL, 'delete', 231, NULL, NULL, 3),
(60027, NULL, NULL, 0, NULL, 'delete', 231, NULL, NULL, 6),
(60028, NULL, NULL, 0, NULL, 'duplicate', 231, NULL, NULL, 1),
(60029, NULL, NULL, 0, NULL, 'duplicate', 231, NULL, NULL, 2),
(60030, NULL, NULL, 0, NULL, 'duplicate', 231, NULL, NULL, 3),
(60031, NULL, NULL, 0, NULL, 'duplicate', 231, NULL, NULL, 6),
(60232, NULL, NULL, 0, NULL, 'insert', 254, NULL, NULL, 1),
(60233, NULL, NULL, 0, NULL, 'insert', 254, NULL, NULL, 2),
(60234, NULL, NULL, 0, NULL, 'insert', 254, NULL, NULL, 3),
(60235, NULL, NULL, 0, NULL, 'insert', 254, NULL, NULL, 6),
(60236, NULL, NULL, 0, NULL, 'update', 254, NULL, NULL, 1),
(60237, NULL, NULL, 0, NULL, 'update', 254, NULL, NULL, 2),
(60238, NULL, NULL, 0, NULL, 'update', 254, NULL, NULL, 3),
(60239, NULL, NULL, 0, NULL, 'update', 254, NULL, NULL, 6),
(60240, NULL, NULL, 0, NULL, 'view', 254, NULL, NULL, 1),
(60241, NULL, NULL, 0, NULL, 'view', 254, NULL, NULL, 2),
(60242, NULL, NULL, 0, NULL, 'view', 254, NULL, NULL, 3),
(60243, NULL, NULL, 0, NULL, 'view', 254, NULL, NULL, 6),
(60244, NULL, NULL, 0, NULL, 'delete', 254, NULL, NULL, 1),
(60245, NULL, NULL, 0, NULL, 'delete', 254, NULL, NULL, 2),
(60246, NULL, NULL, 0, NULL, 'delete', 254, NULL, NULL, 3),
(60247, NULL, NULL, 0, NULL, 'delete', 254, NULL, NULL, 6),
(60248, NULL, NULL, 0, NULL, 'duplicate', 254, NULL, NULL, 1),
(60249, NULL, NULL, 0, NULL, 'duplicate', 254, NULL, NULL, 2),
(60250, NULL, NULL, 0, NULL, 'duplicate', 254, NULL, NULL, 3),
(60251, NULL, NULL, 0, NULL, 'duplicate', 254, NULL, NULL, 6),
(60252, NULL, NULL, 0, NULL, 'insert', 177, NULL, NULL, 1),
(60253, NULL, NULL, 0, NULL, 'insert', 177, NULL, NULL, 2),
(60254, NULL, NULL, 0, NULL, 'insert', 177, NULL, NULL, 3),
(60255, NULL, NULL, 0, NULL, 'insert', 177, NULL, NULL, 6),
(60256, NULL, NULL, 0, NULL, 'update', 177, NULL, NULL, 1),
(60257, NULL, NULL, 0, NULL, 'update', 177, NULL, NULL, 2),
(60258, NULL, NULL, 0, NULL, 'update', 177, NULL, NULL, 3),
(60259, NULL, NULL, 0, NULL, 'update', 177, NULL, NULL, 6),
(60260, NULL, NULL, 0, NULL, 'view', 177, NULL, NULL, 1),
(60261, NULL, NULL, 0, NULL, 'view', 177, NULL, NULL, 2),
(60262, NULL, NULL, 0, NULL, 'view', 177, NULL, NULL, 3),
(60263, NULL, NULL, 0, NULL, 'view', 177, NULL, NULL, 6),
(60264, NULL, NULL, 0, NULL, 'delete', 177, NULL, NULL, 1),
(60265, NULL, NULL, 0, NULL, 'delete', 177, NULL, NULL, 2),
(60266, NULL, NULL, 0, NULL, 'delete', 177, NULL, NULL, 3),
(60267, NULL, NULL, 0, NULL, 'delete', 177, NULL, NULL, 6),
(60268, NULL, NULL, 0, NULL, 'duplicate', 177, NULL, NULL, 1),
(60269, NULL, NULL, 0, NULL, 'duplicate', 177, NULL, NULL, 2),
(60270, NULL, NULL, 0, NULL, 'duplicate', 177, NULL, NULL, 3),
(60271, NULL, NULL, 0, NULL, 'duplicate', 177, NULL, NULL, 6),
(60292, NULL, NULL, 0, NULL, 'insert', 176, NULL, NULL, 1),
(60293, NULL, NULL, 0, NULL, 'insert', 176, NULL, NULL, 2),
(60294, NULL, NULL, 0, NULL, 'insert', 176, NULL, NULL, 3),
(60295, NULL, NULL, 0, NULL, 'insert', 176, NULL, NULL, 6),
(60296, NULL, NULL, 0, NULL, 'update', 176, NULL, NULL, 1),
(60297, NULL, NULL, 0, NULL, 'update', 176, NULL, NULL, 2),
(60298, NULL, NULL, 0, NULL, 'update', 176, NULL, NULL, 3),
(60299, NULL, NULL, 0, NULL, 'update', 176, NULL, NULL, 6),
(60300, NULL, NULL, 0, NULL, 'view', 176, NULL, NULL, 1),
(60301, NULL, NULL, 0, NULL, 'view', 176, NULL, NULL, 2),
(60302, NULL, NULL, 0, NULL, 'view', 176, NULL, NULL, 3),
(60303, NULL, NULL, 0, NULL, 'view', 176, NULL, NULL, 6),
(60304, NULL, NULL, 0, NULL, 'delete', 176, NULL, NULL, 1),
(60305, NULL, NULL, 0, NULL, 'delete', 176, NULL, NULL, 2),
(60306, NULL, NULL, 0, NULL, 'delete', 176, NULL, NULL, 3),
(60307, NULL, NULL, 0, NULL, 'delete', 176, NULL, NULL, 6),
(60308, NULL, NULL, 0, NULL, 'duplicate', 176, NULL, NULL, 1),
(60309, NULL, NULL, 0, NULL, 'duplicate', 176, NULL, NULL, 2),
(60310, NULL, NULL, 0, NULL, 'duplicate', 176, NULL, NULL, 3),
(60311, NULL, NULL, 0, NULL, 'duplicate', 176, NULL, NULL, 6),
(60312, NULL, NULL, 0, NULL, 'insert', 179, NULL, NULL, 1),
(60313, NULL, NULL, 0, NULL, 'insert', 179, NULL, NULL, 2),
(60314, NULL, NULL, 0, NULL, 'insert', 179, NULL, NULL, 3),
(60315, NULL, NULL, 0, NULL, 'insert', 179, NULL, NULL, 6),
(60316, NULL, NULL, 0, NULL, 'update', 179, NULL, NULL, 1),
(60317, NULL, NULL, 0, NULL, 'update', 179, NULL, NULL, 2),
(60318, NULL, NULL, 0, NULL, 'update', 179, NULL, NULL, 3),
(60319, NULL, NULL, 0, NULL, 'update', 179, NULL, NULL, 6),
(60320, NULL, NULL, 0, NULL, 'view', 179, NULL, NULL, 1),
(60321, NULL, NULL, 0, NULL, 'view', 179, NULL, NULL, 2),
(60322, NULL, NULL, 0, NULL, 'view', 179, NULL, NULL, 3),
(60323, NULL, NULL, 0, NULL, 'view', 179, NULL, NULL, 6),
(60324, NULL, NULL, 0, NULL, 'delete', 179, NULL, NULL, 1),
(60325, NULL, NULL, 0, NULL, 'delete', 179, NULL, NULL, 2),
(60326, NULL, NULL, 0, NULL, 'delete', 179, NULL, NULL, 3),
(60327, NULL, NULL, 0, NULL, 'delete', 179, NULL, NULL, 6),
(60328, NULL, NULL, 0, NULL, 'duplicate', 179, NULL, NULL, 1),
(60329, NULL, NULL, 0, NULL, 'duplicate', 179, NULL, NULL, 2),
(60330, NULL, NULL, 0, NULL, 'duplicate', 179, NULL, NULL, 3),
(60331, NULL, NULL, 0, NULL, 'duplicate', 179, NULL, NULL, 6),
(60332, NULL, NULL, 1, NULL, 'insert', 182, NULL, NULL, 1),
(60333, NULL, NULL, 1, NULL, 'insert', 182, NULL, NULL, 2),
(60334, NULL, NULL, 0, NULL, 'insert', 182, NULL, NULL, 3),
(60335, NULL, NULL, 0, NULL, 'insert', 182, NULL, NULL, 6),
(60336, NULL, NULL, 1, NULL, 'update', 182, NULL, NULL, 1),
(60337, NULL, NULL, 1, NULL, 'update', 182, NULL, NULL, 2),
(60338, NULL, NULL, 0, NULL, 'update', 182, NULL, NULL, 3),
(60339, NULL, NULL, 0, NULL, 'update', 182, NULL, NULL, 6),
(60340, NULL, NULL, 1, NULL, 'view', 182, NULL, NULL, 1),
(60341, NULL, NULL, 1, NULL, 'view', 182, NULL, NULL, 2),
(60342, NULL, NULL, 0, NULL, 'view', 182, NULL, NULL, 3),
(60343, NULL, NULL, 0, NULL, 'view', 182, NULL, NULL, 6),
(60344, NULL, NULL, 1, NULL, 'delete', 182, NULL, NULL, 1),
(60345, NULL, NULL, 1, NULL, 'delete', 182, NULL, NULL, 2),
(60346, NULL, NULL, 0, NULL, 'delete', 182, NULL, NULL, 3),
(60347, NULL, NULL, 0, NULL, 'delete', 182, NULL, NULL, 6),
(60348, NULL, NULL, 1, NULL, 'duplicate', 182, NULL, NULL, 1),
(60349, NULL, NULL, 1, NULL, 'duplicate', 182, NULL, NULL, 2),
(60350, NULL, NULL, 0, NULL, 'duplicate', 182, NULL, NULL, 3),
(60351, NULL, NULL, 0, NULL, 'duplicate', 182, NULL, NULL, 6),
(60352, NULL, NULL, 1, NULL, 'insert', 239, NULL, NULL, 1),
(60353, NULL, NULL, 0, NULL, 'insert', 239, NULL, NULL, 2),
(60354, NULL, NULL, 0, NULL, 'insert', 239, NULL, NULL, 3),
(60355, NULL, NULL, 0, NULL, 'insert', 239, NULL, NULL, 6),
(60356, NULL, NULL, 1, NULL, 'update', 239, NULL, NULL, 1),
(60357, NULL, NULL, 0, NULL, 'update', 239, NULL, NULL, 2),
(60358, NULL, NULL, 0, NULL, 'update', 239, NULL, NULL, 3),
(60359, NULL, NULL, 0, NULL, 'update', 239, NULL, NULL, 6),
(60360, NULL, NULL, 1, NULL, 'view', 239, NULL, NULL, 1),
(60361, NULL, NULL, 0, NULL, 'view', 239, NULL, NULL, 2),
(60362, NULL, NULL, 0, NULL, 'view', 239, NULL, NULL, 3),
(60363, NULL, NULL, 0, NULL, 'view', 239, NULL, NULL, 6),
(60364, NULL, NULL, 1, NULL, 'delete', 239, NULL, NULL, 1),
(60365, NULL, NULL, 0, NULL, 'delete', 239, NULL, NULL, 2),
(60366, NULL, NULL, 0, NULL, 'delete', 239, NULL, NULL, 3),
(60367, NULL, NULL, 0, NULL, 'delete', 239, NULL, NULL, 6),
(60368, NULL, NULL, 1, NULL, 'duplicate', 239, NULL, NULL, 1),
(60369, NULL, NULL, 0, NULL, 'duplicate', 239, NULL, NULL, 2),
(60370, NULL, NULL, 0, NULL, 'duplicate', 239, NULL, NULL, 3),
(60371, NULL, NULL, 0, NULL, 'duplicate', 239, NULL, NULL, 6);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `tb_cron_events`
--
ALTER TABLE `tb_cron_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `hook_index` (`hook`),
  ADD KEY `timestamp_index` (`timestamp`);

--
-- Índices de tabela `tb_cruds`
--
ALTER TABLE `tb_cruds`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_cruds_fields`
--
ALTER TABLE `tb_cruds_fields`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_info`
--
ALTER TABLE `tb_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `option_name` (`option_name`),
  ADD KEY `autoload` (`autoload`);

--
-- Índices de tabela `tb_menus`
--
ALTER TABLE `tb_menus`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_pages`
--
ALTER TABLE `tb_pages`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_page_content`
--
ALTER TABLE `tb_page_content`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_tokens`
--
ALTER TABLE `tb_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_type` (`token`,`type`),
  ADD KEY `idx_user_type` (`user_id`,`type`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_status_type` (`status`,`type`);

--
-- Índices de tabela `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_user_roles`
--
ALTER TABLE `tb_user_roles`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_user_role_assignments`
--
ALTER TABLE `tb_user_role_assignments`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tb_user_role_permissions`
--
ALTER TABLE `tb_user_role_permissions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `tb_cron_events`
--
ALTER TABLE `tb_cron_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2374;

--
-- AUTO_INCREMENT de tabela `tb_cruds`
--
ALTER TABLE `tb_cruds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT de tabela `tb_cruds_fields`
--
ALTER TABLE `tb_cruds_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=662;

--
-- AUTO_INCREMENT de tabela `tb_info`
--
ALTER TABLE `tb_info`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de tabela `tb_menus`
--
ALTER TABLE `tb_menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=338;

--
-- AUTO_INCREMENT de tabela `tb_pages`
--
ALTER TABLE `tb_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=338;

--
-- AUTO_INCREMENT de tabela `tb_page_content`
--
ALTER TABLE `tb_page_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269;

--
-- AUTO_INCREMENT de tabela `tb_tokens`
--
ALTER TABLE `tb_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=672;

--
-- AUTO_INCREMENT de tabela `tb_user_roles`
--
ALTER TABLE `tb_user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de tabela `tb_user_role_assignments`
--
ALTER TABLE `tb_user_role_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=857;

--
-- AUTO_INCREMENT de tabela `tb_user_role_permissions`
--
ALTER TABLE `tb_user_role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60372;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
