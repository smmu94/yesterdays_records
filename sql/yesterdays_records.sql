-- ============================================================
-- YESTERDAY'S RECORDS — Seed data script
-- MySQL 8+ / MariaDB 10.4+
-- Importar en la base de datos que Infinity Free te asignó
-- ============================================================

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
INSERT INTO categories (name) VALUES
('Vinilos'),
('Cassettes'),
('CDs'),
('Merchandising'),
('Accesorios');

-- Genres
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

-- Products (41 total)
INSERT INTO products (name, description, id_category, id_genre, artist, price, stock, image) VALUES
-- Vinilos (11)
('Rumours', 'Edición en vinilo del clásico álbum de 1977.', 1, 1, 'Fleetwood Mac', 24.99, 12, 'assets/default.webp'),
('The Dark Side of the Moon', 'Edición remasterizada, incluye póster y pegatinas.', 1, 2, 'Pink Floyd', 27.50, 8, 'assets/default.webp'),
('Thriller', 'El álbum más vendido de la historia, en vinilo de 180g.', 1, 3, 'Michael Jackson', 22.00, 15, 'assets/default.webp'),
('Nevermind', 'Edición estándar, portada original.', 1, 4, 'Nirvana', 23.75, 10, 'assets/default.webp'),
('Back in Black', 'Vinilo negro clásico, sonido remasterizado.', 1, 5, 'AC/DC', 25.99, 6, 'assets/default.webp'),
('Random Access Memories', 'Doble vinilo, incluye booklet de créditos.', 1, 6, 'Daft Punk', 29.99, 5, 'assets/default.webp'),
('Born to Run', 'Clásico del rock en vinilo de 180g, edición remasterizada.', 1, 1, 'Bruce Springsteen', 26.50, 10, 'assets/default.webp'),
('Songs in the Key of Life', 'Doble vinilo de 1976, incluye booklet interior.', 1, 7, 'Stevie Wonder', 32.00, 7, 'assets/default.webp'),
('Is This It', 'Vinilo debut de The Strokes, sonido garage rock.', 1, 9, 'The Strokes', 21.99, 9, 'assets/default.webp'),
('The Rise and Fall of Ziggy Stardust', 'Vinilo conceptual de David Bowie, edición especial.', 1, 10, 'David Bowie', 28.50, 8, 'assets/default.webp'),
('A Night at the Opera', 'Doble vinilo, incluye Bohemian Rhapsody.', 1, 1, 'Queen', 29.99, 11, 'assets/default.webp'),

-- Cassettes (7)
('Purple Rain', 'Cassette reeditado, sonido analógico cálido.', 2, 7, 'Prince', 14.50, 9, 'assets/default.webp'),
('Legend', 'Grandes éxitos en cassette, formato original de los 80.', 2, 8, 'Bob Marley & The Wailers', 13.00, 11, 'assets/default.webp'),
('1989', 'Edición limitada en cassette de color.', 2, 3, 'Taylor Swift', 15.99, 7, 'assets/default.webp'),
('Discovery', 'Cassette de Daft Punk, synth pop francés de los 90s.', 2, 6, 'Daft Punk', 14.99, 12, 'assets/default.webp'),
('Wish You Were Here', 'Cassette de Pink Floyd, sonido analógico clásico.', 2, 2, 'Pink Floyd', 13.50, 8, 'assets/default.webp'),
('Appetite for Destruction', 'Cassette reeditado de Guns N Roses.', 2, 5, 'Guns N Roses', 12.99, 10, 'assets/default.webp'),
('Blue', 'Cassette de Joni Mitchell, folk legendario.', 2, 9, 'Joni Mitchell', 11.99, 6, 'assets/default.webp'),

-- CDs (9)
('OK Computer', 'CD con booklet de letras incluido.', 3, 9, 'Radiohead', 16.99, 14, 'assets/default.webp'),
('Abbey Road', 'Remasterizado en CD, sonido restaurado.', 3, 1, 'The Beatles', 17.50, 10, 'assets/default.webp'),
('Currents', 'CD estándar en estuche de plástico.', 3, 10, 'Tame Impala', 15.50, 13, 'assets/default.webp'),
('Blonde', 'Edición de importación en CD.', 3, 11, 'Frank Ocean', 18.00, 6, 'assets/default.webp'),
('In Rainbows', 'CD de Radiohead, producción experimental.', 3, 9, 'Radiohead', 14.50, 15, 'assets/default.webp'),
('The Miseducation of Lauryn Hill', 'CD ganador de Grammy, R&B/soul.', 3, 11, 'Lauryn Hill', 16.99, 9, 'assets/default.webp'),
('Homework', 'CD debut de Daft Punk, electrónica revolucionaria.', 3, 6, 'Daft Punk', 13.99, 12, 'assets/default.webp'),
('Led Zeppelin IV', 'Remasterizado en CD, incluye Stairway to Heaven.', 3, 1, 'Led Zeppelin', 15.50, 11, 'assets/default.webp'),
('Illmatic', 'CD clásico del hip hop, Nas en su mejor momento.', 3, 11, 'Nas', 14.99, 8, 'assets/default.webp'),

-- Merchandising (8)
('Camiseta Logo Vintage', 'Camiseta de algodón con logo retro serigrafiado.', 4, NULL, 'Tienda Retro', 19.99, 25, 'assets/default.webp'),
('Tote Bag de Vinilo', 'Bolsa de lona estampada, perfecta para llevar tus discos.', 4, NULL, 'Tienda Retro', 12.99, 30, 'assets/default.webp'),
('Gorra Retro Records', 'Gorra ajustable bordada.', 4, NULL, 'Tienda Retro', 16.50, 18, 'assets/default.webp'),
('Póster Vinyl Collection', 'Set de 3 pósters retro de portadas icónicas.', 4, NULL, 'Tienda Retro', 14.99, 20, 'assets/default.webp'),
('Camiseta Cassette Lover', 'Camiseta con estampado de cassette vintage.', 4, NULL, 'Tienda Retro', 18.99, 22, 'assets/default.webp'),
('Llavero Disco de Vinilo', 'Llavero en miniatura tipo disco de 7".', 4, NULL, 'Tienda Retro', 8.99, 35, 'assets/default.webp'),
('Póster Dark Side', 'Póster retro de The Dark Side of the Moon.', 4, NULL, 'Tienda Retro', 11.99, 15, 'assets/default.webp'),
('Calcetines Vinyl', 'Pack de 2 pares de calcetines con estampado de vinilo.', 4, NULL, 'Tienda Retro', 9.99, 25, 'assets/default.webp'),

-- Accesorios (6)
('Kit de Limpieza de Vinilo', 'Kit de limpieza antiestática para discos de vinilo.', 5, NULL, 'AudioCare', 9.99, 40, 'assets/default.webp'),
('Fundas Internas x10', 'Pack de 10 fundas internas antiestáticas.', 5, NULL, 'AudioCare', 7.50, 50, 'assets/default.webp'),
('Aguja de Repuesto', 'Aguja universal de repuesto para tornamesas.', 5, NULL, 'SoundTech', 21.00, 20, 'assets/default.webp'),
('Rascador de Vinilo', 'Herramienta para limpiar surcos de discos.', 5, NULL, 'AudioCare', 6.99, 30, 'assets/default.webp'),
('Tracking Force Gauge', 'Báscula digital para calibrar la aguja del tornamesa.', 5, NULL, 'SoundTech', 34.99, 15, 'assets/default.webp'),
('Funda Protectora Universal', 'Funda de plástico para proteger vinilos de 12".', 5, NULL, 'AudioCare', 4.99, 60, 'assets/default.webp');

-- Addresses
-- Admin e Iker no tienen direcciones
INSERT INTO addresses (id_user, id_city, cp, street_address) VALUES
(2, 1, '15003', 'Calle Real 12, 3ºB'),
(2, 3, '15701', 'Rúa Nova 22, 2ºD'),
(3, 4, '28001', 'Calle Gran Vía 45, 2ºC'),
(4, 2, '15173', 'Avenida de Galicia 8, 1ºA');
