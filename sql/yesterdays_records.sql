-- ============================================================
-- TIENDA RETRO — Database creation + seed data script
-- MySQL 8+ / MariaDB 10.4+
-- 9 tables: categories, genres, cities, users, products,
--           addresses, cart, orders, order_detail
-- ============================================================

DROP DATABASE IF EXISTS yesterdays_records;
CREATE DATABASE yesterdays_records CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE yesterdays_records;

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE categories (
    id_category INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE genres (
    id_genre INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cities (
    id_city INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('client', 'admin') NOT NULL DEFAULT 'client',
    token VARCHAR(255) NULL,
    token_expire DATETIME NULL,
    status ENUM('pending', 'verified') NOT NULL DEFAULT 'pending',
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id_product INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    id_category INT NOT NULL,
    id_genre INT NULL,
    artist VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    image VARCHAR(255) NULL,
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_category) REFERENCES categories(id_category)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_genre) REFERENCES genres(id_genre)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE addresses (
    id_address INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_city INT NOT NULL,
    cp VARCHAR(10) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_city) REFERENCES cities(id_city)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart (
    id_cart INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_product INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    updated_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_product) REFERENCES products(id_product)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
    id_order INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_address INT NOT NULL,
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'sent') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_address) REFERENCES addresses(id_address)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_detail (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    id_product INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_order) REFERENCES orders(id_order)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_product) REFERENCES products(id_product)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Categories
-- Added "Accessories" beyond your 4 examples, since the catalog
-- includes cleaning kits/needles/sleeves that don't fit the other 4
INSERT INTO categories (name) VALUES
('Vinilos'),
('Cassettes'),
('CDs'),
('Merchandising'),
('Accesorios');

-- Genres (matches the actual products below)
INSERT INTO genres (name) VALUES
('Rock'),
('Rock Progresivo'),
('Pop'),
('Grunge'),
('Hard Rock'),
('Electrónica'),
('Funk'),
('Reggae'),
('Alternativo'),
('Psicodélico'),
('R&B');

-- Cities
INSERT INTO cities (name) VALUES
('A Coruña'),
('Oleiros'),
('Santiago de Compostela'),
('Madrid'),
('Barcelona');

-- Users
-- Password for all "client" users: Cliente123!
-- Password for admin: Admin123!
INSERT INTO users (name, email, password, role, token, token_expire, status) VALUES
('Admin Tienda Retro', 'admin@tiendaretro.com', '$2y$10$3vQigacm4cKhAOw65yCXsuVkRYLeOXLrtvd6D5RiWFqMJWIl3QJZW', 'admin', NULL, NULL, 'verified'),
('Marta Fernández', 'marta.fernandez@example.com', '$2y$10$t09Y3ehwoLSOXA3YgXS3ZeZff.RehInymuEZ9gFQ7CXdZOrQfiEva', 'client', NULL, NULL, 'verified'),
('Diego Souto', 'diego.souto@example.com', '$2y$10$t09Y3ehwoLSOXA3YgXS3ZeZff.RehInymuEZ9gFQ7CXdZOrQfiEva', 'client', NULL, NULL, 'verified'),
('Lucía Vázquez', 'lucia.vazquez@example.com', '$2y$10$t09Y3ehwoLSOXA3YgXS3ZeZff.RehInymuEZ9gFQ7CXdZOrQfiEva', 'client', NULL, NULL, 'verified'),
('Iker Pérez', 'iker.perez@example.com', '$2y$10$t09Y3ehwoLSOXA3YgXS3ZeZff.RehInymuEZ9gFQ7CXdZOrQfiEva', 'client', 'a1f9c3e2d8b7465f9a0c1e2d3f4b5a6c', '2026-08-25 12:00:00', 'pending');
-- ^ Iker stays unverified on purpose, with an active token + expiry, to test HU-04

-- Products
INSERT INTO products (name, description, id_category, id_genre, artist, price, stock, image) VALUES
('Rumours', 'Edición en vinilo del clásico álbum de 1977.', 1, 1, 'Fleetwood Mac', 24.99, 12, 'https://picsum.photos/seed/rumours/400/400'),
('The Dark Side of the Moon', 'Edición remasterizada, incluye póster y pegatinas.', 1, 2, 'Pink Floyd', 27.50, 8, 'https://picsum.photos/seed/darkside/400/400'),
('Thriller', 'El álbum más vendido de la historia, en vinilo de 180g.', 1, 3, 'Michael Jackson', 22.00, 15, 'https://picsum.photos/seed/thriller/400/400'),
('Nevermind', 'Edición estándar, portada original.', 1, 4, 'Nirvana', 23.75, 10, 'https://picsum.photos/seed/nevermind/400/400'),
('Back in Black', 'Vinilo negro clásico, sonido remasterizado.', 1, 5, 'AC/DC', 25.99, 6, 'https://picsum.photos/seed/backinblack/400/400'),
('Random Access Memories', 'Doble vinilo, incluye booklet de créditos.', 1, 6, 'Daft Punk', 29.99, 5, 'https://picsum.photos/seed/ram/400/400'),

('Purple Rain', 'Cassette reeditado, sonido analógico cálido.', 2, 7, 'Prince', 14.50, 9, 'https://picsum.photos/seed/purplerain/400/400'),
('Legend', 'Grandes éxitos en cassette, formato original de los 80.', 2, 8, 'Bob Marley & The Wailers', 13.00, 11, 'https://picsum.photos/seed/legend/400/400'),
('1989', 'Edición limitada en cassette de color.', 2, 3, 'Taylor Swift', 15.99, 7, 'https://picsum.photos/seed/1989/400/400'),

('OK Computer', 'CD con booklet de letras incluido.', 3, 9, 'Radiohead', 16.99, 14, 'https://picsum.photos/seed/okcomputer/400/400'),
('Abbey Road', 'Remasterizado en CD, sonido restaurado.', 3, 1, 'The Beatles', 17.50, 10, 'https://picsum.photos/seed/abbeyroad/400/400'),
('Currents', 'CD estándar en estuche de plástico.', 3, 10, 'Tame Impala', 15.50, 13, 'https://picsum.photos/seed/currents/400/400'),
('Blonde', 'Edición de importación en CD.', 3, 11, 'Frank Ocean', 18.00, 6, 'https://picsum.photos/seed/blonde/400/400'),

('Camiseta Logo Vintage', 'Camiseta de algodón con logo retro serigrafiado.', 4, NULL, 'Tienda Retro', 19.99, 25, 'https://picsum.photos/seed/camiseta1/400/400'),
('Tote Bag de Vinilo', 'Bolsa de lona estampada, perfecta para llevar tus discos.', 4, NULL, 'Tienda Retro', 12.99, 30, 'https://picsum.photos/seed/totebag/400/400'),
('Gorra Retro Records', 'Gorra ajustable bordada.', 4, NULL, 'Tienda Retro', 16.50, 18, 'https://picsum.photos/seed/gorra/400/400'),

('Kit de Limpieza de Vinilo', 'Kit de limpieza antiestática para discos de vinilo.', 5, NULL, 'AudioCare', 9.99, 40, 'https://picsum.photos/seed/limpiador/400/400'),
('Fundas Internas x10', 'Pack de 10 fundas internas antiestáticas.', 5, NULL, 'AudioCare', 7.50, 50, 'https://picsum.photos/seed/fundas/400/400'),
('Aguja de Repuesto', 'Aguja universal de repuesto para tornamesas.', 5, NULL, 'SoundTech', 21.00, 20, 'https://picsum.photos/seed/aguja/400/400');
-- ^ Merchandising and Accessories items use id_genre = NULL since
--   "genre" doesn't really apply to them — only vinyls/cassettes/CDs have a real one

-- Addresses
INSERT INTO addresses (id_user, id_city, cp, street_address) VALUES
(2, 1, '15003', 'Calle Real 12, 3ºB'),
(3, 2, '15173', 'Rúa do Porto 8, bajo'),
(4, 1, '15008', 'Avenida de Arteixo 45, 1ºA'),
(2, 3, '15701', 'Rúa Nova 22, 2ºD');
-- ^ Marta has 2 addresses (home and family), to test address selection at checkout

-- Cart (pending items, not yet checked out)
INSERT INTO cart (id_user, id_product, quantity) VALUES
(4, 4, 1),   -- Lucía has Nevermind in her cart
(4, 17, 2),  -- and 2 vinyl cleaning kits
(3, 9, 1);   -- Diego has the 1989 cassette in his cart

-- Orders (each total matches the sum of its order_detail lines exactly)
-- id_address 1 and 4 both belong to Marta (id_user 2) — she picked a different
-- address for each order, which is exactly why orders needs its own id_address
-- instead of just looking up the user's address
INSERT INTO orders (id_user, id_address, date, total, status) VALUES
(2, 1, '2026-08-10 10:15:00', 52.49, 'sent'),
(2, 4, '2026-08-15 18:40:00', 19.99, 'pending'),
(3, 2, '2026-08-12 09:05:00', 44.99, 'paid'),
(4, 3, '2026-08-14 20:22:00', 27.50, 'paid');

-- Order detail
-- Order 1 (Marta): Rumours + The Dark Side of the Moon = 24.99 + 27.50 = 52.49
INSERT INTO order_detail (id_order, id_product, quantity, unit_price) VALUES
(1, 1, 1, 24.99),
(1, 2, 1, 27.50);

-- Order 2 (Marta): Vintage Logo T-Shirt = 19.99
INSERT INTO order_detail (id_order, id_product, quantity, unit_price) VALUES
(2, 14, 1, 19.99);

-- Order 3 (Diego): Thriller + Legend + Vinyl Cleaning Kit = 22.00 + 13.00 + 9.99 = 44.99
INSERT INTO order_detail (id_order, id_product, quantity, unit_price) VALUES
(3, 3, 1, 22.00),
(3, 8, 1, 13.00),
(3, 17, 1, 9.99);

-- Order 4 (Lucía): The Dark Side of the Moon = 27.50
INSERT INTO order_detail (id_order, id_product, quantity, unit_price) VALUES
(4, 2, 1, 27.50);

-- ============================================================
-- VIEWS (evitan repetir JOINs en PHP)
-- ============================================================

CREATE VIEW v_products AS
SELECT p.id_product, p.name, p.description, p.artist, p.price,
       p.stock, p.image, p.date,
       c.id_category, c.name AS category_name,
       g.id_genre, g.name AS genre_name
FROM products p
INNER JOIN categories c ON p.id_category = c.id_category
LEFT JOIN genres g ON p.id_genre = g.id_genre;

CREATE VIEW v_orders AS
SELECT o.id_order, o.total, o.status, o.date, o.paid_date,
       u.id_user, u.name AS client_name, u.email,
       a.street_address, a.cp,
       ci.name AS city_name
FROM orders o
INNER JOIN users u ON o.id_user = u.id_user
INNER JOIN addresses a ON o.id_address = a.id_address
INNER JOIN cities ci ON a.id_city = ci.id_city;

CREATE VIEW v_order_detail AS
SELECT od.id_detail, od.id_order, od.quantity, od.unit_price,
       p.name AS product_name, p.artist, p.image
FROM order_detail od
INNER JOIN products p ON od.id_product = p.id_product;