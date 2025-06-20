<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContractType extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'type',
        'place',
        'weekly_hours'
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
