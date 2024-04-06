<?php

namespace App\Tests\Unit;

use Exception;
use Codeception\Stub;
use App\Service\DataScraper;
use App\Tests\Support\UnitTester;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataScraperCest
{
    private DataScraper $dataScraper;

    /**
     * @throws Exception
     */
    public function _before(UnitTester $I): void
    {
        // Crée un double de httpClientInterface pour l'injecter dans dataScraper
        $httpClientMock = Stub::makeEmpty(HttpClientInterface::class);
        $this->dataScraper = new DataScraper($httpClientMock);
    }

    /**
     * @param UnitTester $I
     * @return void
     */
    public function testISOPENEDMethodReturnsTrueOnWeekDaysIfHourIsAfterEighteen(UnitTester $I): void
    {
        if (in_array(date('w'), range(1, 5), true) && date('G') >= 18) {
            $I->assertTrue($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped("Le test ne peut être joué que s'il est au moins 18:00");
        }
    }

    /**
     * @param UnitTester $I
     * @return void
     */
    public function testISOPENEDMethodReturnsFalseOnWeekDaysIfHourIsBeforeEighteen(UnitTester $I): void
    {
        if (in_array(date('w'), range(1, 5), true) && date('G') < 18) {
            $I->assertFalse($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped("Le test ne peut être joué que s'il est moins de 18:00");
        }
    }

    /**
     * @param UnitTester $I
     * @return void
     */
    public function testISOPENEDMethodReturnsFalseOnSaturday(UnitTester $I): void
    {
        if (date('w') === '6') {
            $I->assertFalse($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped("Le test ne peut être joué que le samedi");
        }
    }

    /**
     * @param UnitTester $I
     * @return void
     */
    public function testISOPENEDMethodWillReturnsFalseOnSunday(UnitTester $I): void
    {
        if (date('w') === '0') {
            $I->assertFalse($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped("Le test ne peut être joué que le dimanche");
        }
    }

    /**
     * @param UnitTester $I
     * @return void
     */
    public function testDeleteFirstIndex(UnitTester $I): void
    {
        $data = ['element1', 'element2', 'element3'];
        $expectedData = ['element2', 'element3'];

        $result = $this->dataScraper->deleteFirstIndex($data);

        $I->assertIsArray($result);
        $I->assertSame($result, $expectedData);
    }
}
