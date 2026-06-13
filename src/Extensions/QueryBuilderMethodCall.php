<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\MethodCallContext;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\QueryBuilder\QueryNavigator;
use PhpParser\Node\Expr\MethodCall;

class QueryBuilderMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCallContext $call): ?Type
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

        if (! in_array($call->methodName, $supportedMethods)) {
            return null;
        }

        $node = $call->node;

        if (! ($node instanceof MethodCall)) {
            return null;
        }

        return (new QueryNavigator($call->scope))->getResultType($node, $call->methodName);
    }
}
