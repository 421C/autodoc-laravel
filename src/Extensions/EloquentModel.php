<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\PhpClass;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\FloatType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\DataTypes\UnresolvedClassType;
use AutoDoc\Exceptions\AutoDocException;
use AutoDoc\Extensions\ClassExtension;
use AutoDoc\Laravel\Helpers\InspectsModelAttributes;
use AutoDoc\Laravel\Helpers\ModelResolver;
use AutoDoc\Laravel\QueryBuilder\EagerLoad;
use AutoDoc\Laravel\QueryBuilder\Relation;
use AutoDoc\Laravel\QueryBuilder\RelationAggregate;
use AutoDoc\Laravel\QueryBuilder\TableSchema;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Handles `Illuminate\Database\Eloquent\Model` converting to ObjectType.
 */
class EloquentModel extends ClassExtension
{
    use InspectsModelAttributes;

    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if (! is_subclass_of($phpClass->className, Model::class)) {
            return null;
        }

        /** @var PhpClass<Model> $phpClass */

        if (isset(EloquentModel::$cache[$phpClass->className])) {
            return $this->copyModelType(EloquentModel::$cache[$phpClass->className]);
        }

        $modelType = $this->getModelObjectType($phpClass);

        $modelType->typeToDisplay = $this->getTypeFromToArrayMethod($phpClass);

        EloquentModel::$cache[$phpClass->className] = $modelType;

        return $this->copyModelType($modelType);
    }


    private function copyModelType(ObjectType $modelType): ObjectType
    {
        $copy = clone $modelType;

        $copy->properties = array_map(fn (Type $type) => clone $type, $copy->properties);
        $copy->hiddenProperties = array_map(fn (Type $type) => clone $type, $copy->hiddenProperties);

        return $copy;
    }


    public function getPropertyType(PhpClass $phpClass, string $propertyName): ?Type
    {
        if (! is_subclass_of($phpClass->className, Model::class)) {
            return null;
        }

        /** @var PhpClass<Model> $phpClass */

        $objectType = $this->getReturnType($phpClass);

        if (! ($objectType instanceof ObjectType)) {
            return null;
        }

        $propertyType = $objectType->properties[$propertyName]
            ?? $objectType->hiddenProperties[$propertyName]
            ?? $this->getRelationType($phpClass, $propertyName);

        if (! $propertyType) {
            $accessorType = $this->getAccessorType($phpClass, $propertyName, new UnknownType);

            return $accessorType ? clone $accessorType : null;
        }

        return clone $this->getModelAttributeType($phpClass, $propertyName, $propertyType);
    }


    /**
     * @param PhpClass<Model> $phpClass
     */
    private function getModelAttributeType(PhpClass $phpClass, string $propertyName, Type $propertyType): Type
    {
        return $this->getAccessorType($phpClass, $propertyName, $propertyType) ?? $propertyType;
    }


    /**
     * @param PhpClass<Model> $phpClass
     */
    private function getAccessorType(PhpClass $phpClass, string $propertyName, Type $rawValueType): ?Type
    {
        $accessorName = Str::camel($propertyName);

        $getMutatorName = 'get' . ucfirst($accessorName) . 'Attribute';

        if ($phpClass->getReflection()->hasMethod($getMutatorName)) {
            $args = ArgumentList::fromTypes([$rawValueType], $phpClass->scope);

            return $phpClass->getMethod($getMutatorName, $args)->getReturnType();
        }

        if ($phpClass->getReflection()->hasMethod($accessorName)) {
            $attributeMutatorMethod = $phpClass->getReflection()->getMethod($accessorName);

            $methodReturnType = $attributeMutatorMethod->getReturnType();

            if ($methodReturnType instanceof ReflectionNamedType && $methodReturnType->getName() === Attribute::class) {
                return new UnknownType;
            }
        }

        return null;
    }


    /**
     * Check if model has a toArray() method with an understandable return type.
     *
     * @param PhpClass<Model> $phpClass
     */
    private function getTypeFromToArrayMethod(PhpClass $phpClass): ?ArrayType
    {
        $modelToArrayMethod = $phpClass->getMethod('toArray');

        $modelToArrayMethodReflection = $modelToArrayMethod->getReflection();
        $modelToArrayMethodDeclaringClass = $modelToArrayMethodReflection instanceof ReflectionMethod
            ? $modelToArrayMethodReflection->getDeclaringClass()->getName()
            : null;

        if ($modelToArrayMethodDeclaringClass && $modelToArrayMethodDeclaringClass !== Model::class) {
            $modelArrayRepresentation = $modelToArrayMethod->getReturnType()->unwrapType($phpClass->scope->config);

            if ($modelArrayRepresentation instanceof ArrayType) {
                if ($modelArrayRepresentation->shape || isset($modelArrayRepresentation->itemType)) {
                    return $modelArrayRepresentation;
                }
            }
        }

        return null;
    }


    public function getModelAttributesArrayType(Scope $scope, string $modelClassName): ?ArrayType
    {
        if (! is_subclass_of($modelClassName, Model::class)) {
            return null;
        }

        $phpClass = $scope->getPhpClassInDeeperScope($modelClassName);

        /** @var PhpClass<Model> $phpClass */

        return new ArrayType(shape: $this->getModelObjectType($phpClass)->properties);
    }


    /**
     * @param PhpClass<Model> $phpClass
     */
    private function getModelObjectType(PhpClass $phpClass): ObjectType
    {
        $objectType = new ObjectType(className: $phpClass->className);

        $offlineMode = config('autodoc.laravel.offline_mode') ?? false;

        try {
            $model = ModelResolver::resolve($phpClass->className) ?? throw new Exception('Model could not be instantiated');

            $columns = $offlineMode
                ? []
                : $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());

        } catch (Throwable $exception) {
            if ($phpClass->scope->isDebugModeEnabled()) {
                throw new AutoDocException('Error reading database model properties for ' . $phpClass->className . ': ', $exception);
            }

            return $objectType;
        }

        $modelCasts = array_merge(
            array_map(
                fn () => 'datetime',
                array_flip(array_filter($model->getDates()))
            ),
            $model->getCasts(),
        );

        $primaryKeyName = $model->getKeyName();

        if ($offlineMode && $primaryKeyName !== '' && ! isset($modelCasts[$primaryKeyName])) {
            $modelCasts[$primaryKeyName] = $model->getKeyType();
        }

        foreach ($columns as $column) {
            /**
             * @var string
             */
            $propertyName = $column['name'];

            if (isset($modelCasts[$propertyName])) {
                $propertyType = $this->getTypeFromCast($modelCasts[$propertyName], $phpClass, $column['type_name']);

            } else {
                $propertyType = TableSchema::typeFromColumnTypeName($column['type_name']);
            }

            if ($column['nullable']) {
                $propertyType = new UnionType([$propertyType, new NullType]);
            }

            $propertyType = $this->getModelAttributeType($phpClass, $propertyName, $propertyType);

            if ($this->isModelAttributeHidden($model, $propertyName)) {
                $objectType->hiddenProperties[$propertyName] = $propertyType;

            } else {
                $objectType->properties[$propertyName] = $propertyType->setRequired(true);
            }
        }

        if ($offlineMode) {
            foreach ($modelCasts as $propertyName => $cast) {
                if (isset($objectType->properties[$propertyName]) || isset($objectType->hiddenProperties[$propertyName])) {
                    continue;
                }

                $propertyType = $this->getTypeFromCast($cast, $phpClass, '');
                $propertyType = $this->getModelAttributeType($phpClass, $propertyName, $propertyType);

                if ($this->isModelAttributeHidden($model, $propertyName)) {
                    $objectType->hiddenProperties[$propertyName] = $propertyType;

                } else {
                    $objectType->properties[$propertyName] = $propertyType->setRequired(true);
                }
            }
        }

        foreach ($model->getAppends() as $appendedAttributeName) {
            $attributeType = $objectType->properties[$appendedAttributeName] ?? $objectType->hiddenProperties[$appendedAttributeName] ?? new UnknownType;
            $attributeType = $this->getModelAttributeType($phpClass, $appendedAttributeName, $attributeType);

            if ($this->isModelAttributeHidden($model, $appendedAttributeName)) {
                $objectType->hiddenProperties[$appendedAttributeName] = $attributeType;

            } else {
                $objectType->properties[$appendedAttributeName] = $attributeType->setRequired(true);
            }
        }

        $modelProperties = $phpClass->handlePhpDocPropertyTags(array_merge(
            $objectType->properties,
            $objectType->hiddenProperties,
        ));

        $objectType->properties = [];
        $objectType->hiddenProperties = [];

        foreach ($modelProperties as $propertyName => $propertyType) {
            if ($this->isModelAttributeHidden($model, $propertyName)) {
                $objectType->hiddenProperties[$propertyName] = $propertyType;

            } else {
                $objectType->properties[$propertyName] = $propertyType;
            }
        }

        $this->addDefaultEagerLoads($objectType, $phpClass, $model);

        return $objectType;
    }


    /**
     * A model's `$with` and `$withCount` apply to every query of it, so their
     * relations and counts are part of the shape it serializes to.
     *
     * Two models naming each other would resolve forever, so a class already
     * being resolved contributes its own columns without its defaults.
     *
     * @param PhpClass<Model> $phpClass
     */
    private function addDefaultEagerLoads(ObjectType $objectType, PhpClass $phpClass, Model $model): void
    {
        if (isset(EloquentModel::$resolvingDefaultEagerLoads[$phpClass->className])) {
            return;
        }

        EloquentModel::$resolvingDefaultEagerLoads[$phpClass->className] = true;

        try {
            $defaults = array_merge(
                EagerLoad::defaultRelationTypes($phpClass->scope, $phpClass->className),
                RelationAggregate::defaultCountColumns($phpClass),
            );

        } finally {
            unset(EloquentModel::$resolvingDefaultEagerLoads[$phpClass->className]);
        }

        foreach ($defaults as $propertyName => $propertyType) {
            if (isset($objectType->properties[$propertyName]) || isset($objectType->hiddenProperties[$propertyName])) {
                continue;
            }

            if ($this->isModelAttributeHidden($model, $propertyName)) {
                $objectType->hiddenProperties[$propertyName] = $propertyType;

            } else {
                $objectType->properties[$propertyName] = $propertyType->setRequired(true);
            }
        }
    }


    /**
     * @param PhpClass<Model> $phpClass
     */
    private function getTypeFromCast(string $cast, PhpClass $phpClass, string $typeName): Type
    {
        $propertyType = match ($cast) {
            'array' => new ArrayType,
            'bool', 'boolean' => new BoolType,
            'collection' => new ArrayType(className: Collection::class),
            'date' => new StringType(format: 'date'),
            'datetime' => new StringType(format: 'date-time'),
            'immutable_date' => new StringType(format: 'date'),
            'immutable_datetime' => new StringType(format: 'date-time'),
            'double' => new FloatType,
            'encrypted' => new StringType,
            'encrypted:array' => new ArrayType,
            'encrypted:collection' => new ArrayType(className: Collection::class),
            'encrypted:object' => new ObjectType,
            'float' => new FloatType,
            'hashed' => new StringType,
            'int', 'integer' => new IntegerType,
            'object' => new ObjectType,
            'real' => new FloatType,
            'string' => new StringType,
            'timestamp' => new IntegerType,
            'Illuminate\Database\Eloquent\Casts\AsArrayObject' => new ObjectType,
            'Illuminate\Database\Eloquent\Casts\AsCollection' => new ArrayType(className: Collection::class),
            'Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject' => new ObjectType,
            'Illuminate\Database\Eloquent\Casts\AsEncryptedCollection' => new ArrayType(className: Collection::class),
            'Illuminate\Database\Eloquent\Casts\AsEnumArrayObject' => new ObjectType,
            'Illuminate\Database\Eloquent\Casts\AsEnumCollection' => new ArrayType(className: Collection::class),
            'Illuminate\Database\Eloquent\Casts\AsStringable' => new StringType,
            default => new UnknownType,
        };

        if ($propertyType instanceof UnknownType && class_exists($cast)) {
            if (enum_exists($cast) || is_a($cast, 'Illuminate\Contracts\Database\Eloquent\Castable', true)) {
                $propertyType = new UnresolvedClassType(className: $cast, scope: $phpClass->scope);

            } else if (is_a($cast, 'Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes', true)) {
                $propertyType = TableSchema::typeFromColumnTypeName($typeName);

            } else if (is_a($cast, 'Illuminate\Contracts\Database\Eloquent\CastsAttributes', true)) {
                $propertyType = $phpClass->scope->getPhpClassInDeeperScope($cast)->getMethod('get')->getReturnType();
            }
        }

        return $propertyType;
    }




    /**
     * @param PhpClass<Model> $phpClass
     */
    private function getRelationType(PhpClass $phpClass, string $relationName): ?Type
    {
        return new Relation(modelPhpClass: $phpClass, name: $relationName)->resolveType();
    }


    public static function clearCache(): void
    {
        EloquentModel::$cache = [];

        Relation::clearCache();

        ModelResolver::clearCache();

        TableSchema::clearCache();
    }


    /**
     * @var array<class-string<Model>, ObjectType>
     */
    private static array $cache = [];

    /**
     * @var array<class-string<Model>, true>
     */
    private static array $resolvingDefaultEagerLoads = [];
}
