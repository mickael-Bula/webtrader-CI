<?php

namespace App\Tests\Functional;

use App\Kernel;
use App\Service\DataScraper;
use App\Command\DataScraperCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DataScraperCommandTest extends KernelTestCase
{
    // Surcharge de la méthode getKernel pour renvoyer une instance du kernel
    protected static function getKernelClass(): string
    {
        // Retourne le nom de la classe du kernel
        return Kernel::class;
    }

    public function testCommandIsSuccessful(): void
    {
        // Démarre le kernel
        self::bootKernel();

        // Récupère le conteneur de service
        $container = self::getContainer();

        // Récupère le service DataScraper depuis le conteneur de services
        $dataScraper = $container->get(DataScraper::class);

        $application = new Application();

        // Ajoute la commande avec la dépendance injectée (raison pour laquelle le conteneur de service est requis)
        $application->add(new DataScraperCommand($dataScraper));

        $command = $application->find('app:data:scraper');
        $commandTester = new CommandTester($command);

        // Exécute la commande
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}