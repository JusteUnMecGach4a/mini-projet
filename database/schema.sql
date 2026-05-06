-- Configuration de la base de données MariaDB sur Raspberry Pi
CREATE DATABASE IF NOT EXISTS bay_monitoring CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bay_monitoring;

-- Table des baies physiques
CREATE TABLE IF NOT EXISTS baies (
    id_baie INT AUTO_INCREMENT PRIMARY KEY,
    nom_baie VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- Table des nœuds capteurs
CREATE TABLE IF NOT EXISTS capteurs (
    id_capteur INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(30) DEFAULT "AHT20",
    type_com VARCHAR(10) DEFAULT "I2C",
    id_baie INT NOT NULL,
    FOREIGN KEY (id_baie) REFERENCES baies(id_baie)
) ENGINE=InnoDB;

-- Table des mesures environnementales
CREATE TABLE IF NOT EXISTS mesures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    temperature FLOAT(4,2),
    humidite FLOAT(4,2),
    date_mesure DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_capteur INT NOT NULL,
    FOREIGN KEY (id_capteur) REFERENCES capteurs(id_capteur)
) ENGINE=InnoDB;

-- Création des utilisateurs et gestion des privilèges
-- Note : Remplacer 'votre_mdp_xxx' par vos vrais mots de passe

-- Admin
CREATE USER IF NOT EXISTS "admin_projet"@"%" IDENTIFIED BY "votre_mdp_admin";
GRANT ALL PRIVILEGES ON bay_monitoring.* TO "admin_projet"@"%";

-- Site PHP (Consultation locale)
CREATE USER IF NOT EXISTS "user_PHP"@"localhost" IDENTIFIED BY "votre_mdp_php";
GRANT SELECT ON bay_monitoring.* TO "user_PHP"@"localhost";

-- Nœuds IoT (Insertion des mesures)
CREATE USER IF NOT EXISTS "user_IoT"@"%" IDENTIFIED BY "iot_pwd_4";
GRANT INSERT ON bay_monitoring.mesures TO "user_IoT"@"%";

FLUSH PRIVILEGES;
