<?php

namespace App\Livewire\Notifications;

use App\Livewire\Concerns\WithFeedback;
use App\Notifications\Inbox;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The signed-in user's own notification settings: which modules' general
 * notifications (announcements, summaries) to mute. Notifications addressed
 * to the user personally always arrive, so only `mutable` modules are listed.
 * Every action is scoped to auth(), so there is no permission to check.
 */
class Settings extends Component
{
    use WithFeedback;

    public function toggleMute(string $module): void
    {
        if (! array_key_exists($module, $this->mutableModules())) {
            return;
        }

        $inbox = app(Inbox::class);
        $muted = ! in_array($module, $inbox->mutedModules(auth()->user()), true);
        $inbox->setMuted(auth()->user(), $module, $muted);

        $this->toast(__($muted ? 'notifications.settings.muted' : 'notifications.settings.unmuted'), 'success');
    }

    public function render(): View
    {
        return view('livewire.notifications.settings', [
            'modules' => $this->mutableModules(),
            'muted' => app(Inbox::class)->mutedModules(auth()->user()),
        ]);
    }

    /**
     * @return array<string, array{icon: string, label: string, mutable: bool}>
     */
    private function mutableModules(): array
    {
        return array_filter(
            (array) config('notifications.inbox_modules'),
            fn (array $module): bool => (bool) ($module['mutable'] ?? false),
        );
    }
}
