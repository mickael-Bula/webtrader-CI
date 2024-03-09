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

## Création d'une chaîne CI avec GithubAction

TODO : https://dev.to/icolomina/using-github-actions-to-execute-your-php-tests-after-every-push-2lpp