<?php

namespace App\Command;

use Exception;
use JsonException;
use App\Service\DataScraper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
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
     * @throws DecodingExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stocks = ['cac' => $_ENV['CAC_DATA'], 'lvc' => $_ENV['LVC_DATA']];

        foreach ($stocks as $stock => $value) {
            try {
                // Appel du service DataScraper pour récupérer les cotations
                $stockData = $this->dataScraper->getData($value);
            } catch (Exception $e) {
                // Si une exception est levée, afficher l'erreur et retourner un code d'échec
                $io->error("Erreur lors de la récupération des données $stock : " . $e->getMessage());

                return Command::FAILURE;
            }

            // Si le résultat est un tableau vide, c'est qu'aucune donnée n'a été récupérée
            if (!is_array($stockData) || count($stockData) === 0) {
                $io->error("Aucune donnée $stock récupérée depuis le site");

                return Command::FAILURE;
            }

            // Envoi des données à l'API pour enregistrement en base
            $responseCode = $this->dataScraper->sendData($stockData, $stock);

            if ($responseCode->getStatusCode() === 201) {
                $io->success("Données $stock envoyées avec succès à l'API" . PHP_EOL);
            } else {
                $content = $responseCode->toArray();
                $errorMessage = $content['error'] ?? "(PAS DE MESSAGE D'ERREUR)";
                $io->error("Erreur lors de l'envoi des données $stock à l'API : " . $errorMessage);
            }
        }

        return Command::SUCCESS;
    }
}
