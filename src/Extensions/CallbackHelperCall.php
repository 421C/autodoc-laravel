<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\DataTypes\CallableType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\FuncCallContext;
use AutoDoc\Extensions\FuncCallExtension;
use AutoDoc\Laravel\Helpers\ResolvesCallbackReturnType;

/**
 * Handles the `rescue()`, `retry()`, `tap()`, `value()` and `with()` helpers,
 * resolving each to its callback's return type or the value it passes through.
 */
class CallbackHelperCall extends FuncCallExtension
{
    use ResolvesCallbackReturnType;

    public function getReturnType(FuncCallContext $call): ?Type
    {
        return match ($call->functionName) {
            'rescue' => $this->resolveCallback($call, callbackIndex: 0),
            'retry' => $this->resolveCallback($call, callbackIndex: 1, callbackArgs: [new IntegerType]),
            'tap' => $this->resolveTap($call),
            'value' => $this->resolveValue($call),
            'with' => $this->resolveWith($call),
            default => null,
        };
    }

    /**
     * @param list<Type> $callbackArgs
     */
    private function resolveCallback(FuncCallContext $call, int $callbackIndex, array $callbackArgs = []): ?Type
    {
        $callbackArgIndex = $call->argTypes->indexForParameter('callback', $callbackIndex);

        if ($callbackArgIndex === null) {
            return null;
        }

        return $this->resolveCallbackReturnType(
            argTypes: $call->argTypes,
            callbackIndex: $callbackArgIndex,
            scope: $call->scope,
            callerNode: $call->node,
            callbackArgs: $callbackArgs,
        );
    }

    private function resolveTap(FuncCallContext $call): ?Type
    {
        if ($call->argTypes->indexForParameter('callback', 1) === null) {
            return null;
        }

        return $this->valueArgType($call);
    }

    private function resolveValue(FuncCallContext $call): ?Type
    {
        $valueType = $this->valueArgType($call);

        if ($valueType === null) {
            return null;
        }

        if (! $valueType instanceof CallableType) {
            return $valueType;
        }

        $valueIndex = $call->argTypes->indexForParameter('value', 0) ?? 0;

        return $valueType->getReturnType(
            args: ArgumentList::fromTypes($this->trailingArgTypes($call, $valueIndex), $call->scope),
            callerNode: $call->node,
        );
    }

    private function resolveWith(FuncCallContext $call): ?Type
    {
        $valueType = $this->valueArgType($call);

        if ($valueType === null) {
            return null;
        }

        $callbackIndex = $call->argTypes->indexForParameter('callback', 1);

        if ($callbackIndex === null) {
            return $valueType;
        }

        return $this->resolveCallbackReturnType(
            argTypes: $call->argTypes,
            callbackIndex: $callbackIndex,
            scope: $call->scope,
            callerNode: $call->node,
            callbackArgs: [$valueType],
        ) ?? $valueType;
    }

    private function valueArgType(FuncCallContext $call): ?Type
    {
        $valueIndex = $call->argTypes->indexForParameter('value', 0);

        return $valueIndex === null ? null : $call->argTypes->get($valueIndex);
    }

    /**
     * @return list<Type>
     */
    private function trailingArgTypes(FuncCallContext $call, int $afterIndex): array
    {
        $args = [];

        for ($index = $afterIndex + 1; $index < count($call->argTypes); $index++) {
            if ($call->argTypes->has($index)) {
                $args[] = $call->argTypes->get($index);
            }
        }

        return $args;
    }
}
