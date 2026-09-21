<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\CallableType;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use WeakMap;

final class ConditionalCallback
{
    private function __construct(
        /** @var non-empty-list<list<QueryChainMethod>> */
        private readonly array $callbackOutcomes,

        /** @var non-empty-list<list<QueryChainMethod>> */
        private readonly array $defaultOutcomes,
        private readonly ?bool $callbackRuns,
        private readonly bool $proxiesNextMethod = false,
    ) {}


    /**
     * @var ?WeakMap<Node\Expr, WeakMap<Scope, self>>
     */
    private static ?WeakMap $cache = null;


    public static function read(QueryChainMethod $method, Node\Expr $callerNode, QueryChain $chain, Scope $scope): ?self
    {
        if ($method->name !== 'tap' && $method->name !== 'when' && $method->name !== 'unless') {
            return null;
        }

        self::$cache ??= new WeakMap;

        /** @var WeakMap<Scope, self> $callbacksByScope */
        $callbacksByScope = self::$cache[$callerNode] ?? new WeakMap;

        self::$cache[$callerNode] = $callbacksByScope;

        if (! isset($callbacksByScope[$scope])) {
            $callbacksByScope[$scope] = self::readCallbacks($method, $callerNode, $chain, $scope);
        }

        return $callbacksByScope[$scope];
    }


    private static function readCallbacks(QueryChainMethod $method, Node\Expr $callerNode, QueryChain $chain, Scope $scope): self
    {
        if ($method->name === 'tap') {
            return new self(
                callbackOutcomes: self::methodsAddedBy($method, 'callback', 0, $callerNode, $chain, $scope),
                defaultOutcomes: [[]],
                callbackRuns: true,
            );
        }

        $condition = self::literalConditionValue($method, $scope);

        return new self(
            callbackOutcomes: self::methodsAddedBy($method, 'callback', 1, $callerNode, $chain, $scope),
            defaultOutcomes: self::methodsAddedBy($method, 'default', 2, $callerNode, $chain, $scope),
            callbackRuns: $condition === null ? null : ($method->name === 'when' ? $condition : ! $condition),
            proxiesNextMethod: $method->args->indexForParameter('callback', 1) === null,
        );
    }


    public function proxiesNextMethod(): bool
    {
        return $this->proxiesNextMethod;
    }


    /**
     * @return non-empty-list<list<QueryChainMethod>>
     */
    public function outcomes(): array
    {
        return self::distinct(match ($this->callbackRuns) {
            true => $this->callbackOutcomes,
            false => $this->defaultOutcomes,
            null => [...$this->defaultOutcomes, ...$this->callbackOutcomes],
        });
    }


    public function isEmpty(): bool
    {
        return $this->outcomes() === [[]];
    }


    /**
     * Only a call that can change the row makes the two outcomes differ, so the
     * rest is dropped rather than carried into a branch of its own.
     */
    public function withoutRowPreservingCalls(?string $modelClassName): self
    {
        return new self(
            callbackOutcomes: self::onlyRowShapeOutcomes($this->callbackOutcomes, $modelClassName),
            defaultOutcomes: self::onlyRowShapeOutcomes($this->defaultOutcomes, $modelClassName),
            callbackRuns: $this->callbackRuns,
            proxiesNextMethod: $this->proxiesNextMethod,
        );
    }


    /**
     * @param non-empty-list<list<QueryChainMethod>> $outcomes
     * @return non-empty-list<list<QueryChainMethod>>
     */
    private static function onlyRowShapeOutcomes(array $outcomes, ?string $modelClassName): array
    {
        return array_map(
            fn (array $methods) => self::onlyRowShapeMethods($methods, $modelClassName),
            $outcomes,
        );
    }


    /**
     * Outcomes that only filtered are identical once their calls are dropped,
     * and splitting on them would describe one row several times over.
     *
     * @param non-empty-list<list<QueryChainMethod>> $outcomes
     * @return non-empty-list<list<QueryChainMethod>>
     */
    private static function distinct(array $outcomes): array
    {
        $distinct = [$outcomes[0]];

        foreach (array_slice($outcomes, 1) as $methods) {
            if (! array_any($distinct, fn (array $kept) => QueryChainMethod::listsAreSame($kept, $methods))) {
                $distinct[] = $methods;
            }
        }

        return $distinct;
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
     * Each builder the callback returns is one outcome. One that mutates and
     * returns nothing leaves none, so its body is read instead.
     *
     * @return non-empty-list<list<QueryChainMethod>>
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
            return [[]];
        }

        $returnedBuilder = $callbackType->getReturnType(
            args: ArgumentList::fromTypes([self::createProbeBuilderType($chain)], $scope),
            callerNode: $callerNode,
        );

        $chains = BuilderType::chainsIn($returnedBuilder);

        if ($chains === []) {
            return [self::methodsInCallbackBody($callerNode, $parameterName, $parameterIndex, $scope)];
        }

        return array_map(fn (QueryChain $returnedChain) => $returnedChain->methods, $chains);
    }


    private static function createProbeBuilderType(QueryChain $chain): BuilderType
    {
        return BuilderType::forChain(new QueryChain(
            modelClassName: $chain->modelClassName,
            methods: [],
            isRawDatabaseQuery: $chain->isRawDatabaseQuery,
        ));
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
