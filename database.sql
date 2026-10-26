-- Create and use database
CREATE DATABASE IF NOT EXISTS depanini_db;
USE depanini_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    dob DATE,
    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
    photo TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    subcategory VARCHAR(50),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image TEXT,
    owner_id INT NOT NULL,
    status ENUM('available', 'rented', 'onhold') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rentals table
CREATE TABLE IF NOT EXISTS rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    renter_id INT NOT NULL,
    owner_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('inperson', 'visa') DEFAULT 'inperson',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (renter_id) REFERENCES users(id),
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- Chats table
CREATE TABLE IF NOT EXISTS chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    rental_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (rental_id) REFERENCES rentals(id)
);

-- Insert admin user (email: darmoulnourchene53@gmail.com, password: admin0000)
-- Password is hashed using password_hash('admin0000', PASSWORD_DEFAULT)
INSERT INTO users (email, password, first_name, last_name, role) 
VALUES ('darmoulnourchene53@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Insert sample products (owner_id = 1 is the admin user)
INSERT INTO products (name, category, subcategory, description, price, image, owner_id) VALUES
('MacBook Pro 14', 'education', NULL, 'M2 Pro chip, 16GB RAM, 512GB SSD - perfect for coding and design work', 25, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&h=300&fit=crop', 1),
('Oil Paint Set', 'painting', NULL, '24 professional colors with high-quality brushes included', 12, 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&h=300&fit=crop', 1),
('Professional Stethoscope', 'medicine', NULL, 'Dual head medical grade stethoscope with excellent acoustics', 10, 'https://images.unsplash.com/photo-1581595220893-b0739db3b8c6?w=400&h=300&fit=crop', 1),
('NutriBullet Blender', 'home', 'kitchen', 'Professional blender perfect for smoothies and shakes', 8, 'https://images.unsplash.com/photo-1570222094114-d054a817e56b?w=400&h=300&fit=crop', 1),
('PS5 Gaming Console', 'entertainment', NULL, 'Includes 2 controllers and 3 popular games', 30, 'https://images.unsplash.com/photo-1607853202273-797f1c22a38e?w=400&h=300&fit=crop', 1),
('Sony Noise Cancelling Headphones', 'electronic', NULL, 'WH-1000XM4 with industry-leading noise cancellation', 15, 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=400&h=300&fit=crop', 1);