<?php


namespace App\Tests\Functional;

use Codeception\Util\HttpCode;
use Doctrine\ORM\Query\Expr\Func;
use App\Tests\Support\FunctionalTester;
use App\Service\DataScraper;
use Symfony\Component\DomCrawler\Crawler;
use function PHPUnit\Framework\isInstanceOf;

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
    public function testCacDataResponseIsOk(FunctionalTester $I): void
    {
        $dataScraper = $I->grabService(DataScraper::class);

        $response = $dataScraper->getResponseFromHttpClient($_ENV['CAC_DATA']);
        $responseCode = $response->getStatusCode();
        $I->assertEquals($responseCode, HttpCode::OK, "Test la récupération des données du Cac");
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testLvcDataResponseIsOk(FunctionalTester $I): void
    {
        $dataScraper = $I->grabService(DataScraper::class);

        $response = $dataScraper->getResponseFromHttpClient($_ENV['LVC_DATA']);
        $responseCode = $response->getStatusCode();
        $I->assertEquals($responseCode, HttpCode::OK, "Test la récupération des données du Lvc");
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
    public function testGetDataResultIsArrayAndIsNotEmpty(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $dataScraper->getData($_ENV['CAC_DATA']);

        // Vérifie que le résultat est un tableau et qu'il n'est pas vide
        $I->assertIsArray($result);
        $I->assertNotCount(0, $result);
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
        try {
            $dataScraper->getData('bad/url');
            // Si aucune exception n'est levée, le test échoue
            $I->fail('Une exception aurait dû être levée pour une URL invalide.');
        } catch (\RuntimeException $e) {
            // Vérifie que le message d'erreur contient la phrase attendue
            $I->assertStringContainsString('Erreur lors de la création du crawler', $e->getMessage());
        }
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testParseDataReturnsArray(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode ParseData avec le crawler en paramètre
        $result = $dataScraper->ParseData($crawler);

        // Vérifie que le résultat est un tableau
        $I->assertIsArray($result);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testResultIsAnArrayOfArrays(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode ParseData avec le crawler en paramètre
        $result = $dataScraper->ParseData($crawler);

        // Vérifie que le résultat est un tableau de sous-tableaux
        $isArray = true;
        foreach ($result as $row) {
            if (!is_array($row)) {
                $isArray = false;
                break;
            }
        }
        $I->assertTrue($isArray);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testLengthOfEachRowInArrayResultEqualsFive(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode ParseData avec le crawler en paramètre
        $result = $dataScraper->ParseData($crawler);

        // Vérifie que les sous-tableaux de résultat contiennent 5 valeurs
        $lengthEqualsFive = true;
        foreach ($result as $row) {
            if (count($row) !== 5) {
                $lengthEqualsFive = false;
                break;
            }
        }
        $I->assertTrue($lengthEqualsFive);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testFirstIndexOfEachRowInArrayResultIsDateFormat(FunctionalTester $I): void
    {
        // Récupère une instance du service DataScraper
        $dataScraper = $I->grabService(DataScraper::class);

        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode ParseData avec le crawler en paramètre
        $result = $dataScraper->ParseData($crawler);

        // Vérifie que le premier indice de chaque ligne est une chaîne de caractère au format jj/mm/aaaa
        $isDateFormat = true;
        foreach ($result as $row) {
            if (!preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $row[0])) {
                $isDateFormat = false;
                break;
            }
        }
        $I->assertTrue($isDateFormat);
    }
}
