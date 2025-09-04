<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name_en',
        'name_ar',
        'base_days',
        'allowed_gender',
        'marital_status'
    ];

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
