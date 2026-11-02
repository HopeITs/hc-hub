<?php

namespace Modules\Hr\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Modules\Hr\Casts\EncryptedSalary;

class ContractPayTier extends Model
{
    use HasTranslation;

    protected $fillable = [
        'contract_id',
        'condition_en',
        'condition_ar',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        // A flexible contract's pay lives here instead of in contracts.salary;
        // it is the same secret and is protected the same way.
        'amount' => EncryptedSalary::class,
        'sort_order' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /** @return Attribute<string|null, string|null> */
    protected function condition(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getTranslatableAttribute('condition'),
            set: fn ($value) => $this->setTranslatableAttribute('condition', $value),
        );
    }
}
