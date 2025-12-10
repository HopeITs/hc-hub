<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Badge extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [
        'id',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('badge')
            ->logOnly([
                'employee_id',
                'evaluation_id',
                'date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
