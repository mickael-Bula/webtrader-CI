<?php

namespace App\Service;

use JsonException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;


class DataScraper
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        // Déclare le fuseau horaire pour une vérification correcte de l'heure courante
        date_default_timezone_set('Europe/Paris');

        $this->client = $client;
    }

    /**
     * @param $url
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function getResponseFromHttpClient($url): ResponseInterface
    {
        return $this->client->request('GET', $url);
    }

    /**
     * @param $url
     * @return Crawler
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getCrawler($url): Crawler
    {
        $response = $this->GetResponseFromHttpClient($url);

        // Récupère le contenu de la réponse
        $htmlContent = $response->getContent();

        // Crée une instance de Crawler avec le contenu HTML
        return new Crawler($htmlContent);
    }

    /**
     * @param $url
     * @return array|string
     */
    public function getData($url): array|string
    {
        try {
            $crawler = $this->getCrawler($url);

            return $this->parseData($crawler);
        } catch (
            ClientExceptionInterface|TransportExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new \RuntimeException(
                sprintf('Erreur lors de la création du crawler : %s', $e->getMessage()),
                1,
                $e
            );
        }
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    public function parseData(Crawler $crawler): array
    {
        $rawData = $this->filterData($crawler);

        // On divise les données recueillies par groupes de sept
        $splitData = $this->dataChunk($rawData);

        // Filtre les résultats pour ne récupérer que les données utiles (date, closing, opening, higher, lower)
        $shrinkData = $this->shrinkData($splitData);

        // On trie $shrinkData en vérifiant que le premier indice est une date au format jj/mm/aaaa
        $data = array_filter($shrinkData, static fn($row) => preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $row[0]));

        // Si le marché est ouvert, je supprime la valeur du jour courant du tableau de résultats
        if ($this->isOpened()) {
            $data = $this->deleteFirstIndex($data);
        }

        // Retourne le tableau contenant les seules données pertinentes
        return $this->getFilteredData($data);
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    public function filterData(Crawler $crawler): array
    {
        return $crawler->filter('table > tbody > tr > td')
            ->each(fn ($node) => $node->text('rien à afficher'));
    }

    /**
     * La fonction array_chunk() divise le tableau passé en paramètre avec une taille fixée par le second
     * @param array $data
     * @return array
     */
    public function dataChunk(array $data): array
    {
        return array_chunk($data, 7);
    }

    /**
     * Réduit chacune des lignes d'un tableau à ses 5 premiers indices
     * @param array $data
     * @return array
     */
    public function shrinkData(array $data): array
    {
        return array_map(static fn($chunk) => array_slice($chunk, 0, 5), $data);
    }

    /**
     * @return bool
     */
    public function isOpened(): bool
    {
        return (in_array((int)date('w'), range(1, 5), true)) && date('G') <= '18';
    }

    /**
     * Supprime le premier indice du tableau
     * @param array $data
     * @return array
     */
    public function deleteFirstIndex(array $data): array
    {
        // Si je retournais directement le tableau, seul l'élément supprimé serait récupéré
        array_splice($data, 0, 1);

        return $data;
    }

    /**
     * Filtre le tableau de résultats pour ne récupérer que les données utiles (date, closing, opening, higher, lower)
     * @param array $data
     * @return array
     */
    public function getFilteredData(array $data): array
    {
        return array_map(static fn($chunk) => array_slice($chunk, 0, 5), $data);
    }

    /**
     * @param array $array
     * @param string $stock
     * @return ResponseInterface
     * @throws JsonException
     * @throws TransportExceptionInterface
     */
    public function sendData(array $array, string $stock): ResponseInterface
    {
        $json = $this->serializeData($array, $stock);

        return $this->client->request(
            'POST',
            "{$_ENV['API']}/stocks/{$stock}",
            [
                'json' => $json,
                'headers' => ['Content-Type' => 'application/json']
            ],
        );
    }

    /**
     * @param array $array
     * @param string $stock
     * @return string
     * @throws JsonException
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
     * @param array $data
     * @param string $stock
     * @return array
     */
    public function convertStringToFloat(array $data, string $stock): array
    {
        if ($stock ==='cac') {
            return array_map(static function ($item) {
                return [
                    'createdAt' => $item['createdAt'],
                    'closing' => (float) str_replace(['.', ','], ['', '.'], $item['closing']),
                    'opening' => (float) str_replace(['.', ','], ['', '.'], $item['opening']),
                    'higher' => (float) str_replace(['.', ','], ['', '.'], $item['higher']),
                    'lower' => (float) str_replace(['.', ','], ['', '.'], $item['lower']),
                ];
            }, $data);
        }

        return array_map(static function ($item) {
            return [
                'createdAt' => $item['createdAt'],
                'closing' => (float) $item['closing'],
                'opening' => (float) $item['opening'],
                'higher' => (float) $item['higher'],
                'lower' => (float) $item['lower'],
            ];
        }, $data);
    }
}