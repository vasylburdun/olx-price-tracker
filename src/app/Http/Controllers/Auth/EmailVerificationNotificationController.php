<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class EmailVerificationNotificationController
 *
 * This controller handles sending new email verification notifications to users.
 * It's typically used when a user needs to re-request a verification link.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * If the user's email is already verified, they are redirected to the dashboard.
     * Otherwise, a new email verification notification is dispatched to the user,
     * and the user is redirected back with a status message.
     *
     * @param Request $request The incoming HTTP request, containing the authenticated user.
     * @return RedirectResponse A redirect response to the dashboard if already verified,
     * or back with a status message if the link was sent.
     */
    public function store(Request $request): RedirectResponse
    {
        // If the user's email is already verified, redirect them to the dashboard.
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Send a new email verification notification to the user.
        $request->user()->sendEmailVerificationNotification();

        // Redirect back with a success status message.
        return back()->with('status', 'verification-link-sent');
    }
}
