<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Class ProfileController
 *
 * This controller manages a user's profile, including displaying their profile
 * information, updating it, and handling account deletion.
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile editing form.
     *
     * @param Request $request The incoming HTTP request, containing the authenticated user.
     * @return View The profile editing view, pre-populated with user data.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * Validates the incoming request and updates the authenticated user's profile details.
     * If the email address is changed, it resets the `email_verified_at` timestamp.
     *
     * @param ProfileUpdateRequest $request The validated request containing profile data.
     * @return RedirectResponse A redirect back to the profile editing page with a status message.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     *
     * Validates the user's password to confirm deletion, logs the user out,
     * deletes the user record from the database, invalidates the session,
     * regenerates the CSRF token, and redirects to the homepage.
     *
     * @param Request $request The incoming HTTP request, containing the authenticated user and password.
     * @return RedirectResponse A redirect response to the homepage after account deletion.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
