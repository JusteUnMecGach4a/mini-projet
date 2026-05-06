# 🛰️ SERVER BAY MONITORING - CANOVA PROJECT
> **CORE_SUPERVISOR v10.0 | Enterprise Edition**

Ce projet est une solution complète de surveillance environnementale temps-réel pour infrastructures serveurs. Il combine l'acquisition de données via IoT (ESP32), le stockage structuré (MariaDB) et une intelligence de supervision proactive (PHP Supervisor).

---

## 💎 Points Forts
- **Supervision Proactive** : Agent PHP capable d'envoyer des alertes via Telegram et Mail.
- **Visualisation Dynamique** : Dashboard haute-performance avec graphiques Chart.js.
- **Résilience Réseau** : Résolution de nom via mDNS (`CANOVA.local`).
- **Sécurité Intégrée** : Gestion granulaire des privilèges SQL.

---

## 🚀 Architecture Technique

### 🟢 Nœuds d'Acquisition (Hardware)
- **Cœur** : ESP32 (NodeMCU).
- **Capteur** : AHT20 Haute Précision (I2C).
- **Protocole** : Client MySQL natif sur port custom `13306`.
- **Temps** : Synchronisation NTP (`pool.ntp.org`).

### 🔵 Serveur Central (Raspberry Pi)
- **OS** : GNU/Linux.
- **Database** : MariaDB (SGBD Relationnel).
- **Web Engine** : Apache2 / PHP 8.1+.
- **Connectivité** : Support mDNS pour un accès via `CANOVA.local`.

---

## 📁 Structure du Projet

```bash
├── 📂 database/      # Scripts SQL et schémas de la base de données
├── 📂 include/       # Entêtes C++ pour la synchronisation SNTP
├── 📂 src/           # Code source C++ (Firmware ESP32)
├── 📂 supervisor/    # Interface Web PHP (Le cerveau du système)
│   └── index.php     # Dashboard, Logique d'alerte & Analytics
└── platformio.ini    # Configuration de l'environnement de build
```

---

## 🛠️ Déploiement Rapide

### 1️⃣ Initialisation de la Base de Données
Exécutez le script SQL sur votre Raspberry Pi :
```bash
mariadb -u root -p < database/schema.sql
```
*Ceci créera la base `bay_monitoring` ainsi que les utilisateurs `user_IoT` et `user_PHP` avec les accès sécurisés.*

### 2️⃣ Flashage de l'ESP32
1. Ouvrez le dossier dans **VS Code** avec l'extension **PlatformIO**.
2. Dans `src/main.cpp`, configurez vos identifiants WiFi.
3. Cliquez sur **Upload**. L'ESP32 se connectera automatiquement à `CANOVA.local`.

### 3️⃣ Mise en ligne du Dashboard
Copiez le contenu du dossier `supervisor/` dans votre répertoire web :
```bash
cp -r supervisor/* /var/www/html/
```

---

## 🤖 Fonctionnement du Supervisor
L'interface de supervision intègre un **Agent de Surveillance** :
- **Mode Armé** : Scanne les mesures toutes les 5 minutes.
- **Alertes Critiques** : Si Temp > 30°C ou Hum > 75%, envoi immédiat sur **Telegram** et **Email**.
- **Log Unit** : Console terminale intégrée pour visualiser les événements système en direct.

---

## 🔒 Sécurité & Privilèges
Le système respecte le principe du moindre privilège :
- **ESP32** : Droits d'insertion (`INSERT`) uniquement sur la table des mesures.
- **Site Web** : Droits de lecture (`SELECT`) uniquement, restreints à `localhost`.

---
*Projet réalisé dans le cadre du BTS CIEL (Cyberécurité, Informatique et réseaux, Électronique).*
