<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Positions;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\Position;

/**
 * Delete a position. One that contracts, vacancies, requests or other
 * positions still point at is refused (false): deactivate it instead.
 */
class DeletePosition
{
    public function handle(Position $position, User $actor): bool
    {
        Gate::forUser($actor)->authorize('delete', $position);

        $inUse = $position->contracts()->exists()
            || Position::query()->where('direct_manager_id', $position->id)->orWhere('tech_manager_id', $position->id)->exists();

        if ($inUse) {
            return false;
        }

        try {
            $position->delete();
        } catch (QueryException) {
            return false;
        }

        return true;
    }
}
