<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
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

            if ($dataForJsonResponse) {
                $payloadType = $scope->resolveType($dataForJsonResponse);

                if ($payloadType instanceof UnknownType) {
                    $payloadType = new ObjectType;
                }

            } else {
                $payloadType = new ObjectType;
            }

            return new ObjectType(
                className: \Illuminate\Http\JsonResponse::class,
                typeToDisplay: $payloadType,
            );
        }

        return null;
    }
}
