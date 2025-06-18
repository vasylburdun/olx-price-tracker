<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\OlxAd;
use App\Models\User;

/**
 * Class PriceChangedNotification
 *
 * This Mailable represents an email notification sent to a user when the price
 * or title of an OLX ad they are subscribed to has changed.
 *
 * It implements `ShouldQueue`, meaning it can be queued for asynchronous
 * sending if a queue driver (e.g., Redis) is configured and a queue worker is running.
 * If no queue driver is configured or no worker is running, it will be sent synchronously.
 */
class PriceChangedNotification extends Mailable
{
    /**
     * The OLX ad instance whose price or title has changed.
     *
     * @var OlxAd
     */
    public OlxAd $olxAd;

    /**
     * The old price of the OLX ad before the change.
     *
     * @var float
     */
    public float $oldPrice;

    /**
     * The new price of the OLX ad after the change.
     *
     * @var float
     */
    public float $newPrice;

    /**
     * The user who will receive the price change notification.
     *
     * @var User
     */
    public User $user;

    /**
     * Create a new message instance.
     *
     * @param OlxAd $olxAd The OLX ad that experienced a change.
     * @param float $oldPrice The original price of the ad.
     * @param float $newPrice The new price of the ad.
     * @param User $user The user subscribed to the ad.
     * @return void
     */
    public function __construct(OlxAd $olxAd, float $oldPrice, float $newPrice, User $user)
    {
        $this->olxAd = $olxAd;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     *
     * Defines the email's subject line.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Price Change Notification: ' . $this->olxAd->title,
        );
    }

    /**
     * Get the message content definition.
     *
     * Specifies the Markdown view to be used for the email's body
     * and passes the necessary data to that view.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.price_changed_notification',
            with: [
                'url' => $this->olxAd->url,
                'title' => $this->olxAd->title,
                'oldPrice' => $this->oldPrice,
                'newPrice' => $this->newPrice,
                'currency' => $this->olxAd->currency,
                'userName' => $this->user->name,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
