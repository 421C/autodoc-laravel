<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\CallableType;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;

final class ConditionalCallback
{
    private function __construct(
        /** @var list<QueryChainMethod> */
        public readonly array $callbackMethods,

        /** @var list<QueryChainMethod> */
        public readonly array $defaultMethods,
        public readonly ?bool $callbackRuns,
    ) {}


    public static function read(QueryChainMethod $method, Node\Expr $callerNode, QueryChain $chain, Scope $scope): ?self
    {
        if ($method->name === 'tap') {
            return new self(
                callbackMethods: self::methodsAddedBy($method, 'callback', 0, $callerNode, $chain, $scope),
                defaultMethods: [],
                callbackRuns: true,
            );
        }

        if ($method->name !== 'when' && $method->name !== 'unless') {
            return null;
        }

        $condition = self::literalConditionValue($method, $scope);

        return new self(
            callbackMethods: self::methodsAddedBy($method, 'callback', 1, $callerNode, $chain, $scope),
            defaultMethods: self::methodsAddedBy($method, 'default', 2, $callerNode, $chain, $scope),
            callbackRuns: $condition === null ? null : ($method->name === 'when' ? $condition : ! $condition),
        );
    }


    public function isEmpty(): bool
    {
        return $this->callbackMethods === [] && $this->defaultMethods === [];
    }


    /**
     * Only a call that can change the row makes the two outcomes differ, so the
     * rest is dropped rather than carried into a branch of its own.
     */
    public function withoutRowPreservingCalls(?string $modelClassName): self
    {
        return new self(
            callbackMethods: self::onlyRowShapeMethods($this->callbackMethods, $modelClassName),
            defaultMethods: self::onlyRowShapeMethods($this->defaultMethods, $modelClassName),
            callbackRuns: $this->callbackRuns,
        );
    }


    /**
     * @param list<QueryChainMethod> $methods
     * @return list<QueryChainMethod>
     */
    private static function onlyRowShapeMethods(array $methods, ?string $modelClassName): array
    {
        return array_values(array_filter(
            $methods,
            fn (QueryChainMethod $method) => BuilderMethodClassifier::belongsInChain($method->name, $modelClassName),
        ));
    }


    private static function literalConditionValue(QueryChainMethod $method, Scope $scope): ?bool
    {
        $index = $method->args->indexForParameter('value', 0);

        if ($index === null) {
            return null;
        }

        $conditionType = $method->args->get($index)->unwrapType($scope->config);

        return $conditionType instanceof BoolType ? $conditionType->value : null;
    }


    /**
     * A callback that returns the builder is read by invoking it, which
     * resolves its arguments in its own scope. One that mutates and returns
     * nothing leaves no trace there, so its body is read instead.
     *
     * @return list<QueryChainMethod>
     */
    private static function methodsAddedBy(
        QueryChainMethod $method,
        string $parameterName,
        int $parameterIndex,
        Node\Expr $callerNode,
        QueryChain $chain,
        Scope $scope,
    ): array {
        $index = $method->args->indexForParameter($parameterName, $parameterIndex);
        $callbackType = $index === null ? null : $method->args->get($index);

        if (! ($callbackType instanceof CallableType)) {
            return [];
        }

        $returnedBuilder = $callbackType->getReturnType(
            args: ArgumentList::fromTypes([self::createProbeBuilderType($chain)], $scope),
            callerNode: $callerNode,
        );

        return BuilderType::chainsIn($returnedBuilder)[0]->methods
            ?? self::methodsInCallbackBody($callerNode, $parameterName, $parameterIndex, $scope);
    }


    private static function createProbeBuilderType(QueryChain $chain): BuilderType
    {
        return new BuilderType(
            chain: new QueryChain(
                modelClassName: $chain->modelClassName,
                methods: [],
                isRawDatabaseQuery: $chain->isRawDatabaseQuery,
            ),
            builderClassName: $chain->isRawDatabaseQuery ? QueryBuilder::class : EloquentBuilder::class,
        );
    }


    /**
     * @return list<QueryChainMethod>
     */
    private static function methodsInCallbackBody(Node\Expr $callerNode, string $parameterName, int $parameterIndex, Scope $scope): array
    {
        $callback = self::findCallbackNode($callerNode, $parameterName, $parameterIndex);

        if ($callback === null) {
            return [];
        }

        $builderParameter = $callback->getParams()[0]->var ?? null;

        if (! ($builderParameter instanceof Node\Expr\Variable) || ! is_string($builderParameter->name)) {
            return [];
        }

        $methods = [];

        foreach (self::callbackBodyExpressions($callback) as $expression) {
            self::collectCallsOnVariable($expression, $builderParameter->name, $methods, $scope);
        }

        return $methods;
    }


    private static function findCallbackNode(Node\Expr $callerNode, string $parameterName, int $parameterIndex): Node\Expr\Closure|Node\Expr\ArrowFunction|null
    {
        $args = $callerNode instanceof MethodCall
            || $callerNode instanceof NullsafeMethodCall
            || $callerNode instanceof StaticCall
                ? $callerNode->args
                : [];

        foreach ($args as $position => $arg) {
            if (! ($arg instanceof Node\Arg)) {
                continue;
            }

            $matchesName = $arg->name?->toString() === $parameterName;
            $matchesPosition = $arg->name === null && $position === $parameterIndex;

            if (($matchesName || $matchesPosition)
                && ($arg->value instanceof Node\Expr\Closure || $arg->value instanceof Node\Expr\ArrowFunction)
            ) {
                return $arg->value;
            }
        }

        return null;
    }


    /**
     * @return list<Node\Expr>
     */
    private static function callbackBodyExpressions(Node\Expr\Closure|Node\Expr\ArrowFunction $callback): array
    {
        if ($callback instanceof Node\Expr\ArrowFunction) {
            return [$callback->expr];
        }

        $expressions = [];

        foreach ($callback->stmts as $statement) {
            if ($statement instanceof Node\Stmt\Expression) {
                $expressions[] = $statement->expr;

            } else if ($statement instanceof Node\Stmt\Return_ && $statement->expr !== null) {
                $expressions[] = $statement->expr;
            }
        }

        return $expressions;
    }


    /**
     * @param list<QueryChainMethod> $methods
     */
    private static function collectCallsOnVariable(Node\Expr $expr, string $variableName, array &$methods, Scope $scope): bool
    {
        if ($expr instanceof Node\Expr\Variable) {
            return $expr->name === $variableName;
        }

        if (! ($expr instanceof MethodCall) || ! self::collectCallsOnVariable($expr->var, $variableName, $methods, $scope)) {
            return false;
        }

        $methods[] = new QueryChainMethod(
            name: (string) $scope->getRawValueFromNode($expr->name),
            args: ArgumentList::fromArgNodes($expr->args, $scope),
        );

        return true;
    }
}
