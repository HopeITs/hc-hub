<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class Department extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'id',
        'name',
        'description',
        'email'
    ];
    public $translatable = ['description'];
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function checklistItem()
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

}
