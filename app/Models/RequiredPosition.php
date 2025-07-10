<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequiredPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'project_id',
        'title',
        'description',
        'requirements',
        'qualifications',
        'responsibilities',
        'is_active'
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

}
