<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;

final class BuilderParameterReader
{
    /**
     * @return non-empty-list<list<QueryChainMethod>>
     */
    public static function outcomesIn(Node\FunctionLike $function, QueryChain $chain, Scope $scope): array
    {
        $builderParameter = $function->getParams()[0]->var ?? null;

        if (! ($builderParameter instanceof Node\Expr\Variable) || ! is_string($builderParameter->name)) {
            return [[]];
        }

        $state = BuilderState::fromChain($chain->withoutMethods());

        foreach ($function->getStmts() ?? [] as $statement) {
            $expression = match (true) {
                $statement instanceof Node\Stmt\Expression,
                $statement instanceof Node\Stmt\Return_ => $statement->expr,
                default => null,
            };

            if ($expression !== null) {
                $state = self::stateAfterCallsOn($expression, $builderParameter->name, $state, $scope) ?? $state;
            }
        }

        return array_map(fn (QueryChain $outcome) => $outcome->methods, $state->chains());
    }


    private static function stateAfterCallsOn(Node\Expr $expr, string $variableName, BuilderState $state, Scope $scope): ?BuilderState
    {
        if ($expr instanceof Node\Expr\Variable) {
            return $expr->name === $variableName ? $state : null;
        }

        if (! ($expr instanceof MethodCall)) {
            return null;
        }

        $receiverState = self::stateAfterCallsOn($expr->var, $variableName, $state, $scope);

        if ($receiverState === null) {
            return null;
        }

        $method = new QueryChainMethod(
            name: (string) $scope->getRawValueFromNode($expr->name),
            args: ArgumentList::fromArgNodes($expr->args, $scope),
        );

        $conditional = ConditionalCallback::read($method, $expr, $receiverState->chain(), $scope);

        return $conditional ? $receiverState->applying($conditional) : $receiverState->withMethod($method);
    }
}
