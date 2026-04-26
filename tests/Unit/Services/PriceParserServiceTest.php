<?php

namespace tests\Unit\Services;

use App\Services\PriceParserService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PriceParserServiceTest extends TestCase
{
    private PriceParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PriceParserService();
    }

    public function test_it_can_extract_price_via_regex_from_meta_tags()
    {
        $html = '<meta property="product:price:amount" content="1250.75">';
        Http::fake(['*' => Http::response($html, 200)]);

        $price = $this->service->getPrice('https://olx.ua/test-ad');

        $this->assertEquals(1250.75, $price);
    }

    public function test_it_can_extract_price_via_json_ld_as_fallback()
    {
        $html = '<html><script type="application/ld+json">
            {"offers": {"price": "3000.00", "priceCurrency": "UAH"}}
        </script></html>';
        Http::fake(['*' => Http::response($html, 200)]);

        $price = $this->service->getPrice('https://olx.ua/test-ad');

        $this->assertEquals(3000.0, $price);
    }

    public function test_it_returns_zero_if_page_loading_failed()
    {
        Http::fake(['*' => Http::response(null, 404)]);

        $price = $this->service->getPrice('https://olx.ua/invalid-url');

        $this->assertEquals(0, $price);
    }

    public function test_it_returns_zero_and_logs_warning_if_price_not_found_in_html()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with("Parser: Price not found for https://olx.ua/no-price");

        Http::fake([
            '*' => Http::response('<html><body>No price info here</body></html>', 200)
        ]);

        $price = $this->service->getPrice('https://olx.ua/no-price');

        $this->assertEquals(0, $price);
    }

    public function test_it_returns_zero_and_logs_error_on_exception()
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn($message) => str_contains($message, 'Parser Error: Connection failed'));

        Http::fake([
            '*' => function () {
                throw new \Exception("Connection failed");
            }
        ]);

        $price = $this->service->getPrice('https://olx.ua/error-page');

        $this->assertEquals(0, $price);
    }
}
