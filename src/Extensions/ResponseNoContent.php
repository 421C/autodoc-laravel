<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NeverType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

/**
 * Handles `response()->noContent(...)`.
 */
class ResponseNoContent extends MethodCallExtension
{
    public function handleSideEffect(MethodCallContext $call): void
    {
        if (! $this->isResponseNoContentCall($call)) {
            return;
        }

        $call->scope->route?->addResponse(
            status: $this->resolveStatusCode($call),
            contentType: 'application/json',
            body: new UnknownType,
        );
    }

    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! $this->isResponseNoContentCall($call)) {
            return null;
        }

        // The body-less response is recorded in handleSideEffect; a `never` return keeps the
        // Stringable `Illuminate\Http\Response` native return type from adding a string body.
        return new NeverType;
    }

    private function isResponseNoContentCall(MethodCallContext $call): bool
    {
        $node = $call->node;

        return $call->methodName === 'noContent'
            && $node->var instanceof FuncCall
            && $node->var->name instanceof Node\Name
            && $node->var->name->name === 'response';
    }

    private function resolveStatusCode(MethodCallContext $call): int
    {
        $statusArgIndex = $call->argTypes->indexForParameter('status', 0);

        if ($statusArgIndex !== null) {
            $statusCodeType = $call->argTypes->get($statusArgIndex)->unwrapType($call->scope->config);

            if ($statusCodeType instanceof IntegerType && is_int($statusCodeType->value)) {
                return $statusCodeType->value;
            }
        }

        return 204;
    }
}
