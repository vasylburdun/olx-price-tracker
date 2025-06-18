<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\OlxAd;

/**
 * Class PriceChangeNotification
 *
 * This notification is dispatched to users when the price of an OLX advertisement
 * they are tracking changes. It is designed to be queued for asynchronous delivery,
 * improving application responsiveness.
 */
class PriceChangeNotification extends Notification implements ShouldQueue // Implement ShouldQueue for async emails
{
    use Queueable;

    /**
     * The OLX ad model instance that has experienced a price change.
     *
     * @var OlxAd
     */
    public $olxAd;

    /**
     * The original price of the OLX ad before the change.
     *
     * @var float
     */
    public $oldPrice;

    /**
     * The currency of the old price (e.g., "UAH", "$").
     *
     * @var string
     */
    public $oldCurrency;

    /**
     * The new price of the OLX ad after the change.
     *
     * @var float
     */
    public $newPrice;

    /**
     * The currency of the new price (e.g., "UAH", "$").
     *
     * @var string
     */
    public $newCurrency;

    /**
     * Create a new notification instance.
     *
     * @param OlxAd $olxAd The OLX ad object.
     * @param float $oldPrice The price before the change.
     * @param string $oldCurrency The currency of the old price.
     * @param float $newPrice The new price after the change.
     * @param string $newCurrency The currency of the new price.
     * @return void
     */
    public function __construct(OlxAd $olxAd, float $oldPrice, string $oldCurrency, float $newPrice, string $newCurrency)
    {
        $this->olxAd = $olxAd;
        $this->oldPrice = $oldPrice;
        $this->oldCurrency = $oldCurrency;
        $this->newPrice = $newPrice;
        $this->newCurrency = $newCurrency;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param object $notifiable The entity that will receive the notification (e.g., a User model).
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // This notification will be sent via email.
    }

    /**
     * Get the mail representation of the notification.
     *
     * Defines the content, subject, and action button for the email notification.
     *
     * @param object $notifiable The entity that will receive the email.
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('OLX Price Change Alert: ' . $this->olxAd->url)
            ->line('The price for the OLX ad you are tracking has changed!')
            ->action('View Ad', $this->olxAd->url) // Button linking to the ad
            ->line('Old Price: ' . number_format($this->oldPrice, 2) . ' ' . $this->oldCurrency)
            ->line('New Price: ' . number_format($this->newPrice, 2) . ' ' . $this->newCurrency)
            ->line('Thank you for using our price tracking service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * This method is used if you want to store the notification in a database or
     * broadcast it to other channels. For mail-only notifications, it might be empty.
     *
     * @param object $notifiable The entity that will receive the notification.
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            // You could include details here if storing in a database notification table
            // 'olx_ad_id' => $this->olxAd->id,
            // 'old_price' => $this->oldPrice,
            // 'new_price' => $this->newPrice,
        ];
    }
}
