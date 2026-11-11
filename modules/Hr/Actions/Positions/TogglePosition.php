<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Positions;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\Position;

/**
 * Switch a position on or off. An inactive position is no longer offered
 * for new contracts; contracts already on it are untouched.
 */
class TogglePosition
{
    public function handle(Position $position, User $actor): Position
    {
        Gate::forUser($actor)->authorize('update', $position);

        $position->forceFill(['is_active' => ! $position->is_active])->save();

        return $position;
    }
}
