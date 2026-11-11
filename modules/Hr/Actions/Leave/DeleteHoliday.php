<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\Holiday;

/**
 * Remove a holiday and its project links (holiday_project cascades). Leave
 * already priced against it is not recounted, as in the old app.
 */
final class DeleteHoliday
{
    public function handle(User $user, int $holidayId): void
    {
        $holiday = Holiday::query()->with('projects:id,holiday_id,program_id,city_id')->findOrFail($holidayId);

        Gate::forUser($user)->authorize('delete', $holiday);

        DB::transaction(function () use ($holiday): void {
            $holiday->projects()->delete();
            $holiday->delete();
        });
    }
}
