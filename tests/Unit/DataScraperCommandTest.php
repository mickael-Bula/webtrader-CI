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
        // Crée un mock de HttpClientInterface
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

    /**
     * @return void
     */
    public function testCommandHandlesExceptionFromDataScraper(): void
    {
        // Crée un mock de HttpClientInterface
        $httpClientMock = $this->getMockBuilder(HttpClientInterface::class)
            ->getMock();

        // Crée un double de la classe DataScraper pour forcer une exception lors de l'appel à getData()
        $dataScraperMock = $this->getMockBuilder(DataScraper::class)
            ->onlyMethods(['getData'])
            ->setConstructorArgs([$httpClientMock])
            ->getMock();

        // Configure le double pour qu'il lance une exception lors de l'appel à getData()
        $dataScraperMock->method('getData')
            ->willThrowException(new \Exception('Erreur de récupération des données'));

        // Crée une instance de la commande DataScraperCommand
        $application = new Application();
        $application->add(new DataScraperCommand($dataScraperMock));

        // Crée un testeur de commande
        $command = $application->find('app:data:scraper');
        $commandTester = new CommandTester($command);

        // Exécute la commande
        $statusCode = $commandTester->execute([]);

        // Vérifie que le code de retour est différent de 0 (indiquant une erreur)
        $this->assertEquals(1, $statusCode);

        // Je récupère la sortie de la commande en console
        $output = $commandTester->getDisplay();

        // Vérifie que la sortie contient le message d'erreur
        $this->assertStringContainsString('Erreur de récupération des données', $output);
    }

}
