<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'description',
        'grade',
        'email'
    ];

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function checklistItem()
    {
        return $this->hasMany(ChecklistItem::class);
    }

}
