# Codeception : commandes utiles

```bash
php vendor\bin\codecept generate Functional DataScraper  # génère une suite de tests
php vendor\bin\codecept run Functional  # lance les tests du répertoire indiqué
php vendor\bin\codecept clean   # supprime le cache de codeception
php vendor\bin\codecept build   # reconstruit les classes de test
```

## Ajout du module CLI de Codeception

Je décide de passer par une commande symfony pour récupérer les données boursières.

Ajout du module Cli

```bash
$ composer require --dev codeception/module-cli
```

Ajout du module dans la suite Functional.suite.yaml :

```yaml
actor: FunctionalTester
modules:
enabled:
- Asserts
- Cli
```

Création d'un test fonctionnel pour la commande :

```bash
$ php vendor\bin\codecept generate:cest Functional DataScraperCommand
```

## Comment tester un service

Pour rendre un service testable dans codeception, il faut que celui-ci soit déclaré comme `public`.
Si le service est déclaré dans le répertoire `src`, cela est fait par défaut dans `config/services.yaml`, 
**mais à condition que ce service soit appelé dans le code applicatif**.

Dans le cas contraire, il faudra ajouter une déclaration explicite, soit dans ce même fichier `config/services.yaml`,
soit dans un fichier dédié à la configuration des tests : `config/services_test.yaml`.

La déclaration se fait de la manière suivante :

```yaml
    services:
        App\Service\DataScraper:
            public: true
```

Je déclare un service pour faire les traitements qui seront appelés dans la commande

Pour convoquer le service dans codeception, j'utilise la méthode suivante :

```bash
$ $dataScraper = $I->grabService(DataScraper::class);	// veiller à utiliser le FQCN
# Les méthodes du service sont alors accessibles :
$result = $dataScraper->getData($_ENV['CAC_DATA']);
```
