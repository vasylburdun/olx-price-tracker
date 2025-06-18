<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class ProfileUpdateRequest
 *
 * This FormRequest handles the validation rules for updating a user's profile information.
 * It ensures that the provided name and email meet the application's requirements.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * These rules define how the 'name' and 'email' fields submitted for
     * profile updates should be validated.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The 'name' field is required, must be a string, and have a maximum length of 255 characters.
            'name' => ['required', 'string', 'max:255'],
            // The 'email' field is required, must be a string, converted to lowercase,
            // must be a valid email format, have a maximum length of 255 characters,
            // and must be unique in the 'users' table,
            // except for the currently authenticated user's own email.
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
