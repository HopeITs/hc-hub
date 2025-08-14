<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'city_id',
        'item_id'
    ];

    public function employee()
    {
        return$this->belongsTo(Employee::class , 'employee_id');
    }
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function item()
    {
        return $this->belongsTo(ChecklistItem::class, 'item_id');
    }

}
