<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'employee_id',
        'title',
        'issuing_organization',
        'provider',
        'start_date',
        'end_date',
        'is_approved'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
