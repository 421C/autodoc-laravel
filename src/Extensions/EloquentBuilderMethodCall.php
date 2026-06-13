<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\MethodCallContext;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallExtension;
use Throwable;

class EloquentBuilderMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCallContext $call): ?Type
    {
        $varType = $call->getVarType();

        if (! ($varType instanceof ObjectType)
            || $varType->className !== \Illuminate\Database\Eloquent\Builder::class
        ) {
            return null;
        }

        $scope = $call->scope;
        $methodName = $call->methodName;
        $methodArgs = $call->argTypes;

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
