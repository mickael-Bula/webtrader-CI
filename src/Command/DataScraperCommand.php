<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\DataScraper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:data:scraper',
    description: 'Commande permettant de récupérer les données boursières',
)]
class DataScraperCommand extends Command
{
    private readonly DataScraper $dataScraper;
    private readonly LoggerInterface $logger;

    public function __construct(DataScraper $dataScraper, LoggerInterface $logger)
    {
        $this->dataScraper = $dataScraper;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Affiche la date et l'heure de lancement du script dans le terminal et dans le fichier de log
        $startTime = new \DateTime();
        $message = sprintf('[%s] LANCEMENT DE LA RÉCUPÉRATION DES DONNÉES BOURSIÈRES',
            $startTime->format('d-m-Y H:i:s')
        );
        $io->writeln($message);
        $this->logger->info($message);

        $stocks = ['cac' => $_ENV['CAC_DATA'], 'lvc' => $_ENV['LVC_DATA']];

        foreach ($stocks as $stock => $value) {
            $iterationTime = (new \DateTime())->format('d-m-Y H:i:s');
            $io->writeln(sprintf('[%s] SCRAPING DES DONNÉES DU %s', $iterationTime, strtoupper($stock)));
            try {
                // Appel du service DataScraper pour récupérer les cotations
                $stockData = $this->dataScraper->getData($value);
            } catch (\Exception $e) {
                // Si une exception est levée, afficher l'erreur et retourner un code d'échec
                $io->error("Erreur lors de la récupération des données $stock : ".$e->getMessage());

                return Command::FAILURE;
            }

            // Si le résultat est un tableau vide, c'est qu'aucune donnée n'a été récupérée
            if (!is_array($stockData) || 0 === count($stockData)) {
                $errorMessage = "Aucune donnée $stock récupérée depuis le site";
                $io->error($errorMessage);
                $this->logger->error($errorMessage);

                return Command::FAILURE;
            }

            // Envoi des données à l'API pour enregistrement en base
            $response = $this->dataScraper->sendData($stockData, $stock);

            // Affiche le contenu de la réponse en fonction du code de retour
            $this->dataScraper->displayFinalMessage($io, $stock, $response);
        }
        // Affiche la date et l'heure de la fin du script dans le terminal et dans le fichier de log
        $endTime = new \DateTime();
        $duration = $startTime->diff($endTime);
        $message = sprintf(
            '[%s] FIN DE LA RÉCUPÉRATION DES DONNÉES BOURSIÈRES | DURÉE %02dm:%02ds',
            $endTime->format('d-m-Y H:i:s'), $duration->i, $duration->s
        );
        $io->writeln($message);
        $this->logger->info($message);

        return Command::SUCCESS;
    }
}
