<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'position_detail_id',
        'center_id',
        'max_emp',
        'exist_emp',
        'is_active',
    ];



    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

}
