<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\Laravel\Helpers\RecordsErrorResponses;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;

class QueryNavigator
{
    use RecordsErrorResponses;

    public function __construct(
        private Scope $scope,
    ) {}


    public function getResultType(MethodCall|StaticCall $methodCall, string $methodName): ?Type
    {
        $resultTypes = [];

        foreach ($this->extractChains($methodCall) as $chain) {
            $resultType = (new QueryResultType(
                scope: $this->scope,
                chain: $chain,
                rowShape: new QueryRowShape($this->scope, $chain),
            ))->resolve($methodCall, $methodName);

            if (! $resultType) {
                return null;
            }

            $resultTypes[] = $resultType;
        }

        if (! $resultTypes) {
            return null;
        }

        return count($resultTypes) === 1
            ? $resultTypes[0]
            : new UnionType($resultTypes)->unwrapType($this->scope->config);
    }


    public function recordFailingFinisherResponse(Node\Expr $queryNode): void
    {
        $route = $this->scope->route;

        if (! $route) {
            return;
        }

        foreach ($this->extractChains($queryNode) as $chain) {
            if ($chain->isEloquentModelQuery()) {
                $this->addModelNotFoundResponse($route);

                return;
            }
        }
    }


    /**
     * @return list<QueryChain>
     */
    private function extractChains(Node\Expr $queryNode): array
    {
        return (new QueryChainExtractor($this->scope))->extract($queryNode);
    }
}
