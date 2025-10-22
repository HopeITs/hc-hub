<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeNote extends Model
{
    protected $fillable = [
        'employee_id','type','content','is_done','color','order',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'order'   => 'integer',
    ];

    public function scopeForEmployee($q, int $employeeId) {
        return $q->where('employee_id', $employeeId);
    }
}
