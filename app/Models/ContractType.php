<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class ContractType extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'id',
        'type',
        'place',
        'name'
    ];

    public array $translatable = ['name'];



    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
