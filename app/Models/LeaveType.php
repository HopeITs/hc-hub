<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class LeaveType extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'id',
        'name',
        'base_days',
        'allowed_gender',
        'marital_status'
    ];

    public array $translatable = ['name'];

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
