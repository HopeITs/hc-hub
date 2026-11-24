<?php

/*
| Web Push (App\Notifications\Delivery\WebPushSender). The VAPID pair comes
| only from .env (`php artisan webpush:vapid` prints one); without it push is
| off and nothing else changes. Replacing the pair disconnects every device
| subscribed with the old public key.
*/

return [

    'vapid' => [
        'subject' => env('VAPID_SUBJECT') ?: env('APP_URL'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    // How long (seconds) a push service keeps a message for an offline device.
    'ttl' => 86400,

    // Failed sends in a row before a device's subscription is dropped.
    'max_failures' => 5,

    // A device not seen for this many days is dropped by push:prune.
    'stale_after_days' => 120,

];
