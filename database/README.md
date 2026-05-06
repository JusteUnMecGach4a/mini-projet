# Documentation de la Base de Données `bay_monitoring`

Cette base de données permet de stocker les relevés environnementaux (température et humidité) des 4 baies serveurs surveillées par les nœuds ESP32.

## Modèle Logique de Données (MLD)

- **baies** : Identifie les emplacements physiques.
  - `id_baie` (PK) : Identifiant unique.
  - `nom_baie` : Nom de la baie (ex: Alpha, Beta...).
- **capteurs** : Référence les microcontrôleurs ESP32.
  - `id_capteur` (PK) : Identifiant unique.
  - `reference` : Modèle du capteur (AHT20).
  - `type_com` : Type de communication (I2C).
  - `id_baie` (FK) : Lien vers la baie surveillée.
- **mesures** : Stocke l'historique des relevés.
  - `id` (PK) : Identifiant unique du relevé.
  - `temperature` : Valeur en °C.
  - `humidite` : Valeur en %.
  - `date_mesure` : Horodatage du relevé.
  - `id_capteur` (FK) : Lien vers le capteur ayant effectué la mesure.

## Sécurité et Accès

La base est sécurisée par trois profils d'utilisateurs :
1. **admin_projet** : Accès total pour la maintenance.
2. **user_PHP** : Accès en lecture seule pour l'affichage sur le site Intranet.
3. **user_IoT** : Accès limité à l'insertion pour les nœuds ESP32.

## Installation
Pour initialiser la base de données sur MariaDB, exécutez le script `schema.sql` :
```bash
mysql -u root -p < schema.sql
```
