<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\CallableType;
use AutoDoc\DataTypes\Type;
use PhpParser\Node;

/**
 * Resolves a callback argument's return type, as if the callback were invoked.
 * Shared by helpers whose result is the value produced by a passed closure
 * (`Cache::remember`, `rescue`, `retry`, ...).
 */
trait ResolvesCallbackReturnType
{
    /**
     * @param list<Type> $callbackArgs types the callback receives on invocation
     */
    protected function resolveCallbackReturnType(
        ArgumentList $argTypes,
        int $callbackIndex,
        Scope $scope,
        ?Node $callerNode,
        array $callbackArgs = [],
    ): ?Type {
        if (! $argTypes->has($callbackIndex)) {
            return null;
        }

        $callbackType = $argTypes->get($callbackIndex);

        if (! $callbackType instanceof CallableType) {
            return null;
        }

        return $callbackType->getReturnType(
            args: ArgumentList::fromTypes($callbackArgs, $scope),
            callerNode: $callerNode,
        );
    }
}
