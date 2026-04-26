<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class PriceParserService
{
    public function getPrice(string $url): float
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            ])->timeout(10)->get($url);

            if ($response->failed()) return 0;

            $html = $response->body();

            if (preg_match('/property="product:price:amount" content="(\d+\.?\d*)"/', $html, $matches)) {
                return (float) $matches[1];
            }

            $crawler = new Crawler($html);

            $jsonLd = $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) {
                return json_decode($node->text(), true);
            });

            foreach ($jsonLd as $data) {
                if (isset($data['offers']['price'])) {
                    $price = (float) $data['offers']['price'];
                    return $price;
                }
            }

            Log::warning("Parser: Price not found for {$url}");
            return 0;

        } catch (\Exception $e) {
            Log::error("Parser Error: " . $e->getMessage());
            return 0;
        } finally {
            unset($crawler, $html);
            gc_collect_cycles();
        }
    }
}
