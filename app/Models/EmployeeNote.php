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
            // Note bodies are free-text personal content and must not be
            // copied into the activity log, which has a far wider audience and
            // a much longer retention than the note itself. The event, the
            // record, and the actor are enough for an audit trail.
            ->logOnly([
                'employee_id',
                'type',
                'is_done',
                'order',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
