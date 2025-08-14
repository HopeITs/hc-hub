<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Education extends Model
{
    use HasFactory;

    protected $table = 'educations';

    protected $fillable = [
        'id',
        'employee_id',
        'title',
        'university',
        'type',
        'level',
        'graduation_year',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
