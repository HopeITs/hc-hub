<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One person's dashboard arrangement for one role (HasDashboardLayout).
 * The layout is whatever the browser last sent, already cleaned; it is
 * cleaned again on every read, so it never grants a tile on its own.
 *
 * @property int $id
 * @property int $user_id
 * @property string $role
 * @property array<mixed>|null $layout
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'role', 'layout'])]
class SavedDashboardLayout extends Model
{
    protected $table = 'dashboard_layouts';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['layout' => 'array'];
    }
}
