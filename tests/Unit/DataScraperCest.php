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
    public function testISOPENEDMethodReturnsTrueOnWeekDaysIfHourIsBeforeEighteen(UnitTester $I): void
    {
        if (in_array((int)date('w'), range(1, 5), true) && date('G') <= 18) {
            $I->assertTrue($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped("Le test ne peut être joué qu'avant 18:00");
        }
    }

    /**
     * @param UnitTester $I
     * @return void
     */
    public function testISOPENEDMethodReturnsFalseOnWeekDaysIfHourIsAfterEighteen(UnitTester $I): void
    {
        if (in_array((int)date('w'), range(1, 5), true) && date('G') > 18) {
            $I->assertFalse($this->dataScraper->isOpened(), 'Le marché est fermé en semaine avant 18:00');
        } else if (in_array((int)date('w'), [0, 6], true)) {
            $I->assertFalse($this->dataScraper->isOpened(), 'Le marché est fermé les jours de week-end');
        } else {
            $I->markTestSkipped("Le test ne peut être joué que s'il est plus de 18:00 un jour de semaine");
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
