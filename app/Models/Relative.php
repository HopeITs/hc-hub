<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Relative extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'employee_id',
        'name',
        'phone',
        'relation'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
