<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Add this use statement
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Add this use statement

/**
 * Class OlxAd
 *
 * Represents an OLX advertisement in the database. This model stores
 * information about the ad, its current price, and tracks when it was last checked.
 * It also defines relationships to subscriptions and users.
 */
class OlxAd extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'current_price',
        'currency',
        'last_checked_at',
        'title',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_checked_at' => 'datetime',
        'current_price' => 'float',
    ];

    /**
     * Get the subscriptions associated with the OLX ad.
     *
     * An OLX ad can have many subscriptions.
     *
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the users that are subscribed to this OLX ad.
     *
     * An OLX ad can be related to many users through its subscriptions.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'olx_ad_id', 'user_id');
    }
}
