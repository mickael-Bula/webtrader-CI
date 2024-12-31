<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DataScraper
{
    private HttpClientInterface $client;

    private LoggerInterface $logger;

    private mixed $token;

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        // Déclare le fuseau horaire pour une vérification correcte de l'heure courante
        date_default_timezone_set('Europe/Paris');

        $this->client = $client;
        $this->logger = $logger;

        // Récupère le token d'authentification auprès de l'API
        $this->token = $this->setToken();
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getResponseFromHttpClient(string $url): ResponseInterface
    {
        return $this->client->request('GET', $url);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getCrawler(string $url): Crawler
    {
        $response = $this->GetResponseFromHttpClient($url);

        // Récupère le contenu de la réponse
        $htmlContent = $response->getContent();

        // Crée une instance de Crawler avec le contenu HTML
        return new Crawler($htmlContent);
    }

    /**
     * @return string|string[][]
     */
    public function getData(string $url): array|string
    {
        try {
            $crawler = $this->getCrawler($url);

            return $this->parseData($crawler);
        } catch (
            ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
                $errorMessage = sprintf('Erreur lors de la création du crawler : %s', $e->getMessage());
                $this->logger->error($errorMessage);

                throw new \RuntimeException($errorMessage, 1, $e);
            }
    }

    /**
     * @return string[][]
     */
    public function parseData(Crawler $crawler): array
    {
        $rawData = $this->filterData($crawler);

        // On divise les données recueillies par groupes de sept
        $splitData = $this->dataChunk($rawData);

        // Filtre les résultats pour ne récupérer que les données utiles (date, closing, opening, higher, lower)
        $shrinkData = $this->shrinkData($splitData);

        // On trie $shrinkData en vérifiant que le premier indice est une date au format dd/mm/yyyy ou Jul 31, 2024
        $pattern = '/^\d{2}\/\d{2}\/\d{4}$|^[A-Za-z]{3} \d{1,2}, \d{4}$/';
        $data = array_filter($shrinkData, static fn ($row): int|false => preg_match($pattern, $row[0]));

        // On s'assure que les dates sont au format dd/mm/yyyy, attendu par l'API
        foreach ($data as $key => $item) {
            if (preg_match('/^[A-Za-z]{3} \d{1,2}, \d{4}$/', $item[0])) {
                $data[$key][0] = \DateTime::createFromFormat('M d, Y', $item[0])->format('d/m/Y');
            }
        }

        // Si le marché est ouvert, je supprime la valeur du jour courant du tableau de résultats
        if ($this->isOpened()) {
            return $this->deleteFirstIndex($data);
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function filterData(Crawler $crawler): array
    {
        return $crawler->filter('table > tbody > tr > td')
            ->each(fn ($node) => $node->text() ?: 'rien à afficher')
        ;
    }

    /**
     * La fonction array_chunk() divise le tableau passé en paramètre avec une taille fixée par le second.
     *
     * @param string[] $data
     *
     * @return string[][]
     */
    public function dataChunk(array $data): array
    {
        return array_chunk($data, 7);
    }

    /**
     * Réduit chacune des lignes d'un tableau à ses 5 premiers indices.
     *
     * @param string[][] $data
     *
     * @return string[][]
     */
    public function shrinkData(array $data): array
    {
        return array_map(static fn ($chunk): array => array_slice($chunk, 0, 5), $data);
    }

    public function isOpened(): bool
    {
        return (int) date('w') >= 1 && (int) date('w') <= 5 && date('G') <= 18;
    }

    /**
     * Supprime le premier indice du tableau.
     *
     * @param string[][] $data
     *
     * @return string[][]
     */
    public function deleteFirstIndex(array $data): array
    {
        // Si je retournais directement le tableau, seul l'élément supprimé serait récupéré
        array_splice($data, 0, 1);

        return $data;
    }

    /**
     * Filtre le tableau de résultats pour ne récupérer que les données utiles (date, closing, opening, higher, lower).
     *
     * @param string[][] $data
     *
     * @return string[][]
     */
    public function getFilteredData(array $data): array
    {
        return array_map(static fn ($chunk): array => array_slice($chunk, 0, 5), $data);
    }

    /**
     * @param string[][] $array
     *
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function sendData(array $array, string $stock): ResponseInterface
    {
        $json = $this->serializeData($array, $stock);

        return $this->client->request(
            'POST',
            sprintf('%s/api/stocks/%s', $_ENV['API'], $stock),
            [
                'headers' => ['Authorization' => 'Bearer '.$this->token],
                'json' => $json,
            ]
        );
    }

    /**
     * @param string[][] $array
     *
     * @throws \JsonException
     */
    public function serializeData(array $array, string $stock): string
    {
        // Construction du tableau associatif
        $data = [];
        $keys = ['createdAt', 'closing', 'opening', 'higher', 'lower'];
        foreach ($array as $row) {
            $data[] = array_combine($keys, $row);
        }

        $data = $this->convertStringToFloat($data, $stock);

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string[][] $data
     *
     * @return string[][]
     */
    public function convertStringToFloat(array $data, string $stock): array
    {
        if ('cac' === $stock) {
            return array_map(static function (array $item): array {
                return [
                    'createdAt' => $item['createdAt'],
                    'closing' => (float) str_replace(['.', ','], ['', '.'], $item['closing']),
                    'opening' => (float) str_replace(['.', ','], ['', '.'], $item['opening']),
                    'higher' => (float) str_replace(['.', ','], ['', '.'], $item['higher']),
                    'lower' => (float) str_replace(['.', ','], ['', '.'], $item['lower']),
                ];
            }, $data);
        }

        return array_map(static function (array $item): array {
            return [
                'createdAt' => $item['createdAt'],
                'closing' => (float) $item['closing'],
                'opening' => (float) $item['opening'],
                'higher' => (float) $item['higher'],
                'lower' => (float) $item['lower'],
            ];
        }, $data);
    }

    /**
     * @return mixed|null
     *
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function setToken(): mixed
    {
        $user = $_ENV['USER'];
        $password = $_ENV['PASSWORD'];

        $credentials = ['username' => $user, 'password' => $password];

        // Récupération du token
        $tokenResponse = $this->client
            ->request(
                'POST',
                $_ENV['API'].'/api/login_check',
                ['json' => $credentials]
            )
        ;

        // Récupération du contenu de la réponse
        $content = $tokenResponse->getContent();

        // Traitement du contenu JSON
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $data['token'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getToken(): mixed
    {
        return $this->token;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function displayFinalMessage(SymfonyStyle $io, string $stock, ResponseInterface $response): void
    {
        switch ($response->getStatusCode()) {
            case 200:
                $responseContent = $response->getContent();
                $decodedResponseMessage = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);

                // Si le contenu de la réponse n'est pas encodé en JSON, le contenu original est utilisé comme fallback.
                $responseMessage = JSON_ERROR_NONE === json_last_error()
                    ? $decodedResponseMessage
                    : $responseContent;

                $io->warning($responseMessage);
                $this->logger->warning($responseMessage);

                break;

            case 201:
                $successMessage = sprintf('Données %s envoyées avec succès à l\'API', $stock).PHP_EOL;
                $responseMessage = $response->getContent();
                $io->success($successMessage);
                $this->logger->info($successMessage.' : '.$responseMessage);

                break;

            default:
                $content = $response->toArray();
                $errorMessage = $content['error'] ?? "(PAS DE MESSAGE D'ERREUR)";
                $io->error(sprintf('Erreur lors de l\'envoi des données %s à l\'API : ', $stock).$errorMessage);
                $this->logger->error(sprintf('Erreur lors de l\'envoi des données %s à l\'API : ', $stock).$errorMessage);

                break;
        }
    }
}
