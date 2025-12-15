<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;

/**
 * Handles Laravel Request `query` method.
 */
class RequestQuery extends MethodCallExtension
{
    public function getRequestType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if (! $scope->route || ! $this->isRequestQueryMethod($methodCall, $scope)) {
            return null;
        }

        $keyArgNode = $methodCall->getArgs()[0]->value ?? null;
        $keyArgType = $keyArgNode ? $scope->resolveType($keyArgNode) : null;

        if ($keyArgType instanceof StringType) {
            foreach ($keyArgType->getPossibleValues() ?? [] as $key) {
                $scope->route->requestQueryParams[$key] ??= new UnknownType;
            }
        }

        return null;
    }


    public function getReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if (! $this->isRequestQueryMethod($methodCall, $scope)) {
            return null;
        }

        $keyArgNode = $methodCall->getArgs()[0]->value ?? null;
        $keyArgType = $keyArgNode ? $scope->resolveType($keyArgNode) : null;

        if ($keyArgType instanceof StringType) {
            return new UnionType([
                new StringType,
                new NullType,
                new ArrayType(itemType: new UnknownType),
            ]);

        } else if ($keyArgType === null || $keyArgType instanceof NullType) {
            return new ArrayType(
                keyType: new StringType,
                itemType: new UnionType([
                    new StringType,
                    new ArrayType(itemType: new UnknownType),
                ]),
            );
        }

        return new ArrayType(
            itemType: new UnionType([
                new StringType,
                new NullType,
                new ArrayType(itemType: new UnknownType),
            ]),
        );
    }


    private function isRequestQueryMethod(MethodCall $methodCall, Scope $scope): bool
    {
        if (! ($methodCall->name instanceof Node\Identifier)
            || $methodCall->name->name !== 'query'
        ) {
            return false;
        }

        if ($methodCall->var instanceof FuncCall
            && $methodCall->var->name instanceof Node\Name
            && $methodCall->var->name->name === 'request'
        ) {
            return true;
        }

        $varType = $scope->resolveType($methodCall->var);

        if ($varType instanceof ObjectType && $varType->className) {
            return is_a($varType->className, Request::class, true);
        }

        return false;
    }
}
