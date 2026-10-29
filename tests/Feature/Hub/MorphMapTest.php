<?php

use App\Support\MorphMap;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;

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

test('every model in app/Models has an alias', function () {
    $mapped = array_values(MorphMap::MAP);

    $missing = collect(File::allFiles(app_path('Models')))
        ->map(fn (SplFileInfo $file) => 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()))
        ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Model::class))
        ->reject(fn (string $class) => (new ReflectionClass($class))->isAbstract())
        ->reject(fn (string $class) => in_array($class, $mapped, true))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

test('an unmapped model cannot be stored polymorphically', function () {
    $unmapped = new class extends Model {};

    $unmapped->getMorphClass();
})->throws(ClassMorphViolationException::class);
