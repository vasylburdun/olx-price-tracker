<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Class AuthenticatedSessionController
 *
 * This controller handles user authentication related tasks, including displaying
 * the login form, processing login attempts, and logging users out.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * Serves the login form page to the user.
     *
     * @return View The login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Processes the user's login attempt. If authentication is successful,
     * the session is regenerated, and the user is redirected to their
     * intended destination or the dashboard.
     *
     * @param LoginRequest $request The validated login request.
     * @return RedirectResponse A redirect response after successful login.
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     *
     * Logs the authenticated user out, invalidates their session,
     * regenerates the CSRF token, and redirects them to the homepage.
     *
     * @param Request $request The incoming HTTP request.
     * @return RedirectResponse A redirect response after logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
