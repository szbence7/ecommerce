-- Create languages table
CREATE TABLE IF NOT EXISTS languages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    is_default BOOLEAN DEFAULT false,
    flag_icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create translations table
CREATE TABLE IF NOT EXISTS translations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    language_code VARCHAR(5) NOT NULL,
    translation_key VARCHAR(255) NOT NULL,
    translation_value TEXT NOT NULL,
    context VARCHAR(50) DEFAULT 'shop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (language_code, translation_key, context)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create product translations table
CREATE TABLE IF NOT EXISTS product_translations (
    product_id INT NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, language_code),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create category translations table
CREATE TABLE IF NOT EXISTS category_translations (
    category_id INT NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id, language_code),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default languages
INSERT INTO languages (code, name, is_active, is_default) VALUES
('hu', 'Magyar', 1, 1),
('en', 'English', 1, 0)
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    is_active = VALUES(is_active);

-- Insert some basic translations
INSERT INTO translations (language_code, translation_key, translation_value, context) VALUES
-- Navigation
('hu', 'nav.home', 'Főoldal', 'shop'),
('en', 'nav.home', 'Home', 'shop'),
('hu', 'nav.products', 'Termékek', 'shop'),
('en', 'nav.products', 'Products', 'shop'),
('hu', 'nav.categories', 'Kategóriák', 'shop'),
('en', 'nav.categories', 'Categories', 'shop'),
('hu', 'nav.cart', 'Kosár', 'shop'),
('en', 'nav.cart', 'Cart', 'shop'),

-- Product related
('hu', 'product.price', 'Ár: {price}', 'shop'),
('en', 'product.price', 'Price: {price}', 'shop'),
('hu', 'product.outofstock', 'Nincs készleten', 'shop'),
('en', 'product.outofstock', 'Out of stock', 'shop'),
('hu', 'product.addtocart', 'Kosárba', 'shop'),
('en', 'product.addtocart', 'Add to Cart', 'shop'),

-- Cart related
('hu', 'cart.empty', 'A kosár üres', 'shop'),
('en', 'cart.empty', 'Cart is empty', 'shop'),
('hu', 'cart.checkout', 'Fizetés', 'shop'),
('en', 'cart.checkout', 'Checkout', 'shop'),

-- Admin panel
('hu', 'admin.dashboard', 'Vezérlőpult', 'admin'),
('en', 'admin.dashboard', 'Dashboard', 'admin'),
('hu', 'admin.products', 'Termékek', 'admin'),
('en', 'admin.products', 'Products', 'admin'),
('hu', 'admin.categories', 'Kategóriák', 'admin'),
('en', 'admin.categories', 'Categories', 'admin'),
('hu', 'admin.orders', 'Rendelések', 'admin'),
('en', 'admin.orders', 'Orders', 'admin'),
('hu', 'admin.settings', 'Beállítások', 'admin'),
('en', 'admin.settings', 'Settings', 'admin')
ON DUPLICATE KEY UPDATE 
    translation_value = VALUES(translation_value);
