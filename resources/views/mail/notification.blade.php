{{--
    The email copy of notifications (App\Notifications\Delivery\NotificationEmail):
    one notification, or an hourly / daily batch of them. Every line arrives
    already rendered in the reader's language; the mail's locale sets the
    layout direction.
--}}
<x-mail::message :message="$message ?? null" :preheader="$items[0]['body'] ?? ''">
# {{ $heading }}

@if (count($items) === 1)
{{ $items[0]['body'] }}
@else
@foreach ($items as $item)
- **{{ $item['title'] }}:** {{ $item['body'] }}
@endforeach
@endif

<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>

{{ $footer }}
</x-mail::message>
