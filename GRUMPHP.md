# mise en place de grumphp

## Installation

```bash
$ composer require --dev phpro/grumphp friendsofphp/php-cs-fixer symfony/yaml phpspec/phpspec phpunit/phpunit
$ vendor\bin\grumphp configure
$ vendor\bin\grumphp git:init
```

## Exécution de grumphp

```bash
$ vendor\bin\grumphp run  # lancer grumphp
$ vendor\bin\grumphp run --files path/to/your/file.php  # lancer un fichier particulier
$ vendor\bin\grumphp list # affiche la liste des tâches disponibles
```

## Exemple de configuration personnalisée

```yaml
parameters:
    git_dir: .
    bin_dir: vendor/bin
    tasks:
        phpcsfixer:
            config: .php_cs.dist
        phpspec: ~
        phpunit:
            config_file: phpunit.xml.dist
        yamllint:
            parse_php: false
            syntax:
                - symfony
            directories:
                - src
                - config
        phpstan:
            level: max
            autoload_file: vendor/autoload.php
        jsonlint:
            ignore_patterns:
                - "vendor/*"
```

>NOTE : j'ai dû modifier la version de php (et y ajouter xdebug) pour corriger l'erreur suivante :
> 
>`Error: Your version of PHP is affected by serious garbage collector bugs related to fibers. Please upgrade to a newer version of PHP, i.e. >= 8.1.17 or => 8.2.4`

Après avoir téléchargé et configuré la nouvelle version de php, j'ai pu lancer `grumphp` sans erreurs.

### Troubleshooting avec grumphp

J'ai rencontré une difficulté dans la validation des commits faits depuis phpstorm.
En effet, malgré une exécution réussir de la commande `vendor\bin\grumphp run`,
le commit des modifications depuis les outils phpstorm n'a pas fonctionné.
L'erreur signalée reprenait l'erreur suivante :

```bash
`Error: Your version of PHP is affected by serious garbage collector bugs related to fibers. Please upgrade to a newer version of PHP, i.e. >= 8.1.17 or => 8.2.4`
```

Or, la version de php utilisée est bien >= à 8.1.17.

J'ai pu passer outre le problème en exécutant le commit depuis la console.
Il semblerait donc qu'il y ait une différence entre les versions utilisées pour commiter entre console et phpstorm...