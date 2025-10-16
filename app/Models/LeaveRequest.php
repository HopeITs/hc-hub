<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'leave_balance_id',
        'start_date',
        'end_date',
        'reason',
        'approved_by',
        'approved_at',
        'status',
        'alternative_employee'
    ];

    public function leaveBalance(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }
}
