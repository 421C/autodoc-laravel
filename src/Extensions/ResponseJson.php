<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\MethodCallContext;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

/**
 * Handles `response()->json(...)`.
 */
class ResponseJson extends MethodCallExtension
{
    public function getReturnType(MethodCallContext $call): ?Type
    {
        $node = $call->node;

        if ($call->methodName === 'json'
            && $node->var instanceof FuncCall
            && $node->var->name instanceof Node\Name
            && $node->var->name->name === 'response'
        ) {
            $payloadType = $call->argTypes->has(0) ? $call->argTypes->get(0) : null;

            if ($payloadType === null || $payloadType instanceof UnknownType) {
                $payloadType = new ObjectType;
            }

            $responseType = new ObjectType(
                className: \Illuminate\Http\JsonResponse::class,
                typeToDisplay: $payloadType,
            );

            if ($call->argTypes->has(1)) {
                $statusCodeType = $call->argTypes->get(1);

                if ($statusCodeType instanceof IntegerType && is_int($statusCodeType->value)) {
                    $responseType->httpStatusCode = $statusCodeType->value;
                }
            }

            return $responseType;
        }

        return null;
    }
}
