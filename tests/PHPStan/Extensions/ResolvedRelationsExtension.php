<?php

declare(strict_types=1);

namespace Tests\PHPStan\Extensions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Larastan\Larastan\Properties\ModelProperty;
use Larastan\Larastan\Reflection\EloquentBuilderMethodReflection;
use Larastan\Larastan\Support\CollectionHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Makes the relations a module registers on another module's model
 * (Model::resolveRelationUsing in its ServiceProvider) visible to Larastan,
 * as methods and as properties, with the same generic types a declared
 * relation method has. Core and HR models cannot name those related models
 * in a docblock (D10: Pint would import them), so the types are read from the
 * booted application instead: the resolver is called on a fresh model and the
 * relation it returns gives the related (and intermediate) model.
 */
final class ResolvedRelationsExtension implements MethodsClassReflectionExtension, PropertiesClassReflectionExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private CollectionHelper $collectionHelper,
    ) {}

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->resolver($classReflection, $methodName) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new EloquentBuilderMethodReflection($methodName, $classReflection, [], $this->relationType($classReflection, $methodName));
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return ! $classReflection->hasNativeProperty($propertyName)
            && $this->resolver($classReflection, $this->relationOf($propertyName)) !== null;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if ($this->relationOf($propertyName) !== $propertyName) {
            return new ModelProperty($classReflection, IntegerRangeType::createAllGreaterThanOrEqualTo(0), new NeverType, false);
        }

        $result = $this->relationType($classReflection, $propertyName)->getTemplateType(Relation::class, 'TResult');

        return new ModelProperty($classReflection, $this->collectionHelper->replaceCollectionsInType($result), new NeverType, false);
    }

    /**
     * The relation a property reads: `projects` for `projects` and for the
     * `projects_count` withCount() adds.
     */
    private function relationOf(string $propertyName): string
    {
        return str_ends_with($propertyName, '_count') ? Str::camel(Str::before($propertyName, '_count')) : $propertyName;
    }

    private function resolver(ClassReflection $classReflection, string $name): ?Closure
    {
        if (! $classReflection->is(Model::class) || $classReflection->isAbstract()) {
            return null;
        }

        /** @var array<class-string, array<string, Closure>> $resolvers */
        $resolvers = Closure::bind(static fn (): array => Model::$relationResolvers, null, Model::class)();

        return $resolvers[$classReflection->getName()][$name] ?? null;
    }

    private function relationType(ClassReflection $classReflection, string $name): Type
    {
        $resolver = $this->resolver($classReflection, $name);
        $model = $classReflection->getNativeReflection()->newInstance();
        $relation = $resolver === null ? null : $resolver($model);

        if (! $relation instanceof Relation) {
            return new ObjectType(Relation::class);
        }

        $models = [
            'TRelatedModel' => $relation->getRelated()::class,
            'TIntermediateModel' => $relation->getParent()::class,
            'TDeclaringModel' => $classReflection->getName(),
        ];
        $templates = array_keys($this->reflectionProvider->getClass($relation::class)->getTemplateTags());

        return new GenericObjectType($relation::class, array_map(
            fn (string $template): Type => new ObjectType($models[$template] ?? Model::class),
            $templates,
        ));
    }
}
