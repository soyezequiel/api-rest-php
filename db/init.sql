-- 1. Creación de la Base de Datos
CREATE DATABASE IF NOT EXISTS seminariophp;
USE seminariophp;
-- 2. Tabla de Usuarios
CREATE TABLE users ( id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50)
NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT
NULL, balance DECIMAL(16, 2) DEFAULT 1000.00, is_admin TINYINT(1) DEFAULT 0,
token VARCHAR(500) NULL, token_expired_at DATETIME NULL, created_at TIMESTAMP
DEFAULT CURRENT_TIMESTAMP );
-- 3. Tabla de Activos (Assets)
CREATE TABLE assets ( id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50)
NOT NULL UNIQUE, current_price DECIMAL(16, 2) NOT NULL, last_update TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP );
-- 4. Tabla de Portfolio (Relaciona usuarios con sus activos)
CREATE TABLE portfolio ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT
NULL, asset_id INT NOT NULL, quantity INT NOT NULL DEFAULT 0, FOREIGN KEY
(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (asset_id)
REFERENCES assets(id), UNIQUE KEY unique_user_asset (user_id, asset_id) );
-- 5. Tabla de Transacciones (Historial)
CREATE TABLE transactions ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT
NOT NULL, asset_id INT NOT NULL, transaction_type ENUM('buy', 'sell') NOT NULL,
quantity INT NOT NULL, price_per_unit DECIMAL(16, 2) NOT NULL, total_amount
DECIMAL(16, 2) NOT NULL, transaction_date TIMESTAMP DEFAULT
CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN
KEY (asset_id) REFERENCES assets(id) );

-- 6. Inserción de los 7 activos iniciales
INSERT INTO assets (name, current_price) VALUES ('Bitcoin', 65000.50), ('YPF', 25.30),
('Gold', 2300.15), ('Silver', 28.45), ('Petroleum', 85.20), ('Apple', 175.10), ('Soybean', 430.00);