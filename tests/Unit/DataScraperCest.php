<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\DataScraper;
use App\Tests\Support\UnitTester;
use Codeception\Stub;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataScraperCest
{
    private DataScraper $dataScraper;

    /**
     * @throws \Exception
     */
    public function _before(): void
    {
        // Crée un double de httpClientInterface pour l'injecter dans dataScraper
        $httpClientMock = Stub::makeEmpty(HttpClientInterface::class);

        // Crée un double du logger
        $loggerMock = Stub::makeEmpty(LoggerInterface::class);

        // Crée un double de DataScraper avec une dépendance doublée et une méthode dont on force le retour
        $this->dataScraper = Stub::construct(
            DataScraper::class,
            ['client' => $httpClientMock, 'logger' => $loggerMock],
            ['setToken' => 'token']
        );
    }

    public function testISOPENEDMethodReturnsTrueOnWeekDaysIfHourIsBeforeEighteen(UnitTester $I): void
    {
        if ((int) date('w') >= 1 && (int) date('w') <= 5 && date('G') <= 18) {
            $I->assertTrue($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped("Le test ne peut être joué qu'avant 18:00");
        }
    }

    public function testISOPENEDMethodReturnsFalseOnWeekDaysIfHourIsAfterEighteen(UnitTester $I): void
    {
        if ((int) date('w') >= 1 && (int) date('w') <= 5 && date('G') <= 18) {
            $I->assertFalse($this->dataScraper->isOpened(), 'Le marché est fermé en semaine avant 18:00');
        } elseif (in_array((int) date('w'), [0, 6], true)) {
            $I->assertFalse($this->dataScraper->isOpened(), 'Le marché est fermé les jours de week-end');
        } else {
            $I->markTestSkipped("Le test ne peut être joué que s'il est plus de 18:00 un jour de semaine");
        }
    }

    public function testISOPENEDMethodReturnsFalseOnSaturday(UnitTester $I): void
    {
        if ('6' === date('w')) {
            $I->assertFalse($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped('Le test ne peut être joué que le samedi');
        }
    }

    public function testISOPENEDMethodWillReturnsFalseOnSunday(UnitTester $I): void
    {
        if ('0' === date('w')) {
            $I->assertFalse($this->dataScraper->isOpened());
        } else {
            $I->markTestSkipped('Le test ne peut être joué que le dimanche');
        }
    }

    public function testDeleteFirstIndex(UnitTester $I): void
    {
        $data = [['element1', 'element2', 'element3'], ['element4', 'element5', 'element6']];
        $expectedData = [['element4', 'element5', 'element6']];

        $result = $this->dataScraper->deleteFirstIndex($data);

        $I->assertIsArray($result);
        $I->assertSame($result, $expectedData);
    }
}
