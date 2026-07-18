<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\CallableType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\NumberType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\Variable;

/**
 * Handles method calls on Laravel Collection classes.
 */
class CollectionMethodCall extends MethodCallExtension
{
    public function handleSideEffect(MethodCallContext $call): void
    {
        if ($call->methodName !== 'each') {
            return;
        }

        $var = $call->node->var;

        if (! ($var instanceof Variable) || ! is_string($var->name)) {
            return;
        }

        $collectionType = $call->getVarType();

        if (! ($collectionType instanceof ArrayType)
            || ! $collectionType->className
            || ! is_a($collectionType->className, Collection::class, true)
        ) {
            return;
        }

        $mutatedItemType = $this->getEachMutatedItemType($call, $collectionType);

        if (! $mutatedItemType) {
            return;
        }

        $newCollectionType = clone $collectionType;
        $newCollectionType->itemType = $mutatedItemType;

        $call->setVarType($var->name, $newCollectionType);
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        $methodName = $call->methodName;

        $supportedMethodNames = [
            'first', 'last', 'toArray', 'map', 'mapWithKeys', 'filter', 'reject', 'pluck', 'flatten',
            'groupBy', 'sortBy', 'sortByDesc', 'take', 'skip', 'get', 'keyBy', 'values', 'sum',
            'count', 'avg', 'average', 'isEmpty', 'isNotEmpty', 'contains', 'implode',
            'unique', 'reverse', 'slice', 'sortDesc', 'each',
        ];

        if (! in_array($methodName, $supportedMethodNames)) {
            return null;
        }

        $varType = $call->getVarType();

        $isLaravelCollection = fn (Type $type): bool => $type instanceof ArrayType
            && $type->className
            && is_a($type->className, Collection::class, true);

        if (! $isLaravelCollection($varType)) {
            $foundCollection = false;

            if ($varType instanceof UnionType) {
                foreach ($varType->types as $typeInUnion) {
                    if ($isLaravelCollection($typeInUnion)) {
                        $varType = $typeInUnion;
                        $foundCollection = true;
                        break;
                    }
                }
            }

            if (! $foundCollection) {
                return null;
            }
        }

        if (! ($varType instanceof ArrayType)) {
            return null;
        }

        if ($methodName === 'toArray') {
            $varType->className = null;

            return $varType;
        }

        if ($methodName === 'values') {
            $varType->keyType = null;

            return $varType;
        }

        return match ($methodName) {
            'first', 'last', 'get' => $this->handleSingleEntryWithDefaultValue($call, $varType),
            'map' => $this->handleMapMethod($call, $varType),
            'mapWithKeys' => $this->handleMapWithKeysMethod($call, $varType),
            'pluck' => $this->handlePluckMethod($call, $varType),
            'sum' => new NumberType,
            'count' => new IntegerType(minimum: 0),
            'avg', 'average' => new UnionType([new NumberType, new NullType]),
            'isEmpty', 'isNotEmpty', 'contains' => new BoolType,
            'implode' => new StringType,
            'each' => $this->handleEachMethod($call, $varType),
            'filter', 'reject', 'flatten', 'groupBy', 'sortBy', 'sortByDesc', 'take', 'skip', 'keyBy',
            'unique', 'reverse', 'slice', 'sortDesc' => $varType,
        };
    }


    private function handleEachMethod(MethodCallContext $call, ArrayType $collectionType): ArrayType
    {
        $mutatedItemType = $this->getEachMutatedItemType($call, $collectionType);

        if (! $mutatedItemType) {
            return $collectionType;
        }

        $newCollectionType = clone $collectionType;
        $newCollectionType->itemType = $mutatedItemType;

        return $newCollectionType;
    }


    /**
     * Resolves surviving object mutations from an `each()` callback, accounting
     * for pass-by-value items and early termination on `false`.
     */
    private function getEachMutatedItemType(MethodCallContext $call, ArrayType $collectionType): ?Type
    {
        if (! $call->argTypes->has(0)) {
            return null;
        }

        $callbackType = $call->argTypes->get(0);
        $originalItemType = $collectionType->itemType;

        if (! ($callbackType instanceof CallableType)
            || ! ($originalItemType instanceof ObjectType)
            || $originalItemType->className === null
        ) {
            return null;
        }

        $args = ArgumentList::fromTypes([
            $originalItemType,
            $collectionType->keyType ?? new IntegerType,
        ], $call->scope);

        $itemTypeAfterCallback = $callbackType->resolveParameterTypeAfterInvocation(
            parameterIndex: 0,
            args: $args,
            callerNode: $call->node,
        );

        if (! ($itemTypeAfterCallback instanceof ObjectType)
            || $itemTypeAfterCallback->className !== $originalItemType->className
        ) {
            return null;
        }

        if ($this->callbackMayReturnFalse($callbackType, $args, $call)) {
            foreach ($itemTypeAfterCallback->properties as $key => $propertyType) {
                $originalPropertyType = $originalItemType->properties[$key] ?? null;

                if ($originalPropertyType === null) {
                    $itemTypeAfterCallback->properties[$key] = (clone $propertyType)->setRequired(false);

                } else if ($originalPropertyType !== $propertyType) {
                    $itemTypeAfterCallback->properties[$key] = (new UnionType([clone $originalPropertyType, clone $propertyType]))
                        ->unwrapType($call->scope->config)
                        ->setRequired($originalPropertyType->required);
                }
            }
        }

        return $itemTypeAfterCallback;
    }


    /**
     * Whether the `each()` callback may return `false`, which stops Laravel's
     * iteration and leaves later items unmutated.
     */
    private function callbackMayReturnFalse(CallableType $callbackType, ArgumentList $args, MethodCallContext $call): bool
    {
        $returnType = $callbackType->getReturnType($args, $call->node)->unwrapType($call->scope->config);

        $variants = $returnType instanceof UnionType ? $returnType->types : [$returnType];

        foreach ($variants as $variant) {
            if ($variant instanceof BoolType && $variant->value !== true) {
                return true;
            }
        }

        return false;
    }


    private function handleSingleEntryWithDefaultValue(MethodCallContext $call, ArrayType $collectionType): Type
    {
        $itemType = $collectionType->itemType;

        if (! $itemType) {
            return new UnknownType;
        }

        $defaultValueType = $call->argTypes->has(1) ? $call->argTypes->get(1) : new NullType;

        $returnType = new UnionType([$itemType, $defaultValueType]);

        return $returnType->unwrapType($call->scope->config);
    }


    private function handleMapMethod(MethodCallContext $call, ArrayType $collectionType): ArrayType
    {
        if (! $call->argTypes->has(0)) {
            return $collectionType;
        }

        $callbackType = $call->argTypes->get(0);

        if ($callbackType instanceof CallableType) {
            $collectionType->shape = [];
            $collectionType->itemType = $callbackType->getReturnType(
                ArgumentList::fromTypes([
                    $collectionType->itemType ?? new UnknownType,
                    $collectionType->keyType ?? new IntegerType,
                ], $call->scope),
                $call->node,
            );

            return $collectionType;
        }

        return new ArrayType(className: Collection::class);
    }


    private function handleMapWithKeysMethod(MethodCallContext $call, ArrayType $collectionType): Type
    {
        if (! $call->argTypes->has(0)) {
            return $collectionType;
        }

        $callbackType = $call->argTypes->get(0);

        if ($callbackType instanceof CallableType) {
            $returnType = $callbackType->getReturnType(
                ArgumentList::fromTypes([
                    $collectionType->itemType ?? new UnknownType,
                    $collectionType->keyType ?? new IntegerType,
                ], $call->scope),
                $call->node,
            );

            if ($returnType instanceof UnionType) {
                foreach ($returnType->types as $variantIndex => $returnTypeVariant) {
                    if ($returnType->types[$variantIndex] instanceof ArrayType) {
                        $returnType->types[$variantIndex]->className = Collection::class;
                    }
                }

                return $returnType;
            }

            if ($returnType instanceof ArrayType) {
                $returnType->className = Collection::class;

                return $returnType;
            }
        }

        return new ArrayType(className: Collection::class);
    }

    private function handlePluckMethod(MethodCallContext $call, ArrayType $collectionType): ArrayType
    {
        if (! $collectionType->itemType) {
            return new ArrayType(className: Collection::class);
        }

        if (! $call->argTypes->has(0)) {
            return new ArrayType(className: Collection::class);
        }

        $scope = $call->scope;
        $keyType = null;

        if ($call->argTypes->has(1)) {
            $keyArgType = $call->argTypes->get(1);
            $keyType = $this->getCollectionItemPropertyType($collectionType, $keyArgType, $scope);
        }

        $columnArgType = $call->argTypes->get(0);
        $resultItemType = $this->getCollectionItemPropertyType($collectionType, $columnArgType, $scope);

        if (! $resultItemType) {
            return new ArrayType(className: Collection::class);
        }

        return new ArrayType(
            className: Collection::class,
            itemType: $resultItemType->unwrapType($scope->config),
            keyType: $keyType?->unwrapType($scope->config),
        );
    }


    private function getCollectionItemPropertyType(ArrayType $collectionType, Type $columnNameType, Scope $scope): ?Type
    {
        $columnVariants = [];

        if ($columnNameType instanceof StringType) {
            $columnVariants = $columnNameType->getPossibleValues() ?? [];

        } else if ($columnNameType instanceof UnionType) {
            foreach ($columnNameType->types as $variantIndex => $variantType) {
                if ($variantType instanceof StringType) {
                    $columnVariants = array_merge($columnVariants, $variantType->getPossibleValues() ?? []);

                } else {
                    return null;
                }
            }

        } else {
            return null;
        }

        $resultItemType = new UnionType;

        foreach ($columnVariants as $columnName) {
            $columnType = new UnionType;

            $itemTypeVariants = $collectionType->itemType instanceof UnionType
                ? $collectionType->itemType->types
                : [$collectionType->itemType];

            $valueTypeVariants = [];

            foreach ($itemTypeVariants as $itemTypeVariant) {
                if ($itemTypeVariant instanceof ArrayType) {
                    if ($itemTypeVariant->shape) {
                        $valueTypeVariants[] = $itemTypeVariant->shape[$columnName] ?? new UnknownType;

                    } else {
                        $valueTypeVariants[] = $itemTypeVariant->itemType ?? new UnknownType;
                    }

                } else if ($itemTypeVariant instanceof ObjectType) {
                    $valueTypeVariants[] = $itemTypeVariant->properties[$columnName] ?? new UnknownType;
                }
            }

            $resultItemType->types[] = (new UnionType($valueTypeVariants))->unwrapType($scope->config);
        }

        return $resultItemType->unwrapType($scope->config);
    }
}
