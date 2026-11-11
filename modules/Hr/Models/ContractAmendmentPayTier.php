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
 * One pay tier of a flexible contract, as it stood before or after an
 * amendment. Same shape and same protection as ContractPayTier.
 *
 * @property int $id
 * @property int $contract_amendment_id
 * @property int $side
 * @property string $condition_en
 * @property string $condition_ar
 * @property string|null $amount
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['contract_amendment_id', 'side', 'condition_en', 'condition_ar', 'amount', 'sort_order'])]
class ContractAmendmentPayTier extends Model
{
    use HasTranslation;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'side' => 'integer',
            'amount' => EncryptedSalary::class,
            'sort_order' => 'integer',
        ];
    }

    const SIDE_BEFORE = 0;

    const SIDE_AFTER = 1;

    /**
     * @return BelongsTo<ContractAmendment, $this>
     */
    public function amendment(): BelongsTo
    {
        return $this->belongsTo(ContractAmendment::class, 'contract_amendment_id');
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function condition(): Attribute
    {
        return $this->translatable('condition');
    }
}
