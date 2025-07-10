<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'jobLevel_id',
        'contract_id',
        'name',
        'description',
        'grade',
        'email'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

}
