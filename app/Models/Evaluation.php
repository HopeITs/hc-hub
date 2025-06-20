<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'contract_id',
        'date',
        'current_manager_id',
        'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function badges()
    {
        return $this->hasMany(Badge::class);
    }
}
