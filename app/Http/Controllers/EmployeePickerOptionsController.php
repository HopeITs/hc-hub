<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Hr\Models\Employee;

class EmployeePickerOptionsController extends Controller
{
    /** Query keys every list accepts: the sort language and the cache version. */
    private const CommonParameters = ['l', 'v'];

    /**
     * Every employee a person dropdown offers, as one small JSON list.
     *
     * The assignee and responsible dropdowns read this once per page load
     * (Alpine's employeeOptions store) instead of the server rendering every
     * employee into every dropdown on every Livewire response. The list is
     * sorted for the interface language, so the page asks with `?l=<locale>`
     * and the browser may reuse the answer for five minutes.
     *
     * With `?scope=<name>` it serves a module's named, filtered list instead
     * (ModuleRegistry picker scopes): an unknown scope is a 404, a parameter
     * the scope does not declare a 422, and a viewer the scope refuses a 403.
     */
    public function __invoke(Request $request, ModuleRegistry $modules): JsonResponse
    {
        $scope = $request->query('scope');

        if ($scope === null) {
            return response()
                ->json(Employee::pickerLabels()->values())
                ->header('Cache-Control', 'private, max-age=300');
        }

        $source = is_string($scope) ? $modules->pickerScopeSourceFor($scope) : null;
        abort_if($source === null, 404);

        $parameters = collect($request->query())->except(['scope', ...self::CommonParameters]);
        abort_if($parameters->keys()->diff($source->parameters($scope))->isNotEmpty(), 422);
        abort_if($parameters->contains(fn (mixed $value): bool => ! is_string($value)), 422);

        $options = $source->options($scope, $request->user(), $parameters->all());
        abort_if($options === null, 403);

        return response()
            ->json($options)
            ->header('Cache-Control', 'private, no-store');
    }
}
