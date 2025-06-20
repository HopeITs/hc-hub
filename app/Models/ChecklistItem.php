<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'is_checked',
        'department_id'
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

}
