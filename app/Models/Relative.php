<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\RedactsSensitiveActivityLog;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Relative extends Model
{
    use HasFactory, LogsActivity, RedactsSensitiveActivityLog;

    /**
     * A relative's name and phone number identify a third party who never
     * consented to being in an audit trail. @see RedactsSensitiveActivityLog
     */
    protected array $sensitiveLogAttributes = [
        'name',
        'phone',
    ];

    protected $fillable = [
        'employee_id',
        'name',
        'phone',
        'relation',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('relative')
            ->logOnly([
                'employee_id',
                'relation',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
