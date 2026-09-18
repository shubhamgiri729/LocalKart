-- LocalKart — DEMO database (for anyone cloning the repo)
-- Small, self-contained dataset with no real personal data — safe to
-- commit to a public repo. Covers every role and feature: verified
-- and unverified stores, orders in every status, helpdesk tickets in
-- every status, and store ratings.
--
-- Login as:
--   Admin:      admin / admin123
--   Shopkeeper: techworld / password123   (or stylehub, bookbarn,
--               homemart, sportszone, vishalelectronics — same password)
--   Customer:   customer1 / password123   (through customer6)

-- Create database
DROP DATABASE IF EXISTS multi_vendor_ecommerce;
CREATE DATABASE multi_vendor_ecommerce;
USE multi_vendor_ecommerce;
SET NAMES utf8mb4;


-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'shopkeeper', 'customer') NOT NULL,
    reset_token VARCHAR(64) NULL,
    reset_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- VENDORS TABLE
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(100) NOT NULL,
    address TEXT,
    verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- PRODUCTS TABLE
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CART ITEMS TABLE (schema kept for completeness — the app currently
-- stores the active cart in $_SESSION['cart'], not this table, so it's
-- intentionally left empty in the seed data below.)
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ORDERS TABLE
-- Status values match what the app actually sets: process_order.php writes
-- 'pending', shopkeeper.php's status page sets 'dispatched', and
-- shopkeeper.php's own dashboard sets 'delivered'. ('shipped' was in the
-- original enum but nothing in the code ever sets it.)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'dispatched', 'delivered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ORDER ITEMS TABLE
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    vendor_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- HELPDESK TABLE
CREATE TABLE IF NOT EXISTS helpdesk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vendor_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    response TEXT,
    status ENUM('open', 'answered', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- VENDOR RATINGS TABLE
CREATE TABLE IF NOT EXISTS vendor_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_vendor_customer (vendor_id, user_id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- INSERT SAMPLE DATA
SET FOREIGN_KEY_CHECKS=0;

-- CATEGORIES
INSERT INTO categories (id, name, description) VALUES
(1, 'Electronics', 'Devices and gadgets'),
(2, 'Clothing', 'Men and women apparel'),
(3, 'Books', 'Fiction and educational books'),
(4, 'Home & Kitchen', 'Furniture, utensils, and decor'),
(5, 'Sports', 'Sports gear and fitness items'),
(6, 'Beauty', 'Cosmetics and skincare products'),
(7, 'Groceries', 'Daily essentials and food items');

-- USERS
INSERT INTO users (id, username, email, password, role, created_at) VALUES
(1, 'admin', 'admin@example.com', '$2b$10$Vd472sRi2o5Dy79SqkqWXezewPVG6kTIj/B1nzzScpDQZnKPV2USm', 'admin', '2026-06-19 10:37:17'),
(2, 'techworld', 'techworld@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'shopkeeper', '2026-06-19 10:37:17'),
(3, 'stylehub', 'stylehub@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'shopkeeper', '2026-06-19 10:37:17'),
(4, 'bookbarn', 'bookbarn@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'shopkeeper', '2026-06-19 10:37:17'),
(5, 'homemart', 'homemart@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'shopkeeper', '2026-06-19 10:37:17'),
(6, 'sportszone', 'sportszone@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'shopkeeper', '2026-06-19 10:37:17'),
(7, 'vishalelectronics', 'vishalelectronics@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'shopkeeper', '2026-06-19 10:37:17'),
(8, 'customer1', 'customer1@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'customer', '2026-06-19 10:37:17'),
(9, 'customer2', 'customer2@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'customer', '2026-06-19 10:37:17'),
(10, 'customer3', 'customer3@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'customer', '2026-06-19 10:37:17'),
(11, 'customer4', 'customer4@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'customer', '2026-06-19 10:37:17'),
(12, 'customer5', 'customer5@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'customer', '2026-06-19 10:37:17'),
(13, 'customer6', 'customer6@example.com', '$2b$10$nPmt8pNsxCtoj5zt4hdZhuBYyPr75oELOQ2QKTx.hB7qjmBP1cOsi', 'customer', '2026-06-19 10:37:17');

-- VENDORS
INSERT INTO vendors (id, user_id, store_name, address, verified) VALUES
(1, 2, 'TechWorld', 'Andheri West, Mumbai-400058', 1),
(2, 3, 'StyleHub', 'Bandra West, Mumbai-400050', 1),
(3, 4, 'BookBarn', 'Dadar West, Mumbai-400028', 1),
(4, 5, 'HomeMart', 'Powai, Mumbai-400076', 1),
(5, 6, 'SportsZone', 'Malad West, Mumbai-400064', 1),
(11, 7, 'Vishal Electronics', 'Ghatkopar East, Mumbai-400077', 0);

-- PRODUCTS
INSERT INTO products (id, vendor_id, category_id, name, description, price, stock, image) VALUES
(1, 1, 1, 'Wireless Mouse', 'Ergonomic 2.4GHz wireless mouse', 649.00, 100, 'wireless_mouse.jpg'),
(2, 1, 1, 'Bluetooth Keyboard', 'Compact slim keyboard', 1299.00, 80, 'bluetooth_keyboard.jpg'),
(3, 1, 1, 'USB-C Fast Charger 65W', 'Fast charging adapter, USB-C PD', 899.00, 120, 'usb_c_charger.webp'),
(4, 1, 1, 'Gaming Headset', 'Surround sound headset with mic', 2499.00, 50, 'gamming_headset.jpg'),
(5, 1, 1, 'Power Bank 20000mAh', 'Dual-port fast charging power bank', 1499.00, 70, 'power_bank.jpg'),
(6, 1, 1, 'HDMI Cable 2m', 'High-speed 4K HDMI cable', 299.00, 200, 'hdmi_cable.jpg'),
(7, 2, 2, 'Men T-Shirt', 'Cotton casual round-neck t-shirt', 499.00, 200, 'men_tshirt.jpg'),
(8, 2, 2, 'Women Jeans', 'Slim fit denim jeans', 1299.00, 150, 'women_jeans.webp'),
(9, 2, 2, 'Leather Jacket', 'Classic black leather jacket', 3999.00, 40, 'leather_jacket.webp'),
(10, 2, 2, 'Hoodie', 'Comfortable fleece sweatshirt', 999.00, 90, 'hoodie.webp'),
(11, 2, 2, 'Formal Shirt', 'Slim fit cotton formal shirt', 899.00, 100, 'formal_shirt.jpg'),
(12, 3, 3, 'Novel: The Lost World', 'Adventure fiction paperback', 299.00, 120, 'the_last_world_novel.jpg'),
(13, 3, 3, 'Data Science Handbook', 'Learn data science fundamentals', 599.00, 90, 'the_data_science_hand_book.jpg'),
(14, 3, 3, 'Cooking 101', 'Beginner''s recipe book', 349.00, 130, 'cooking_101_book.jpg'),
(15, 3, 3, 'Mindset', 'Personal growth and psychology', 399.00, 100, 'mindset_book.jpg'),
(16, 3, 3, 'Children''s Story Collection', 'Illustrated bedtime stories', 249.00, 150, 'kids_stories.jpg'),
(17, 4, 4, 'Non-stick Frying Pan', 'Durable 24cm kitchen pan', 799.00, 110, 'non_steaky_frying_pan.jpg'),
(18, 4, 4, 'Sofa Set (3-Piece)', 'Comfortable fabric sofa set', 18999.00, 8, 'sofa_set.jpg'),
(19, 4, 4, 'Dinner Plate Set', 'Porcelain 12-piece dinner set', 1299.00, 60, 'dinner_plate_set.webp'),
(20, 4, 4, 'Table Lamp', 'Ceramic bedside table lamp', 899.00, 55, 'table_lamp.jpg'),
(21, 4, 4, 'Pressure Cooker 5L', 'Stainless steel pressure cooker', 1799.00, 45, 'pressure_cooker.jpg'),
(22, 5, 5, 'Football', 'Standard size 5 football', 699.00, 70, 'football.png'),
(23, 5, 5, 'Tennis Racket', 'Lightweight aluminium racket', 2199.00, 40, 'tennis_racket.webp'),
(24, 5, 5, 'Yoga Mat', 'Non-slip 6mm exercise mat', 599.00, 90, 'yoga_mat.jpg'),
(25, 5, 5, 'Skipping Rope', 'Adjustable speed skipping rope', 249.00, 150, 'skipping_rope.jpg'),
(26, 5, 5, 'Dumbbell Set 10kg', 'Rubber coated dumbbell pair', 1999.00, 30, 'dumbbell_set.jpg'),
(27, 11, 1, 'Vivo Y20', 'Budget Android smartphone, 4GB/64GB', 10999.00, 25, 'vivo_y20.jpg'),
(28, 11, 1, 'Vivo Y15', 'Budget Android smartphone, 3GB/32GB', 8999.00, 20, 'vivo_y15.jpg'),
(29, 11, 1, 'Wired Earphones', 'In-ear wired earphones with mic', 249.00, 150, 'wired_earphones.jpg');

-- ORDERS
INSERT INTO orders (id, customer_id, total, status, created_at) VALUES
(1, 8, 3497.00, 'delivered', '2026-09-01 12:00:00'),
(2, 9, 499.00, 'dispatched', '2026-09-04 12:00:00'),
(3, 10, 847.00, 'pending', '2026-09-07 12:00:00'),
(4, 11, 1598.00, 'delivered', '2026-09-10 12:00:00'),
(5, 12, 2898.00, 'dispatched', '2026-09-13 12:00:00'),
(6, 13, 8999.00, 'pending', '2026-09-15 12:00:00');

-- ORDER ITEMS
INSERT INTO order_items (id, order_id, product_id, vendor_id, quantity, price) VALUES
(1, 1, 3, 1, 1, 899.00),
(2, 1, 2, 1, 2, 1299.00),
(3, 2, 7, 2, 1, 499.00),
(4, 3, 16, 3, 1, 249.00),
(5, 3, 12, 3, 2, 299.00),
(6, 4, 17, 4, 2, 799.00),
(7, 5, 23, 5, 1, 2199.00),
(8, 5, 22, 5, 1, 699.00),
(9, 6, 28, 11, 1, 8999.00);

-- HELPDESK
INSERT INTO helpdesk (id, user_id, vendor_id, subject, message, response, status, created_at, updated_at) VALUES
(1, 8, 11, 'Wrong Product Delivery', 'I ordered a Vivo Y20 but received a Vivo Y15 instead. Please replace the order or refund the money.', NULL, 'open', '2026-09-15 11:31:02', '2026-09-15 11:31:02'),
(2, 9, 4, 'Sofa delivered with a scratch', 'The sofa set I received has a visible scratch on the left armrest. Can I get a replacement or partial refund?', 'Sorry about that! We have arranged a replacement delivery for next week, no extra cost.', 'answered', '2026-09-05 09:15:00', '2026-09-06 10:00:00'),
(3, 11, 1, 'Power bank not charging', 'The power bank does not hold charge for more than an hour. Requesting a replacement.', NULL, 'open', '2026-09-14 17:00:00', '2026-09-14 17:00:00'),
(4, 10, 5, 'Late delivery for tennis racket', 'My order was supposed to arrive in 3 days but it has been a week with no update.', 'Apologies for the delay — this has been dispatched today and should reach you within 2 days.', 'closed', '2026-09-02 10:00:00', '2026-09-04 08:30:00');

-- VENDOR RATINGS
INSERT INTO vendor_ratings (id, vendor_id, user_id, rating, created_at) VALUES
(1, 11, 8, 5, '2026-09-15 16:45:26'),
(2, 1, 9, 4, '2026-09-03 09:00:00'),
(3, 1, 10, 5, '2026-09-06 14:00:00'),
(4, 2, 11, 4, '2026-09-09 11:00:00'),
(5, 4, 12, 2, '2026-09-11 17:00:00'),
(6, 5, 13, 5, '2026-09-12 10:00:00');

SET FOREIGN_KEY_CHECKS=1;