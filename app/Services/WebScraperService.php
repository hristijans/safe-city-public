<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class WebScraperService
{
    public function fetchHtml(string $url, int $timeout = 30, int $retries = 3): string
    {
        $response = Http::timeout($timeout)
            ->retry($retries, 1000)
            ->get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch URL: {$url}, HTTP status: ".$response->status());
        }

        return $response->body();
    }

    public function extractText(string $html, string $selector): string
    {
        $crawler = new Crawler($html);
        $content = $crawler->filter($selector)->first();

        if ($content->count() === 0) {
            throw new \Exception("Could not find element with selector: {$selector}");
        }

        return $content->text();
    }

    public function extractLinks(string $html, callable $filter): Collection
    {
        $crawler = new Crawler($html);
        $links = collect();

        $crawler->filter('a')->each(function (Crawler $node) use ($filter, $links) {
            if ($filter($node)) {
                $links->push($node->attr('href'));
            }
        });

        return $links;
    }

    public function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return rtrim($baseUrl, '/').$url;
        }

        return rtrim($baseUrl, '/').'/'.$url;
    }
}
