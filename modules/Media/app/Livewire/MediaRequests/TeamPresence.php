<?php

namespace Modules\Media\Livewire\MediaRequests;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Hr\Models\Employee;
use Modules\Media\Support\MediaTeam;

/**
 * Who of the media team is online now, on a big clock card. "Online" is a request
 * to the app in the last few minutes (the sessions table: SESSION_DRIVER=database);
 * the others show when they were last seen. Read only; it refreshes every minute
 * while it is on screen.
 */
class TeamPresence extends Component
{
    public function render(): View
    {
        $members = app(MediaTeam::class)->members();
        $userIds = $members->pluck('account.id')->filter()->all();

        $seen = config('session.driver') === 'database' && $userIds
            ? DB::table(config('session.table', 'sessions'))->whereIn('user_id', $userIds)
                ->groupBy('user_id')->selectRaw('user_id, max(last_activity) as at')->pluck('at', 'user_id')
            : collect();

        $since = now()->subMinutes((int) config('media-requests.presence.online_minutes', 5))->getTimestamp();
        $locale = app()->getLocale();

        $people = $members->map(function (Employee $e) use ($seen, $since, $locale) {
            $account = $e->getRelationValue('account');
            $at = $account instanceof User ? ($seen[$account->id] ?? null) : null;

            return [
                'id' => $e->id,
                'name' => $e->nameForLocale($locale),
                'online' => $at !== null && (int) $at >= $since,
                'seen' => $at !== null ? Carbon::createFromTimestamp((int) $at)->diffForHumans() : null,
            ];
        })->sortBy(fn ($p) => [$p['online'] ? 0 : 1, $p['name']])->values();

        return view('livewire.media-requests.team-presence', ['people' => $people]);
    }
}
