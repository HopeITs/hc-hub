<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeItem extends Model
{
    protected $fillable = [
        'employee_id',
        'item',
        'brand',
        'model',
        'serial_number',
        'status',
        'notes',
    ];
}
