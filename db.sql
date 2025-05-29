

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    stock INT NOT NULL,
    image VARCHAR(255)
);

-- Mark some products as featured (replace IDs with your actual product IDs)
UPDATE products SET featured = 1 WHERE id IN (1, 2, 3);
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
ALTER TABLE users ADD COLUMN is_seller BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
UPDATE users SET is_admin = 1 WHERE id = 2 ;
select * from  users;

CREATE TABLE IF NOT EXISTS user_carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
-- Add shipping and payment columns to orders table
ALTER TABLE orders
ADD COLUMN shipping_name VARCHAR(100) NOT NULL AFTER total,
ADD COLUMN shipping_email VARCHAR(100) NOT NULL AFTER shipping_name,
ADD COLUMN shipping_address TEXT NOT NULL AFTER shipping_email,
ADD COLUMN shipping_city VARCHAR(100) NOT NULL AFTER shipping_address,
ADD COLUMN shipping_state VARCHAR(100) NOT NULL AFTER shipping_city,
ADD COLUMN shipping_zip VARCHAR(20) NOT NULL AFTER shipping_state,
ADD COLUMN payment_method VARCHAR(50) NOT NULL AFTER shipping_zip;
ALTER TABLE orders 
MODIFY COLUMN status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending';
ALTER TABLE orders
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN tracking_number VARCHAR(50) AFTER payment_method;
-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role) 
VALUES ('Admin', 'admin@example.com', 'admin1234', 'admin');SELECT * FROM user ;


-- Sample products
INSERT INTO products (name, description, price, category, stock, image) VALUES
('Wireless Headphones', 'Premium noise-cancelling headphones', 250, 'Electronics', 50, 'headphones.jpg'),
('Smart Watch', 'Fitness tracker with heart rate monitor', 1500, 'Electronics', 30, 'smartwatch.jpg'),
('Laptop Backpack', 'Water-resistant with USB charging port', 800, 'Accessories', 100, 'backpack.jpg');

CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);

ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_categories (
    product_id INT,
    category_id INT,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);



CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `email` VARCHAR(255),
    `subject` VARCHAR(255) NOT NULL,
    `feedback_text` TEXT NOT NULL,
    `rating` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 



ALTER TABLE categories 
MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY,
MODIFY COLUMN name VARCHAR(255) NOT NULL,
MODIFY COLUMN icon VARCHAR(50) DEFAULT 'bi-tag',
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active',
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;


DROP TABLE verification_codes;


-- Make password field nullable for social login users
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL;

-- Add social_id field for Google users
ALTER TABLE users ADD COLUMN social_id VARCHAR(255) NULL AFTER password;

-- Add social_provider field
ALTER TABLE users ADD COLUMN social_provider ENUM('google') NULL AFTER social_id;


-- Add email_verified 
ALTER TABLE users
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER password; 

ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'user') DEFAULT 'user'; 



-- First, update any NULL or invalid roles to 'user'
UPDATE users SET role = 'user' WHERE role IS NULL OR role NOT IN ('admin', 'user');

-- Then temporarily change role to VARCHAR to ensure no data loss
ALTER TABLE users MODIFY COLUMN role VARCHAR(10);

-- Update any remaining invalid values to 'user'
UPDATE users SET role = 'user' WHERE role NOT IN ('admin', 'user');

-- Now we can safely convert it to ENUM
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') DEFAULT 'user'; 

ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'seller') DEFAULT 'user'; 


ALTER TABLE orders ADD COLUMN cancellation_date TIMESTAMP NULL DEFAULT NULL;

-- Add index for better query performance
ALTER TABLE orders ADD INDEX idx_cancellation_date (cancellation_date); 



CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 