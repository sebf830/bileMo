# bileMo

## Technologies
1.symfony 6.0
2.PHP 8

## Installation du projet
cloner le repository
assurez-vous d'avoir php, mysql, composer, docker et npm installés sur votre machine
lancer les commandes suivantes
"composer install" pour installer les librairies du projet
"php bin/console d:d:c" pour créer une base de donnée mysql
"php bin/console d:s:u --force" pour charger les tables de la base de donnée
"php bin/console d:f:l --no-interaction" pour créer des données de test tels que les utilisateurs, figures, commentaires..
"symfony serve" pour lancer le projet, l'interface est accessible par defaut sur le port local 8000
