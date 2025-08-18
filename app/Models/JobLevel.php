<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class JobLevel extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'description',
        'grade',
        'email',
    ];
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'job_level_id');
    }

}
