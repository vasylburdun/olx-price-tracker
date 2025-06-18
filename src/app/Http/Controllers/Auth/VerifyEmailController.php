<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Class VerifyEmailController
 *
 * This single-action controller handles the process of marking an authenticated
 * user's email address as verified. It's typically invoked when a user clicks
 * on the verification link sent to their email.
 */
class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * This method is the primary entry point for the controller. It's called
     * when a user clicks on an email verification link.
     *
     * 1. It checks if the user's email is already verified; if so, it redirects
     * them to the dashboard with a 'verified' status.
     * 2. If not already verified, it attempts to mark the email as verified.
     * 3. Upon successful verification, it dispatches a `Verified` event.
     * 4. Finally, it redirects the user to the dashboard with a 'verified' status.
     *
     * @param EmailVerificationRequest $request The incoming request, which authenticates the user
     * and validates the verification link's signature.
     * @return RedirectResponse A redirect response to the dashboard with a 'verified' status.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // If the user's email is already verified, redirect them to the dashboard.
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // Attempt to mark the user's email as verified.
        // This updates the 'email_verified_at' timestamp in the database.
        if ($request->user()->markEmailAsVerified()) {
            // If the email was successfully marked as verified, dispatch the Verified event.
            // This event can trigger listeners, e.g., to send welcome emails or update user roles.
            event(new Verified($request->user()));
        }

        // Redirect the user to the dashboard with a 'verified=1' query parameter,
        // which can be used to display a success message on the frontend.
        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
