-- Création de la base de données
CREATE DATABASE IF NOT EXISTS serziam_ssh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE serziam_ssh;

-- Table des administrateurs
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- Table des serveurs
CREATE TABLE servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    price DECIMAL(10, 2) NOT NULL DEFAULT 4000.00,
    current_price DECIMAL(10, 2) NOT NULL DEFAULT 4000.00,
    days INT NOT NULL DEFAULT 4,
    days_left INT NOT NULL DEFAULT 4,
    features TEXT,
    config_content TEXT,
    config_file VARCHAR(255),
    status ENUM('active', 'payed', 'expired') DEFAULT 'active',
    is_payed BOOLEAN DEFAULT FALSE,
    payed_date DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_is_payed (is_payed)
) ENGINE=InnoDB;

-- Table des transactions
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server_id INT,
    transaction_ref VARCHAR(100) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    operator ENUM('orange', 'mtn') NOT NULL,
    message TEXT,
    status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
    user_ip VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table des témoignages
CREATE TABLE testimonials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) DEFAULT 'Anonyme',
    rating INT NOT NULL DEFAULT 5,
    content TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- Table des annonces
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Table des logs
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insérer l'administrateur par défaut (mot de passe: yayacamara1995)
INSERT INTO admins (username, password_hash) VALUES 
('yayacamara', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insérer des données de test
INSERT INTO servers (name, description, price, current_price, days, days_left, features) VALUES
('Serveur USA Premium', 'Serveur VPN haute vitesse aux USA', 4000.00, 4000.00, 4, 4, '["100 Mbps", "Trafic illimité", "Support 24/7", "Ping <50ms"]'),
('Serveur Europe Standard', 'Serveur SSH en France', 3500.00, 3500.00, 3, 3, '["50 Mbps", "Trafic illimité", "Support 24/7", "Ping <80ms"]'),
('Serveur Asia Pro', 'Serveur VPN au Japon', 4500.00, 4500.00, 5, 5, '["200 Mbps", "Trafic illimité", "Support 24/7", "Ping <100ms"]');

INSERT INTO announcements (title, content, is_active) VALUES
('🎉 Promotion spéciale!', 'Réduction de 25% sur tous les serveurs cette semaine', TRUE),
('⚡ Nouveaux serveurs', 'Des serveurs USA supplémentaires ont été ajoutés', TRUE),
('🔒 Sécurité renforcée', 'Tous nos serveurs utilisent maintenant le chiffrement 256-bit', TRUE);
