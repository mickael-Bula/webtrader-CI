# Codeception : commandes utiles

```bash
php vendor\bin\codecept generate Functional DataScraper  # génère une suite de tests
php vendor\bin\codecept run Functional  # lance les tests du répertoire indiqué
php vendor\bin\codecept clean   # supprime le cache de codeception
php vendor\bin\codecept build   # reconstruit les classes de test
php vendor\bin\codecept run unit --filter getData  # teste uniquement la méthode getData du répertoire Unit

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
            arguments: [ '@Symfony\Contracts\HttpClient\HttpClientInterface' ]
```

Je déclare un service pour faire les traitements qui seront appelés dans la commande

Pour convoquer le service dans codeception, j'utilise la méthode suivante :

```bash
$ $dataScraper = $I->grabService(DataScraper::class);	// veiller à utiliser le FQCN
# Les méthodes du service sont alors accessibles :
$result = $dataScraper->getData($_ENV['CAC_DATA']);
```

## Utilisation de l'interface HttpClientInterface

Plutôt que d'instancier HttpClient dans la méthode DataScraper::getData, j'opte pour l'injection de dépendance.
Pour ce faire, j'injecte HttpClientInterface dans le constructeur de la classe DataScraper,
puis j'utilise la propriété résultante pour gérer les requêtes http :

```php
class DataScraper
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getCrawler($url): Crawler
    {
    }
        $response = $this->client->request('GET', $url);
        // ...
    }
```

En procédant de la sorte, une instance de HttpClient est immédiatement servie par le container de services,
sans configuration complémentaire dans le fichier services.yaml.

Cependant, un container spécifique étant utilisé pour les tests, il faut préciser dans le fichier services_test.yaml
la dépendance injectée, sinon une erreur est lancée par codeception. Voici la configuration :

```yaml
services:
    App\Service\DataScraper:
        public: true
        arguments:
            # injecte la dépendance HttpClientInterface dans le constructeur de DataScraper lors des tests
          [ '@Symfony\Contracts\HttpClient\HttpClientInterface' ]
```

De cette manière, HttpClientInterface est injecté dans le constructeur de la classe DataScraper lors des tests.

## Test de la commande DataScraper

J'ai créé un test qui vérifie le résultat de la commande lorsque la méthode getData() ne retourne pas un tableau ou 
que celui-ci est vide.

J'ai tenté de l'implémenter avec codeception, mais sans succès : j'ai obtenu une erreur signalant ceci :

```
Fatal error: Uncaught ArgumentCountError: Too few arguments to function PHPUnit\Framework\TestCase::__construct(), 
0 passed in C:\laragon\www\stocks_from_csv\vendor\codeception\codeception\src\Codeception\Test\Loader\Cest.php on 
line 57 and exactly 1 expected in C:\laragon\www\stocks_from_csv\vendor\phpunit\phpunit\src\Framework\TestCase.php 
on line 322
```

Pour résoudre le problème, j'ai décidé d'exécuter ce test directement avec phpUnit.
Voici les commandes pour l'installer puis exécuter le test :

```bash
$ composer require --dev phpunit/phpunit
$ php vendor/bin/phpunit tests/Unit/DataScraperCommandTest.php
```

Avec ces modifications, le test passe.

>NOTE : Le fait de nommer la classe DataScraperCommandTest à la place de DataScraperCommandCest permet d'exécuter les 
> tests avec phpUnit exécuté depuis codeception

```bash
$ php vendor\bin\codecept run Unit
```

## Création d'une chaîne CI avec Github Actions

Pour configurer la CI, il est possible de se référer à la source suivante sur 
[dev.to](https://dev.to/icolomina/using-github-actions-to-execute-your-php-tests-after-every-push-2lpp).

Pour l'essentiel, il s'agit de créer un fichier `.github/workflows/github-CI.yaml` déclarant la configuration.
Pour cette CI, j'ai opté pour un lancement de l'ensemble des tests lors d'un `push` ou d'une `pull request`.

Cependant, pour que les tests phpunit et les tests codeception s'exécutent dans tous les environnements,
il faut déclarer les variables dans plusieurs fichiers.

Pour l'environnement de test local :
- pour phpunit, elles doivent être déclarées dans le fichier `.env.local`
- pour codeception, c'est dans les fichiers `.env.test` et `codeception.yaml`

Pour la CI au push sur github :
- pour phpunit, dans le fichier phpunit.xml.dist
- pour codeception, dans les fichiers `.env` et `github-codeception-config.yaml` (nom arbitraire).

Cette dernière configuration est requise pour jouer la diversité des tests disponibles.

Pour être complet, les tests lancés par codeception récupèrent les variables d'environnement dans le fichier
`codeception.yaml`, tandis que les tests qui lancent les commandes avec 
`$I->runShellCommand('php bin/console app:data:scraper');`
vont les chercher dans le fichier `.env`.

Quant à celles lancées depuis la CI et qui ne disposent que des fichiers poussés,
les variables doivent être déclarées dans ces derniers.

### Exclure des tests de la CI

Certains de mes tests fonctionnels nécessitent une connexion à mon API et ne sont donc pas testables en l'état.
En outre, l'URL de connexion doit être spécifiée en dur dans le fichier `.env.test`.

Pour signifier à github-CI de ne pas exécuter ces tests, il est possible d'utiliser le filtre `--exclude-group`.
Voici la manière de procéder :

1 - annoter les tests à exclure, par exemple @functional-local (la chaîne est libre, mais doit commencer par @)

2 - dans le fichier github-CI.yaml, ajouter le filtre comme ceci : 

    ```yaml
    run: vendor/bin/phpunit --exclude-group functional-local
    ```
