<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'holidayable_id',
        'holidayable_type', 'is_recurring',
    ];
}
