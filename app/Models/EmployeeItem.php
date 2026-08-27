<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_id',
        'item',
        'brand',
        'model',
        'serial_number',
        'status',
        'notes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_item')
            ->logOnly([
                'employee_id',
                'item',
                'brand',
                'model',
                'serial_number',
                'status',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
