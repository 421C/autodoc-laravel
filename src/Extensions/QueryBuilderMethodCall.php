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
        if (! ($methodCall->name instanceof Node\Identifier)
            || ! in_array($methodCall->name->name, ['get', 'first', 'firstWhere', 'firstOrFail', 'findOrFail', 'firstOrNew', 'firstOrCreate', 'latest', 'oldest', 'pluck', 'paginate'])
        ) {
            return null;
        }

        return (new QueryNavigator($scope))->getResultType($methodCall, $methodCall->name->name);
    }
}
