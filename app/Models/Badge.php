<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Badge extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'evaluation_id',
        'currency',
        'type',
        'date',
        'reason',
        'current_salary',
        'new_salary',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime:d-m-Y',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->activeRoleIs('HR', 'super-admin')) {
            return $query->whereRaw('1 = 0');
        }

        $cityId = $user->activeCityId();

        return $query->when(
            $cityId !== null,
            fn (Builder $query) => $query->whereHas(
                'employee',
                fn (Builder $query) => $query->where('city_id', $cityId),
            ),
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('badge')
            ->logOnly([
                'employee_id',
                'evaluation_id',
                'type',
                'currency',
                'date',
                'reason',
                'current_salary',
                'new_salary',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
