<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $order
 * @property string $onboarding_group
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'order', 'onboarding_group'])]
class ChecklistItem extends Model
{
    /** Which delivery step of the onboarding journey an item belongs to (OnboardingDeliveries). */
    public const GROUP_ELECTRONICS = 'electronics';

    public const GROUP_CARD = 'card';

    public const GROUP_AFTER_PROBATION = 'after_probation';

    public const GROUP_OTHER = 'other';

    public const GROUP_EXIT = 'exit';

    /** Item name => group for the items the seeder creates; everything else is "other", the order-3 return items "exit". */
    public const GROUP_BY_NAME = [
        'Email' => self::GROUP_ELECTRONICS,
        'Email Signature' => self::GROUP_ELECTRONICS,
        'Laptop' => self::GROUP_ELECTRONICS,
        'Mouse' => self::GROUP_ELECTRONICS,
        'Mobile' => self::GROUP_ELECTRONICS,
        'SIM' => self::GROUP_ELECTRONICS,
        'ID Cartoon' => self::GROUP_CARD,
        'ID Plastic' => self::GROUP_CARD,
        'Mug' => self::GROUP_AFTER_PROBATION,
    ];

    /**
     * @return BelongsTo<Checklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * @return HasMany<DefaultDeliverable, $this>
     */
    public function defaultDeliverable(): HasMany
    {
        return $this->hasMany(DefaultDeliverable::class, 'item_id');
    }
}
