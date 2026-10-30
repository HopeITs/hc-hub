<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AnnualLeaveRule extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'days',
        'day_per_month',
        'years_of_volunteering',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('annual_leave_rule')
            ->logOnly([
                'days',
                'day_per_month',
                'years_of_volunteering',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
