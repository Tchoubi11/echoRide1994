"# echoRide1994" 

# 🚗 EcoRide – Plateforme de covoiturage écologique

**EcoRide** est une plateforme de covoiturage écologique développée en PHP avec Symfony, pensée pour rapprocher conducteurs et passagers tout en favorisant l’utilisation de véhicules hybrides et électriques. Le projet a été conçu dans le cadre d’un examen, et est destiné à être accessible au public ainsi qu’au jury.

## 📸 Aperçu de l’application

![Aperçu de l'application](public/uploads/images/6821b525b8057.png)

---

## 📚 Documentation technique

Vous pouvez consulter la documentation technique complète du projet EcoRide ici :  
👉 [Documentation technique - EcoRide](https://1drv.ms/w/c/6921ec449c7d056d/EURi_bfBBJlNhUuK9DfehOEBSYsHi4-DUNtq2q0U3Lblmg?e=Lds5TZ)


## 🌱 Objectif

Faciliter la mise en relation de conducteurs et passagers pour des trajets partagés, en intégrant des filtres écologiques et un système de crédits interne. L’objectif est de promouvoir une mobilité plus responsable.

---

## 🧩 Fonctionnalités principales

- 🔍 **Recherche de trajets** avec filtres avancés (écologique, prix, durée, note conducteur…)
- 🧑‍💻 **Gestion des utilisateurs** (inscription, profils passager/conducteur)
- 🧾 **Réservation de trajets**
- ⭐ **Système de notation** des conducteurs
- 🔐 **Espace administrateur** (modération, gestion des comptes)
- 📊 **Tableau de bord** avec statistiques (Chart.js)
- 📧 **Notifications email** avec MailHog
- 👨‍🏭 **Espace employé** pour gestion des avis et modération
- 💰 **Système de crédits intégré** :
  - 20 crédits offerts à l’inscription
  - 2 crédits de commission par réservation
  - Validation manuelle par modérateur avant attribution des crédits

---

## 🗄️ Intégration de MongoDB pour la gestion des crédits

Ce projet utilise MongoDB pour gérer le système de crédits des utilisateurs. Les crédits sont stockés dans une collection dédiée et sont manipulés via le repository `CreditRepository`.

### Prérequis

- MongoDB installé localement ou via MongoDB Atlas.
- Configuration de la connexion MongoDB dans le fichier `.env.local`.

### Fonctionnalités

- Attribution de 20 crédits à l'inscription.
- Déduction de 2 crédits par réservation, représentant la commission de la plateforme.
- Validation manuelle des crédits par un modérateur avant attribution.

### Sécurité

- Protection contre les injections MongoDB via l'utilisation de Doctrine MongoDB ODM.
- Validation des données côté serveur avant toute opération sur la base de données.

----
## 🛠️ Technologies utilisées

- **Backend** : PHP 8.2.27, Symfony
- **Base de données** : MySQL 8.2
- **Frontend** : Twig, Bootstrap 5, JavaScript, Chart.js
- **Email** : MailHog pour l’environnement local

---

## ⚙️ Installation

### Pré-requis

- PHP ≥ 8.2
- Symfony 6.4.21 
- Composer
- MySQL
- MongoDB

### Étapes

```bash
git clone https://github.com/Tchoubi11/echoRide1994.git
cd ecoride

# Installation des dépendances
composer install

# Configuration de l'environnement
cp .env.dev .env.local

Pour MySQL :
DATABASE_URL="mysql://echorideuser:Tchoubaye11@127.0.0.1:3306/ecoride"

Pour MongoDB :
MONGODB_URL="mongodb://Tchoubi:Tchoubaye11@localhost:27017/ecoride1994"


# Migration + Fixtures
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Lancer le serveur local
symfony server:start

# Accéder à l'application :

`Ouvrir un navigateur et aller à http://localhost:8000.`

## 🔐 Accès par défaut

- **Admin**
  - Email : `admin@echoride.com`
  - Mot de passe : `Admin22.`

## 🧾 Structure du projet
- `/public` : Fichiers publics (images, JS, CSS)
- `/templates` : Vues et composants Twig
- `/uploads/photos` : Stockage des photos de profil
- `/vendor` : Dépendances Composer
- `/config` : Configuration Symfony


## 🔒 Sécurité
Le projet respecte les bonnes pratiques de sécurité :

✅ Protection SQL Injection avec Doctrine ORM

✅ Protection XSS (filtrage des entrées, échappement Twig)

✅ Token CSRF pour tous les formulaires

✅ Sessions sécurisées

✅ Validation côté serveur

✅ Tests avec Nikto + Snyk

✅ En-têtes HTTP sécurisés configurés

📱 Responsive
Interface adaptative optimisée pour :

🖥️ Ordinateurs

📱 Smartphones

📱 Tablettes
Grâce à Bootstrap.

🧑‍🎓 Auteur / Licence
Développé par Tchoubaye Bakala Frank Hervé
📚 Dans le cadre d’un projet d’examen

📬 Contact
Pour toute question, amélioration ou bug :
📧 tchoubayefranck@gmail.com