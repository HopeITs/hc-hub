<?php

namespace Modules\Hr\Livewire\Dashboard;

use App\Livewire\Concerns\HandlesLoadFailure;
use App\Livewire\Concerns\IsDashboardTile;
use App\Models\Center;
use App\Models\City;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\News;

/**
 * Events of the next seven days meant for the viewer: for everyone, or for
 * their own record, city, center or program (it was a section of
 * Dashboard\Index).
 */
class UpcomingEventsCard extends Component
{
    use HandlesLoadFailure;
    use IsDashboardTile;

    public function render(): View
    {
        return view('livewire.dashboard.upcoming-events-card', [
            'upcomingEvents' => $this->guardedLoad(fn () => $this->loadUpcomingEvents(), collect()),
        ]);
    }

    /** @return Collection<int, News> */
    protected function loadUpcomingEvents(): Collection
    {
        [$employee, $contract] = $this->tileViewer();

        $start = now()->timezone(config('app.timezone'))->addDay()->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $employeeId = $employee->id ?? null;
        $cityId = $employee->city_id ?? null;
        $centerId = $contract->position->center_id ?? null;
        $programId = $contract->position->program_id ?? null;

        return News::query()
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($cityId, $centerId, $programId, $employeeId) {
                $q->whereNull('newsable_type')
                    ->orWhere(function ($q2) use ($employeeId) {
                        $q2->where('newsable_type', (new Employee)->getMorphClass())->where('newsable_id', $employeeId);
                    })
                    ->orWhere(function ($q2) use ($cityId) {
                        $q2->where('newsable_type', (new City)->getMorphClass())->where('newsable_id', $cityId);
                    })
                    ->orWhere(function ($q2) use ($centerId) {
                        $q2->where('newsable_type', (new Center)->getMorphClass())->where('newsable_id', $centerId);
                    })
                    ->orWhere(function ($q2) use ($programId) {
                        $q2->where('newsable_type', (new Program)->getMorphClass())->where('newsable_id', $programId);
                    });
            })
            ->where(function ($q) {
                $q->whereNull('display_until')->orWhere('display_until', '>=', now()->startOfDay());
            })
            ->get()
            ->map(function (News $news): News {
                $timestamp = $news->event_date;
                $daysLeft = now()->diffInDays($timestamp, false);

                $news->setAttribute('event_title', $news->title);
                $news->setAttribute('event_description', $news->description);
                $news->setAttribute('event_timestamp', $timestamp);
                $news->setAttribute('image_url', $news->getFirstMediaUrl('news-image') ?: '/images/news-default.png');
                $news->setAttribute('daysLeftText', trans_choice('dashboard.in-days', (int) $daysLeft));

                return $news;
            })
            ->sortBy('event_timestamp')
            ->values();
    }
}
