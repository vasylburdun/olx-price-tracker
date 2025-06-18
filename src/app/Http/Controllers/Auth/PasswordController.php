<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Class PasswordController
 *
 * This controller handles updating an authenticated user's password.
 * It ensures the current password is correct before setting a new one.
 */
class PasswordController extends Controller
{
    /**
     * Update the user's password.
     *
     * This method validates the user's current password and then updates it
     * to the new provided password if validation passes. It redirects back
     * with a status message indicating success or failure.
     *
     * @param Request $request The incoming HTTP request containing the current, new, and confirmed passwords.
     * @return RedirectResponse A redirect response back to the previous page with a status message.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'], // Requires the user's current password to match
            'password' => ['required', Password::defaults(), 'confirmed'], // New password must meet default rules and be confirmed
        ]);

        // Update the user's password with the hashed new password
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Redirect back to the previous page with a success status
        return back()->with('status', 'password-updated');
    }
}
