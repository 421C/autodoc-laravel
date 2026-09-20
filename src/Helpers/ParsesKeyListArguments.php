<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\Extensions\MethodCallContext;

/**
 * Laravel key lists are written as either one array of keys or, for methods
 * spelled `is_array($keys) ? $keys : func_get_args()`, variadic string keys.
 */
trait ParsesKeyListArguments
{
    /**
     * @return list<string>
     */
    protected function resolveKeyListNames(MethodCallContext $call, bool $allowVariadic): array
    {
        if (! $call->argTypes->has(0)) {
            return [];
        }

        $firstArgType = $call->argTypes->get(0)->unwrapType($call->scope->config);

        if ($firstArgType instanceof ArrayType) {
            return $this->resolveKeyNamesFromArrayType($firstArgType, $call);
        }

        if (! $allowVariadic) {
            return [];
        }

        $keyNames = [];

        for ($index = 0; $index < count($call->argTypes); $index++) {
            $this->appendKeyNamesFromType($call->argTypes->get($index), $call, $keyNames);
        }

        return array_values(array_unique($keyNames));
    }


    /**
     * @return list<string>
     */
    private function resolveKeyNamesFromArrayType(ArrayType $keyListType, MethodCallContext $call): array
    {
        $keyNames = [];

        foreach ($keyListType->shape as $keyNameType) {
            $this->appendKeyNamesFromType($keyNameType, $call, $keyNames);
        }

        if ($keyListType->itemType) {
            $this->appendKeyNamesFromType($keyListType->itemType, $call, $keyNames);
        }

        return array_values(array_unique($keyNames));
    }


    /**
     * Unions can preserve literal key names from variable key lists.
     *
     * @param list<string> $keyNames
     */
    private function appendKeyNamesFromType(Type $type, MethodCallContext $call, array &$keyNames): void
    {
        $type = $type->unwrapType($call->scope->config);

        if ($type instanceof StringType) {
            array_push($keyNames, ...($type->getPossibleValues() ?? []));

            return;
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $typeInUnion) {
                $this->appendKeyNamesFromType($typeInUnion, $call, $keyNames);
            }
        }
    }
}
