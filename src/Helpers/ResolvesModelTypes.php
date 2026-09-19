<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use Illuminate\Database\Eloquent\Model;

trait ResolvesModelTypes
{
    protected function resolveModelObjectType(Type $type): ?ObjectType
    {
        $objectType = $this->resolveSingleObjectType($type);

        if (! $objectType || ! $this->isModelClassName($objectType->className)) {
            return null;
        }

        return $objectType;
    }


    /**
     * @return ?class-string<Model>
     */
    protected function resolveModelClassName(Type $type): ?string
    {
        $className = $this->resolveSingleObjectType($type)?->className;

        if (! $this->isModelClassName($className)) {
            return null;
        }

        return $className;
    }


    protected function typeIncludesNull(Type $type): bool
    {
        if ($type instanceof NullType) {
            return true;
        }

        return $type instanceof UnionType
            && array_any($type->types, $this->typeIncludesNull(...));
    }


    /**
     * Nullable relation receivers expose one object alongside null, so reject
     * unions that do not resolve to exactly one object type.
     */
    private function resolveSingleObjectType(Type $type): ?ObjectType
    {
        $variants = $type instanceof UnionType ? $type->types : [$type];
        $objectType = null;

        foreach ($variants as $variant) {
            if ($variant instanceof NullType) {
                continue;
            }

            if (! ($variant instanceof ObjectType) || $objectType !== null) {
                return null;
            }

            $objectType = $variant;
        }

        return $objectType;
    }


    /**
     * @phpstan-assert-if-true class-string<Model> $className
     */
    private function isModelClassName(?string $className): bool
    {
        return $className !== null && is_subclass_of($className, Model::class, true);
    }
}
