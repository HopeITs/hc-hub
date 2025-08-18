<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'required_position_id',
        'center_id',
        'responsibilities',
        'max_emp',
        'exist_emp',
        'is_active',
    ];

    protected $casts = [
        'max_emp'   => 'integer',
        'exist_emp' => 'integer',
        'is_active' => 'boolean',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'position_id');
    }

    public function center()
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function requiredPosition()
    {
        return $this->belongsTo(RequiredPosition::class, 'required_position_id');
    }


}
