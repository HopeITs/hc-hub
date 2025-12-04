<?php

namespace App\Livewire\Dashboards;

use Carbon\Carbon;
use Livewire\Component;

class CalendarCard extends Component
{
    public Carbon $current;

    public function mount()
    {
        $this->current = Carbon::now();
    }

    public function next()
    {
        $this->current = $this->current->clone()->addMonth();
    }

    public function prev()
    {
        $this->current = $this->current->clone()->subMonth();
    }

    public function render()
    {
        $start = $this->current->clone()->startOfMonth();
        $end = $this->current->clone()->endOfMonth();

        // Build days array including empty slots before the first day
        $days = collect();
        $weekdayIndex = $start->dayOfWeekIso; // 1=Mon, 7=Sun

        // Add empty placeholders before first day
        for ($i = 1; $i < $weekdayIndex; $i++) {
            $days->push(null);
        }

        // Add actual days
        $cursor = $start->clone();
        while ($cursor->lte($end)) {
            $days->push($cursor->clone());
            $cursor->addDay();
        }

        return view('livewire.dashboards.calendar-card', [
            'days' => $days,
        ]);
    }
}
