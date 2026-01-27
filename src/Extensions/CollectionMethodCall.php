<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpFunctionArgument;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\CallableType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;

/**
 * Handles method calls on Laravel Collection classes.
 */
class CollectionMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if (! ($methodCall->name instanceof Node\Identifier)) {
            return null;
        }

        $methodName = $methodCall->name->name;

        $supportedMethodNames = [
            'first', 'last', 'toArray', 'map', 'mapWithKeys', 'filter', 'reject', 'pluck', 'flatten',
            'groupBy', 'sortBy', 'sortByDesc', 'take', 'skip', 'get', 'keyBy', 'values',
        ];

        if (! in_array($methodName, $supportedMethodNames)) {
            return null;
        }

        $varType = $scope->resolveType($methodCall->var);

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
            'first', 'last', 'get' => $this->handleSingleEntryWithDefaultValue($methodCall, $varType, $scope),
            'map' => $this->handleMapMethod($methodCall, $varType, $scope),
            'mapWithKeys' => $this->handleMapWithKeysMethod($methodCall, $varType, $scope),
            'pluck' => $this->handlePluckMethod($methodCall, $varType, $scope),
            'filter', 'reject', 'flatten', 'groupBy', 'sortBy', 'sortByDesc', 'take', 'skip', 'keyBy' => $varType,
        };
    }


    private function handleSingleEntryWithDefaultValue(MethodCall $methodCall, ArrayType $collectionType, Scope $scope): Type
    {
        $itemType = $collectionType->itemType;

        if (! $itemType) {
            return new UnknownType;
        }

        $defaultValueArg = $methodCall->args[1] ?? null;
        $defaultValueType = new NullType;

        if ($defaultValueArg) {
            $defaultValueType = $scope->resolveType($defaultValueArg);
        }

        $returnType = new UnionType([$itemType, $defaultValueType]);

        return $returnType->unwrapType($scope->config);
    }


    private function handleMapMethod(MethodCall $methodCall, ArrayType $collectionType, Scope $scope): ArrayType
    {
        $callbackArg = $methodCall->args[0] ?? null;

        if (! ($callbackArg instanceof Node\Arg)) {
            return $collectionType;
        }

        $callbackType = $scope->resolveType($callbackArg->value);

        if ($callbackType instanceof CallableType) {
            $collectionType->shape = [];
            $collectionType->itemType = $callbackType->getReturnType(
                args: [
                    new PhpFunctionArgument($collectionType->itemType ?? new UnknownType, $scope),
                    new PhpFunctionArgument($collectionType->keyType ?? new IntegerType, $scope),
                ],
                callerNode: $methodCall,
            );

            return $collectionType;
        }

        return new ArrayType(className: Collection::class);
    }


    private function handleMapWithKeysMethod(MethodCall $methodCall, ArrayType $collectionType, Scope $scope): Type
    {
        $callbackArg = $methodCall->args[0] ?? null;

        if (! ($callbackArg instanceof Node\Arg)) {
            return $collectionType;
        }

        $callbackType = $scope->resolveType($callbackArg->value);

        if ($callbackType instanceof CallableType) {
            $returnType = $callbackType->getReturnType(
                args: [
                    new PhpFunctionArgument($collectionType->itemType ?? new UnknownType, $scope),
                    new PhpFunctionArgument($collectionType->keyType ?? new IntegerType, $scope),
                ],
                callerNode: $methodCall,
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

    private function handlePluckMethod(MethodCall $methodCall, ArrayType $collectionType, Scope $scope): ArrayType
    {
        if (! $collectionType->itemType) {
            return new ArrayType(className: Collection::class);
        }

        $columnArg = $methodCall->args[0] ?? null;
        $keyArg = $methodCall->args[1] ?? null;

        if (! ($columnArg instanceof Node\Arg)) {
            return new ArrayType(className: Collection::class);
        }

        $keyType = null;

        if ($keyArg instanceof Node\Arg) {
            $keyArgType = $scope->resolveType($keyArg->value);
            $keyType = $this->getCollectionItemPropertyType($collectionType, $keyArgType, $scope);
        }

        $columnArgType = $scope->resolveType($columnArg->value);
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
