<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpFunctionArgument;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallExtension;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use Throwable;

class EloquentBuilderMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if (! ($methodCall->name instanceof Node\Identifier)) {
            return null;
        }

        $varType = $scope->resolveType($methodCall->var);

        if (! ($varType instanceof ObjectType)
            || $varType->className !== \Illuminate\Database\Eloquent\Builder::class
        ) {
            return null;
        }

        $methodName = $methodCall->name->name;
        $methodArgs = PhpFunctionArgument::list($methodCall->args, scope: $scope);

        try {
            $phpClassMethod = $scope->getPhpClassInDeeperScope(\Illuminate\Database\Eloquent\Builder::class)->getMethod(
                name: $methodName,
                args: $methodArgs,
            );

            return $phpClassMethod->getReturnType()->unwrapType($scope->config);

        } catch (Throwable $exception) {
            $phpClassMethod = $scope->getPhpClassInDeeperScope(\Illuminate\Database\Query\Builder::class)->getMethod(
                name: $methodName,
                args: $methodArgs,
            );

            return $phpClassMethod->getReturnType()->unwrapType($scope->config);
        }
    }
}
