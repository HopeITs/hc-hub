<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $item_id
 * @property int $employee_id
 * @property int $delivered_id
 * @property int|null $receiver_id
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $serial
 * @property mixed|null $delivered_at
 * @property string $delivered_status
 * @property mixed|null $returned_at
 * @property string|null $returned_status
 * @property string|null $notes
 * @property string|null $return_notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['item_id', 'employee_id', 'delivered_id', 'receiver_id', 'brand', 'model', 'serial', 'delivered_at', 'delivered_status', 'returned_at', 'returned_status', 'notes'])]
class Checklist extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<ChecklistItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'item_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function delivered(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delivered_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }
}
