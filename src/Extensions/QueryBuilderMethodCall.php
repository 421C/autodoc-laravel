<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\QueryBuilder\QueryNavigator;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;

class QueryBuilderMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $supportedMethods = [
            'get',
            'create',
            'first',
            'firstWhere',
            'firstOrFail',
            'find',
            'findOrFail',
            'firstOrNew',
            'firstOrCreate',
            'updateOrCreate',
            'latest',
            'oldest',
            'pluck',
            'paginate',
        ];

        if (! ($methodCall->name instanceof Node\Identifier)
            || ! in_array($methodCall->name->name, $supportedMethods)
        ) {
            return null;
        }

        return (new QueryNavigator($scope))->getResultType($methodCall, $methodCall->name->name);
    }
}
