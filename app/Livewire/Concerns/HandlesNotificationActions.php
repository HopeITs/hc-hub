<?php

namespace App\Livewire\Concerns;

trait HandlesNotificationActions
{
    public function openNotification(string $id): void
    {
        $notification = auth()->user()
            ->notifications()
            ->find($id);

        abort_unless($notification, 404);

        if ($notification->unread()) {
            $notification->markAsRead();
            $this->dispatch('notification-read');
        }

        $url = $this->safeNotificationUrl($notification->data['url'] ?? null);

        if ($url !== null) {
            // Navigation starts only after the read state is persisted, avoiding
            // the race caused by wire:click and wire:navigate firing together.
            // This application still includes global page scripts that are not
            // idempotent under wire:navigate. A normal internal redirect keeps
            // the atomic read-before-navigation guarantee without a failed DOM
            // swap leaving the user on the previous page.
            $this->redirect($url);
        }
    }

    public function markAsRead(string $id): void
    {
        $updated = auth()->user()
            ->unreadNotifications()
            ->whereKey($id)
            ->update(['read_at' => now()]);

        if ($updated > 0) {
            $this->dispatch('notification-read');
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->dispatch('notification-read');
    }

    /**
     * Convert an application URL into an internal path. Invalid, external, and
     * protocol-relative destinations are never rendered or redirected to.
     */
    public function safeNotificationUrl(mixed $url): ?string
    {
        if (
            ! is_string($url)
            || $url === ''
            || preg_match('/[\x00-\x1F\x7F]/', $url)
        ) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return str_starts_with($url, '//') ? null : $url;
        }

        $parts = parse_url($url);

        if (
            $parts === false
            || ! isset($parts['host'], $parts['scheme'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        ) {
            return null;
        }

        $allowedHosts = array_unique(array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower(request()->getHost()),
        ]));

        if (! in_array(strtolower($parts['host']), $allowedHosts, true)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $path.$query;
    }
}
