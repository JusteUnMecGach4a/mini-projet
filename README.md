# E4-MINI-PROJET : Server Bay Monitoring

Ce projet est un système de surveillance environnementale pour 4 baies serveurs. Il utilise des nœuds ESP32 pour mesurer la température et l'humidité et transmet ces données à une base de données centralisée sur une Raspberry Pi pour une visualisation via un site Intranet.

## 🚀 Architecture Technique

- **Microcontrôleur** : NodeMCU ESP32.
- **Capteurs** : AHT20 (Température & Humidité) via interface I2C (Pins 21/22).
- **Serveur Central** : Raspberry Pi 400 (GNU/Linux).
- **Base de Données** : MariaDB (SGBD Relationnel) sur le port 13306.
- **Serveur Web** : Apache2 avec PHP8.

## 📁 Structure du Projet

- `src/main.cpp` : Code source C++ pour l'ESP32 (Lecture capteur, WiFi, Client SQL).
- `include/` : Entêtes C++ (Synchronisation temporelle SNTP).
- `database/` : Scripts SQL et documentation de la base de données `bay_monitoring`.
- `platformio.ini` : Configuration de l'environnement de développement PlatformIO.

## 🛠️ Installation et Configuration

### 1. Base de données
Exécutez le script dans `/database/schema.sql` sur votre Raspberry Pi pour créer la base de données et configurer les privilèges utilisateurs (`user_IoT`, `user_PHP`).

### 2. Firmware ESP32
1. Ouvrez le projet avec VS Code et l'extension **PlatformIO**.
2. Configurez vos identifiants WiFi (`ssid`, `password`) dans `src/main.cpp`.
3. Vérifiez l'adresse IP de votre Raspberry Pi (défaut : `172.17.7.4`).
4. Compilez et uploadez le code sur l'ESP32.

### 3. Site Intranet
Déployez vos fichiers PHP dans `/var/www/html/projet_v1/`. Le portail est accessible via :
- `http://canova.local/` ou `http://172.17.7.4/`

## 🔒 Sécurité
- Accès au SGBD restreint via des privilèges minimaux.
- Authentification du site Intranet via `.htaccess` (Identifiant : `admin` / Password : `btscielir`).
- Communications sécurisées (TLS prévu pour la version finale).

---
*Projet réalisé dans le cadre du BTS CIEL (Cyberécurité, Informatique et réseaux, Électronique).*
