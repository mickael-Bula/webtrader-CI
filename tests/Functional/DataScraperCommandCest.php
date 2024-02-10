<?php


namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use App\Service\DataScraper;
use Symfony\Component\DomCrawler\Crawler;

class DataScraperCommandCest
{
    public function _before(FunctionalTester $I): void
    {
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testDataScraperServiceIsAvailable(FunctionalTester $I): void
    {
        $dataScraperService = $I->grabService(DataScraper::class);
        $I->assertNotNull($dataScraperService);
    }


    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testDataScraperCommandReturnsZero(FunctionalTester $I): void
    {
        // Exécute la commande Symfony
        $I->runShellCommand('php bin/console app:data:scraper');
        $I->seeResultCodeIs(0);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testDataScraperCommandWriteASuccessMessage(FunctionalTester $I): void
    {
        $I->runShellCommand('php bin/console app:data:scraper');
        $I->seeShellOutputMatches('/Les données ont été importées avec succès./');
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testDataScraperHttpClientIsCreated(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Vérifie que l'on obtient une instance de Crawler
        $I->assertInstanceOf(Crawler::class, $result);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testGetDataResultIsArray(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $dataScraper->getData($_ENV['CAC_DATA']);

        // Vérifie que le résultat est un tableau
        $I->assertIsArray($result);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testGetDataResultWithBadUrlMatchesAString(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $dataScraper->getData('bad/url');

        // Vérifie qu'un message d'erreur est affiché en console
        $I->assertStringContainsString('Erreur lors de la création du crawler', $result);
    }
}
