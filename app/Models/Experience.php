<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'employee_id',
        'job_title',
        'company',
        'start_date',
        'end_date',
        'place',
        'type'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
