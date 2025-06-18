<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Class LoginRequest
 *
 * This FormRequest handles the validation and authentication logic for user login attempts.
 * It includes built-in rate limiting to prevent brute-force attacks.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always true, as authorization for login is handled by the authentication attempt itself.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * This method first checks if the login attempt is rate-limited.
     * If not, it attempts to authenticate the user using the provided email and password.
     * On successful authentication, the rate limiter for this key is cleared.
     * If authentication fails, the rate limiter is hit, and a validation exception is thrown.
     *
     * @throws \Illuminate\Validation\ValidationException If the authentication fails or if the request is rate limited.
     */
    public function authenticate(): void
    {
        // Ensure the login attempt is not currently rate-limited.
        $this->ensureIsNotRateLimited();

        // Attempt to authenticate the user using the 'email' and 'password' fields,
        // respecting the 'remember' checkbox if present.
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // If authentication fails, record a hit on the rate limiter for this throttle key.
            RateLimiter::hit($this->throttleKey());

            // Throw a validation exception with a generic 'auth.failed' message.
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // If authentication is successful, clear any previous rate limiting attempts for this key.
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * This method checks if the current login attempt has exceeded the allowed number
     * of attempts within a given time frame. If it has, it dispatches a `Lockout` event
     * and throws a validation exception informing the user to wait.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        // Check if the current throttle key has exceeded 5 attempts.
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            // If not too many attempts, allow the request to proceed.
            return;
        }

        // If too many attempts, dispatch the Lockout event.
        event(new Lockout($this));

        // Get the number of seconds until the user can try again.
        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Throw a validation exception with a rate limiting error message.
        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * This key uniquely identifies a login attempt for rate limiting purposes,
     * typically based on the email address and the client's IP address.
     *
     * @return string
     */
    public function throttleKey(): string
    {
        // Create a unique key by combining the lowercased, transliterated email and the client's IP address.
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
