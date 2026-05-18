-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 18, 2026 alle 13:59
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'Smartphone', 'smartphone'),
(2, 'Laptop', 'laptop'),
(3, 'Accessori', 'accessori'),
(4, 'Gaming', 'gaming'),
(9, 'Casa', 'casa'),
(10, 'Sport', 'sport');

-- --------------------------------------------------------

--
-- Struttura della tabella `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_email` varchar(120) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('created','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'created',
  `payment_method` enum('wallet','card','paypal','mixed') NOT NULL DEFAULT 'card',
  `wallet_amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stripe_amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paypal_amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `paypal_order_id` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_email`, `total_amount`, `status`, `payment_method`, `wallet_amount_paid`, `stripe_amount_paid`, `paypal_amount_paid`, `stripe_session_id`, `paypal_order_id`, `payment_status`, `notes`, `created_at`) VALUES
(1, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, 'cs_test_a1nzWg60G1cIrWGYQpC3HQoUPnXb9QMUxwPz2EDyuEFgXHWeGR3Jy7Ohw8', NULL, 'pending', NULL, '2026-05-03 10:17:45'),
(2, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, 'cs_test_a1SaM6QzWjvXDseXoc15TLZNMsT3OVFoGQ62WA3dPCBd15fXAqMdRmRyWu', NULL, 'pending', NULL, '2026-05-03 11:13:11'),
(3, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, 'cs_test_a1pkMsIVECXIEaDWiETNhLw02oJZDCaIqJB2NqeLl0j8iDeYuEpjZEw31s', NULL, 'pending', NULL, '2026-05-03 11:18:03'),
(4, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, NULL, NULL, 'pending', NULL, '2026-05-03 11:49:04'),
(5, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, NULL, NULL, 'pending', NULL, '2026-05-03 11:49:22'),
(6, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, NULL, NULL, 'pending', NULL, '2026-05-03 11:49:24'),
(7, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, 'cs_test_a1pRLDOmqV4R8NZhyNMjG8kZyYEDfyEpBv7wV2nKHBgsBi1BmPpiyXqYwN', NULL, 'pending', NULL, '2026-05-03 11:50:02'),
(8, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, '', '', 0.00, 999.00, 0.00, 'cs_test_a1zeyy35bHpmtbH2Cs0WkVkvkQvwAAY6LLqlhHOOhfo9QNpHmVBUzPS0A1', NULL, 'pending', NULL, '2026-05-03 11:52:38'),
(12, 9, 'Succhino', 'bomoho1918@kynninc.com', 999.00, 'paid', '', 0.00, 999.00, 0.00, 'cs_test_a1HLVPJhMiYV8mDajLhKbJLZkmboqzE8y09s1JeR0XjNirAq8qB5Oxnxsj', NULL, 'paid', NULL, '2026-05-03 11:59:42'),
(13, 3, 'Eduardo Sandragan', 'sandragan.eduardo.studente@itispaleocapa.it', 2997.00, 'created', '', 0.00, 2997.00, 0.00, 'cs_test_a1JlAKk2ZZp3GyKsu7elJGfzk8uM5EAD7LZwPXwrJGzOFZdpfBtt509ytz', NULL, 'pending', NULL, '2026-05-12 15:05:56'),
(14, 3, 'Eduardo Sandragan', 'sandragan.eduardo.studente@itispaleocapa.it', 2997.00, 'paid', '', 0.00, 2997.00, 0.00, 'cs_test_a1UYcCeSd54IbiPccY4YCvC7XNpE7TR6uwmXo75OBHT6KEyFaOGWsLsutp', NULL, 'pending', NULL, '2026-05-13 13:15:56'),
(15, 3, 'Eduardo Sandragan', 'sandragan.eduardo.studente@itispaleocapa.it', 699.00, 'paid', '', 0.00, 699.00, 0.00, 'cs_test_a114863A6ZhWCh2HN6e1h2zMacoaLZcPa6ZlXqvLFfPNrDMMMbFriCt7Qw', NULL, 'paid', NULL, '2026-05-13 13:59:14'),
(16, 10, 'AAA', 'nevok10302@imashr.com', 19.90, 'paid', '', 0.00, 19.90, 0.00, 'cs_test_a1gdbWfGv4mnxkyonxoWrfDpvlUt1Ya0Ipq13Ugox9WbwnI8FVvaAjqKcC', NULL, 'paid', NULL, '2026-05-13 14:31:46');

-- --------------------------------------------------------

--
-- Struttura della tabella `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 1, 999.00),
(2, 2, 1, 1, 999.00),
(3, 3, 1, 1, 999.00),
(4, 4, 1, 1, 999.00),
(5, 5, 1, 1, 999.00),
(6, 6, 1, 1, 999.00),
(7, 7, 1, 1, 999.00),
(8, 8, 1, 1, 999.00),
(12, 12, 1, 1, 999.00),
(13, 13, 1, 3, 999.00),
(14, 14, 1, 3, 999.00),
(15, 15, 1, 1, 699.00),
(16, 16, 4, 1, 19.90);

-- --------------------------------------------------------

--
-- Struttura della tabella `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `image_path`, `created_at`) VALUES
(1, 1, 'iPhone 15', 'Smartphone Apple di ultima generazione', 699.00, 8, 'images/iphone15.png', '2026-03-20 11:33:25'),
(2, 2, 'MacBook Pro', 'Laptop Apple ad alte prestazioni', 1999.00, 5, 'images/macbook.png', '2026-03-20 11:33:25'),
(3, 3, 'Caricatore USB-C', 'Caricatore rapido 30W', 29.90, 50, 'images/charger.png', '2026-03-20 11:33:25'),
(4, 3, 'Cover iPhone 15', 'Cover protettiva in silicone per iPhone 15', 19.90, 24, 'images/coveriphone15.png', '2026-03-20 11:33:25'),
(6, 3, 'AirPods Pro', 'Auricolari wireless con cancellazione del rumore', 279.00, 12, 'images/airpodspro.png', '2026-03-20 11:33:25');

-- --------------------------------------------------------

--
-- Struttura della tabella `product_specs`
--

CREATE TABLE `product_specs` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `spec_key` varchar(120) NOT NULL,
  `spec_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `product_specs`
--

INSERT INTO `product_specs` (`id`, `product_id`, `spec_key`, `spec_value`) VALUES
(1, 1, 'Display', '6.1 OLED'),
(2, 1, 'CPU', 'A17 Pro'),
(3, 1, 'Storage', '128GB'),
(4, 2, 'CPU', 'M3'),
(5, 2, 'RAM', '16GB'),
(6, 2, 'Storage', '512GB'),
(7, 3, 'Potenza', '30W'),
(8, 3, 'Tipo', 'USB-C');

-- --------------------------------------------------------

--
-- Struttura della tabella `related_products`
--

CREATE TABLE `related_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `related_product_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `related_products`
--

INSERT INTO `related_products` (`id`, `product_id`, `related_product_id`) VALUES
(1, 1, 3),
(2, 1, 4),
(4, 1, 6),
(5, 2, 3);

-- --------------------------------------------------------

--
-- Struttura della tabella `two_factor_codes`
--

CREATE TABLE `two_factor_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `two_factor_codes`
--

INSERT INTO `two_factor_codes` (`id`, `user_id`, `otp_code`, `expires_at`, `is_used`) VALUES
(1, 5, '157479', '2026-04-01 08:50:46', 1),
(2, 5, '283684', '2026-04-01 08:51:53', 1),
(3, 5, '399817', '2026-04-01 08:51:58', 0),
(4, 6, '565988', '2026-04-01 08:53:38', 1),
(5, 6, '394724', '2026-04-01 08:57:30', 0),
(6, 7, '356798', '2026-04-01 09:14:44', 1),
(7, 7, '575971', '2026-04-01 09:15:05', 1),
(8, 7, '534188', '2026-04-01 09:15:36', 1),
(9, 7, '653568', '2026-04-01 09:26:37', 1),
(10, 7, '707749', '2026-04-01 09:28:39', 0),
(11, 8, '729793', '2026-04-01 15:04:43', 1),
(12, 8, '801568', '2026-04-01 15:55:02', 1),
(13, 8, '678210', '2026-04-01 15:55:13', 1),
(14, 8, '236580', '2026-05-03 12:12:21', 1),
(15, 8, '235280', '2026-05-03 12:25:48', 0),
(16, 9, '880841', '2026-05-03 12:29:23', 1),
(17, 9, '597614', '2026-05-03 12:29:31', 1),
(18, 9, '125257', '2026-05-03 12:29:46', 1),
(19, 9, '885505', '2026-05-03 12:37:19', 1),
(20, 9, '779103', '2026-05-03 12:37:31', 1),
(21, 9, '697237', '2026-05-03 12:39:25', 1),
(22, 9, '546467', '2026-05-03 12:39:33', 1),
(23, 9, '270407', '2026-05-03 12:41:34', 1),
(24, 9, '413262', '2026-05-03 12:42:29', 1),
(25, 9, '252245', '2026-05-03 13:19:53', 1),
(26, 9, '394356', '2026-05-03 13:21:27', 1),
(27, 9, '356676', '2026-05-03 13:22:43', 1),
(28, 9, '844541', '2026-05-03 16:39:51', 1),
(29, 9, '338044', '2026-05-03 16:50:09', 1),
(30, 10, '693916', '2026-05-13 16:24:19', 1),
(31, 10, '187639', '2026-05-13 16:31:10', 1),
(32, 10, '859476', '2026-05-13 16:37:37', 1),
(33, 10, '601052', '2026-05-13 16:39:36', 1),
(34, 10, '694331', '2026-05-13 16:41:03', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(120) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `auth_provider` enum('local','google') NOT NULL DEFAULT 'local',
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(120) NOT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id`, `email`, `google_id`, `auth_provider`, `password`, `full_name`, `wallet_balance`, `role`, `remember_token`, `created_at`, `email_verified_at`) VALUES
(1, 'admin@techshop.it', NULL, 'local', '$2y$10$abcdefghijklmnopqrstuv', 'Admin TechShop', 0.00, 'admin', NULL, '2026-03-20 11:33:25', NULL),
(2, 'user@techshop.it', NULL, 'local', '$2y$10$abcdefghijklmnopqrstuv', 'Mario Rossi', 150.00, 'user', NULL, '2026-03-20 11:33:25', NULL),
(3, 'sandragan.eduardo.studente@itispaleocapa.it', '108264398018672446537', 'google', NULL, 'Eduardo Sandragan', 2000.00, 'admin', NULL, '2026-03-25 07:22:36', '2026-03-25 08:22:36'),
(4, 'carminati.luca.studente@itispaleocapa.it', NULL, 'local', '$2y$10$ADh.cdUp8dvg4KcJ4B8qA.XKWgsmTC0esTRqRr6z5WyzGcH0vqP9i', 'Luca Carminati', 0.00, 'user', NULL, '2026-03-25 07:40:24', NULL),
(5, 'simij27908@availors.com', NULL, 'local', '$2y$10$eOzwmkQes9FgTSZFD/fq5.PKbl/gb2fMjunufzgQfLltzwRxaOL9u', 'Marco Rossi', 0.00, 'user', NULL, '2026-04-01 06:38:22', NULL),
(6, 'bonardi.luca.studente@itispaleocapa.it', NULL, 'local', '$2y$10$Na79tWkVRxHjUMWyVJ.WTOJxeHG/eOhb9t/c7yWf1WuAnHwY/yODm', 'Luca Bonardi', 0.00, 'user', NULL, '2026-04-01 06:43:26', NULL),
(7, 'kesebe6497@cosdas.com', NULL, 'local', '$2y$10$LJJZVsyfrKS19o62sW2MsuVT8fku9rU8JsZBVc6nwyq2RrQ4Wz1gu', 'Marco', 0.00, 'user', NULL, '2026-04-01 07:04:36', NULL),
(8, 'feniwij715@marvetos.com', NULL, 'local', '$2y$10$AzJvctviJYC8fffieflV1OAtZMViCd9d.DQ53Uk8oYqf1OTqBt6Vu', 'Marcolino', 0.00, 'user', NULL, '2026-04-01 12:54:38', NULL),
(9, 'bomoho1918@kynninc.com', NULL, 'local', '$2y$10$a.3XE/uU3Fz/nJBWjCy3AOIhxx4LgtmV.0MO2qXuuZ/jCiD38Jqoa', 'Succhino', 100.00, 'admin', NULL, '2026-05-03 10:17:07', NULL),
(10, 'nevok10302@imashr.com', NULL, 'local', '$2y$10$I44VEjmXFWQ3pLBMcXyNkuf0sfa0txHLS.b5ntSLKydISg3RTvdLm', 'AAA', 0.00, 'user', NULL, '2026-05-13 14:13:20', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `last_activity` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `wallet_logs`
--

CREATE TABLE `wallet_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `wallet_logs`
--

INSERT INTO `wallet_logs` (`id`, `user_id`, `amount`, `description`, `created_at`) VALUES
(1, 9, 100.00, 'Ricarica manuale', '2026-05-03 14:35:15'),
(2, 3, 2000.00, 'Ricarica manuale da admin', '2026-05-12 15:36:36');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indici per le tabelle `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indici per le tabelle `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indici per le tabelle `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indici per le tabelle `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indici per le tabelle `product_specs`
--
ALTER TABLE `product_specs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_specs_product` (`product_id`);

--
-- Indici per le tabelle `related_products`
--
ALTER TABLE `related_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_related_products_main` (`product_id`),
  ADD KEY `fk_related_products_related` (`related_product_id`);

--
-- Indici per le tabelle `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_two_factor_user` (`user_id`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indici per le tabelle `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_user_sessions_user` (`user_id`);

--
-- Indici per le tabelle `wallet_logs`
--
ALTER TABLE `wallet_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_wallet_logs_user` (`user_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT per la tabella `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT per la tabella `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT per la tabella `product_specs`
--
ALTER TABLE `product_specs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT per la tabella `related_products`
--
ALTER TABLE `related_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `wallet_logs`
--
ALTER TABLE `wallet_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `product_specs`
--
ALTER TABLE `product_specs`
  ADD CONSTRAINT `fk_product_specs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `related_products`
--
ALTER TABLE `related_products`
  ADD CONSTRAINT `fk_related_products_main` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_related_products_related` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  ADD CONSTRAINT `fk_two_factor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `wallet_logs`
--
ALTER TABLE `wallet_logs`
  ADD CONSTRAINT `fk_wallet_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
