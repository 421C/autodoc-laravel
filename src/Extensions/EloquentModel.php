<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
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
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Handles `Illuminate\Database\Eloquent\Model` converting to ObjectType.
 */
class EloquentModel extends ClassExtension
{
    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if (! is_subclass_of($phpClass->className, Model::class)) {
            return null;
        }

        if (isset(EloquentModel::$cache[$phpClass->className])) {
            return EloquentModel::$cache[$phpClass->className];
        }

        $modelType = $this->getTypeFromToArrayMethod($phpClass);

        if (! $modelType) {
            $modelType = $this->getModelObjectType($phpClass);
        }

        EloquentModel::$cache[$phpClass->className] = $modelType;

        return $modelType;
    }


    /**
     * Check if model has a toArray() method with an understandable return type.
     *
     * @param PhpClass<object> $phpClass
     */
    protected function getTypeFromToArrayMethod(PhpClass $phpClass): ?ArrayType
    {
        $modelToArrayMethod = $phpClass->getMethod('toArray');

        $modelToArrayMethodDeclaringClass = $modelToArrayMethod->getReflection()?->class;

        if ($modelToArrayMethodDeclaringClass && $modelToArrayMethodDeclaringClass !== Model::class) {
            $modelArrayRepresentation = $modelToArrayMethod->getReturnType()->unwrapType();

            if ($modelArrayRepresentation instanceof ArrayType) {
                if (isset($modelArrayRepresentation->shape) || isset($modelArrayRepresentation->itemType)) {
                    return $modelArrayRepresentation;
                }
            }
        }

        return null;
    }


    /**
     * @param PhpClass<object> $phpClass
     */
    protected function getModelObjectType(PhpClass $phpClass): ObjectType
    {
        $objectType = new ObjectType(className: $phpClass->className);

        try {
            $model = app()->make($phpClass->className);

            $columns = $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());

        } catch (Throwable $exception) {
            if ($phpClass->scope->isDebugModeEnabled()) {
                throw new AutoDocException('Error reading database model properties for ' . $phpClass->className . ': ', $exception);
            }

            return $objectType;
        }

        $visibleProps = array_flip($model->getVisible());
        $hiddenProps = array_flip($model->getHidden());

        $modelCasts = array_merge(
            array_map(
                fn () => 'datetime',
                array_flip(array_filter($model->getDates()))
            ),
            $model->getCasts(),
        );

        foreach ($columns as $column) {
            /**
             * @var string
             */
            $propertyName = $column['name'];

            if (count($visibleProps) > 0 && ! isset($visibleProps[$propertyName])) {
                continue;
            }

            if (count($hiddenProps) > 0 && isset($hiddenProps[$propertyName])) {
                continue;
            }

            if (isset($modelCasts[$propertyName])) {
                $cast = $modelCasts[$propertyName];

                $propertyType = match ($cast) {
                    'array' => new ArrayType,
                    'boolean' => new BoolType,
                    'collection' => new ArrayType,
                    'date' => new StringType,
                    'datetime' => new StringType,
                    'immutable_date' => new StringType,
                    'immutable_datetime' => new StringType,
                    'double' => new FloatType,
                    'encrypted' => new StringType,
                    'encrypted:array' => new ArrayType,
                    'encrypted:collection' => new ArrayType,
                    'encrypted:object' => new ObjectType,
                    'float' => new FloatType,
                    'hashed' => new StringType,
                    'integer' => new IntegerType,
                    'object' => new ObjectType,
                    'real' => new FloatType,
                    'string' => new StringType,
                    'timestamp' => new IntegerType,
                    'Illuminate\Database\Eloquent\Casts\AsArrayObject' => new ObjectType,
                    'Illuminate\Database\Eloquent\Casts\AsCollection' => new ArrayType,
                    'Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject' => new ObjectType,
                    'Illuminate\Database\Eloquent\Casts\AsEncryptedCollection' => new ArrayType,
                    'Illuminate\Database\Eloquent\Casts\AsEnumArrayObject' => new ObjectType,
                    'Illuminate\Database\Eloquent\Casts\AsEnumCollection' => new ArrayType,
                    'Illuminate\Database\Eloquent\Casts\AsStringable' => new StringType,
                    default => new UnknownType,
                };

                if ($propertyType instanceof UnknownType && class_exists($cast)) {
                    if (enum_exists($cast) || is_a($cast, 'Illuminate\Contracts\Database\Eloquent\Castable', true)) {
                        $propertyType = new UnresolvedClassType(className: $cast, scope: $phpClass->scope);

                    } else if (is_a($cast, 'Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes', true)) {
                        $propertyType = $this->getTypeFromColumnTypeName($column['type_name']);

                    } else if (is_a($cast, 'Illuminate\Contracts\Database\Eloquent\CastsAttributes', true)) {
                        $propertyType = $phpClass->scope->getPhpClassInDeeperScope($cast)->getMethod('get')->getReturnType();
                    }
                }

            } else {
                $propertyType = $this->getTypeFromColumnTypeName($column['type_name']);
            }

            if ($column['nullable']) {
                $propertyType = new UnionType([$propertyType, new NullType]);
            }

            $objectType->properties[$propertyName] = $propertyType;
        }

        foreach ($model->getAppends() as $appendedAttributeName) {
            /**
             * @var string $appendedAttributeName
             */
            $objectType->properties[$appendedAttributeName] ??= new UnknownType;
        }

        $objectType->properties = $phpClass->handlePhpDocPropertyTags($objectType->properties);

        return $objectType;
    }


    private function getTypeFromColumnTypeName(string $typeName): Type
    {
        return match ($typeName) {
            'bit', 'int', 'bigint', 'smallint', 'tinyint', 'integer' => new IntegerType,
            'float', 'double', 'decimal' => new FloatType,
            'string', 'varchar', 'nvarchar', 'text', 'nchar', 'datetime', 'date', 'uniqueidentifier' => new StringType,
            'bool', 'boolean' => new BoolType,
            default => new UnknownType,
        };
    }


    /**
     * @var array<class-string, Type>
     */
    private static array $cache = [];
}
