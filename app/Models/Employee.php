<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use App\Traits\HasTranslation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasTranslation;

    protected $guarded = ['id'];

    protected $casts = [
        'dob' => 'datetime',
        'actual_dob' => 'datetime',
        'board_member_started_at' => 'datetime',
        'board_member_end_at' => 'datetime',
        'is_active' => 'boolean',
        'is_board_member' => 'boolean',
        'children' => 'integer',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('name'),
            set: fn($value) => $this->setTranslatableAttribute('name', $value)
        );
    }

    protected function father_name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('father_name'),
            set: fn($value) => $this->setTranslatableAttribute('father_name', $value)
        );
    }

    protected function mother_name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('mother_name'),
            set: fn($value) => $this->setTranslatableAttribute('mother_name', $value)
        );
    }

    public function getDepartmentAttribute()
    {
        return $this->latestActiveContract()?->position->department;
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('address'),
            set: fn($value) => $this->setTranslatableAttribute('address', $value)
        );
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function allContracts()
    {
        return $this->hasMany(Contract::class, 'employee_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'employee_id')
            ->where('is_active', true)
            ->whereDate('start_date', '<=', Carbon::now())
            ->whereDate('end_date', '>=', Carbon::now());

    }


    public function latestActiveContract(): Contract|null
    {
        return $this->contracts()->with(['position.department', 'leaveBalances'])->latest()->first();
    }

    public function defaultDeliverItems(): Employee|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DefaultDeliverable::class, 'employee_id');
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'contracts', 'employee_id', 'position_id')
            ->withPivot([
                'id', 'contract_type_id', 'start_date', 'end_date', 'terminated_at',
                'salary', 'currency', 'weekly_hours', transColumn('work_days'), 'work_hours_from','work_hours_to', 'is_active'
            ])
            ->wherePivot('is_active', true)
            ->where(function ($query) {
                $today = now()->toDateString();
                $query->whereDate('contracts.start_date', '<=', $today)
                    ->whereDate('contracts.end_date', '>=', $today);
            });
    }

    public function scopeInvolvedInProgram($query, $programId)
    {
        return $query->whereHas('contracts.position.center', function ($q) use ($programId) {
            $q->where('program_id', $programId);
        });
    }

    public function educations()
    {
        return $this->hasMany(Education::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function badges()
    {
        return $this->hasMany(Badge::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function relatives()
    {
        return $this->hasMany(Relative::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }


    public function items()
    {
        return $this->hasMany(EmployeeItem::class);
    }

    public function jobLevels(): BelongsToMany
    {
        return $this->belongsToMany(JobLevel::class, 'contracts', 'employee_id', 'job_level_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'work_days', 'work_hours']);
    }

    public function contractTypes(): BelongsToMany
    {
        return $this->belongsToMany(ContractType::class, 'contracts', 'employee_id', 'contract_type_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'work_days', 'work_hours']);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'contracts', 'employee_id', 'department_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'work_days', 'work_hours']);
    }

    public function account()
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    public function availableLeaves()
    {
        $leaves = $this->latestActiveContract()?->leaveBalances()
//            ->whereHas('leaveType', fn ($q) => $q->where('base_days', '>', 10))
            ->with(['leaveRequests' => fn ($q) => $q->pending()
                ->select('id','leave_balance_id','type','start_at','end_at')
            ])
            ->get();

        // Now compute pending durations using the real accessor logic
        foreach ($leaves as $leave) {

            $pendingDays = 0;
            $pendingHours = 0;

            foreach ($leave->leaveRequests as $req) {

                if ($req->type === 'day') {
                    $weekends = array_map('intval', explode(',', $req->leaveBalance->contract->position->center->weekends));

                   Carbon::setWeekendDays($weekends);

                    $pendingDays += $req->start_at->diffInWeekDays($req->end_at) + 1;
                }

                if ($req->type === 'hour') {
                    $pendingHours += $req->start_at->floatDiffInRealHours($req->end_at);
                }
            }

            $leave->pending_days = $pendingDays;
            $leave->pending_hours = $pendingHours;
        }

        return $leaves;
    }


    public function allAvailableLeaves()
    {
        return $this->latestActiveContract()?->leaveBalances()->whereColumn('deserved', '>', 'used')->with('leaveType')->get();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useFallbackUrl('https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=be1f24&background=FFF')
            ->singleFile();
    }

    public function getJoinAtAttribute()
    {
        return $this->allContracts()->first()?->start_date->format('d-M-Y') ?? '-';
    }

    public function getYearsOfServiceFormattedAttribute(): string
    {
        $contracts = $this->allContracts()
            ->select(['start_date', 'end_date', 'terminated_at'])
            ->orderBy('start_date')
            ->get();

        if ($contracts->isEmpty()) {
            return 0 . ' ' . trans_choice('general.year', 0);
        }

        // Build the time periods for each contract
        $periods = $contracts->map(function ($c) {
            $start = Carbon::parse($c->start_date);

            // Determine the actual end date of the contract
            $end = null;

            if ($c->terminated_at) {
                // Employee left before the planned end date
                $end = Carbon::parse($c->terminated_at);
            } elseif (today() > $c->end_date) {
                // Contract ended normally
                $end = Carbon::parse($c->end_date);
            } else {
                // Contract is still active
                $end = Carbon::now();
            }

            // Correct data errors where end date is before start date
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            return compact('start', 'end');
        })->sortBy('start')->values()->toArray();

        // Merge overlapping or continuous contract periods
        $merged = [$periods[0]];
        foreach (array_slice($periods, 1) as $p) {
            $last = &$merged[count($merged) - 1];
            if ($p['start']->lte($last['end'])) {
                // Extend the end date if periods overlap or touch
                $last['end'] = $p['end']->gt($last['end']) ? $p['end'] : $last['end'];
            } else {
                // Separate period (gap between contracts)
                $merged[] = $p;
            }
        }

        // Calculate total service time (in years + fractional months)
        $totalYears = 0;
        $totalMonths = 0;
        foreach ($merged as $m) {
            $years = floor($m['start']->diffInYears($m['end']));

            $months = $m['start']->diffInMonths($m['end']) % 12;
            $totalYears += $years;
            $totalMonths += $months;
        }
        $result = '';
        if ($totalYears > 0) {
            $result = $totalYears . ' ' . trans_choice('general.year', $totalYears) . ' ';
        }
        if ($totalMonths > 0) {
            $result .= $totalMonths . ' ' . trans_choice('general.month', $totalMonths);
        }
        if ($result === '') {
            $result = $totalYears . ' ' . trans_choice('general.year', $totalYears);
        }
        // Return total years of service rounded to one decimal
        return $result;
    }

    public function getYearsOfServiceAttribute(): int
    {
        $contracts = $this->allContracts()
            ->select(['start_date', 'end_date', 'terminated_at'])
            ->orderBy('start_date')
            ->get();

        if ($contracts->isEmpty()) {
            return 0 ;
        }

        // Build the time periods for each contract
        $periods = $contracts->map(function ($c) {
            $start = Carbon::parse($c->start_date);

            // Determine the actual end date of the contract
            $end = null;

            if ($c->terminated_at) {
                // Employee left before the planned end date
                $end = Carbon::parse($c->terminated_at);
            } elseif (today() > $c->end_date) {
                // Contract ended normally
                $end = Carbon::parse($c->end_date);
            } else {
                // Contract is still active
                $end = Carbon::now();
            }

            // Correct data errors where end date is before start date
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            return compact('start', 'end');
        })->sortBy('start')->values()->toArray();

        // Merge overlapping or continuous contract periods
        $merged = [$periods[0]];
        foreach (array_slice($periods, 1) as $p) {
            $last = &$merged[count($merged) - 1];
            if ($p['start']->lte($last['end'])) {
                // Extend the end date if periods overlap or touch
                $last['end'] = $p['end']->gt($last['end']) ? $p['end'] : $last['end'];
            } else {
                // Separate period (gap between contracts)
                $merged[] = $p;
            }
        }

        // Calculate total service time (in years + fractional months)
        $totalYears = 0;

        foreach ($merged as $m) {
            $years = $m['start']->diffInYears($m['end']);
            $months = $m['start']->diffInMonths($m['end']) % 12;
            $totalYears += $years + ($months / 12);

        }

        return $totalYears;

    }

    public function abilityToTakeLeave($leaveType)
    {
        return $this->contracts()
            ->whereHas('leaveBalances.leaveRequests', function ($q) use ($leaveType) {
                $q->whereHas('leaveBalance', function ($b) use ($leaveType) {
                    $b->where('type_id', $leaveType);
                })->where('status', 'approved');
            })
            ->exists();

    }


}
