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

            return $crawler
                ->filter('table > tbody > tr > td')
                ->each(fn ($node) => $node->text('rien à afficher'));
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return sprintf('Erreur lors de la création du crawler : %s', $e->getMessage());
        }
    }
}