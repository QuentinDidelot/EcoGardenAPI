# EcoGardenAPI

## Description

EcoGardenAPI est une API REST qui permet de partager des conseils de jardinage selon les mois et d'obtenir la météo de différentes villes en France. 
Elle propose des fonctionnalités de création et gestion de comptes utilisateurs, avec authentification via JWT. 
Les utilisateurs peuvent accéder à des conseils personnalisés et à la météo de leur ville, tandis que les administrateurs peuvent gérer les conseils et les utilisateurs via des routes spécifiques.

## Installation du projet

1. **Cloner le projet** :
   ```bash
   git clone https://github.com/QuentinDidelot/EcoGardenAPI.git
1. Modifier le fichier _.env_ et renseigner vos informations de connexion à la base de données
2. Créer la base de données avec `php bin/console doctrine:database:create`
3. Appliquer les migrations avec `php bin/console doctirne:migrations:migrate`
4. Insérer les fixtures avec `php bin/console doctrine:fixtures:load`
5. Lancer le serveur

## Fonctionnalités 

1. ✅ Créer et gérer des comptes utilisateurs avec des informations essentielles (comme la ville).
2. ✅ Authentification sécurisée via un système de tokens JWT pour l'accès aux fonctionnalités.
3. ✅ Accéder aux conseils de jardinage mensuels pour des recommandations adaptées.
4. ✅ Consulter les conseils de jardinage actuels directement depuis l'API.
5. ✅ Obtenir des informations météorologiques pour une ville donnée afin de mieux planifier les activités de jardinage.
6. ✅ Intégrer la météo locale pour la ville de l'utilisateur afin d'affiner les conseils de jardinage.
7. ✅ Ajouter, modifier et supprimer des conseils de jardinage en fonction des besoins des administrateurs.
8. ✅ Gérer les utilisateurs, y compris la mise à jour de leurs informations ou leur suppression.
9. ✅ Gestion robuste des erreurs et réponses appropriées pour assurer une utilisation fluide et sécurisée.

## Technologies utilisées
1. PHP 8.3
2. Symfony 6.4
3. MySQL
4. API Rest (Création)
5. API Publique : OpenWeatherMap (Utilisation)
6. Token JWT
7. Nelmio

✨ Projet réalisé dans le cadre du parcours OpenClassrooms.
