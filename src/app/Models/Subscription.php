<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Add this use statement

/**
 * Class Subscription
 *
 * Represents a user's subscription to a specific OLX advertisement.
 * This model establishes the many-to-many relationship between users and OLX ads.
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'olx_ad_id',
    ];

    /**
     * Get the user that owns the subscription.
     *
     * A subscription belongs to a single user.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the OLX ad that the subscription belongs to.
     *
     * A subscription is related to a single OLX advertisement.
     *
     * @return BelongsTo
     */
    public function olxAd(): BelongsTo
    {
        return $this->belongsTo(OlxAd::class);
    }
}
