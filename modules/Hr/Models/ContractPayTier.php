<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Hr\Casts\EncryptedSalary;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property int $contract_id
 * @property string $condition_en
 * @property string $condition_ar
 * @property string|null $amount
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['contract_id', 'condition_en', 'condition_ar', 'amount', 'sort_order'])]
class ContractPayTier extends Model
{
    use HasTranslation;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // A flexible contract's pay lives here instead of in contracts.salary;
            // it is the same secret and is protected the same way.
            'amount' => EncryptedSalary::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function condition(): Attribute
    {
        return $this->translatable('condition');
    }
}
