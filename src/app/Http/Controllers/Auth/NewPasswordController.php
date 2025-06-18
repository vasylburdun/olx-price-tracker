<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Class NewPasswordController
 *
 * This controller handles requests for setting a new password after a user
 * has initiated a password reset process (e.g., via an email link).
 * It displays the new password form and processes the password reset request.
 */
class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     *
     * This method serves the form where a user can enter their new password,
     * typically after clicking a password reset link received via email.
     * The request usually contains a 'token' and 'email' parameter.
     *
     * @param Request $request The incoming HTTP request, containing password reset token and email.
     * @return View The password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * This method validates the provided password reset token, email, and new password.
     * If valid, it resets the user's password in the database and dispatches a
     * `PasswordReset` event. It then redirects the user to the login page on success,
     * or back with errors on failure.
     *
     * @param Request $request The incoming HTTP request containing the reset token, email, and new password.
     * @return RedirectResponse A redirect response to the login page on success, or back with errors on failure.
     * @throws \Illuminate\Validation\ValidationException If the validation rules fail.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Attempt to reset the user's password using Laravel's built-in password broker.
        // The callback function handles updating the user model and dispatching the event.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                // Force fill the password and reset the remember token, then save.
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60), // Generate a new remember token
                ])->save();

                // Dispatch the PasswordReset event
                event(new PasswordReset($user));
            }
        );

        // Based on the reset status, redirect the user appropriately.
        // If successful, redirect to login with a success message.
        // If there's an error, redirect back with the input and an error message.
        return $status == Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
