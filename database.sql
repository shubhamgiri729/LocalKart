-- Create database
DROP DATABASE IF EXISTS multi_vendor_ecommerce;
CREATE DATABASE multi_vendor_ecommerce;
USE multi_vendor_ecommerce;


-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'shopkeeper', 'customer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- VENDORS TABLE
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(100) NOT NULL,
    address TEXT,
    verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

-- PRODUCTS TABLE
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- CART ITEMS TABLE
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ORDERS TABLE
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'shipped', 'delivered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

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
);


-- INSERT SAMPLE DATA
SET FOREIGN_KEY_CHECKS=0;

-- USERS
INSERT INTO users (id, username, email, password, role) VALUES
(1, 'admin', 'admin@example.com', 'password', 'admin'),
(2, 'techworld', 'techworld@example.com', 'password', 'shopkeeper'),
(3, 'stylehub', 'stylehub@example.com', 'password', 'shopkeeper'),
(4, 'bookbarn', 'bookbarn@example.com', 'password', 'shopkeeper'),
(5, 'homemart', 'homemart@example.com', 'password', 'shopkeeper'),
(6, 'sportszone', 'sportszone@example.com', 'password', 'shopkeeper'),
(7, 'beautycorner', 'beautycorner@example.com', 'password', 'shopkeeper'),
(8, 'grocerly', 'grocerly@example.com', 'password', 'shopkeeper'),
(9, 'gadgetgalaxy', 'gadgetgalaxy@example.com', 'password', 'shopkeeper'),
(10, 'trendify', 'trendify@example.com', 'password', 'shopkeeper'),
(11, 'user1', 'user1@example.com', 'password', 'customer'),
(12, 'user2', 'user2@example.com', 'password', 'customer'),
(13, 'user3', 'user3@example.com', 'password', 'customer'),
(14, 'user4', 'user4@example.com', 'password', 'customer'),
(15, 'user5', 'user5@example.com', 'password', 'customer'),
(16, 'user6', 'user6@example.com', 'password', 'customer'),
(17, 'user7', 'user7@example.com', 'password', 'customer'),
(18, 'user8', 'user8@example.com', 'password', 'customer'),
(19, 'user9', 'user9@example.com', 'password', 'customer'),
(20, 'user10', 'user10@example.com', 'password', 'customer');

-- VENDORS
INSERT INTO vendors (id, user_id, store_name, address, verified) VALUES
(1, 2, 'TechWorld', '123 Silicon Ave', TRUE),
(2, 3, 'StyleHub', '456 Fashion St', TRUE),
(3, 4, 'BookBarn', '789 Reading Rd', TRUE),
(4, 5, 'HomeMart', '12 Comfort Ln', TRUE),
(5, 6, 'SportsZone', '99 Fitness Blvd', TRUE),
(6, 7, 'BeautyCorner', '88 Glamour Ave', TRUE),
(7, 8, 'Grocerly', '21 Fresh Market Rd', TRUE),
(8, 9, 'GadgetGalaxy', '55 Innovation Park', TRUE),
(9, 10, 'Trendify', '111 Style Plaza', TRUE),
(10, 2, 'SmartShops', '777 Commerce St', TRUE);

-- CATEGORIES
INSERT INTO categories (name, description) VALUES
('Electronics', 'Devices and gadgets'),
('Clothing', 'Men and women apparel'),
('Books', 'Fiction and educational books'),
('Home & Kitchen', 'Furniture, utensils, and decor'),
('Sports', 'Sports gear and fitness items'),
('Beauty', 'Cosmetics and skincare products'),
('Groceries', 'Daily essentials and food items');

-- PRODUCTS
INSERT INTO products (vendor_id, category_id, name, description, price, stock) VALUES
(1, 1, 'Wireless Mouse', 'Ergonomic wireless mouse', 15.99, 100),
(1, 1, 'Bluetooth Keyboard', 'Compact keyboard', 25.49, 80),
(1, 1, 'USB-C Charger', 'Fast charging adapter', 19.99, 120),
(1, 1, 'Gaming Headset', 'Surround sound headset', 59.99, 50),
(2, 2, 'Men T-Shirt', 'Cotton casual t-shirt', 12.99, 200),
(2, 2, 'Women Jeans', 'Slim fit denim jeans', 39.99, 150),
(2, 2, 'Leather Jacket', 'Classic black leather', 89.99, 75),
(2, 2, 'Hoodie', 'Comfortable sweatshirt', 29.99, 90),
(3, 3, 'Novel: The Lost World', 'Adventure fiction', 9.99, 120),
(3, 3, 'Data Science Handbook', 'Learn data science fundamentals', 29.99, 90),
(3, 3, 'Cooking 101', 'Beginner’s recipe book', 14.99, 130),
(3, 3, 'Mindset', 'Personal growth book', 19.99, 100),
(4, 4, 'Non-stick Frying Pan', 'Durable kitchen pan', 19.99, 110),
(4, 4, 'Sofa Set', 'Comfortable 3-piece sofa', 499.99, 15),
(4, 4, 'Dinner Plate Set', 'Porcelain 12-piece set', 39.99, 60),
(5, 5, 'Football', 'Standard size football', 24.99, 70),
(5, 5, 'Tennis Racket', 'Lightweight and durable', 49.99, 60),
(5, 5, 'Yoga Mat', 'Non-slip exercise mat', 19.99, 90),
(6, 6, 'Face Cream', 'Moisturizing cream 50ml', 14.99, 90),
(6, 6, 'Lipstick', 'Long-lasting matte finish', 9.49, 150),
(6, 6, 'Shampoo', 'For smooth silky hair', 8.99, 200),
(7, 7, 'Organic Rice', '1kg premium quality', 2.99, 500),
(7, 7, 'Olive Oil', '500ml extra virgin olive oil', 7.99, 300),
(7, 7, 'Honey', 'Pure organic honey 250g', 5.99, 250),
(8, 1, 'Smart Watch', 'Fitness tracking smartwatch', 59.99, 75),
(8, 1, 'Bluetooth Speaker', 'Portable sound system', 29.99, 100),
(9, 2, 'Jacket', 'Winter jacket for men', 59.99, 80),
(9, 2, 'Sneakers', 'Comfortable sports shoes', 49.99, 100),
(10, 2, 'Sunglasses', 'UV protected eyewear', 19.99, 120),
(10, 2, 'Dress', 'Summer floral dress', 29.99, 100);

SET FOREIGN_KEY_CHECKS=1;

