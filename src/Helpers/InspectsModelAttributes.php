<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\FloatType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Laravel\Extensions\EloquentModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

trait InspectsModelAttributes
{
    protected function isModelAttributeHidden(Model $model, string $attributeName): bool
    {
        $visible = $model->getVisible();

        if (count($visible) > 0 && ! in_array($attributeName, $visible, true)) {
            return true;
        }

        return in_array($attributeName, $model->getHidden(), true);
    }


    /**
     * Whether Laravel transforms values assigned to this attribute in
     * `setAttribute()`: casts, date attributes, and set mutators (classic
     * `set{Studly}Attribute` or `Attribute`-style).
     */
    protected function isAttributeValueTransformed(Model $model, string $key): bool
    {
        return $model->hasCast($key)
            || in_array($key, $model->getDates(), true)
            || $this->hasSetMutator($model, $key);
    }


    /**
     * Whether Laravel transforms this attribute when reading it in
     * `attributesToArray()`: classic `get{Studly}Attribute` accessors or
     * `Attribute`-style attribute methods.
     */
    protected function isAttributeTransformedOnRead(Model $model, string $key): bool
    {
        return method_exists($model, 'get' . Str::studly($key) . 'Attribute')
            || $this->hasAttributeStyleMutator($model, $key);
    }


    /**
     * Whether a scalar cast preserves an already matching assigned type.
     * Dates preserve only `null`; mutators never qualify.
     */
    protected function castKeepsAssignedValueType(Model $model, string $key, Type $assignedType): bool
    {
        if ($this->hasSetMutator($model, $key)) {
            return false;
        }

        $cast = $model->getCasts()[$key] ?? null;

        if ($assignedType instanceof NullType) {
            return $cast !== null
                ? $this->castPreservesNull($cast)
                : in_array($key, $model->getDates(), true);
        }

        if (in_array($key, $model->getDates(), true)) {
            return false;
        }

        return match ($cast) {
            'int', 'integer' => $assignedType instanceof IntegerType,
            'bool', 'boolean' => $assignedType instanceof BoolType,
            'float', 'double', 'real' => $assignedType instanceof FloatType,
            'string' => $assignedType instanceof StringType,
            default => false,
        };
    }


    /**
     * Laravel preserves `null` through primitive and enum casts, but a class
     * caster receives `null` in `get()`/`set()` and can transform it.
     */
    private function castPreservesNull(string $cast): bool
    {
        $castClassName = explode(':', $cast, 2)[0];

        return ! class_exists($castClassName) || enum_exists($castClassName);
    }


    protected function hasSetMutator(Model $model, string $key): bool
    {
        return method_exists($model, 'set' . Str::studly($key) . 'Attribute')
            || $this->hasAttributeStyleMutator($model, $key);
    }


    private function hasAttributeStyleMutator(Model $model, string $key): bool
    {
        $accessorName = Str::camel($key);

        if (! method_exists($model, $accessorName)) {
            return false;
        }

        $returnType = new ReflectionMethod($model, $accessorName)->getReturnType();

        return $returnType instanceof ReflectionNamedType && $returnType->getName() === Attribute::class;
    }


    protected function makeModel(string $className): ?Model
    {
        try {
            $model = app()->make($className);

        } catch (Throwable) {
            return null;
        }

        return $model instanceof Model ? $model : null;
    }


    /**
     * Applies Laravel serialization semantics (`getArrayableItems()`) to a
     * model variable's resolved properties: `$hidden`/`$visible` exclusions
     * are dropped, and attributes whose values Laravel transforms on write
     * (casts, dates, set mutators) or on read (get accessors) keep the
     * class-level attribute type instead of a recorded assigned value type.
     *
     * @param array<string, Type> $properties
     * @return array<string, Type>
     */
    protected function normalizeSerializedModelProperties(Scope $scope, string $modelClassName, array $properties): array
    {
        if (! is_subclass_of($modelClassName, Model::class)) {
            return $properties;
        }

        $model = $this->makeModel($modelClassName);

        if (! $model) {
            return $properties;
        }

        $classLevelModelType = null;

        foreach ($properties as $key => $propertyType) {
            if ($this->isModelAttributeHidden($model, $key)) {
                unset($properties[$key]);

                continue;
            }

            $transformedOnRead = $this->isAttributeTransformedOnRead($model, $key);

            if (! $transformedOnRead && ! $this->isAttributeValueTransformed($model, $key)) {
                continue;
            }

            $propertyType = $propertyType->unwrapType($scope->config);

            if (! $transformedOnRead && $this->castKeepsAssignedValueType($model, $key, $propertyType)) {
                continue;
            }

            $classLevelModelType ??= (new EloquentModel)->getReturnType($scope->getPhpClassInDeeperScope($modelClassName));

            $classPropertyType = $classLevelModelType instanceof ObjectType
                ? $classLevelModelType->properties[$key] ?? null
                : null;

            $properties[$key] = ($classPropertyType ? clone $classPropertyType : new UnknownType)
                ->setRequired($propertyType->required);
        }

        return $properties;
    }
}
