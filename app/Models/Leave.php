<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
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
}
