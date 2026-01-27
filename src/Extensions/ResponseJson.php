<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;

/**
 * Handles `response()->json(...)`.
 */
class ResponseJson extends MethodCallExtension
{
    public function getReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if ($methodCall->name instanceof Node\Identifier
            && $methodCall->name->name === 'json'
            && $methodCall->var instanceof FuncCall
            && $methodCall->var->name instanceof Node\Name
            && $methodCall->var->name->name === 'response'
        ) {
            $dataForJsonResponse = $methodCall->args[0]->value ?? null;
            $statusCodeType = $methodCall->args[1]->value ?? null;

            if ($dataForJsonResponse) {
                $payloadType = $scope->resolveType($dataForJsonResponse);

                if ($payloadType instanceof UnknownType) {
                    $payloadType = new ObjectType;
                }

            } else {
                $payloadType = new ObjectType;
            }

            $responseType = new ObjectType(
                className: \Illuminate\Http\JsonResponse::class,
                typeToDisplay: $payloadType,
            );

            if ($statusCodeType) {
                $statusCodeType = $scope->resolveType($statusCodeType);

                if ($statusCodeType instanceof IntegerType && is_int($statusCodeType->value)) {
                    $responseType->httpStatusCode = $statusCodeType->value;
                }
            }

            return $responseType;
        }

        return null;
    }
}
