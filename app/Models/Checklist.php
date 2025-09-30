<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'employee_id',
        'delivered_id',
        'brand',
        'model',
        'serial',
        'delivered_at',
        'delivered_status',
        'returned_at',
        'returned_status',
        'notes',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function item()
    {
        return $this->belongsTo(ChecklistItem::class, 'item_id');
    }
}
