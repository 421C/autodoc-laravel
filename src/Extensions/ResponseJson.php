<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

/**
 * Handles `response()->json(...)` and `response()->streamJson(...)`.
 */
class ResponseJson extends MethodCallExtension
{
    /**
     * Method name => response class.
     */
    private const array METHODS = [
        'json' => JsonResponse::class,
        'streamJson' => StreamedJsonResponse::class,
    ];

    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! isset(self::METHODS[$call->methodName])) {
            return null;
        }

        $receiverType = $call->getVarType()->unwrapType($call->scope->config);

        if (! ($receiverType instanceof ObjectType)
            || $receiverType->className === null
            || ! is_a($receiverType->className, ResponseFactory::class, true)
        ) {
            return null;
        }

        $payloadArgIndex = $call->argTypes->indexForParameter('data', 0);
        $payloadType = $payloadArgIndex !== null ? $call->argTypes->get($payloadArgIndex) : null;

        if ($payloadType === null || $payloadType instanceof UnknownType) {
            $payloadType = new ObjectType;
        }

        $responseType = new ObjectType(
            className: self::METHODS[$call->methodName],
            typeToDisplay: $payloadType,
        );

        $statusArgIndex = $call->argTypes->indexForParameter('status', 1);

        if ($statusArgIndex !== null) {
            $statusCodeType = $call->argTypes->get($statusArgIndex);

            if ($statusCodeType instanceof IntegerType && is_int($statusCodeType->value)) {
                $responseType->httpStatusCode = $statusCodeType->value;
            }
        }

        return $responseType;
    }
}
