<?php

namespace App\Livewire\Dashboards;

use Carbon\Carbon;
use App\Models\{Badge, Checklist, Employee, Contract};
use Livewire\Component;
class HrDashboard extends Component
{
    public $employee;
    public $contract;
    public $positionTitle;
    public $departmentName;
    public $joinedOn;
    public $workEmail;

    public function getTodayBirthdaysProperty()
    {
        $today = now()->timezone(config('app.timezone'));
        $day = $today->day;
        $month = $today->month;

        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol = $locale === 'ar' ? 'name_ar' : 'name_en';

        return Employee::query()
            ->whereNotNull('dob')
            ->whereRaw('DAY(dob) = ?', [$day])
            ->whereRaw('MONTH(dob) = ?', [$month])
            ->with(['media', 'contracts.position.department'])
            ->get()
            ->map(function ($emp) use ($titleCol, $deptCol) {
                $contract = Contract::with(['position.department'])
                    ->where('employee_id', $emp->id)
                    ->orderByDesc('start_date')
                    ->first();

                $emp->positionTitle = $contract?->position?->$titleCol ?? null;
                $emp->departmentName = $contract?->position?->department?->$deptCol ?? null;

                return $emp;
            });
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

                $daysLeft = (int) now()->diffInDays($dob, false);
                $emp->upcomingDob = $dob->format('d M');

                if ($daysLeft === 0) {
                    $emp->daysLeftText = 'Today 🎂';
                } elseif ($daysLeft === 1) {
                    $emp->daysLeftText = 'Tomorrow';
                } else {
                    $emp->daysLeftText = "In {$daysLeft} days";
                }

                return $emp;
            })
            ->sortBy(fn($emp) => Carbon::parse($emp->dob)->setYear(now()->year)->dayOfYear)
            ->values();
    }

    public function getTeamMembersProperty()
    {
        if (!$this->contract?->position) {
            return collect();
        }

        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol = $locale === 'ar' ? 'name_ar' : 'name_en';

        $position = $this->contract->position;
        $cityId = $position->city_id;
        $programId = $position->program_id;

        $members = collect();

        $ceo = Employee::query()
            ->where('name_en', 'Fredy Youssef')
            ->with(['media', 'contracts.position.jobLevel'])
            ->first();

        if ($ceo) {
            $contract = $ceo->latestActiveContract();
            $ceo->positionTitle = 'Chief Executive Officer';
            $members->push($ceo);
        }

        $directManager = null;
        if ($position?->direct_manager_id) {
            $managerPosition = \App\Models\Position::with(['jobLevel', 'activeContracts.employee.media'])->find($position->direct_manager_id);

            if ($managerPosition) {
                $activeContract = $managerPosition
                    ->activeContracts()
                    ->with(['employee.contracts.position.jobLevel'])
                    ->latest('start_date')
                    ->first();

                $directManager = $activeContract?->employee;

                if ($directManager) {
                    $directManager->positionTitle = $activeContract?->position?->$titleCol ?? '—';
                }
            }
        }

        if ($directManager && !$members->contains('id', $directManager->id)) {
            $members->push($directManager);
        }

        $techManager = null;
        if ($position?->tech_manager_id) {
            $techManagerPosition = \App\Models\Position::with(['jobLevel', 'activeContracts.employee.media'])->find($position->tech_manager_id);

            if ($techManagerPosition) {
                $activeContract = $techManagerPosition
                    ->activeContracts()
                    ->with(['employee.contracts.position.jobLevel'])
                    ->latest('start_date')
                    ->first();

                $techManager = $activeContract?->employee;

                if ($techManager) {
                    $techManager->positionTitle = $activeContract?->position?->$titleCol ?? '—';
                }
            }
        }

        if ($techManager && !$members->contains('id', $techManager->id)) {
            $members->push($techManager);
        }

        $nameCol = "name_{$locale}";

        $excludeIds = collect([$this->employee?->id, $ceo?->id, $directManager?->id, $techManager?->id])
            ->filter()
            ->unique()
            ->all();

        $sameCityEmployees = \App\Models\Employee::query()
            ->where('city_id', $this->employee?->city_id)
            ->whereNotIn('id', $excludeIds)
            ->with(['contracts.position.jobLevel'])
            ->get()
            ->map(function ($emp) use ($nameCol, $titleCol) {
                $contract = $emp->contracts->filter(fn($c) => $c->is_active || (!$c->terminated_at && $c->start_date <= now() && $c->end_date >= now()))->sortByDesc('start_date')->first() ?? $emp->latestActiveContract();

                $emp->displayName = $emp->$nameCol ?? $emp->name_en;
                $emp->positionTitle = $contract?->position?->$titleCol ?? ($contract?->position?->title_en ?? '—');
                $emp->jobLevelGrade = $contract?->position?->jobLevel?->grade ?? 999;

                return $emp;
            })
            ->sortBy('jobLevelGrade')
            ->values();

        return $members->merge($sameCityEmployees)->unique('id')->values();
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
            ->map(function ($checklist) {
                return [
                    'id' => $checklist->id,
                    'item' => $checklist->item?->name ?? '—',
                    'employee_name' => $checklist->employee?->name ?? '—',
                    'request_date' => $checklist->created_at->format('Y-m-d'),
                    'notes' => $checklist->notes ?? null,
                ];
            });
    }

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

        $newsItems = \App\Models\News::query()
            ->where(function ($q) use ($todayDate) {
                $q->whereDate('event_date', $todayDate)->orWhereDate('published_at', $todayDate);
            })
            ->where(function ($q) use ($employeeId, $cityId, $centerId, $programId) {
                $q->whereNull('newsable_type')
                    ->orWhere(function ($q2) use ($employeeId) {
                        $q2->where('newsable_type', Employee::class)->where('newsable_id', $employeeId);
                    })
                    ->orWhere(function ($q2) use ($cityId) {
                        $q2->where('newsable_type', \App\Models\City::class)->where('newsable_id', $cityId);
                    })
                    ->orWhere(function ($q2) use ($centerId) {
                        $q2->where('newsable_type', \App\Models\Center::class)->where('newsable_id', $centerId);
                    })
                    ->orWhere(function ($q2) use ($programId) {
                        $q2->where('newsable_type', \App\Models\Program::class)->where('newsable_id', $programId);
                    });
            })
            ->where(function ($q) {
                $q->whereNull('display_until')->orWhere('display_until', '>=', now()->startOfDay());
            })
            ->with('media')
            ->get()
            ->map(function ($news) {
                $timestamp = $news->event_date ?? $news->published_at;
                $timestamp = $timestamp instanceof \Carbon\Carbon ? $timestamp : \Carbon\Carbon::parse($timestamp);
                $news->event_type = 'news';
                $news->event_title = $news->title;
                $news->event_description = $news->description;
                $news->event_timestamp = $timestamp;
                $news->image_url = $news->photo ? asset('storage/' . $news->photo) : '/images/news-default.png';

                return $news;
            });

        return $birthdays->merge($newsItems)->sortByDesc('event_timestamp')->values();
    }

    public function getUpcomingEventsProperty()
    {
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $start = $today->copy()->addDay();
        $end = $today->copy()->addWeeks(7);
        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol = $locale === 'ar' ? 'name_ar' : 'name_en';

        return Employee::query()
            ->whereNotNull('dob')
            ->get()
            ->filter(function ($emp) use ($start, $end) {
                $dob = \Carbon\Carbon::parse($emp->dob)->setYear(now()->year);
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
                $dob = \Carbon\Carbon::parse($emp->dob)->setYear(now()->year);
                if ($dob->lt(now())) {
                    $dob->addYear();
                }
                $daysLeft = now()->diffInDays($dob, false);
                $emp->event_type = 'birthday';
                $emp->event_title = '🎂 Upcoming Birthday';
                $emp->event_description = ($emp->positionTitle ? $emp->positionTitle . ' · ' : '') . ($emp->departmentName ?? '');
                $emp->event_timestamp = $dob;
                $emp->upcomingDob = $dob->format('d M');
                $emp->daysLeftText = match ($daysLeft) {
                    0 => 'Today 🎂',
                    1 => 'Tomorrow',
                    default => "In {$daysLeft} days",
                };
                return $emp;
            })
            ->sortBy(fn($emp) => $emp->event_timestamp)
            ->values();
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
            $this->contract = Contract::with(['position.department'])
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
        return view('livewire.dashboards.hr-dashboard.hr-dashboard', [
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
        ]);
    }
}
