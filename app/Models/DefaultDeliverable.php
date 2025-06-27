<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'default_deliverable_id',
        'city_id',
        'item_id'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function item()
    {
        return $this->belongsTo(ChecklistItem::class);
    }

}
