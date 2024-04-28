<?php declare(strict_types=1);

namespace App\Tests\Functional;

use Codeception\Util\HttpCode;
use App\Tests\Support\FunctionalTester;
use App\Service\DataScraper;
use Symfony\Component\DomCrawler\Crawler;

class DataScraperCommandCest
{
    /** @var object Récupère une instance de la classe DataScraper */
    private object $dataScraper;

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function _before(FunctionalTester $I): void
    {
        $this->dataScraper = $I->grabService(DataScraper::class);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testDataScraperServiceIsAvailable(FunctionalTester $I): void
    {
        $I->assertNotNull($this->dataScraper);
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
    public function testDataScraperCommandDisplaysASuccessMessage(FunctionalTester $I): void
    {
        $I->runShellCommand('php bin/console app:data:scraper');
        $I->seeShellOutputMatches('/.*Données cac envoyées avec succès à l\'API.*/');
        $I->seeShellOutputMatches('/.*Données lvc envoyées avec succès à l\'API.*/');
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testCacDataResponseIsOk(FunctionalTester $I): void
    {
        $response = $this->dataScraper->getResponseFromHttpClient($_ENV['CAC_DATA']);
        $responseCode = $response->getStatusCode();
        $I->assertEquals($responseCode, HttpCode::OK, "Test la récupération des données du Cac");
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testLvcDataResponseIsOk(FunctionalTester $I): void
    {
        $response = $this->dataScraper->getResponseFromHttpClient($_ENV['LVC_DATA']);
        $responseCode = $response->getStatusCode();
        $I->assertEquals($responseCode, HttpCode::OK, "Test la récupération des données du Lvc");
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testDataScraperHttpClientIsCreated(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Vérifie que l'on obtient une instance de Crawler
        $I->assertInstanceOf(Crawler::class, $result);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testGetDataResultIsArrayAndIsNotEmpty(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $this->dataScraper->getData($_ENV['CAC_DATA']);

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
        // Exécute la méthode getData() du service avec l'URL en argument
        try {
            $this->dataScraper->getData('bad/url');
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
        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode ParseData avec le crawler en paramètre
        $result = $this->dataScraper->ParseData($crawler);

        // Vérifie que le résultat est un tableau
        $I->assertIsArray($result);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testResultIsAnArrayOfArrays(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode ParseData avec le crawler en paramètre
        $result = $this->dataScraper->ParseData($crawler);

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

    public function testFilterDataReturnsAnArray(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode FilterData avec le crawler en paramètre
        $result = $this->dataScraper->filterData($crawler);

        // Vérifie que le résultat est un tableau
        $I->assertIsArray($result);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testDataChunkReturnsAnArrayWithRowsOfSevenValues(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode filterData avec le crawler en paramètre
        $rawData = $this->dataScraper->filterData($crawler);

        // Exécute la méthode dataChunk
        $result = $this->dataScraper->dataChunk($rawData);

        // Vérifie que les sous-tableaux de résultat contiennent 7 valeurs
        $lengthEqualsSeven = true;
        foreach ($result as $row) {
            if (count($row) !== 7) {
                $lengthEqualsSeven = false;
                break;
            }
        }
        $I->assertTrue($lengthEqualsSeven);
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function testShrinkDataReturnsAnArrayWithRowsOfFiveValues(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode filterData avec le crawler en paramètre
        $rawData = $this->dataScraper->filterData($crawler);

        // Exécute la méthode dataChunk
        $dataChunk = $this->dataScraper->dataChunk($rawData);

        // Exécute la méthode shrinkData
        $result = $this->dataScraper->shrinkData($dataChunk);

        // Vérifie que chaque ligne contient 5 valeurs
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
    public function testFirstIndexOfParseDataResultIsDateFormat(FunctionalTester $I): void
    {
        // Exécute la méthode getCrawler() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode parseData avec le crawler en paramètre
        $result = $this->dataScraper->parseData($crawler);

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
