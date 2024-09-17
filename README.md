# EcoGardenAPI

EcoGardenAPI est une API REST qui permet de partager des conseils de jardinage selon les mois et d'obtenir la météo de différentes villes en France. 
Elle propose des fonctionnalités de création et gestion de comptes utilisateurs, avec authentification via JWT. 
Les utilisateurs peuvent accéder à des conseils personnalisés et à la météo de leur ville, tandis que les administrateurs peuvent gérer les conseils et les utilisateurs via des routes spécifiques.

## Installation

1. Télécharger le projet
2. Modifier le fichier _.env_ et renseigner vos informations de connexion à la base de données
3. Créer la base de données avec `php bin/console doctrine:database:create`
4. Appliquer les migrations avec `php bin/console doctirne:migrations:migrate`
5. Insérer les fixtures avec `php bin/console doctrine:fixtures:load`
6. Lancer le serveur
