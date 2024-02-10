<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;


class DataScraper
{
    /**
     * @param $url
     * @return Crawler
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public static function getCrawler($url): Crawler
    {
        $client = HttpClient::create();
        $response = $client->request('GET', $url);

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
            $crawler = self::getCrawler($url);

            return $crawler
                ->filter('table > tbody > tr > td')
                ->each(fn ($node) => $node->text('rien à afficher'));
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return sprintf('Erreur lors de la création du crawler : %s', $e->getMessage());
        }
    }
}