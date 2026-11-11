<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Checklists;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Create or change who hands a checklist item out in a governorate.
 *
 * The governorate must be one the actor may write to, the employee must live
 * in it, and the same employee cannot be set twice for the same item there.
 */
final class SaveDefaultDeliverable
{
    /**
     * @param  array{employee_id?: mixed, city_id?: mixed, item_id?: mixed}  $input
     *
     * @throws ValidationException
     */
    public function handle(?int $deliverableId, array $input): DefaultDeliverable
    {
        $user = Auth::user();
        abort_if($user === null, 403);

        $model = $deliverableId === null ? new DefaultDeliverable : DefaultDeliverable::query()->findOrFail($deliverableId);

        if ($model->exists) {
            Gate::authorize('update', $model);
        } else {
            Gate::authorize('create', DefaultDeliverable::class);
        }

        // Before the rules: the employee rule depends on the governorate being one this user may write to.
        HrAccess::for($user)->assertCity($input['city_id'] ?? null, 'city_id');

        $cityId = (int) ($input['city_id'] ?? 0);

        $unique = Rule::unique('default_deliverables', 'item_id')
            ->where(fn (Builder $query) => $query
                ->where('employee_id', $input['employee_id'] ?? null)
                ->where('city_id', $cityId));

        if ($model->exists) {
            $unique->ignore($model->id);
        }

        $validated = Validator::make($input, [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where(fn (Builder $query) => $query->where('city_id', $cityId))],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'item_id' => ['required', 'integer', 'exists:checklist_items,id', $unique],
        ], [
            'employee_id.exists' => __('hr_checklists.defaults.employee_not_in_city'),
            'item_id.unique' => __('hr_checklists.defaults.already_set'),
        ])->validate();

        Gate::authorize($model->exists ? 'update' : 'create', $model->exists ? $model : [DefaultDeliverable::class, $cityId]);

        $model->fill($validated)->save();

        return $model;
    }
}
