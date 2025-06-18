<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Class RegisteredUserController
 *
 * This controller handles the registration of new users. It provides methods
 * for displaying the registration form and processing new user registration requests.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * This method serves the registration form page to allow new users to sign up.
     *
     * @return View The registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * This method validates the registration data, creates a new user record in the database,
     * dispatches a `Registered` event, logs the new user in, and then redirects them to the dashboard.
     *
     * @param Request $request The incoming HTTP request containing the registration data.
     * @return RedirectResponse A redirect response to the dashboard after successful registration.
     * @throws \Illuminate\Validation\ValidationException If the validation rules fail.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the incoming registration data based on defined rules.
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class], // Email must be unique in the 'users' table
            'password' => ['required', 'confirmed', Rules\Password::defaults()], // Password must be confirmed and meet default rules
        ]);

        // Create a new user record in the database with the provided data.
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password before storing
        ]);

        // Dispatch the 'Registered' event. This typically triggers email verification.
        event(new Registered($user));

        // Log the newly registered user into the application.
        Auth::login($user);

        // Redirect the user to the dashboard after successful registration.
        return redirect(route('dashboard', absolute: false));
    }
}
