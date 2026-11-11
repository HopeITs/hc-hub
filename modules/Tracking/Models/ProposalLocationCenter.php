<?php

declare(strict_types=1);

namespace Modules\Tracking\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Center;

/**
 * One HR center of a proposal location row (contracts.md §7, W-34a). The
 * table has a composite primary key and no timestamps; rows are inserted
 * and deleted through the row's `centers()` relation, never updated.
 *
 * @property int $proposal_location_id
 * @property int $center_id
 */
#[Fillable(['proposal_location_id', 'center_id'])]
class ProposalLocationCenter extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proposal_location_id' => 'integer',
            'center_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProposalLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(ProposalLocation::class, 'proposal_location_id');
    }

    /**
     * The center itself, from the in-memory HR lookup (active or not).
     */
    public function center(): ?Center
    {
        return Center::remembered($this->center_id);
    }
}
