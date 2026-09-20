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


    protected function modelKeyType(Model $model): Type
    {
        return $model->getKeyType() === 'int' ? new IntegerType : new StringType;
    }


    protected function isAttributeValueTransformed(Model $model, string $key): bool
    {
        return $model->hasCast($key)
            || in_array($key, $model->getDates(), true)
            || $this->hasSetMutator($model, $key);
    }


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


    /**
     * Mirrors `getArrayableItems()`. `hiddenProperties` is the receiver's own
     * hidden set, so the class `$hidden`/`$visible` rule only decides keys the
     * class does not declare.
     *
     * @return array<string, Type>
     */
    protected function normalizeSerializedModelProperties(Scope $scope, ObjectType $modelType): array
    {
        $modelClassName = $modelType->className;
        $properties = $modelType->properties;

        if ($modelClassName === null || ! is_subclass_of($modelClassName, Model::class)) {
            return $properties;
        }

        $model = ModelResolver::resolve($modelClassName);

        if (! $model) {
            return $properties;
        }

        $classLevelModelType = null;

        foreach ($properties as $key => $propertyType) {
            if (isset($modelType->hiddenProperties[$key])
                || ($this->isModelAttributeHidden($model, $key)
                    && ! $this->classDeclaresAttribute($scope, $modelClassName, $key))
            ) {
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


    /**
     * @param class-string<Model> $modelClassName
     */
    private function classDeclaresAttribute(Scope $scope, string $modelClassName, string $key): bool
    {
        $classLevelModelType = (new EloquentModel)->getReturnType($scope->getPhpClassInDeeperScope($modelClassName));

        return $classLevelModelType instanceof ObjectType
            && (isset($classLevelModelType->properties[$key]) || isset($classLevelModelType->hiddenProperties[$key]));
    }
}
