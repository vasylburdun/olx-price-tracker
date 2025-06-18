@component('mail::message')
# Price Change Notification

Hello, {{ $userName }}!

We're notifying you that the price for the OLX ad
**{{ $title }}**
has changed.

- **Old Price:** {{ number_format($oldPrice, 0, '.', ' ') }} {{ $currency }}
- **New Price:** {{ number_format($newPrice, 0, '.', ' ') }} {{ $currency }}

@component('mail::button', ['url' => $url])
    View Ad
@endcomponent

Thank you for using our service!
<br><br>
Regards,
The OLX Price Tracker Team
@endcomponent
