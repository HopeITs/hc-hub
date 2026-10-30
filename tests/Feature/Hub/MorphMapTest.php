<?php

use App\Support\MorphMap;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Tests\Support\HubPaths;

test('the morph map is enforced', function () {
    expect(Relation::requiresMorphMap())->toBeTrue()
        ->and(Relation::morphMap())->toBe(MorphMap::MAP);
});

test('every mapped model resolves to its alias and back', function (string $alias, string $class) {
    expect(class_exists($class))->toBeTrue()
        ->and(is_subclass_of($class, Model::class))->toBeTrue()
        ->and(Relation::getMorphedModel($alias))->toBe($class)
        ->and(Relation::getMorphAlias($class))->toBe($alias)
        ->and((new $class)->getMorphClass())->toBe($alias)
        ->and($alias)->toMatch('/^[a-z][a-z0-9_]*$/');
})->with(fn () => collect(MorphMap::MAP)->map(fn ($class, $alias) => [$alias, $class])->values()->all());

test('every model in app/Models and modules/*/app/Models has an alias', function () {
    $mapped = array_values(MorphMap::MAP);

    $classes = collect(HubPaths::models())->flatMap(function (string $dir) {
        // app/Models -> App\Models\, modules/Hr/app/Models -> Modules\Hr\Models\
        $namespace = $dir === app_path('Models')
            ? 'App\\Models\\'
            : 'Modules\\'.basename(dirname($dir, 2)).'\\Models\\';

        return collect(File::allFiles($dir))
            ->map(fn (SplFileInfo $file) => $namespace.str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $file->getRelativePathname()));
    });

    // A file that does not autoload under its folder's namespace would
    // otherwise be skipped silently.
    expect($classes->reject(fn (string $class) => class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class))->values()->all())->toBe([])
        ->and($classes->filter(fn (string $class) => str_starts_with($class, 'Modules\\'))->isNotEmpty())->toBeTrue();

    $missing = $classes
        ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Model::class))
        ->reject(fn (string $class) => (new ReflectionClass($class))->isAbstract())
        ->reject(fn (string $class) => in_array($class, $mapped, true))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

test('the stored aliases are unchanged', function () {
    // Aliases are data in every *_type column: renaming or dropping one
    // needs a data migration, never just an edit of the map.
    expect(array_keys(MorphMap::MAP))->toContain(
        'user', 'role', 'permission', 'city', 'center', 'program', 'department', 'position', 'job_level',
        'employee', 'employee_item', 'employee_note', 'relative', 'education', 'course', 'course_category',
        'experience', 'evaluation', 'attachment', 'profile_change_request', 'contract', 'contract_type',
        'contract_pay_tier', 'badge', 'termination_reason', 'leave_type', 'leave_balance', 'leave_request',
        'leave_request_note', 'annual_leave_rule', 'holiday', 'holiday_project', 'birthday_holiday_choice',
        'checklist', 'checklist_item', 'default_deliverable', 'task_attempt', 'folder', 'news',
    );
});

test('an unmapped model cannot be stored polymorphically', function () {
    $unmapped = new class extends Model {};

    $unmapped->getMorphClass();
})->throws(ClassMorphViolationException::class);
