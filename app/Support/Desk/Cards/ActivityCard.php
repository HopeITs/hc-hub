<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\Activity;
use App\Models\User;
use App\Notifications\ActivityPresenter;
use App\Notifications\NotificationPresenter;
use App\Notifications\SubjectLinks;
use App\Support\Desk\RelativeDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The activity log in card form: what changed lately, newest first, each line
 * the same sentence the log writes and opening the record it is about.
 */
final class ActivityCard extends PageCard
{
    public function route(): string
    {
        return 'activity.index';
    }

    public function figures(User $user): array
    {
        $today = CarbonImmutable::today();

        return [
            $this->figure(__('desk-workspace.card.figure.today'), Activity::query()->where('created_at', '>=', $today)->count(), 'active'),
            $this->figure(__('desk-workspace.card.figure.this_week'), Activity::query()->where('created_at', '>=', $today->subDays(6))->count()),
            $this->figure(__('desk-workspace.card.figure.people'), Activity::query()->where('created_at', '>=', $today->subDays(6))->whereNotNull('actor_id')->distinct()->count('actor_id')),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $sentences = app(ActivityPresenter::class);
        $modules = app(NotificationPresenter::class);
        $links = app(SubjectLinks::class);

        return Activity::query()->with('actor:id,hr_user_id,employee_id')->latest('created_at')->latest('id')->limit(self::RowLimit)->get()
            ->map(fn (Activity $activity): array => $this->row(
                'ac-'.$activity->id,
                $sentences->sentence($activity),
                collect([$activity->actor?->name, $activity->created_at !== null ? RelativeDay::for($activity->created_at).' · '.$activity->created_at->format('H:i') : null])->filter()->implode(' · '),
                ['label' => $modules->moduleLabel($activity->module), 'tone' => 'neutral'],
                $links->routeFor($activity->subject_type, (int) $activity->subject_id) ?? route($this->route()),
            ));
    }
}
