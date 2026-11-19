<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Config;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\CallableType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\FuncCallContext;
use AutoDoc\Extensions\StaticCallContext;

/**
 * Reads `data_get()`-style dot paths through the array types of call arguments.
 */
trait ReadsDataPath
{
    private const UNSUPPORTED_DATA_GET_SEGMENTS = ['*', '{first}', '{last}', '\*', '\{first}', '\{last}'];


    protected function readDataPath(Type $target, ?Type $key, Type $default, Config $config): Type
    {
        if ($key instanceof NullType) {
            return clone $target;
        }

        $paths = $key ? $this->literalValues($key, $config) : null;

        if ($paths === null) {
            return new UnknownType;
        }

        $results = [];

        foreach ($paths as $path) {
            $segments = explode('.', (string) $path);

            $results[] = array_intersect($segments, self::UNSUPPORTED_DATA_GET_SEGMENTS)
                ? new UnknownType
                : $this->readPath($target, $segments, $default, $config);
        }

        return $this->union($results, $config);
    }


    /**
     * @param list<int|string> $segments
     */
    protected function readPath(Type $target, array $segments, Type $default, Config $config): Type
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return clone $target;
        }

        $results = [];

        foreach ($this->variants($target, $config) as $variant) {
            [$value, $mayBeMissing] = $this->readSegment($variant, $segment, $config);

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
    protected function readSegment(Type $target, int|string $segment, Config $config): array
    {
        if ($target instanceof ArrayType) {
            if ($target->shape === [] && ! $target->hasEntriesOutsideShape()) {
                return [new UnknownType, true];
            }

            $key = $this->asArrayKey($segment);
            $knownValue = $target->shape[$key] ?? null;

            return [
                $knownValue ?? $target->getTypeOfEntryOutsideShapeAt($key, $config),
                $knownValue === null || ! $knownValue->required,
            ];
        }

        if ($target instanceof ObjectType || $target instanceof UnknownType) {
            return [new UnknownType, false];
        }

        return [null, true];
    }


    private function asArrayKey(int|string $segment): int|string
    {
        return array_key_first([$segment => null]);
    }


    protected function defaultValue(FuncCallContext|StaticCallContext $call, int $position): Type
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
    protected function invoke(FuncCallContext|StaticCallContext $call, CallableType $callback, array $args): Type
    {
        return $callback->getReturnType(
            args: ArgumentList::fromTypes($args, $call->scope),
            callerNode: $call->node,
        );
    }


    protected function argument(FuncCallContext|StaticCallContext $call, string $name, int $position): ?Type
    {
        $index = $call->argTypes->indexForParameter($name, $position);

        return $index === null ? null : $call->argTypes->get($index)->unwrapType($call->scope->config);
    }


    /**
     * @return ?list<int|string>
     */
    protected function literalValues(Type $type, Config $config): ?array
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


    /**
     * @return list<Type>
     */
    protected function variants(?Type $type, Config $config): array
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
    protected function union(array $types, Config $config): Type
    {
        return new UnionType($types)->unwrapType($config);
    }
}
