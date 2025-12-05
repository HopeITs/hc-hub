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

        return view('livewire.dashboards.calendar-card', [
            'calendar' => $calendar,
        ]);
    }


    private function formatDay(Carbon $date, bool $isCurrentMonth)
    {

        $fakeHolidays = [
            '2025-12-26' => 'Boxing Day',
            '2025-12-29' => 'National Day',
        ];

        $fakeSaints = [
            '2025-12-15' => 'St. Verena Day',
            '2025-12-20' => 'St. Ignatius Feast',
        ];

        $fakeEvents = [
            '2025-12-04' => ['Doctor Appointment'],
            '2025-12-11' => ['Workshop'],
            '2025-12-24' => ['Family Dinner'],
            '2025-12-31' => ['New Year Event'],
        ];

        $fakeHolidays['2025-12-30'] = 'End of Year Cleaning Day';
        $fakeEvents['2025-12-30'] = ['Office Celebration'];

        $fakeHolidays['2025-12-28'] = 'Remembrance Day';
        $fakeSaints['2025-12-28'] = 'St. Marcus Feast';

        $fakeSaints['2025-12-17'] = 'St. Helena Feast';
        $fakeEvents['2025-12-17'] = ['Community Gathering'];

        $fakeHolidays['2025-12-22'] = 'Unity Day';
        $fakeSaints['2025-12-22'] = 'St. Clement';
        $fakeEvents['2025-12-22'] = ['Charity Event'];

        $fakeHolidays['2025-12-25'] = 'Christmas Day';
        $fakeSaints['2025-12-25'] = 'St. Example Patron';
        $fakeEvents['2025-12-25'] = ['Gift Exchange'];

        $iso = $date->toDateString();

        $isHoliday = isset($fakeHolidays[$iso]);
        $isSaint   = isset($fakeSaints[$iso]);
        $hasEvent  = isset($fakeEvents[$iso]);
        $isToday   = $date->isToday();

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

            if ($isSaint && !$isHoliday) {
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
            }

            elseif ($isSaint) {
                $numberColor = 'text-secondary-dark';
                $fontWeight = 'font-extrabold';

                if ($hasEvent) {
                    $underline = true;
                }
            }

            elseif ($hasEvent) {
                $underline = true;
            }
        }

        $tooltip = [];

        if ($isHoliday) $tooltip[] = $fakeHolidays[$iso];
        if ($isSaint)   $tooltip[] = $fakeSaints[$iso];
        if ($hasEvent)  foreach ($fakeEvents[$iso] as $e) $tooltip[] = $e;


        return [
            'date'           => $date,
            'day'            => $date->day,
            'isToday'        => $isToday,

            'numberColor'    => $textColorOverride ?? $numberColor,
            'fontWeight'     => $fontWeight,

            'circle'         => false,
            'circleFill'     => $circleFill,

            'underline'      => $underline,
            'underlineColor' => $underlineColor,

            'tooltip'        => $tooltip,
        ];
    }

}
