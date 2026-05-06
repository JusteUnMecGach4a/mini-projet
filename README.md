# Mini Projet ESP32

Ce projet est un système de surveillance environnementale pour baie serveur utilisant un ESP32 et un capteur AHT20.

## Fonctionnalités
- Lecture de la température et de l'humidité via AHT20.
- Connexion WiFi.
- Synchronisation temporelle via SNTP.
- Envoi des données vers une base de données MySQL/MariaDB.

## Structure du projet
- `src/main.cpp` : Logique principale.
- `include/SNTP_Client.hpp` : Client pour la synchronisation NTP.
- `platformio.ini` : Configuration PlatformIO.

## Installation
1. Clonez le dépôt.
2. Ouvrez avec VS Code + PlatformIO.
3. Configurez vos identifiants WiFi et MySQL dans le code.
4. Compilez et uploadez sur l'ESP32.
