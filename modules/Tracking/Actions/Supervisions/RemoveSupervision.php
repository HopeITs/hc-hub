<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Supervisions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Supervision;
use Modules\Tracking\Support\SupervisionMap;

/**
 * Remove a local supervision (contracts §10). It takes effect at once;
 * removing a row that is already gone changes nothing. HR relations are
 * read-only and cannot be removed here.
 */
class RemoveSupervision
{
    /**
     * @return bool false when the row was already removed (show users.supervision_already_removed)
     */
    public function handle(int $supervisionId): bool
    {
        Gate::authorize('manage', Supervision::class);

        $removed = DB::transaction(function () use ($supervisionId): bool {
            $supervision = Supervision::query()->lockForUpdate()->find($supervisionId);

            if ($supervision === null) {
                return false;
            }

            $supervision->delete();

            return true;
        });

        SupervisionMap::flush();

        return $removed;
    }
}
