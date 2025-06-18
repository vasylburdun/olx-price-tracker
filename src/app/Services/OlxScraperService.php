<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class OlxScraperService
 *
 * This service is responsible for scraping data from OLX advertisement pages.
 * It primarily aims to extract the ad's price, currency, and title using
 * robust methods, including JSON-LD parsing and HTML DOM crawling.
 */
class OlxScraperService
{
    /**
     * The Guzzle HTTP client instance used for making requests.
     *
     * @var Client
     */
    private Client $client;

    /**
     * The User-Agent string used for HTTP requests to mimic a web browser.
     *
     * @var string
     */
    private string $userAgent;

    /**
     * OlxScraperService constructor.
     *
     * Initializes the Guzzle HTTP client with appropriate headers (including a custom User-Agent),
     * redirect handling, and a timeout.
     */
    public function __construct()
    {
        // Retrieve the user agent from configuration; fallback to a default if not set.
        $this->userAgent = config('app.olx_scraper_user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $this->client = new Client([
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
            'allow_redirects' => true,
            'timeout' => 30, // Timeout for the request in seconds
            /**
             * WARNING: 'verify' => false disables SSL certificate verification.
             * This is generally UNSAFE for production environments as it makes
             * your application vulnerable to Man-in-the-Middle attacks.
             * In production, set this to `true` and configure proper CA certificates.
             */
            'verify' => false,
        ]);
    }

    /**
     * Scrapes an OLX ad page to extract its price, currency, and title.
     *
     * The method first attempts to extract data from JSON-LD script tags,
     * which is typically the most reliable method. If JSON-LD data is not found
     * or is invalid, it falls back to parsing the HTML structure using CSS selectors.
     *
     * @param string $url The URL of the OLX advertisement page to scrape.
     * @return array|null An associative array containing 'price' (float|null),
     * 'currency' (string|null), and 'title' (string|null) if successful.
     * Returns `null` if the price or currency could not be reliably extracted.
     */
    public function scrapePrice(string $url): ?array
    {
        try {
            Log::info("Attempting to scrape URL: " . $url);
            $response = $this->client->get($url);
            $html = (string) $response->getBody();

            $price = null;
            $currency = null;
            $title = null;

            $crawler = new Crawler($html);

            // --- 1. Attempt to find price and title via JSON-LD (most reliable method) ---
            $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$price, &$currency, &$title) {
                if ($price !== null) return; // Stop searching if price is already found from a previous JSON-LD block

                $jsonLdString = $node->text();
                $jsonLd = json_decode($jsonLdString, true);

                if ($jsonLd === null) {
                    Log::debug("Invalid JSON-LD found: " . substr($jsonLdString, 0, 100) . "...");
                    return; // Continue to next script tag or fallback
                }

                // Check for required fields for a Product/Offer schema
                if (isset($jsonLd['@type']) && in_array($jsonLd['@type'], ['Product', 'Offer', 'ItemPage']) &&
                    isset($jsonLd['offers']['price']) && isset($jsonLd['offers']['priceCurrency'])) {

                    $price = (float) $jsonLd['offers']['price'];
                    $currency = $jsonLd['offers']['priceCurrency'];
                    $title = $jsonLd['name'] ?? null; // Ad title from JSON-LD

                    Log::info("Price/Title found in JSON-LD. Price: {$price} {$currency}, Title: {$title}");
                }
            });

            // If price and currency were successfully extracted from JSON-LD, return immediately
            if ($price !== null && $currency !== null) {
                return ['price' => $price, 'currency' => $currency, 'title' => $title];
            }

            Log::info("JSON-LD method failed or incomplete. Attempting to scrape from HTML structure.");

            // --- 2. Fallback: Attempt to find price and title from HTML structure (less reliable, may need updates) ---
            // Selector for price container, verify this selector against current OLX HTML
            $priceNode = $crawler->filter('[data-testid="ad-price-container"] span');
            if ($priceNode->count() > 0) {
                $priceString = $priceNode->first()->text();
                Log::debug("Raw price string from HTML: " . $priceString);

                // Clean the price string: remove spaces, replace comma with dot for float conversion
                $priceStringClean = str_replace([' ', ','], ['', '.'], $priceString);

                // Extract numerical price part using regex
                if (preg_match('/(\d+(\.\d+)?)/', $priceStringClean, $priceMatches)) {
                    $price = (float) $priceMatches[1];
                }

                // Extract currency symbol/code from the end of the string
                if (preg_match('/([^\d\s\.]+)$/u', $priceString, $currencyMatches)) {
                    $currency = trim($currencyMatches[1]);
                }
                Log::info("Price found via CSS selector. Price: {$price} {$currency}");
            } else {
                Log::debug("Price node [data-testid='ad-price-container'] span not found in HTML.");
            }

            // Selector for ad title container, verify this selector against current OLX HTML
            $titleNode = $crawler->filter('[data-testid="ad-title-container"]');
            if ($titleNode->count() > 0) {
                $title = trim($titleNode->first()->text());
                Log::info("Title found via CSS selector: " . $title);
            } else {
                Log::debug("Title node [data-testid='ad-title-container'] not found in HTML.");
            }

            // If price and currency were found via HTML, return them
            if ($price !== null && $currency !== null) {
                return ['price' => $price, 'currency' => $currency, 'title' => $title];
            }

            Log::warning("Could not extract price or currency for URL: " . $url . " after all attempts (JSON-LD and HTML fallback).");
            return null; // Return null if essential data couldn't be extracted
        } catch (RequestException $e) {
            // Log HTTP request-specific errors (e.g., 404, 500, network issues)
            Log::error("HTTP Request failed for URL: " . $url . " - " . $e->getMessage() . " - Response: " . ($e->hasResponse() ? $e->getResponse()->getStatusCode() : 'No Response') . " - Body: " . ($e->hasResponse() ? substr($e->getResponse()->getBody(), 0, 200) : ''));
            return null;
        } catch (\Exception $e) {
            // Catch any other general PHP exceptions during the scraping process
            Log::error("Scraping error for URL: " . $url . " - " . $e->getMessage() . " - Line: " . $e->getLine() . " - File: " . $e->getFile());
            return null;
        }
    }
}
