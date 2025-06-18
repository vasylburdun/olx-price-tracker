<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class EmailVerificationPromptController
 *
 * This single-action controller is responsible for displaying the email
 * verification prompt to users whose email address has not yet been verified.
 * If the user's email is already verified, they are redirected to their dashboard.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * This method serves as the main entry point for the controller. It checks if the
     * authenticated user's email is verified. If it is, the user is redirected to the dashboard.
     * Otherwise, the email verification prompt view is displayed.
     *
     * @param Request $request The incoming HTTP request, containing the authenticated user.
     * @return RedirectResponse|View A redirect response if the email is verified,
     * or the email verification view otherwise.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : view('auth.verify-email');
    }
}
