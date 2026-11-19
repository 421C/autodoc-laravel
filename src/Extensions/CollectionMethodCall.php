<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\IntKeyOrder;
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
use Illuminate\Support\Enumerable;
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

        if (! $this->isLaravelCollection($collectionType)) {
            return;
        }

        $mutatedCollectionType = $this->getCollectionTypeAfterEach($call, $collectionType);

        if (! $mutatedCollectionType) {
            return;
        }

        $call->setVarType($var->name, $mutatedCollectionType);
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        $methodName = $call->methodName;

        $supportedMethodNames = [
            'first', 'last', 'toArray', 'map', 'mapWithKeys', 'filter', 'reject', 'pluck', 'flatten',
            'groupBy', 'sortBy', 'sortByDesc', 'take', 'skip', 'get', 'keyBy', 'values', 'sum',
            'count', 'avg', 'average', 'isEmpty', 'isNotEmpty', 'contains', 'implode',
            'unique', 'reverse', 'slice', 'sortDesc', 'each',
            'min', 'max', 'median', 'join', 'every', 'some', 'doesntContain', 'containsStrict',
            'sole', 'firstOrFail', 'keys',
            'where', 'whereIn', 'whereNotIn', 'whereNull', 'whereNotNull', 'whereBetween', 'whereNotBetween',
            'sortKeys', 'sortKeysDesc', 'shuffle', 'nth', 'tap',
        ];

        if (! in_array($methodName, $supportedMethodNames)) {
            return null;
        }

        $varType = $call->getVarType();

        if (! $this->isLaravelCollection($varType)) {
            $foundCollection = false;

            if ($varType instanceof UnionType) {
                foreach ($varType->types as $typeInUnion) {
                    if ($this->isLaravelCollection($typeInUnion)) {
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

        $config = $call->scope->config;
        $collectionType = clone $varType;

        if ($methodName === 'toArray') {
            $collectionType->className = null;

            return $collectionType;
        }

        return match ($methodName) {
            'first', 'last', 'get' => $this->handleSingleEntryWithDefaultValue($call, $collectionType),
            'sole', 'firstOrFail' => $collectionType->getTypeOfEveryEntry($config),
            'map' => $this->handleMapMethod($call, $collectionType),
            'mapWithKeys' => $this->handleMapWithKeysMethod($call, $collectionType),
            'pluck' => $this->handlePluckMethod($call, $collectionType),
            'keyBy' => $this->handleKeyByMethod($call, $collectionType),
            'keys' => new ArrayType(className: Collection::class, itemType: $collectionType->convertShapeToTypePair($config)->keyType ?? new IntegerType),
            'sum' => new NumberType,
            'count' => new IntegerType(minimum: 0),
            'avg', 'average', 'min', 'max', 'median' => new UnionType([new NumberType, new NullType]),
            'isEmpty', 'isNotEmpty', 'contains', 'containsStrict', 'doesntContain', 'every', 'some' => new BoolType,
            'implode', 'join' => new StringType,
            'each' => $this->getCollectionTypeAfterEach($call, $collectionType) ?? $collectionType,
            'values', 'flatten', 'shuffle' => $collectionType->toList($config),
            'nth' => $collectionType->markItemsOptional()->toList($config),
            'filter', 'reject', 'unique', 'skip', 'slice',
            'where', 'whereIn', 'whereNotIn', 'whereNull', 'whereNotNull', 'whereBetween', 'whereNotBetween' => $collectionType->markItemsOptional(),
            'take' => $this->mayTakeFromEnd($call) ? $collectionType->markItemsOptional() : $collectionType,
            'sortBy', 'sortByDesc', 'sortDesc', 'reverse', 'sortKeysDesc' => $collectionType->reorderItems($config),
            'groupBy', 'sortKeys', 'tap' => $collectionType,
        };
    }


    private function mayTakeFromEnd(MethodCallContext $call): bool
    {
        $limit = $call->argTypes->has(0) ? $call->argTypes->get(0)->unwrapType($call->scope->config) : null;

        if (! $limit instanceof IntegerType) {
            return true;
        }

        $values = $limit->getPossibleValues();

        return $values === null
            ? $limit->minimum === null || $limit->minimum < 0
            : array_any($values, fn (int $value) => $value < 0);
    }


    /**
     * `LazyCollection` shares the `Enumerable` surface but not the `Collection`
     * class, so a `cursor()`/`lazy()` result is handled here too.
     *
     * @phpstan-assert-if-true ArrayType $type
     */
    private function isLaravelCollection(Type $type): bool
    {
        return $type instanceof ArrayType
            && $type->className !== null
            && is_a($type->className, Enumerable::class, true);
    }


    private function getCollectionTypeAfterEach(MethodCallContext $call, ArrayType $collectionType): ?ArrayType
    {
        $foldedCollectionType = (clone $collectionType)->convertShapeToTypePair($call->scope->config);
        $mutatedItemType = $this->getEachMutatedItemType($call, $foldedCollectionType);

        if (! $mutatedItemType) {
            return null;
        }

        $foldedCollectionType->itemType = $mutatedItemType;

        return $foldedCollectionType;
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
        $itemType = $collectionType->convertShapeToTypePair($call->scope->config)->itemType;

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
            $collectionType->convertShapeToTypePair($call->scope->config);
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
            $collectionType->convertShapeToTypePair($call->scope->config);
            $returnType = $callbackType->getReturnType(
                ArgumentList::fromTypes([
                    $collectionType->itemType ?? new UnknownType,
                    $collectionType->keyType ?? new IntegerType,
                ], $call->scope),
                $call->node,
            );

            if ($returnType instanceof UnionType) {
                $returnType = clone $returnType;

                foreach ($returnType->types as $variantIndex => $returnTypeVariant) {
                    if ($returnTypeVariant instanceof ArrayType) {
                        $returnType->types[$variantIndex] = $this->asCollection($returnTypeVariant);
                    }
                }

                return $returnType;
            }

            if ($returnType instanceof ArrayType) {
                return $this->asCollection($returnType);
            }
        }

        return new ArrayType(className: Collection::class);
    }


    private function asCollection(ArrayType $arrayType): ArrayType
    {
        $collectionType = clone $arrayType;
        $collectionType->className = Collection::class;

        return $collectionType;
    }


    private function handlePluckMethod(MethodCallContext $call, ArrayType $collectionType): ArrayType
    {
        $collectionType->convertShapeToTypePair($call->scope->config);

        if (! $collectionType->itemType) {
            return new ArrayType(className: Collection::class);
        }

        if (! $call->argTypes->has(0)) {
            return new ArrayType(className: Collection::class);
        }

        $scope = $call->scope;
        $keyArgType = $call->argTypes->has(1) ? $call->argTypes->get(1)->unwrapType($scope->config) : new NullType;
        $isKeyed = ! $keyArgType instanceof NullType;
        $keyType = $isKeyed ? $this->getCollectionItemPropertyType($collectionType, $keyArgType, $scope) : null;

        $columnArgType = $call->argTypes->get(0);
        $resultItemType = $this->getCollectionItemPropertyType($collectionType, $columnArgType, $scope, nullWhenMissing: true);

        if (! $resultItemType) {
            return new ArrayType(className: Collection::class);
        }

        return new ArrayType(
            className: Collection::class,
            itemType: $resultItemType->unwrapType($scope->config),
            keyType: $keyType?->unwrapType($scope->config),
            intKeyOrder: $isKeyed ? IntKeyOrder::NonSequential : IntKeyOrder::Sequential,
        );
    }


    private function handleKeyByMethod(MethodCallContext $call, ArrayType $collectionType): ArrayType
    {
        $scope = $call->scope;
        $collectionType->convertShapeToTypePair($scope->config);

        $keyType = $collectionType->itemType !== null && $call->argTypes->has(0)
            ? $this->getCollectionItemPropertyType($collectionType, $call->argTypes->get(0), $scope)
            : null;

        return new ArrayType(
            itemType: $collectionType->itemType,
            keyType: $keyType?->unwrapType($scope->config),
            className: $collectionType->className,
            intKeyOrder: IntKeyOrder::NonSequential,
        );
    }


    private function getCollectionItemPropertyType(ArrayType $collectionType, Type $columnNameType, Scope $scope, bool $nullWhenMissing = false): ?Type
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

            $itemType = $collectionType->itemType?->unwrapType($scope->config);
            $itemTypeVariants = $itemType instanceof UnionType ? $itemType->types : [$itemType];

            $valueTypeVariants = [];

            foreach ($itemTypeVariants as $itemTypeVariant) {
                if ($itemTypeVariant instanceof ArrayType) {
                    $knownValueType = $itemTypeVariant->shape[$columnName] ?? null;
                    $valueType = $knownValueType ?? $itemTypeVariant->getTypeOfEntryOutsideShapeAt($columnName, $scope->config);

                    $valueTypeVariants[] = $valueType ?? new UnknownType;

                    if ($nullWhenMissing && $valueType !== null && ($knownValueType === null || ! $knownValueType->required)) {
                        $valueTypeVariants[] = new NullType;
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
