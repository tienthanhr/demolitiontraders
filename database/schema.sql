-- Demolition Traders Database Schema
-- IdealPOS Integration E-commerce Platform
-- Created: 2025-11-20

CREATE DATABASE IF NOT EXISTS demolitiontraders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE demolitiontraders;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'staff', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    idealpos_customer_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_idealpos_customer (idealpos_customer_id)
) ENGINE=InnoDB;

-- Addresses Table
CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_type ENUM('billing', 'shipping') NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    street_address VARCHAR(255) NOT NULL,
    suburb VARCHAR(100),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postcode VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'New Zealand',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2),
    compare_at_price DECIMAL(10, 2),
    category_id INT,
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 0,
    weight DECIMAL(10, 2),
    dimensions VARCHAR(100),
    condition_type ENUM('new', 'recycled', 'refurbished') DEFAULT 'new',
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    idealpos_product_id VARCHAR(100),
    last_synced_at TIMESTAMP NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_idealpos (idealpos_product_id),
    INDEX idx_featured (is_featured),
    FULLTEXT idx_search (name, description, short_description)
) ENGINE=InnoDB;

-- Product Images Table
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    display_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    guest_email VARCHAR(255),
    status ENUM('pending', 'paid', 'processing', 'ready', 'shipped', 'delivered', 'cancelled', 'completed', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    shipping_amount DECIMAL(10, 2) DEFAULT 0,
    discount_amount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'NZD',
    billing_address TEXT,
    shipping_address TEXT,
    customer_notes TEXT,
    admin_notes TEXT,
    idealpos_order_id VARCHAR(100),
    synced_to_pos BOOLEAN DEFAULT FALSE,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_idealpos_order (idealpos_order_id)
) ENGINE=InnoDB;

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    sku VARCHAR(100) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- Cart Table (for logged in users)
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- Wishlist Table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Wanted Items (Customer requests)
CREATE TABLE IF NOT EXISTS wanted_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(20),
    status ENUM('pending', 'found', 'closed') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Sell Items (Items people want to sell to us)
CREATE TABLE IF NOT EXISTS sell_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    condition_type VARCHAR(50),
    quantity INT,
    asking_price DECIMAL(10, 2),
    images TEXT,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    status ENUM('pending', 'reviewing', 'approved', 'rejected', 'purchased') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- IdealPOS Sync Log
CREATE TABLE IF NOT EXISTS idealpos_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_type ENUM('products', 'inventory', 'orders', 'customers') NOT NULL,
    direction ENUM('from_pos', 'to_pos') NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    records_processed INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    error_message TEXT,
    sync_data JSON,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- Newsletter Subscribers
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('subscribed', 'unsubscribed') DEFAULT 'subscribed',
    token VARCHAR(255) UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Reviews Table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- Insert Default Categories
INSERT INTO categories (name, slug, description, display_order) VALUES
('Doors', 'doors', 'Wide selection of new and recycled doors', 1),
('Windows', 'windows', 'Aluminium and wooden windows', 2),
('Timber & Landscaping', 'timber-landscaping', 'Native timber and landscaping materials', 3),
('Plywood', 'plywood', 'Various grades of plywood', 4),
('Kitchens', 'kitchens', 'Kitchen units and fittings', 5),
('Pavers', 'pavers', 'Concrete and stone pavers', 6),
('Roofing', 'roofing', 'Roofing iron and materials', 7),
('Flooring', 'flooring', 'T&G flooring and other flooring options', 8),
('Hardware', 'hardware', 'Door handles, hinges, and hardware', 9),
('ACP Seratone', 'acp-seratone', 'ACP boards and panels', 10);

-- Insert Default Admin User (password: admin123)
INSERT INTO users (email, password, first_name, last_name, role, status) VALUES
('admin@demolitiontraders.co.nz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active');

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Demolition Traders', 'Website name'),
('site_email', 'info@demolitiontraders.co.nz', 'Contact email'),
('site_phone', '0800 DEMOLITION', 'Contact phone'),
('site_address', '249 Kahikatea Drive, Hamilton', 'Physical address'),
('tax_rate', '0.15', 'GST rate (15%)'),
('currency', 'NZD', 'Currency code'),
('currency_symbol', '$', 'Currency symbol'),
('idealpos_sync_enabled', '1', 'Enable IdealPOS sync'),
('idealpos_sync_interval', '300', 'Sync interval in seconds'),
('maintenance_mode', '0', 'Maintenance mode status');

-- Insert Sample Products
INSERT INTO products (sku, name, slug, description, short_description, price, category_id, stock_quantity, condition_type, is_featured) VALUES
('ACP-WG-2400', 'ACP Board 2400 x 1200 White Gloss', 'acp-board-2400-white-gloss', 'High quality ACP board with white gloss finish. Perfect for interior and exterior applications.', 'White gloss ACP board 2400x1200mm', 145.00, 10, 50, 'new', TRUE),
('PLY-GRV-2440', 'Grooved Plywood Cladding Structural Grade 2440 x 1200', 'grooved-plywood-cladding-structural', 'Structural grade grooved plywood suitable for cladding applications.', 'Structural grooved plywood 2440x1200mm', 89.00, 4, 30, 'new', TRUE),
('DOOR-RIMU-01', 'Recycled Rimu Door 2040 x 820', 'recycled-rimu-door-2040', 'Beautiful recycled rimu door with original hardware. Unique character pieces.', 'Recycled rimu door with character', 450.00, 1, 5, 'recycled', TRUE),
('WIN-AL-01', 'Aluminium Window 1200 x 1200', 'aluminium-window-1200', 'Double glazed aluminium window in good condition.', 'Double glazed aluminium window', 280.00, 2, 15, 'recycled', FALSE);

COMMIT;
