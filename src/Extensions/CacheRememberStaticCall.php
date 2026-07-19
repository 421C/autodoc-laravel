<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\StaticCallContext;
use AutoDoc\Extensions\StaticCallExtension;
use AutoDoc\Laravel\Helpers\ResolvesCallbackReturnType;
use Illuminate\Support\Facades\Cache;

/**
 * Handles `Cache::remember(...)`, `Cache::rememberForever(...)` and `Cache::sear(...)`,
 * resolving each to its callback's return type.
 */
class CacheRememberStaticCall extends StaticCallExtension
{
    use ResolvesCallbackReturnType;

    private const CALLBACK_INDEX = [
        'remember' => 2,
        'rememberForever' => 1,
        'sear' => 1,
    ];

    public function getReturnType(StaticCallContext $call): ?Type
    {
        if ($call->className === null || ! is_a($call->className, Cache::class, true)) {
            return null;
        }

        $callbackIndex = self::CALLBACK_INDEX[$call->methodName] ?? null;

        if ($callbackIndex === null) {
            return null;
        }

        $callbackArgIndex = $call->argTypes->indexForParameter('callback', $callbackIndex);

        if ($callbackArgIndex === null) {
            return null;
        }

        return $this->resolveCallbackReturnType(
            argTypes: $call->argTypes,
            callbackIndex: $callbackArgIndex,
            scope: $call->scope,
            callerNode: $call->node,
        );
    }
}
