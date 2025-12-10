<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmployeeNote extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_id',
        'type',
        'content',
        'is_done',
        'color',
        'order',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'order'   => 'integer',
    ];

    public function scopeForEmployee($q, int $employeeId)
    {
        return $q->where('employee_id', $employeeId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_note')
            ->logOnly([
                'employee_id',
                'type',
                'content',
                'is_done',
                'color',
                'order',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
