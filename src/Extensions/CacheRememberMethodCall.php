<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\ResolvesCallbackReturnType;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

/**
 * Handles `cache()->remember(...)` and repository `remember(...)`/`rememberForever(...)`/`sear(...)`,
 * resolving each to its callback's return type.
 */
class CacheRememberMethodCall extends MethodCallExtension
{
    use ResolvesCallbackReturnType;

    private const CALLBACK_INDEX = [
        'remember' => 2,
        'rememberForever' => 1,
        'sear' => 1,
    ];

    private const RECEIVER_CLASSES = [
        Repository::class,
        Factory::class,
    ];

    public function getReturnType(MethodCallContext $call): ?Type
    {
        $callbackIndex = self::CALLBACK_INDEX[$call->methodName] ?? null;

        if ($callbackIndex === null || ! $this->isCacheReceiver($call)) {
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

    private function isCacheReceiver(MethodCallContext $call): bool
    {
        $var = $call->node->var;

        if ($var instanceof FuncCall
            && $var->name instanceof Node\Name
            && $var->name->name === 'cache'
        ) {
            return true;
        }

        $varType = $call->getVarType()->unwrapType($call->scope->config);

        if ($varType instanceof ObjectType && $varType->className !== null) {
            foreach (self::RECEIVER_CLASSES as $receiverClass) {
                if (is_a($varType->className, $receiverClass, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
