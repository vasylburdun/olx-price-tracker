<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Class PasswordResetLinkController
 *
 * This controller handles requests for sending a password reset link to a user's email address.
 * It provides methods to display the "forgot password" form and process the request to send the link.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * This method serves the "forgot password" form, where a user can enter their email
     * address to request a password reset link.
     *
     * @return View The "forgot password" view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * This method validates the provided email address and attempts to send a password
     * reset link to that email. It utilizes Laravel's built-in password broker.
     * On success, it redirects back with a status message; on failure, it redirects
     * back with input and an error message.
     *
     * @param Request $request The incoming HTTP request containing the user's email.
     * @return RedirectResponse A redirect response back with a status message (success or error).
     * @throws \Illuminate\Validation\ValidationException If the email validation fails.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the incoming request, ensuring an email address is provided and valid.
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Attempt to send the password reset link using Laravel's Password facade.
        // This will trigger the PasswordReset notification if the user exists.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Based on the status returned by the password broker, redirect the user.
        // If the link was successfully sent, redirect back with a success status message.
        // If there was an error (e.g., user not found), redirect back with input and an error.
        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
