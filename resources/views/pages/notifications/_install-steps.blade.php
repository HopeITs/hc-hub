{{-- One platform's install steps (lang/{en,ar}/notifications.php, app.platforms.<key>). --}}
<h3 class="mb-2 text-sm font-bold">{{ __("notifications.app.platforms.{$platform}.title") }}</h3>
<ol class="list-decimal space-y-1.5 ps-5 text-sm text-secondary-medium marker:font-semibold dark:text-ink-muted">
    @foreach (__("notifications.app.platforms.{$platform}.steps") as $step)
        <li>{{ $step }}</li>
    @endforeach
</ol>
