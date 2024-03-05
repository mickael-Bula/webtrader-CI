<?php

namespace App\Service;

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
        } catch (ClientExceptionInterface|TransportExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
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

        // on trie $shrinkData en vérifiant que le premier indice est une date au format jj/mm/aaaa
        return array_filter($shrinkData, static fn($row) => preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $row[0]));
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
     * @param $data
     * @return array
     */
    public function dataChunk($data): array
    {
        return array_chunk($data, 7);
    }

    /**
     * Réduit chacune des lignes d'un tableau à ses 5 premiers indices
     * @param $data
     * @return array
     */
    public function shrinkData($data): array
    {
        return array_map(static fn($chunk) => array_slice($chunk, 0, 5), $data);
    }
}