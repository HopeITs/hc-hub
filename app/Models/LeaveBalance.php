<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'leaveBalance_id',
        'contract_id',
        'type_id',
        'deserved',
        'used'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

}

