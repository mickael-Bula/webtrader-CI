<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Service\DataScraper;
use App\Tests\Support\FunctionalTester;
use Codeception\Util\HttpCode;
use Symfony\Component\DomCrawler\Crawler;

class DataScraperCommandCest
{
    /** @var object Récupère une instance de la classe DataScraper */
    private object $dataScraper;

    public function _before(FunctionalTester $I): void
    {
        $this->dataScraper = $I->grabService(DataScraper::class);
    }

    public function testDataScraperServiceIsAvailable(FunctionalTester $I): void
    {
        $I->assertNotNull($this->dataScraper);
    }

    public function testDataScraperCommandReturnsZero(FunctionalTester $I): void
    {
        // Exécute la commande Symfony
        $I->runShellCommand('php bin/console app:data:scraper');
        $I->seeResultCodeIs(0);
    }

    public function testDataScraperCommandDisplaysASuccessMessage(FunctionalTester $I): void
    {
        $I->runShellCommand('php bin/console app:data:scraper');

        // Récupère la sortie en console
        $output = $I->grabShellOutput();

        // Vérifie si l'une des chaînes recherchées est présente dans la sortie de la console
        $found = (
            str_contains($output, 'ENTITÉ App\Entity\Cac À JOUR : AUCUNE DONNEE INSÉRÉE')
            || str_contains($output, 'ENTITÉ App\Entity\Lvc À JOUR : AUCUNE DONNEE INSÉRÉE')
            || str_contains($output, 'Données cac envoyées avec succès à l\'API')
            || str_contains($output, 'Données lvc envoyées avec succès à l\'API')
        );

        $I->assertTrue($found, "L'un des messages attendus a été trouvé dans la sortie de la console.");
    }

    public function testCacDataResponseIsOk(FunctionalTester $I): void
    {
        $response = $this->dataScraper->getResponseFromHttpClient($_ENV['CAC_DATA']);
        $responseCode = $response->getStatusCode();
        $I->assertEquals($responseCode, HttpCode::OK, 'Test la récupération des données du Cac');
    }

    public function testLvcDataResponseIsOk(FunctionalTester $I): void
    {
        $response = $this->dataScraper->getResponseFromHttpClient($_ENV['LVC_DATA']);
        $responseCode = $response->getStatusCode();
        $I->assertEquals($responseCode, HttpCode::OK, 'Test la récupération des données du Lvc');
    }

    public function testDataScraperHttpClientIsCreated(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Vérifie que l'on obtient une instance de Crawler
        $I->assertInstanceOf(Crawler::class, $result);
    }

    public function testGetDataResultIsArrayAndIsNotEmpty(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $result = $this->dataScraper->getData($_ENV['CAC_DATA']);

        // Vérifie que le résultat est un tableau et qu'il n'est pas vide
        $I->assertIsArray($result);
        $I->assertNotCount(0, $result);
    }

    public function testGetDataResultWithBadUrlMatchesAString(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        try {
            $this->dataScraper->getData('bad/url');
            // Si aucune exception n'est levée, le test échoue
            $I->fail('Une exception aurait dû être levée pour une URL invalide.');
        } catch (\RuntimeException $runtimeException) {
            // Vérifie que le message d'erreur contient la phrase attendue
            $I->assertStringContainsString('Erreur lors de la création du crawler', $runtimeException->getMessage());
        }
    }

    public function testParseDataReturnsArray(FunctionalTester $I): void
    {
        // Exécute la méthode getData() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode ParseData avec le crawler en paramètre
        $result = $this->dataScraper->ParseData($crawler);

        // Vérifie que le résultat est un tableau
        $I->assertIsArray($result);
    }

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
            if (7 !== count($row)) {
                $lengthEqualsSeven = false;

                break;
            }
        }

        $I->assertTrue($lengthEqualsSeven);
    }

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
            if (5 !== count($row)) {
                $lengthEqualsFive = false;

                break;
            }
        }

        $I->assertTrue($lengthEqualsFive);
    }

    public function testFirstIndexOfParseDataResultIsDateFormat(FunctionalTester $I): void
    {
        // Exécute la méthode getCrawler() du service avec l'URL en argument
        $crawler = $this->dataScraper->getCrawler($_ENV['CAC_DATA']);

        // Exécute la méthode parseData avec le crawler en paramètre
        $result = $this->dataScraper->parseData($crawler);

        // Vérifie que le premier indice de chaque ligne est une chaîne de caractère au format jj/mm/aaaa
        $isDateFormat = true;
        foreach ($result as $row) {
            if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $row[0])) {
                $isDateFormat = false;

                break;
            }
        }

        $I->assertTrue($isDateFormat);
    }
}
