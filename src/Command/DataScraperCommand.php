<?php

namespace App\Command;

use App\Service\DataScraper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:data:scraper',
    description: 'Commande permettant de récupérer les données boursières',
)]
class DataScraperCommand extends Command
{
    private DataScraper $dataScraper;

    public function __construct(DataScraper $dataScraper)
    {
        $this->dataScraper = $dataScraper;

        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Appel du service DataScraper pour récupérer les cotations du Cac
        $CacData = $this->dataScraper->getData($_ENV['CAC_DATA']);

        // Si le résultat est une chaîne, c'est qu'une exception a été rencontrée : on l'affiche (à faire ici ?)

        // Si le résultat est un tableau vide, c'est qu'aucune donnée n'a été récupéré : on affiche un message (ici ?)

        // Autrement, on structure les données utiles (cela ne devrait-il pas être fait en amont ?)

        $io->success('Les données ont été importées avec succès.');

        return Command::SUCCESS;
    }
}
