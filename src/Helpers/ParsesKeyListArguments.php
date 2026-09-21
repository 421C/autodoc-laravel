<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Config;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;

/**
 * Laravel key lists are written as either one array of keys or, for methods
 * spelled `is_array($keys) ? $keys : func_get_args()`, variadic string keys.
 */
trait ParsesKeyListArguments
{
    /**
     * @return list<string>
     */
    protected function resolveKeyListNames(ArgumentList $args, Config $config, bool $allowVariadic): array
    {
        if (! $args->has(0)) {
            return [];
        }

        $firstArgType = $args->get(0)->unwrapType($config);

        if ($firstArgType instanceof ArrayType) {
            return $this->resolveKeyNamesFromArrayType($firstArgType, $config);
        }

        if (! $allowVariadic) {
            return [];
        }

        $keyNames = [];

        for ($index = 0; $index < count($args); $index++) {
            $this->appendKeyNamesFromType($args->get($index), $config, $keyNames);
        }

        return array_values(array_unique($keyNames));
    }


    protected function hasEmptyKeyListArgument(ArgumentList $args, Config $config): bool
    {
        if (! $args->has(0)) {
            return false;
        }

        $firstArgType = $args->get(0)->unwrapType($config);

        return $firstArgType instanceof ArrayType
            && $firstArgType->shape === []
            && $firstArgType->itemType === null;
    }


    /**
     * @return list<string>
     */
    private function resolveKeyNamesFromArrayType(ArrayType $keyListType, Config $config): array
    {
        $keyNames = [];

        foreach ($keyListType->shape as $keyNameType) {
            $this->appendKeyNamesFromType($keyNameType, $config, $keyNames);
        }

        if ($keyListType->itemType) {
            $this->appendKeyNamesFromType($keyListType->itemType, $config, $keyNames);
        }

        return array_values(array_unique($keyNames));
    }


    /**
     * Unions can preserve literal key names from variable key lists.
     *
     * @param list<string> $keyNames
     */
    private function appendKeyNamesFromType(Type $type, Config $config, array &$keyNames): void
    {
        $type = $type->unwrapType($config);

        if ($type instanceof StringType) {
            array_push($keyNames, ...($type->getPossibleValues() ?? []));

            return;
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $typeInUnion) {
                $this->appendKeyNamesFromType($typeInUnion, $config, $keyNames);
            }
        }
    }
}
