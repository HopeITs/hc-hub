<?php

namespace Modules\Hr\Livewire\Dashboard;

use App\Livewire\Concerns\HandlesLoadFailure;
use App\Livewire\Concerns\IsDashboardTile;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Hr\Models\News;

/** The past week's news, newest first (it was a query inside the Blade card). */
class NewsCard extends Component
{
    use HandlesLoadFailure;
    use IsDashboardTile;

    public function render(): View
    {
        $today = Carbon::today();

        return view('livewire.dashboard.news-card', [
            'newsItems' => $this->guardedLoad(fn () => News::with(['newsable', 'creator.profile'])
                ->whereDate('event_date', '<', $today)
                ->whereDate('event_date', '>=', $today->copy()->subDays(7))
                ->orderByDesc('event_date')
                ->get(), collect()),
        ]);
    }
}
