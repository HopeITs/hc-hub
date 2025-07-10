<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualLeaveRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'days',
        'day_per_month',
        'years_of_volunteering'
    ];
}
