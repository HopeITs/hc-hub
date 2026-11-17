<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Supervisions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Supervision;
use Modules\Tracking\Support\SupervisionMap;

/**
 * Remove a local supervision (contracts §10). It takes effect at once: the
 * row is ended today (ends_on, ended_by) and kept as history, never deleted
 * (roadmap 2.5). Removing a row that is already ended, or gone, changes
 * nothing. HR relations are read-only and cannot be removed here.
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

            if ($supervision === null || ! $supervision->isCurrent()) {
                return false;
            }

            $supervision->forceFill(['ends_on' => now()->toDateString(), 'ended_by' => Auth::id()])->save();

            return true;
        });

        SupervisionMap::flush();

        return $removed;
    }
}
