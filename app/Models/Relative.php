<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Relative extends Model
{
    use HasFactory, LogsActivity;

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
                'name',
                'phone',
                'relation',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
