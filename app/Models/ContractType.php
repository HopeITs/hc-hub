<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class ContractType extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'type',
        'place',
        'name'
    ];

    public array $translatable = ['name'];

    protected $casts = [
        'type'  => 'integer',
        'place' => 'integer',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class ,'contract_type_id');
    }


    public function getCategoryAttribute(): string
    {
        $map = config('contracts.map_by_id');

        return $map[$this->id] ?? 'annual';

    }
}
