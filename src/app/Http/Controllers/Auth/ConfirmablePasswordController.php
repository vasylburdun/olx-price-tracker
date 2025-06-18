<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Class ConfirmablePasswordController
 *
 * This controller handles the "confirm password" functionality, often used
 * to re-authenticate a user before they can access sensitive parts of the application.
 * It provides methods to display the confirmation form and validate the entered password.
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     *
     * Displays the form where the user can re-enter their password to confirm their identity.
     *
     * @return View The confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     *
     * Validates the provided password against the authenticated user's credentials.
     * If validation fails, a `ValidationException` is thrown.
     * On success, it records the confirmation timestamp in the session and
     * redirects the user to their intended destination.
     *
     * @param Request $request The incoming HTTP request containing the password.
     * @return RedirectResponse A redirect response to the intended URL or dashboard on success.
     * @throws ValidationException If the provided password does not match the user's current password.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        // Store the timestamp in the session to indicate password has been confirmed recently.
        $request->session()->put('auth.password_confirmed_at', time());

        // Redirect the user to their intended URL or the default dashboard.
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
