<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Checklists;

use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\DefaultDeliverable;

/**
 * Remove a governorate's default deliverer for an item.
 */
final class DeleteDefaultDeliverable
{
    public function handle(int $deliverableId): void
    {
        $model = DefaultDeliverable::query()->findOrFail($deliverableId);

        Gate::authorize('delete', $model);

        $model->delete();
    }
}
