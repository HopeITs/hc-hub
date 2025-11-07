<?php

namespace App\Livewire\Dashboards;

use Carbon\Carbon;
use App\Models\{Badge, Checklist, Employee, Contract, News, City, Center, Program};
use Livewire\Component;
use Illuminate\Support\Facades\Storage;

class EmployeeDashboard extends Component
{
    public $employee;
    public $contract;
    public $positionTitle;
    public $departmentName;
    public $joinedOn;
    public $workEmail;

    public function getTodayEventsProperty()
    {
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $todayDate = $today->toDateString();
        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol = $locale === 'ar' ? 'name_ar' : 'name_en';
        $employeeId = $this->employee->id ?? null;
        $cityId = $this->employee->city_id ?? null;
        $centerId = $this->contract->position->center_id ?? null;
        $programId = $this->contract->position->program_id ?? null;

        $birthdays = Employee::query()
            ->whereNotNull('dob')
            ->whereRaw('DAY(dob) = ?', [$today->day])
            ->whereRaw('MONTH(dob) = ?', [$today->month])
            ->with(['media', 'contracts.position.department'])
            ->get()
            ->map(function ($emp) use ($titleCol, $deptCol, $today) {
                $contract = Contract::with(['position.department'])
                    ->where('employee_id', $emp->id)
                    ->orderByDesc('start_date')
                    ->first();

                $emp->positionTitle = $contract?->position?->$titleCol ?? null;
                $emp->departmentName = $contract?->position?->department?->$deptCol ?? null;
                $emp->event_type = 'birthday';
                $emp->event_title = 'Happy Birthday!';
                $emp->event_description = trim(($emp->positionTitle ? $emp->positionTitle . ' · ' : '') . ($emp->departmentName ?? ''));
                $emp->event_timestamp = $today;

                return $emp;
            });

        $newsItems = News::query()
            ->where(function ($q) use ($todayDate) {
                $q->whereDate('event_date', $todayDate)->orWhereDate('published_at', $todayDate);
            })
            ->where(function ($q) use ($cityId, $centerId, $programId, $employeeId) {
                $q->whereNull('newsable_type')
                    ->orWhere(function ($q2) use ($employeeId) {
                        $q2->where('newsable_type', Employee::class)->where('newsable_id', $employeeId);
                    })
                    ->orWhere(function ($q2) use ($cityId) {
                        $q2->where('newsable_type', City::class)->where('newsable_id', $cityId);
                    })
                    ->orWhere(function ($q2) use ($centerId) {
                        $q2->where('newsable_type', Center::class)->where('newsable_id', $centerId);
                    })
                    ->orWhere(function ($q2) use ($programId) {
                        $q2->where('newsable_type', Program::class)->where('newsable_id', $programId);
                    });
            })
            ->where(function ($q) {
                $q->whereNull('display_until')->orWhere('display_until', '>=', now()->startOfDay());
            })
            ->with('media')
            ->get()
            ->map(function ($news) {
                $timestamp = $news->event_date ?? $news->published_at;
                if (!$timestamp instanceof Carbon) {
                    $timestamp = Carbon::parse($timestamp);
                }

                $news->event_type = 'news';
                $news->event_title = $news->title;
                $news->event_description = $news->description;
                $news->event_timestamp = $timestamp;
                $news->image_url = $news->photo ? Storage::url($news->photo) : '/images/news-default.png';

                return $news;
            });

        return $birthdays->merge($newsItems)->sortByDesc('event_timestamp')->values();
    }

    public function getUpcomingEventsProperty()
    {
        $start = now()->timezone(config('app.timezone'))->addDay()->startOfDay();
        $end = $start->copy()->addDays(7)->endOfDay();

        $locale = app()->getLocale();
        $employeeId = $this->employee->id ?? null;
        $cityId = $this->employee->city_id ?? null;
        $centerId = $this->contract->position->center_id ?? null;
        $programId = $this->contract->position->program_id ?? null;

        return News::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('event_date', [$start, $end])->orWhereBetween('published_at', [$start, $end]);
            })
            ->where(function ($q) use ($cityId, $centerId, $programId, $employeeId) {
                $q->whereNull('newsable_type')
                    ->orWhere(function ($q2) use ($employeeId) {
                        $q2->where('newsable_type', Employee::class)->where('newsable_id', $employeeId);
                    })
                    ->orWhere(function ($q2) use ($cityId) {
                        $q2->where('newsable_type', City::class)->where('newsable_id', $cityId);
                    })
                    ->orWhere(function ($q2) use ($centerId) {
                        $q2->where('newsable_type', Center::class)->where('newsable_id', $centerId);
                    })
                    ->orWhere(function ($q2) use ($programId) {
                        $q2->where('newsable_type', Program::class)->where('newsable_id', $programId);
                    });
            })
            ->where(function ($q) {
                $q->whereNull('display_until')->orWhere('display_until', '>=', now()->startOfDay());
            })
            ->get()
            ->map(function ($news) {
                $timestamp = $news->event_date ?? $news->published_at;
                if (!$timestamp instanceof Carbon) {
                    $timestamp = Carbon::parse($timestamp);
                }

                $daysLeft = now()->diffInDays($timestamp, false);

                $news->event_title = $news->title;
                $news->event_description = $news->description;
                $news->event_timestamp = $timestamp;
                $news->image_url = $news->photo ? \Storage::url($news->photo) : '/images/news-default.png';
                $news->daysLeftText = match ($daysLeft) {
                    0 => 'Today 🎉',
                    1 => 'Tomorrow',
                    default => "In {$daysLeft} days",
                };

                return $news;
            })
            ->sortBy('event_timestamp')
            ->values();
    }

    public function getUpcomingBirthdaysProperty()
    {
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $start = $today->copy()->addDay();
        $end = $today->copy()->addDays(7);
        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol = $locale === 'ar' ? 'name_ar' : 'name_en';

        return Employee::query()
            ->whereNotNull('dob')
            ->get()
            ->filter(function ($emp) use ($start, $end) {
                $dob = Carbon::parse($emp->dob)->setYear(now()->year);
                if ($dob->lt($start)) {
                    $dob->addYear();
                }
                return $dob->isBetween($start, $end, true);
            })
            ->map(function ($emp) use ($titleCol, $deptCol) {
                $contract = Contract::with(['position.department'])
                    ->where('employee_id', $emp->id)
                    ->orderByDesc('start_date')
                    ->first();

                $emp->positionTitle = $contract?->position?->$titleCol ?? null;
                $emp->departmentName = $contract?->position?->department?->$deptCol ?? null;
                $dob = Carbon::parse($emp->dob)->setYear(now()->year);
                if ($dob->lt(now())) {
                    $dob->addYear();
                }
                $daysLeft = now()->diffInDays($dob, false);
                $emp->upcomingDob = $dob->format('d M');
                $emp->daysLeftText = match ($daysLeft) {
                    0 => 'Today 🎂',
                    1 => 'Tomorrow',
                    default => "In {$daysLeft} days",
                };
                return $emp;
            })
            ->sortBy(fn($emp) => Carbon::parse($emp->dob)->setYear(now()->year)->dayOfYear)
            ->values();
    }

    public function getDeliveredItemsProperty()
    {
        if (!$this->employee) {
            return collect();
        }

        return Checklist::query()
            ->with(['item', 'delivered'])
            ->where('employee_id', $this->employee->id)
            ->where('delivered_status', 'delivered')
            ->orderByDesc('delivered_at')
            ->get();
    }

    public function getMyBadgesProperty()
    {
        if (!$this->employee) {
            return collect();
        }

        return Badge::query()->with('employee')->where('employee_id', $this->employee->id)->orderByDesc('date')->get();
    }

    public function getPendingRequestsProperty()
    {
        if (!$this->employee) {
            return collect();
        }

        return Checklist::with(['item', 'employee'])
            ->where('delivered_id', $this->employee->id)
            ->where('delivered_status', 'pending')
            ->latest()
            ->get()
            ->map(
                fn($checklist) => [
                    'id' => $checklist->id,
                    'item' => $checklist->item?->name ?? '—',
                    'employee_name' => $checklist->employee?->name ?? '—',
                    'request_date' => $checklist->created_at->format('Y-m-d'),
                    'notes' => $checklist->notes ?? null,
                ],
            );
    }

    public function getTeamMembersProperty()
    {
        if (!$this->contract || !$this->contract->position_id) {
            return collect();
        }

        return Employee::whereHas('contracts', function ($query) {
            $query->where('position_id', $this->contract->position_id);
        })
            ->where('id', '!=', $this->employee->id)
            ->with(['media'])
            ->get();
    }

    public function mount(): void
    {
        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol = $locale === 'ar' ? 'name_ar' : 'name_en';

        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $this->employee = $user
            ->profile()
            ->with(['media', 'city'])
            ->first();

        if ($this->employee) {
            $this->contract = Contract::with(['position.department', 'position.center', 'position.program'])
                ->where('employee_id', $this->employee->id)
                ->orderByDesc('start_date')
                ->first();

            $this->positionTitle = $this->contract?->position?->$titleCol ?? '—';
            $this->departmentName = $this->contract?->position?->department?->$deptCol ?? '—';
            $this->joinedOn = $this->contract?->start_date ? date('d M Y', strtotime($this->contract->start_date)) : '—';
            $this->workEmail = $user->email ?? '—';
        } else {
            $this->positionTitle = $this->departmentName = $this->joinedOn = $this->workEmail = '—';
        }
    }

    public function render()
    {
        return view('livewire.dashboards.employee-dashboard.employee-dashboard', [
            'employee' => $this->employee,
            'contract' => $this->contract,
            'positionTitle' => $this->positionTitle,
            'departmentName' => $this->departmentName,
            'joinedOn' => $this->joinedOn,
            'workEmail' => $this->workEmail,
            'todayEvents' => $this->todayEvents,
            'upcomingEvents' => $this->upcomingEvents,
            'deliveredItems' => $this->deliveredItems,
            'myBadges' => $this->myBadges,
            'pendingRequests' => $this->pendingRequests,
            'teamMembers' => $this->teamMembers,
        ]);
    }
}
