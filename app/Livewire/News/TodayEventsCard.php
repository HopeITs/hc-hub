<?php

namespace App\Livewire\News;

use Livewire\Component;
use App\Models\Employee;
use App\Models\News;
use Carbon\Carbon;

class TodayEventsCard extends Component
{
    public array $events = [];

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function loadEvents(): void
    {
        $today = Carbon::today();

        // ------------------------------
        // Collect Birthdays
        // ------------------------------
        $birthdays = Employee::whereNotNull('actual_dob')
            ->get()
            ->filter(fn($e) => optional($e->actual_dob)->format('m-d') === $today->format('m-d'))
            ->map(fn($employee) => [
                'type'       => 'birthday',
                'name'       => $employee->name,
                'position'   => $employee->latestActiveContract()?->position?->title ?? '',
                'department' => $employee->latestActiveContract()?->position?->department?->name ?? '',
                'message'    => 'Happy Birthday! 🎉',
                'photo'      => $employee->getFirstMediaUrl('avatar')
                                ?: 'https://ui-avatars.com/api/?name=' . urlencode($employee->name) . '&color=be1f24&background=FFF',
            ]);

        // ------------------------------
        // Collect Event News
        // ------------------------------
        $newsEvents = News::whereDate('event_date', $today)
            ->latest()
            ->get()
            ->map(fn($news) => [
                'type'       => 'event',
                'name'       => $news->title,
                'position'   => $news->newsable_name ?? '',
                'department' => '',
                'message'    => strip_tags($news->description ?? 'Event Today'),
                'photo'      => $news->photo ? asset('storage/' . $news->photo) : '/assets/icons/event.svg',
            ]);

        // ------------------------------
        // Merge All Events
        // ------------------------------
        $this->events = $birthdays->concat($newsEvents)->toArray();
    }

    public function render()
    {
        return view('components.dashboard-cards.today-events-card');
    }
}
