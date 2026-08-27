<?php

namespace App\Livewire\Dashboard;

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
        $startOfMonth = $this->current->clone()->startOfMonth();
        $startWeekday = $startOfMonth->dayOfWeekIso;
        $daysInMonth = $this->current->daysInMonth;

        $totalCells = 42;
        $prevDays = $startWeekday - 1;

        $calendar = collect();

        $prevMonth = $this->current->clone()->subMonth();
        $prevMonthDays = $prevMonth->daysInMonth;

        for ($i = $prevDays; $i > 0; $i--) {
            $date = $prevMonth->clone()->day($prevMonthDays - $i + 1);
            $calendar->push($this->formatDay($date, false));
        }

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $this->current->clone()->day($d);
            $calendar->push($this->formatDay($date, true));
        }

        $remaining = $totalCells - $calendar->count();
        $nextMonth = $this->current->clone()->addMonth();

        for ($i = 1; $i <= $remaining; $i++) {
            $date = $nextMonth->clone()->day($i);
            $calendar->push($this->formatDay($date, false));
        }

        return view('livewire.dashboard.calendar-card', [
            'calendar' => $calendar,
        ]);
    }

    private function formatDay(Carbon $date, bool $isCurrentMonth)
    {
        $fakeHolidays = config('samples.calendar.holidays', []);
        $fakeEvents = config('samples.calendar.events', []);
        $fakeSaints = config('samples.calendar.saints', []);

        $iso = $date->toDateString();

        $isHoliday = array_key_exists($iso, $fakeHolidays);
        $isSaint = array_key_exists($iso, $fakeSaints);
        $hasEvent = array_key_exists($iso, $fakeEvents);
        $isToday = $date->isToday();

        $numberColor = $isCurrentMonth ? 'text-secondary-light' : 'text-secondary-soft';
        $fontWeight = 'font-normal';
        $underline = false;
        $underlineColor = 'border-secondary-light';

        $circleFill = null;
        $circle = false;
        $textColorOverride = null;

        if ($isToday) {

            $circleFill = 'bg-secondary-medium';
            $textColorOverride = 'text-white';

            if ($isSaint && ! $isHoliday) {
                $circleFill = 'bg-secondary-light';
                $fontWeight = 'font-extrabold';
            }

            if ($isHoliday) {
                $circleFill = 'bg-primary';
                $fontWeight = 'font-extrabold';
            }

            if ($hasEvent) {
                $underline = true;
                $underlineColor = 'border-white';
            }

        } else {

            if ($isHoliday) {
                $numberColor = 'text-primary';
                $fontWeight = 'font-extrabold';

                if ($hasEvent) {
                    $underline = true;
                }
            } elseif ($isSaint) {
                $numberColor = 'text-secondary-dark';
                $fontWeight = 'font-extrabold';

                if ($hasEvent) {
                    $underline = true;
                }
            } elseif ($hasEvent) {
                $underline = true;
            }
        }

        $tooltip = [];

        if ($isHoliday) {
            foreach ($fakeHolidays[$iso] as $h) {
                $tooltip[] = $h;
            }
        }

        if ($isSaint) {
            foreach ($fakeSaints[$iso] as $s) {
                $tooltip[] = $s;
            }
        }

        if ($hasEvent) {
            $events = is_array($fakeEvents[$iso]) ? $fakeEvents[$iso] : [$fakeEvents[$iso]];
            foreach ($events as $e) {
                $tooltip[] = $e;
            }
        }

        return [
            'date' => $date,
            'day' => $date->day,
            'isToday' => $isToday,

            'numberColor' => $textColorOverride ?? $numberColor,
            'fontWeight' => $fontWeight,

            'circle' => false,
            'circleFill' => $circleFill,

            'underline' => $underline,
            'underlineColor' => $underlineColor,

            'tooltip' => $tooltip,
        ];
    }
}
