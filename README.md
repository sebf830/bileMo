# bileMo

## Environnement de développement
* Symfony 6.1
* Php 8.2
* Composer 2.5.1
* Bootstrap 5
* Mamp

## Installation du projet
1. Clonez ou téléchargez le repository GitHub à l'aide de la commande:
```
    git clone https://github.com/sebf830/snowTricks.git
```
2. Copier le contenu du fichier '.env' dans un fichier '.env.local' que vous aller créer à la racine du projet.
- Renseignez vos valeur de connexion à votre base de donnée dans la variable : 'DATABASE_URL' en suivant cet exemple :
```
    DATABASE_URL=mysql://{identifier}:{password}@{adress_host}/{datbase_name}?serverVersion=14&charset=utf8"
```
3. Installer les dépendences back du projet avec composer en lancant la commande depuis le projet :
```
    composer install
```
4. Pour installer Composer : veuillez vous rendre sur https://getcomposer.org/download/
5. Installer l'interface de commande de symfony sur votre machine, lancer la commande suivante : 
```
    curl -sS https://get.symfony.com/cli/installer | bash 
```
6. Créer la base de donnée du projet, lancer la commande suivante depuis le projet :
```
    symfony console d:d:c
```
7. Créer les tables de la base de donnée, lancer la commande suivante depuis le projet :
```
    symfony console d:s:u --force
```
8. Installer les données de démonstration (fixtures), lancez la commande suivante depuis le projet :
```
    symfony console d:f:l --no-interaction
```
9. Créer une nouvelle pair de clé de sécurité pour la librairie Lexik
```
     symfony console lexik:jwt:generate-keypair
```
10. Une paire de clé sera générée dans le dossier /config/jwt
11. Démarrer un server symfony sur le port 8000 par defaut, lancez la commande suivante depuis le projet :
```
    symfony serve -d
```
12. L'application est maintenant accessible à cette adresse : 
```
    http://localhost:8000
```
13. Email de connexion du client de test : 
```
    admin@gmail.com
```
14. Password de connexion du client de test: 
```
    BilMo123!
```
15. Une documentation de l'API est accessible à cette adresse : 
```
    http://localhost:8000/api/doc
```