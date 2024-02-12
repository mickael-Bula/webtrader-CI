<?php

namespace App\Tests\Unit;

use App\Service\DataScraper;
use PHPUnit\Framework\TestCase;
use App\Command\DataScraperCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Cette classe de test utilise uniquement phpUnit
 */
class DataScraperCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testCommandReturnsOneWhenResponseIsAnEmptyArray(): void
    {
        // Créer un mock de HttpClientInterface
        $httpClientMock = $this->getMockBuilder(HttpClientInterface::class)
            ->getMock();

        // Je crée un double de la classe DataScraper pour que la méthode getData retourne un tableau vide
        $dataScraperMock = $this->getMockBuilder(DataScraper::class)
            ->onlyMethods(['getData'])
            ->setConstructorArgs([$httpClientMock]) // Passe le mock au constructeur
            ->getMock();

        $dataScraperMock->method('getData')->willReturn([]);

        // Je crée une instance de la commande DataScraperCommand
        $application = new Application();
        $application->add(new DataScraperCommand($dataScraperMock));

        // Je crée un testeur de commande
        $command = $application->find('app:data:scraper');
        $commandTester = new CommandTester($command);

        // Exécute la commande
        $statusCode = $commandTester->execute([]);

        // Je récupère la sortie de la commande en console
        $output = $commandTester->getDisplay();

        // Vérifie que le code de retour est 1
        $this->assertEquals(1, $statusCode);

        // Vérifie la sortie en console
        $this->assertStringContainsString('Aucune données récupérées depuis le site', $output);
    }
}
