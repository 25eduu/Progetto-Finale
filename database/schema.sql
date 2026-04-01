CREATE DATABASE IF NOT EXISTS ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS wallet_logs;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS two_factor_codes;
DROP TABLE IF EXISTS related_products;
DROP TABLE IF EXISTS product_specs;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    remember_token VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_cart_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cart_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(120) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('created','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'created',
    payment_method ENUM('wallet','card','paypal','mixed') NOT NULL DEFAULT 'card',
    wallet_amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stripe_amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paypal_amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stripe_session_id VARCHAR(255) NULL DEFAULT NULL,
    paypal_order_id VARCHAR(255) NULL DEFAULT NULL,
    payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_specs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    spec_key VARCHAR(120) NOT NULL,
    spec_value VARCHAR(255) NOT NULL,
    CONSTRAINT fk_product_specs_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE related_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    related_product_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_related_products_main
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_related_products_related
        FOREIGN KEY (related_product_id) REFERENCES products(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE two_factor_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_two_factor_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    last_activity DATETIME NOT NULL,
    CONSTRAINT fk_user_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wallet_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO categories (id, name, slug) VALUES
(1, 'Smartphone', 'smartphone'),
(2, 'Laptop', 'laptop'),
(3, 'Accessori', 'accessori');

INSERT INTO products (id, category_id, name, description, price, stock, image_path) VALUES
(1, 1, 'iPhone 15', 'Smartphone Apple di ultima generazione', 999.00, 10, 'images/iphone15.png'),
(2, 2, 'MacBook Pro', 'Laptop Apple ad alte prestazioni', 1999.00, 5, 'images/macbook.png'),
(3, 3, 'Caricatore USB-C', 'Caricatore rapido 30W', 29.90, 50, 'images/charger.jpg'),
(4, 3, 'Cover iPhone 15', 'Cover protettiva in silicone per iPhone 15', 19.90, 25, 'images/coveriphone15.png'),
(5, 3, 'Power Bank 10000mAh', 'Batteria portatile USB-C', 34.90, 20, 'images/powerbank.png'),
(6, 3, 'AirPods Pro', 'Auricolari wireless con cancellazione del rumore', 279.00, 12, 'images/airpods.png');

INSERT INTO product_specs (product_id, spec_key, spec_value) VALUES
(1, 'Display', '6.1 OLED'),
(1, 'CPU', 'A17 Pro'),
(1, 'Storage', '128GB'),
(2, 'CPU', 'M3'),
(2, 'RAM', '16GB'),
(2, 'Storage', '512GB'),
(3, 'Potenza', '30W'),
(3, 'Tipo', 'USB-C');

INSERT INTO related_products (product_id, related_product_id) VALUES
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(2, 3);

INSERT INTO users (id, email, password, full_name, wallet_balance, role) VALUES
(1, 'admin@techshop.it', '$2y$10$abcdefghijklmnopqrstuv', 'Admin TechShop', 0.00, 'admin'),
(2, 'user@techshop.it', '$2y$10$abcdefghijklmnopqrstuv', 'Mario Rossi', 150.00, 'user');