<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A user's personal saved table view (B4-D section 5.1): table STATE only,
 * visible to its owner and only under the role context it was saved in.
 *
 * @property int $id
 * @property int $user_id
 * @property string $table
 * @property string $role_context
 * @property string $name
 * @property array<mixed> $state
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'table', 'role_context', 'name', 'state'])]
class SavedTableView extends Model
{
    public const LIMIT = 10;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['state' => 'array'];
    }
}
