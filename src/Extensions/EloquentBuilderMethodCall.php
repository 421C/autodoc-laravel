<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodResolver;

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

        return (new BuilderMethodResolver($scope))->getReturnType($methodName, $methodArgs);
    }
}
