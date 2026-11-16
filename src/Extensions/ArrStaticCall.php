<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\IntKeyOrder;
use AutoDoc\Config;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\CallableType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NeverType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use Closure;
use Illuminate\Support\Arr;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;

class ArrStaticCall extends StaticCallExtension
{
    private const UNSUPPORTED_DATA_GET_SEGMENTS = ['*', '{first}', '{last}', '\*', '\{first}', '\{last}'];


    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->className === null || ! is_a($call->className, Arr::class, true)) {
            return null;
        }

        return match ($call->methodName) {
            'whereNotNull' => $this->mapArrays($call, fn (ArrayType $array) => $this->whereNotNull($array, $call->scope->config)),
            'where' => $this->mapArrays($call, fn (ArrayType $array) => $this->where($call, $array)),
            'only' => $this->mapArrays($call, fn (ArrayType $array) => $this->only($call, $array)),
            'except' => $this->mapArrays($call, fn (ArrayType $array) => $this->except($call, $array)),
            'map' => $this->mapArrays($call, fn (ArrayType $array) => $this->map($call, $array)),
            'mapWithKeys' => $this->mapArrays($call, fn (ArrayType $array) => $this->mapWithKeys($call, $array)),
            'pluck' => $this->mapArrays($call, fn (ArrayType $array) => $this->pluck($call, $array)),
            'keyBy' => $this->mapArrays($call, fn (ArrayType $array) => $this->keyBy($call, $array)),
            'sortRecursive', 'sortRecursiveDesc' => $this->mapArrays($call, fn (ArrayType $array) => $this->sortRecursive($array, $call->scope->config)),
            'get' => $this->get($call),
            'first' => $this->firstOrLast($call, fromEnd: false),
            'last' => $this->firstOrLast($call, fromEnd: true),
            'wrap' => $this->wrap($call),
            default => null,
        };
    }


    /**
     * @param Closure(ArrayType): Type $transform
     */
    private function mapArrays(StaticCallContext $call, Closure $transform): Type
    {
        $results = [];

        foreach ($this->variants($this->argument($call, 'array', 0), $call->scope->config) as $variant) {
            if ($variant instanceof ArrayType) {
                $array = clone $variant;
                $array->className = null;

                $results[] = $transform($array);
            }
        }

        return $results ? $this->union($results, $call->scope->config) : new ArrayType;
    }


    private function whereNotNull(ArrayType $array, Config $config): ArrayType
    {
        if (! $array->shape) {
            if ($array->itemType === null || ! $this->mayBeNull($array->itemType, $config)) {
                return $array;
            }

            $itemType = $array->itemType->unwrapType($config)->removeNull($config);
            $array->itemType = $itemType instanceof NeverType ? null : $itemType;

            return $array->markItemsOptional();
        }

        foreach ($array->shape as $key => $value) {
            $variants = $this->variants($value, $config);
            $nonNullVariants = array_values(array_filter($variants, fn (Type $variant) => ! $variant instanceof NullType));

            if ($nonNullVariants === []) {
                $array->removeItemFromArray($key);

                continue;
            }

            if (count($nonNullVariants) !== count($variants)) {
                $array->shape[$key] = $this->union($nonNullVariants, $config);
            }

            if ($this->mayBeNull($value, $config)) {
                $array->removeItemFromArray($key, isCertain: false);
            }
        }

        return $array;
    }


    private function where(StaticCallContext $call, ArrayType $array): ArrayType
    {
        $callback = $this->argument($call, 'callback', 1);

        if (! $array->shape) {
            if ($array->itemType && $callback instanceof CallableType) {
                $itemType = $this->narrowEntry($call, $callback, $array->itemType, null);
                $array->itemType = $itemType instanceof NeverType ? null : $itemType;
            }

            return $array->markItemsOptional();
        }

        foreach ($array->shape as $key => $value) {
            $kept = $callback instanceof CallableType ? $this->narrowEntry($call, $callback, $value, $key) : $value;

            if ($kept instanceof NeverType) {
                $array->removeItemFromArray($key);

            } else {
                $array->shape[$key] = $kept;
                $array->removeItemFromArray($key, isCertain: false);
            }
        }

        return $array;
    }


    private function only(StaticCallContext $call, ArrayType $array): ArrayType
    {
        $keys = $this->listedKeys($call);

        if ($keys === null) {
            return $array->markItemsOptional();
        }

        if ($array->shape) {
            foreach (array_keys($array->shape) as $key) {
                if (! ($keys[$key] ?? false)) {
                    $array->removeItemFromArray($key, isCertain: ! isset($keys[$key]));
                }
            }

            return $array;
        }

        if ($array->itemType === null) {
            return $array;
        }

        return new ArrayType(shape: array_fill_keys(array_keys($keys), $array->itemType))->markItemsOptional();
    }


    private function except(StaticCallContext $call, ArrayType $array): ArrayType
    {
        $config = $call->scope->config;
        $keys = $this->listedKeys($call);

        if ($keys === null) {
            return $this->mayListPathKey($this->argument($call, 'keys', 1), $config)
                ? $this->markItemsOptionalAtAnyDepth($array, $config)
                : $array->markItemsOptional();
        }

        foreach ($keys as $key => $isAlwaysListed) {
            $isShapeKey = array_key_exists($key, $array->shape);

            if ($isShapeKey || $array->shape === []) {
                $array->removeItemFromArray($key, isCertain: $isAlwaysListed);
            }

            if (! $isShapeKey && is_string($key) && str_contains($key, '.')) {
                $array = $this->forgetPath($array, explode('.', $key), $isAlwaysListed, $config);
            }
        }

        return $array;
    }


    /**
     * @param non-empty-list<string> $segments
     */
    private function forgetPath(ArrayType $array, array $segments, bool $isCertain, Config $config): ArrayType
    {
        $segment = array_shift($segments);

        if ($segments === []) {
            return $array->removeItemFromArray($segment, $isCertain);
        }

        if ($array->shape === []) {
            if ($array->itemType !== null) {
                $array->itemType = $this->mapArrayVariants(
                    value: $array->itemType,
                    transform: fn (ArrayType $nested) => $this->forgetPath($nested, $segments, isCertain: false, config: $config),
                    config: $config,
                );
            }

            return $array;
        }

        if (isset($array->shape[$segment])) {
            $array->shape[$segment] = $this->mapArrayVariants(
                value: $array->shape[$segment],
                transform: fn (ArrayType $nested) => $this->forgetPath($nested, $segments, $isCertain, $config),
                config: $config,
            );
        }

        return $array;
    }


    private function markItemsOptionalAtAnyDepth(ArrayType $array, Config $config): ArrayType
    {
        return $this->mapNestedArrays(
            array: $array,
            transform: fn (ArrayType $nested) => $this->markItemsOptionalAtAnyDepth($nested, $config),
            config: $config,
        )->markItemsOptional();
    }


    private function mayListPathKey(?Type $keys, Config $config): bool
    {
        $listedTypes = $keys instanceof ArrayType
            ? [...array_values($keys->shape), ...array_filter([$keys->itemType])]
            : array_filter([$keys]);

        foreach ($listedTypes as $listedType) {
            foreach ($this->variants($listedType, $config) as $variant) {
                if ($variant instanceof IntegerType) {
                    continue;
                }

                $values = $variant instanceof StringType ? $variant->getPossibleValues() : null;

                if ($values === null || array_any($values, fn (string $value) => str_contains($value, '.'))) {
                    return true;
                }
            }
        }

        return false;
    }


    private function map(StaticCallContext $call, ArrayType $array): ArrayType
    {
        $callback = $this->argument($call, 'callback', 1);

        if (! $callback instanceof CallableType) {
            return new ArrayType;
        }

        if (! $array->shape) {
            $array->itemType = $this->invoke($call, $callback, [$array->itemType ?? new UnknownType, $array->keyType ?? new IntegerType]);

            return $array;
        }

        foreach ($array->shape as $key => $value) {
            $mappedValue = $this->invoke($call, $callback, [$value->unwrapType($call->scope->config), $this->literalKeyType($key)]);

            $array->shape[$key] = (clone $mappedValue)->setRequired($value->required);
        }

        return $array;
    }


    private function mapWithKeys(StaticCallContext $call, ArrayType $array): Type
    {
        $callback = $this->argument($call, 'callback', 1);

        if (! $callback instanceof CallableType) {
            return new ArrayType;
        }

        $config = $call->scope->config;
        $mayBeEmpty = $this->mayBeEmpty($array);
        $array->convertShapeToTypePair($config);

        $returnedArrays = array_values(array_filter(
            $this->variants($this->invoke($call, $callback, [$array->itemType ?? new UnknownType, $array->keyType ?? new IntegerType]), $config),
            fn (Type $variant) => $variant instanceof ArrayType,
        ));

        if ($returnedArrays === []) {
            return new ArrayType;
        }

        if (array_all($returnedArrays, fn (ArrayType $returned) => $returned->shape !== [])) {
            return $this->mergeShapes($returnedArrays, $mayBeEmpty, $config);
        }

        $itemTypes = [];
        $keyTypes = [];
        $intKeyOrder = null;

        foreach ($returnedArrays as $returned) {
            $pair = (clone $returned)->convertShapeToTypePair($config);

            $itemTypes[] = $pair->itemType ?? new UnknownType;
            $keyTypes[] = $pair->keyType ?? new IntegerType;
            $intKeyOrder = $intKeyOrder?->union($pair->intKeyOrder) ?? $pair->intKeyOrder;
        }

        return new ArrayType(
            itemType: $this->union($itemTypes, $config),
            keyType: $this->union($keyTypes, $config),
            intKeyOrder: $intKeyOrder,
        );
    }


    /**
     * @param list<ArrayType> $shapes
     */
    private function mergeShapes(array $shapes, bool $mayBeEmpty, Config $config): ArrayType
    {
        $valuesByKey = [];

        foreach ($shapes as $shape) {
            foreach ($shape->shape as $key => $value) {
                $valuesByKey[$key][] = $value;
            }
        }

        $merged = new ArrayType(shape: array_map(
            callback: fn (array $values) => (clone $this->union($values, $config))->setRequired(true),
            array: $valuesByKey,
        ));

        foreach ($valuesByKey as $key => $values) {
            if ($mayBeEmpty
                || count($values) !== count($shapes)
                || ! array_all($values, fn (Type $value) => $value->required)
            ) {
                $merged->removeItemFromArray($key, isCertain: false);
            }
        }

        return $merged;
    }


    private function pluck(StaticCallContext $call, ArrayType $array): ArrayType
    {
        $array->convertShapeToTypePair($call->scope->config);

        if ($array->itemType === null) {
            return new ArrayType;
        }

        $key = $this->argument($call, 'key', 2);
        $keyType = $key === null || $key instanceof NullType ? null : $this->retrieve($call, $key, [$array->itemType]);

        return new ArrayType(
            itemType: $this->retrieve($call, $this->argument($call, 'value', 1), [$array->itemType]),
            keyType: $keyType,
            intKeyOrder: $keyType === null ? IntKeyOrder::Sequential : IntKeyOrder::NonSequential,
        );
    }


    private function keyBy(StaticCallContext $call, ArrayType $array): ArrayType
    {
        $array->convertShapeToTypePair($call->scope->config);

        if ($array->itemType === null) {
            return new ArrayType;
        }

        return new ArrayType(
            itemType: $array->itemType,
            keyType: $this->retrieve($call, $this->argument($call, 'keyBy', 1), [$array->itemType, $array->keyType ?? new IntegerType]),
            intKeyOrder: IntKeyOrder::NonSequential,
        );
    }


    /**
     * @param array{0: Type, 1?: Type} $item
     */
    private function retrieve(StaticCallContext $call, ?Type $retriever, array $item): Type
    {
        if ($retriever instanceof CallableType) {
            return $this->invoke($call, $retriever, $item);
        }

        $config = $call->scope->config;
        $paths = $retriever ? $this->literalValues($retriever, $config) : null;

        if ($paths === null) {
            return new UnknownType;
        }

        $results = [];

        foreach ($paths as $path) {
            $segments = explode('.', (string) $path);

            $results[] = array_intersect($segments, self::UNSUPPORTED_DATA_GET_SEGMENTS)
                ? new UnknownType
                : $this->readPath($item[0], $segments, new NullType, $config);
        }

        return $this->union($results, $config);
    }


    private function get(StaticCallContext $call): Type
    {
        $config = $call->scope->config;
        $array = $this->argument($call, 'array', 0) ?? new UnknownType;
        $key = $this->argument($call, 'key', 1);

        if ($key instanceof NullType) {
            return clone $array;
        }

        $keys = $key ? $this->literalValues($key, $config) : null;

        if ($keys === null) {
            return new UnknownType;
        }

        $default = $this->defaultValue($call, 2);
        $results = [];

        foreach ($keys as $literalKey) {
            $segments = explode('.', (string) $literalKey);

            foreach ($this->variants($array, $config) as $variant) {
                [$value, $mayBeMissing] = $this->readSegment($variant, $literalKey);

                if ($value) {
                    $results[] = clone $value;
                }

                if ($mayBeMissing) {
                    $results[] = count($segments) > 1 ? $this->readPath($variant, $segments, $default, $config) : $default;
                }
            }
        }

        return $this->union($results, $config);
    }


    /**
     * @param list<int|string> $segments
     */
    private function readPath(Type $target, array $segments, Type $default, Config $config): Type
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return clone $target;
        }

        $results = [];

        foreach ($this->variants($target, $config) as $variant) {
            [$value, $mayBeMissing] = $this->readSegment($variant, $segment);

            if ($value) {
                $results[] = $this->readPath($value, $segments, $default, $config);
            }

            if ($mayBeMissing) {
                $results[] = $default;
            }
        }

        return $this->union($results, $config);
    }


    /**
     * @return array{?Type, bool}
     */
    private function readSegment(Type $target, int|string $segment): array
    {
        if ($target instanceof ArrayType) {
            if (! $target->shape) {
                return [$target->itemType ?? new UnknownType, true];
            }

            $value = $target->shape[$segment] ?? null;

            return [$value, $value === null || ! $value->required];
        }

        if ($target instanceof ObjectType || $target instanceof UnknownType) {
            return [new UnknownType, false];
        }

        return [null, true];
    }


    private function firstOrLast(StaticCallContext $call, bool $fromEnd): Type
    {
        $config = $call->scope->config;
        $callback = $this->argument($call, 'callback', 1);
        $callback = $callback instanceof CallableType ? $callback : null;
        $default = $this->defaultValue($call, 2);
        $results = [];

        foreach ($this->variants($this->argument($call, 'array', 0), $config) as $variant) {
            if ($variant instanceof ArrayType) {
                array_push($results, ...$this->entryCandidates($call, $variant, $callback, $fromEnd, $default));

            } else {
                $results[] = $variant instanceof NullType ? $default : new UnknownType;
            }
        }

        return $results ? $this->union($results, $config) : $default;
    }


    /**
     * @return list<Type>
     */
    private function entryCandidates(StaticCallContext $call, ArrayType $array, ?CallableType $callback, bool $fromEnd, Type $default): array
    {
        $config = $call->scope->config;

        if (! $array->shape) {
            if ($array->itemType === null) {
                return [$default];
            }

            $itemType = $callback ? $this->narrowEntry($call, $callback, $array->itemType, null) : $array->itemType;

            if ($itemType instanceof NeverType) {
                return [$default];
            }

            return $callback === null && ($array->minItems ?? 0) >= 1 ? [$itemType] : [$itemType, $default];
        }

        $candidates = [];
        $entries = $fromEnd ? array_reverse($array->shape, preserve_keys: true) : $array->shape;

        foreach ($entries as $key => $value) {
            $candidate = $callback ? $this->narrowEntry($call, $callback, $value, $key) : $value->unwrapType($config);

            if ($candidate instanceof NeverType) {
                continue;
            }

            $candidates[] = clone $candidate;

            if ($callback === null && $value->required) {
                return $candidates;
            }
        }

        $candidates[] = $default;

        return $candidates;
    }


    private function wrap(StaticCallContext $call): Type
    {
        $results = [];

        foreach ($this->variants($this->argument($call, 'value', 0), $call->scope->config) as $variant) {
            $results[] = match (true) {
                $variant instanceof NullType, $variant instanceof UnknownType => new ArrayType,
                $variant instanceof ArrayType && $variant->className === null => clone $variant,
                default => new ArrayType(shape: [(clone $variant)->setRequired(true)]),
            };
        }

        return $results ? $this->union($results, $call->scope->config) : new ArrayType;
    }


    private function narrowEntry(StaticCallContext $call, CallableType $callback, Type $value, int|string|null $key): Type
    {
        if ($key !== null
            && $callback->narrowArgumentTypeFromTruthyReturn(1, $this->literalKeyType($key), $call->node) instanceof NeverType
        ) {
            return new NeverType;
        }

        $value = $value->unwrapType($call->scope->config);

        return $callback->narrowArgumentTypeFromTruthyReturn(0, $value, $call->node) ?? $value;
    }


    private function sortRecursive(ArrayType $array, Config $config): ArrayType
    {
        $this->mapNestedArrays(
            array: $array,
            transform: fn (ArrayType $nested) => $nested->className === null ? $this->sortRecursive($nested, $config) : $nested,
            config: $config,
        );

        return $array->mayEncodeAsObject($config) ? $array->reorderItems($config) : $array->toList($config);
    }


    /**
     * @param Closure(ArrayType): ArrayType $transform
     */
    private function mapNestedArrays(ArrayType $array, Closure $transform, Config $config): ArrayType
    {
        foreach ($array->shape as $key => $value) {
            $array->shape[$key] = $this->mapArrayVariants($value, $transform, $config);
        }

        if ($array->itemType !== null) {
            $array->itemType = $this->mapArrayVariants($array->itemType, $transform, $config);
        }

        return $array;
    }


    /**
     * @param Closure(ArrayType): ArrayType $transform
     */
    private function mapArrayVariants(Type $value, Closure $transform, Config $config): Type
    {
        $hasArrayVariant = false;
        $variants = [];

        foreach ($this->variants($value, $config) as $variant) {
            if ($variant instanceof ArrayType) {
                $variant = $transform(clone $variant);
                $hasArrayVariant = true;
            }

            $variants[] = $variant;
        }

        return $hasArrayVariant ? (clone $this->union($variants, $config))->setRequired($value->required) : $value;
    }


    private function mayBeNull(Type $type, Config $config): bool
    {
        return array_any($this->variants($type, $config), fn (Type $variant) => $variant instanceof NullType || $variant instanceof UnknownType);
    }


    private function mayBeEmpty(ArrayType $array): bool
    {
        if ($array->shape) {
            return ! array_any($array->shape, fn (Type $value) => $value->required);
        }

        return ($array->minItems ?? 0) < 1;
    }


    private function defaultValue(StaticCallContext $call, int $position): Type
    {
        $default = $this->argument($call, 'default', $position);

        if ($default instanceof CallableType) {
            return $this->invoke($call, $default, []);
        }

        return $default ?? new NullType;
    }


    /**
     * @param list<Type> $args
     */
    private function invoke(StaticCallContext $call, CallableType $callback, array $args): Type
    {
        return $callback->getReturnType(
            args: ArgumentList::fromTypes($args, $call->scope),
            callerNode: $call->node,
        );
    }


    private function argument(StaticCallContext $call, string $name, int $position): ?Type
    {
        $index = $call->argTypes->indexForParameter($name, $position);

        return $index === null ? null : $call->argTypes->get($index)->unwrapType($call->scope->config);
    }


    /**
     * @return ?array<int|string, bool>
     */
    private function listedKeys(StaticCallContext $call): ?array
    {
        $listedKeys = $this->listedKeysFromType($this->argument($call, 'keys', 1), $call->scope->config);
        $index = $call->argTypes->indexForParameter('keys', 1);
        $keysArg = $index === null ? null : $call->node->args[$index] ?? null;

        if ($listedKeys === null || ! $keysArg instanceof Arg || ! $keysArg->value instanceof Array_) {
            return $listedKeys;
        }

        foreach ($keysArg->value->items as $item) {
            $value = $item->unpack ? null : $call->scope->getRawValueFromNode($item->value);

            if (is_int($value) || is_string($value)) {
                $listedKeys[$value] = true;
            }
        }

        return $listedKeys;
    }


    /**
     * @return ?array<int|string, bool>
     */
    private function listedKeysFromType(?Type $keys, Config $config): ?array
    {
        if (! $keys instanceof ArrayType) {
            $values = $keys ? $this->literalValues($keys, $config) : null;

            return $values === null ? null : array_fill_keys($values, count($values) === 1);
        }

        if (! $keys->shape) {
            $values = $keys->itemType ? $this->literalValues($keys->itemType, $config) : null;

            return $values === null ? null : array_fill_keys($values, false);
        }

        $listedKeys = [];

        foreach ($keys->shape as $member) {
            $values = $this->literalValues($member, $config);

            if ($values === null) {
                return null;
            }

            foreach ($values as $value) {
                $listedKeys[$value] = ($listedKeys[$value] ?? false) || ($member->required && count($values) === 1);
            }
        }

        return $listedKeys;
    }


    /**
     * @return ?list<int|string>
     */
    private function literalValues(Type $type, Config $config): ?array
    {
        $values = [];

        foreach ($this->variants($type, $config) as $variant) {
            $possibleValues = $variant instanceof StringType || $variant instanceof IntegerType
                ? $variant->getPossibleValues()
                : null;

            if (! $possibleValues) {
                return null;
            }

            array_push($values, ...$possibleValues);
        }

        return $values;
    }


    private function literalKeyType(int|string $key): Type
    {
        return is_int($key) ? new IntegerType($key) : new StringType($key);
    }


    /**
     * @return list<Type>
     */
    private function variants(?Type $type, Config $config): array
    {
        $type = $type?->unwrapType($config);

        if ($type instanceof UnionType) {
            return array_values(array_map(fn (Type $variant) => $variant->unwrapType($config), $type->types));
        }

        return $type ? [$type] : [];
    }


    /**
     * @param list<Type> $types
     */
    private function union(array $types, Config $config): Type
    {
        return new UnionType($types)->unwrapType($config);
    }
}
