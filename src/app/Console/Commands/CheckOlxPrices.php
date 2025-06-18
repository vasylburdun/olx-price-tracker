<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OlxAd;
use App\Models\Subscription;
use App\Services\OlxScraperService;
use App\Mail\PriceChangedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Class CheckOlxPrices
 *
 * This Artisan command checks OLX ad prices and notifies users about any changes.
 * It iterates through subscribed OLX ads, scrapes their current data,
 * and dispatches email notifications if prices or titles have changed.
 */
class CheckOlxPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'olx:check-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check OLX ad prices and notify users about changes.';

    /**
     * The OLX scraper service instance.
     *
     * @var OlxScraperService
     */
    protected OlxScraperService $olxScraperService;

    /**
     * Create a new command instance.
     *
     * @param OlxScraperService $olxScraperService The service to scrape OLX data.
     * @return void
     */
    public function __construct(OlxScraperService $olxScraperService)
    {
        parent::__construct();
        $this->olxScraperService = $olxScraperService;
    }

    /**
     * Execute the console command.
     *
     * This method orchestrates the price checking process:
     * 1. Fetches all OLX ads with active subscriptions.
     * 2. Iterates through each ad to scrape new price and title data.
     * 3. Compares new data with old; if changed, updates the ad in the database.
     * 4. Dispatches email notifications to subscribed users if a change is detected.
     * 5. Logs the process and any encountered issues.
     *
     * @return int Command::SUCCESS or Command::FAILURE
     */
    public function handle(): int
    {
        Log::info('OLX price check started.');
        // Retrieve all unique ads that have active subscriptions
        $olxAds = OlxAd::has('subscriptions')->get();

        if ($olxAds->isEmpty()) {
            Log::info('No OLX ads with active subscriptions found. Skipping price check.');
            return Command::SUCCESS;
        }

        foreach ($olxAds as $olxAd) {
            $oldPrice = $olxAd->current_price;
            $oldTitle = $olxAd->title; // Store old title for logging and comparison

            // Scrape the new price and title for the ad
//            $scrapedData = $this->olxScraperService->scrapePrice($olxAd->url);
//
//            if ($scrapedData && $scrapedData['price'] !== null) {
                $newPrice = 123;
                // Use old title if new one isn't scraped, to prevent accidental overwrites
                $newTitle = $oldTitle;

                // Check if price or title has changed
                // (Commented out for continuous email sending as per user request)
                // if ($newPrice !== $oldPrice || $newTitle !== $oldTitle) {
                $olxAd->current_price = $newPrice;
                $olxAd->currency = 'UAH';
                $olxAd->title = $newTitle;
                $olxAd->last_checked_at = now();
                $olxAd->save();

//                Log::info("Ad data changed for: {$olxAd->url}. Old price: {$oldPrice}, New price: {$newPrice}. Old title: '{$oldTitle}', New title: '{$newTitle}'");

                // Notify all users subscribed to this ad
                foreach ($olxAd->subscriptions as $subscription) {
                    $user = $subscription->user;

                    if ($user) {
                        try {
                            // Sending emails synchronously as queues are currently not in use.
                            // If queues are enabled, Laravel will automatically queue this Mailable
                            // because PriceChangedNotification implements ShouldQueue.
                            Mail::to('kk@gmail.com')->send(
                                (new \Illuminate\Mail\Mailable()) // Використовуємо базовий клас Mailable
                                ->subject('Test Command Email')
                                    ->html('<h1>Hello from Command '.$user->name.'!</h1><p>This is a test email sent from the Artisan command.</p>')
                                    ->from('sender@example.com', 'Laravel Test')
                            );
                            Mail::to($user->email)->send(new PriceChangedNotification($olxAd, $oldPrice, $newPrice, $user));
                            Log::info("Sent price change notification to {$user->email} for ad: {$olxAd->url}");
                        } catch (\Exception $e) {
                            Log::error("Failed to send email to {$user->email} for ad {$olxAd->url}: " . $e->getMessage());
                        }
                    }
                }
                // } else {
                //     // If price and title are unchanged, just update the last checked timestamp
                //     $olxAd->last_checked_at = now();
                //     $olxAd->save();
                //     Log::info("Ad data unchanged for: {$olxAd->url}. Current price: {$newPrice}");
                // }
//            } else {
//                Log::warning("Failed to scrape price or data for ad: {$olxAd->url}. Skipping update.");
//            }
        }

        Log::info('OLX price check finished.');
        return Command::SUCCESS;
    }
}
