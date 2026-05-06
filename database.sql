-- =============================================
-- script de création de la base en gros elle sert à créer la base de données 
-- on copie colle le fichier et on l'execute dans phpmyadmin
-- =============================================

CREATE DATABASE IF NOT EXISTS footstyle
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE footstyle;

-- =============================================
-- Table utilisateur
-- =============================================
CREATE TABLE IF NOT EXISTS utilisateur (
  id           INT           AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100)  NOT NULL,
  prenom       VARCHAR(100)  NOT NULL,
  email        VARCHAR(150)  UNIQUE NOT NULL,
  mot_de_passe VARCHAR(255)  NOT NULL,
  adresse      TEXT,
  role         ENUM('utilisateur', 'admin') DEFAULT 'utilisateur',
  created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Table maillot
-- =============================================
CREATE TABLE IF NOT EXISTS maillot (
  id          INT            AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(150)   NOT NULL,
  equipe      VARCHAR(100),
  prix        DECIMAL(10,2)  NOT NULL,
  stock       INT            DEFAULT 0,
  image_url   VARCHAR(255),
  description TEXT
);

-- =============================================
-- Table commande
-- =============================================
CREATE TABLE IF NOT EXISTS commande (
  id             INT            AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur INT            NOT NULL,
  date_commande  DATETIME       DEFAULT CURRENT_TIMESTAMP,
  statut         VARCHAR(50)    DEFAULT 'en cours',
  montant_total  DECIMAL(10,2)  NOT NULL,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id)
);

-- =============================================
-- Table commande_ligne (détail de chaque commande)
-- Nécessaire pour conserver l'historique après vidage du panier
-- =============================================
CREATE TABLE IF NOT EXISTS commande_ligne (
  id                      INT            AUTO_INCREMENT PRIMARY KEY,
  id_commande             INT            NOT NULL,
  id_maillot              INT            NOT NULL,
  personnalisation_nom    VARCHAR(100),
  personnalisation_numero INT,
  quantite                INT            DEFAULT 1,
  prix_unitaire           DECIMAL(10,2)  NOT NULL,
  FOREIGN KEY (id_commande) REFERENCES commande(id),
  FOREIGN KEY (id_maillot)  REFERENCES maillot(id)
);

-- =============================================
-- Table panier
-- =============================================
CREATE TABLE IF NOT EXISTS panier (
  id                      INT  AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur          INT  NOT NULL,
  id_maillot              INT  NOT NULL,
  personnalisation_nom    VARCHAR(100),
  personnalisation_numero INT,
  quantite                INT  DEFAULT 1,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id),
  FOREIGN KEY (id_maillot)     REFERENCES maillot(id)
);

-- =============================================
-- Maillots de démonstration
-- =============================================
INSERT INTO maillot (nom, equipe, prix, stock, image_url, description) VALUES
('Maillot Domicile 2024/25', 'PSG',             89.99, 50, 'https://placehold.co/400x500/001942/ffffff?text=PSG+Home',      'Maillot domicile officiel du Paris Saint-Germain saison 2024/25'),
('Maillot Exterieur 2024/25','PSG',             89.99, 30, 'https://placehold.co/400x500/c8102e/001942?text=PSG+Away',      'Maillot exterieur du Paris Saint-Germain'),
('Maillot Domicile 2024/25', 'Real Madrid',     94.99, 45, 'https://placehold.co/400x500/f5f5f5/c9b037?text=Real+Madrid',   'Le mythique maillot blanc du Real Madrid'),
('Maillot Exterieur 2024/25','Real Madrid',     94.99, 25, 'https://placehold.co/400x500/7b2d8b/f5f5f5?text=Real+Away',     'Maillot exterieur violet du Real Madrid'),
('Maillot Domicile 2024/25', 'FC Barcelone',    89.99, 40, 'https://placehold.co/400x500/a50044/004d98?text=Barcelona',     'Maillot blaugrana emblematique du FC Barcelone'),
('Maillot Domicile 2024/25', 'Manchester City', 84.99, 35, 'https://placehold.co/400x500/6cabdd/ffffff?text=Man+City',      'Le maillot bleu ciel de Manchester City'),
('Maillot Domicile 2024/25', 'Bayern Munich',   89.99, 20, 'https://placehold.co/400x500/dc052d/000000?text=Bayern',        'Le maillot rouge du Bayern Munchen'),
('Maillot Domicile 2024/25', 'Liverpool FC',    84.99, 30, 'https://placehold.co/400x500/c8102e/f6eb61?text=Liverpool',     'Le maillot rouge de Liverpool FC'),
('Maillot Domicile 2024/25', 'AC Milan',        84.99, 15, 'https://placehold.co/400x500/fb090b/000000?text=AC+Milan',      'Le maillot rossonero de l\'AC Milan'),
('Maillot Domicile 2024/25', 'Juventus',        84.99, 20, 'https://placehold.co/400x500/000000/f5f5f5?text=Juventus',      'Le mythique maillot bianconero de la Juventus');
