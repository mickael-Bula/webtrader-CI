<?php

namespace App\Command;

use JsonException;
use App\Service\DataScraper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

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
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Appel du service DataScraper pour récupérer les cotations du Cac
            $cacData = $this->dataScraper->getData($_ENV['CAC_DATA']);
            $lvcData = $this->dataScraper->getData($_ENV['LVC_DATA']);
        } catch (\Exception $e) {
            // Si une exception est levée, afficher l'erreur et retourner un code d'échec
            $io->error('Erreur lors de la récupération des données : ' . $e->getMessage());

            return Command::FAILURE;
        }

        // Si le résultat est un tableau vide, c'est qu'aucune donnée n'a été récupérée
        if (!is_array($cacData) || count($cacData) === 0) {
            $io->error('Aucune données récupérées depuis le site');

            return Command::FAILURE;
        }

        // Les données utiles sont disponibles
        $fetchedCacData = $this->dataScraper->getFilteredData($cacData);
        $fetchedLvcData = $this->dataScraper->getFilteredData($lvcData);

        $response = $this->dataScraper->sendData($fetchedCacData, 'cac');
        $response['success'] ? $io->success($response['content']) : $io->error($response['content']);

        $response = $this->dataScraper->sendData($fetchedLvcData, 'lvc');
        $response['success'] ? $io->success($response['content']) : $io->error($response['content']);

        return Command::SUCCESS;
    }
}
