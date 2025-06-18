<?php

namespace App\Http\Controllers;

use App\Models\OlxAd;
use App\Models\Subscription;
use App\Services\OlxScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Validation\Rule; // This use statement is no longer needed

class SubscriptionController extends Controller
{
    /**
     * The OlxScraperService instance.
     *
     * @var OlxScraperService
     */
    protected OlxScraperService $olxScraperService;

    /**
     * Create a new controller instance.
     *
     * @param OlxScraperService $olxScraperService The OLX scraper service.
     * @return void
     */
    public function __construct(OlxScraperService $olxScraperService)
    {
        $this->olxScraperService = $olxScraperService;
    }

    /**
     * Display a listing of the user's subscriptions.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
    {
        $subscriptions = Auth::user()->subscriptions()->with('olxAd')->get();
        return view('subscriptions.index', compact('subscriptions'));
    }

    /**
     * Store a newly created subscription in storage.
     *
     * This method handles the creation of a new subscription. It validates the OLX URL,
     * scrapes initial data if the ad is new, and then checks for existing subscriptions
     * before creating a new one.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        // 1. Validate only the URL format initially
        $request->validate([
            'url' => [
                'required',
                'url',
                'regex:/^https:\/\/www\.olx\.ua\/d\/.+?(\.html|\/)$/i', // Basic OLX URL validation
            ],
        ], [
            'url.regex' => 'Please provide a valid OLX ad link (e.g., https://www.olx.ua/d/uk/obyavlenie/...).',
        ]);

        $url = $request->input('url');

        // 2. Find or create the OlxAd based on the URL
        $olxAd = OlxAd::firstOrNew(['url' => $url]);

        // If it's a new ad, scrape initial price and title
        if (!$olxAd->exists) {
            $initialPriceData = $this->olxScraperService->scrapePrice($url);
            if ($initialPriceData) {
                $olxAd->current_price = $initialPriceData['price'];
                $olxAd->currency = $initialPriceData['currency'];
                $olxAd->last_checked_at = now();
                $olxAd->title = $initialPriceData['title'] ?? 'Unknown Title'; // Ensure a title is set
            } else {
                return back()->with('error', 'Failed to retrieve initial price or title for the ad. Please check the link.');
            }
        }
        $olxAd->save(); // Save (or update) the OlxAd to ensure it has an ID

        // 3. Explicitly check if a subscription already exists for this user and this specific ad.
        $existingSubscription = Subscription::where('user_id', Auth::id())
            ->where('olx_ad_id', $olxAd->id)
            ->first();

        if ($existingSubscription) {
            // If the subscription already exists, return a validation error for the URL field
            return back()->withErrors(['url' => 'You are already subscribed to this ad.'])->withInput();
        }

        // 4. Create the subscription if validation passes and no existing subscription is found
        Auth::user()->subscriptions()->create(['olx_ad_id' => $olxAd->id]);

        return back()->with('success', 'Subscription successfully added!');
    }

    /**
     * Remove the specified subscription from storage.
     *
     * Ensures that the authenticated user owns the subscription before deleting it.
     *
     * @param  \App\Models\Subscription  $subscription The subscription instance to delete.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Subscription $subscription): \Illuminate\Http\RedirectResponse
    {
        // Ensure the user owns the subscription
        if ($subscription->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.'); // Added a message for 403
        }

        $subscription->delete();

        return back()->with('success', 'Subscription removed successfully.');
    }
}
