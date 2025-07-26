
-- Create Database
CREATE DATABASE IF NOT EXISTS inventory_app;
USE inventory_app;

-- Table: users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'Croissant 1',
  reset_code VARCHAR(100),
  reset_code_expiry DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  avatar_url VARCHAR(255)
);

-- Table: products
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  category VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  user_id INT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: sales
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  notes TEXT,
  sold_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  user_id INT,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: stock
CREATE TABLE stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  quantity INT NOT NULL,
  notes TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  user_id INT,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: notifications
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  message TEXT NOT NULL,
  target_compenent VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  seen BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
