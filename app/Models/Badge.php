<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'employee_id',
        'evaluation_id',
        'type',
        'date',
        'reason',
        'current_salary',
        'new_salary',
        'notes'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }
}
