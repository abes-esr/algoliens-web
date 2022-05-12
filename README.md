# Algoliens Web

Ce repository comprend les sources d'une application web dont le but est de proposer à l'usager des notices issues des résultats du web service de l'Abes Algoliens.

Le développement propose une interface web qui va aller chercher dans une base de données des notices "à corriger" puis enregistrer l'action faite par l'utilisateur sur la notice proposée.

L'outil est développé en PHP sur la base du framework Symfony dans sa version 5.

# Capture d'écran

![Visualistion d'une notice](doc/screenshot.png)

# Installation

Afin d'installer une copie de cet outil, la procédure à suivre est la suivante :

## Pré-requis
* PHP 7.2 (fonctionne peut-être sur des versions plus anciennes mais testé sur 7.2)
* Mysql
* composer ( https://getcomposer.org/download/ )
* Yarn ( https://yarnpkg.com/ )

## Étapes d'installation
Récupération des sources sur github, via le téléchargement du [Zip Master](https://github.com/abes-esr/algoliens-web/archive/master.zip) ou clonage du dépôt : 
```
git clone https://github.com/abes-esr/algoliens-web.git
```

Dans le répertoire ainsi créé, on va ajouter un fichier .env.local qui contiendra les informations suivantes :
```
APP_ENV=prod
DATABASE_URL="mysql://UTILISATEUR:MOTDEPASSE@HOST:3306/DBNAME"
```

APP_ENV peut prendre les valeurs ```prod``` ou ```dev```.

Les variables à modifier étant : 
- ```UTILISATEUR```
- ```MOTDEPASSE```
- ```HOST```
- ```DBNAME```

Une fois cette configuration effectuée, on lancera les commandes suivantes :

- ```composer install``` : récupération des librairies PHP qui nous seront utiles;
- ```yarn install``` : installation de Encore pour la gestion du CSS;
- ```php bin/console doctrine:database:create``` : création de la base de données via Symfony
- ```php bin/console doctrine:schema:update --force``` : création de la structure de la base de données

Dans la structure proposée pour l'installation, les informations de sessions sont stockées en base de données. Pour que cela fonctionne il convient de créer manuellement la table sessions dans la base de données : 

```mysql
CREATE TABLE `sessions` (
    `sess_id` VARCHAR(128) NOT NULL PRIMARY KEY,
    `sess_data` BLOB NOT NULL,
    `sess_time` INTEGER UNSIGNED NOT NULL,
    `sess_lifetime` INTEGER UNSIGNED NOT NULL
) COLLATE utf8mb4_bin, ENGINE = InnoDB;
```

On va ensuite générer les fichiers nécessaires au CSS/JS de l'interface publique via la commande suivante : 

```yarn encore dev``` ou ```yarn encore prod```

# Configuration de l'outil
Une fois les étapes précédentes réalisées, l'application est fonctionnelle mais la base est vide. Pour pouvoir commencer à l'utiliser il convient de créer un administrateur. Pour cela, on va créer dans la base de données, dans la table ```user``` une entrée qui contient les valeurs suivantes : 

- ```email``` : l'identifiant qui vous permettra de vous connecter;
- ```roles``` : la chaîne ```a:1:{i:0;s:10:"ROLE_ADMIN";}``` qui signifie en terme Symfony que l'utilisateur a les droits administrateur sur l'outil;
- ```password``` : le "Encoded password" généré par la commande ```php bin/console security:encode-password``` que l'on aura lancée depuis la racine du projet;

Une fois cet utilisateur créé, on peut vérifier que tout s'est bien passé en se rendant à l'adresse à laquelle on a installé l'outil, dans le sous dossier /admin. 

L'interface d'admin est vide dans un premier temps. Pour la remplir il convient de créer un chantier/ILN. Pour cela, directement dans la base de données, on va créer une entrée dans la table ```iln``` : 

- ```code``` : un code interne pour identifier l'ILN, qui peut être l'ILN suffixé d'un code lisible (```015ubm``` pour Bordeaux Montaigne qui appartient à l'ILN 15 par exemple)
- ```number``` : le numéro de l'ILN auquel appartiennent les bibliothèques concernées, sous sa forme numérique, sans 0 de tête;
- ```label``` : le nom du chantier/ILN
- ```secret``` : un code à 4 chiffres qui permet d'avoir une URL unique et non "devinable" par projet; 

Une fois cet ILN créé, si l'on retourne sur l'interface d'administration et qu'on choisit d'aller sur l'administration de cet ILN. À la première consultation de cette page, Algoliens-web va identifier que l'ILN ne contient aucune bibliothèque et proposer de les charger via [le Web service iln2rcr](http://documentation.abes.fr/aideidrefdeveloppeur/index.html#MicroWebIln2rcr).

Une fois les RCR créés, si l'on choisit d'accéder au dashboard d'administration de l'ILN concerné, on se retrouve face à la liste des RCR avec pour chacun deux croix rouges qui correspondent aux résultats des récupérations de notices qui n'ont pas encore été jouées. 

Lorsque l'on choisit "Voir le détail" sur un des RCR. Il est alors possible de lancer la récupération des notices depuis le webservice Algoliens. Deux types de récupérations sont proposées pour le moment : 

- **RCR Créateur** : toutes les notices pour lesquelles le RCR concerné est créateur. Ce premier appel est relativement rapide;
- **UNICA** : notices Unica, indépendamment de la création de la notice par le RCR ou non. Ce second appel est assez lent et peut parfois être amené à ne pas répondre. Si tel est le cas il est aussi possible de charger directement des fichiers via la ligne de commande (cf. $ Chargement par fichier ci-dessous);

Dès que des notices ont commencé à être chargées il est possible de commencer à utiliser l'interface publique.

## Chargement par fichier
La récupération des fichiers Unica peut parfois être assez longue, conduisant à un timeout du serveur. Pour éviter cela ou permettre de charger tout un ILN par la ligne de commande, il est possible d'utiliser la fonction ```php bin/console app:harvest-records```.

Cette fonction prend plusieurs arguments potentiels :
- --clean-database : permet de vider la base de données avant de lancer l'import. Pour le moment cette fonction vide toute la base de données pour l'ensemble des ILN si plusieurs sont chargés, attention;
- --iln : permet d'indiquer le numéro d'ILN sur lequel on souhaite travailler
- --rcr-from-file : permet d'indiquer un RCR spécifique

Il existe deux manières principales d'utiliser ce script :
- ```php bin/console app:harvest-records --iln XXX``` : va lancer un script en ligne de commande qui va parcourir tous les RCR de l'ILN XXX et aller interroger le webservice pour récupérer les contenus et les charger dans la base;
- ```php bin/console app:harvest-records --rcr-from-file YYY``` : cette option nécessite d'avoir téléchargé en amont le fichier depuis le Webservice (avec un lien du type https://www.idref.fr/AlgoLiens?localisationRcr=335222103&unica=localisationRcr&paprika=1&rownum=15) et l'avoir déposé dans le dossier ws_files. Le script va alors poser quelques questions interactives avant d'aller charger les notices.

## Gestion de raisons pour le non traitement (optionnel)
Par défaut il existe deux options : Notice traitée ou Notice à reprendre plus tard. Si l'on souhaite préciser la raison qui pousse à repousser le traitement d'un document (gestion de chantiers divers par exemple), il est possible de créer des entrées dans la table ```skip_reason```. 

Les options présentes dans cette table seront présentées sous la forme d'une liste de cases à cocher au moment de l'enregistrement. La première valeur insérée dans la table sera considérée comme la raison "par défaut".

# Historique

La version initiale du projet a été développée par Sylvain Machefert au sein de l'université Bordeaux Montaigne durant la période de confinement du printemps 2020. Le code source est mis à disposition selon les termes de la licence GNU GPL v3.

# Docker
Ce répertoire contient les éléments dockers pour lancer le serveur avec la commande suivante : 

```
docker-compose up
```