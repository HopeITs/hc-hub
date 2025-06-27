<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'item_id',
        'employee_id',
        'deliverable_id',
        'brand',
        'model',
        'serial',
        'delivered_at',
        'delivered_status',
        'returned_at',
        'returned_status',
        'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function items()
    {
        return $this->hasMany(ChecklistItem::class);
    }
}
