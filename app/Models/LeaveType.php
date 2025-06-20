<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'base_days',
        'allowed_gender',
        'material_status'
    ];

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
