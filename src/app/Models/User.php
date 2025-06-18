<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany; // Add this use statement

/**
 * Class User
 *
 * Represents a user of the application. This model extends Laravel's
 * `Authenticatable` base class, providing core authentication functionalities.
 * It also includes traits for factory generation and notifications.
 */
class User extends Authenticatable
{
    /**
     * Use traits for factory generation and notifications.
     *
     * @use HasFactory<\Database\Factories\UserFactory>
     * @use Notifiable
     */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * These attributes can be set via mass assignment (e.g., during creation or update).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * These attributes will not be included when the model is converted to an array or JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * This method defines how certain attributes are cast to native PHP types
     * when they are retrieved from the database.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the subscriptions associated with the user.
     *
     * A user can have many subscriptions to various OLX ads.
     *
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
