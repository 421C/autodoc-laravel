<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\QueryBuilder\BuilderMethodClassifier;
use AutoDoc\Laravel\QueryBuilder\QueryNavigator;
use PhpParser\Node\Expr\MethodCall;

class QueryBuilderMethodCall extends MethodCallExtension
{
    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! BuilderMethodClassifier::supportsResultInference($call->methodName)) {
            return null;
        }

        $node = $call->node;

        if (! ($node instanceof MethodCall)) {
            return null;
        }

        return (new QueryNavigator($call->scope))->getResultType($node, $call->methodName);
    }
}
