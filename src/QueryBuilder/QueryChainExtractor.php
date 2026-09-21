<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnresolvedParserNodeType;
use AutoDoc\Laravel\Helpers\ResolvesModelTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use WeakMap;

final class QueryChainExtractor
{
    use ResolvesModelTypes;

    public function __construct(
        private Scope $scope,
    ) {}

    /** @var ?class-string<Model> */
    private ?string $modelClassName = null;

    private bool $isRawDatabaseQuery = false;

    /** @var list<list<QueryChainMethod>> */
    private array $methodVariants = [[]];

    private bool $shortCircuitsToNull = false;

    /** @var list<QueryChain> */
    private array $rootChains = [];

    /** @var array<int, Type> */
    private array $resolvedReceiverTypes = [];


    /**
     * A finisher is extracted at least twice, once for its side effects and
     * once for its return type. The same node analysed from a different scope
     * can see different variable types, so both are part of the key.
     *
     * @var ?WeakMap<Node\Expr, WeakMap<Scope, list<QueryChain>>>
     */
    private static ?WeakMap $cache = null;


    /**
     * @return list<QueryChain>
     */
    public function extract(Node\Expr $queryNode): array
    {
        self::$cache ??= new WeakMap;

        /** @var WeakMap<Scope, list<QueryChain>> $chainsByScope */
        $chainsByScope = self::$cache[$queryNode] ?? new WeakMap;

        self::$cache[$queryNode] = $chainsByScope;

        if (! isset($chainsByScope[$this->scope])) {
            $chainsByScope[$this->scope] = $this->walkAndBuildChains($queryNode);
        }

        return $chainsByScope[$this->scope];
    }


    /**
     * @return list<QueryChain>
     */
    private function walkAndBuildChains(Node\Expr $queryNode): array
    {
        $this->walkChain($queryNode);

        $chains = $this->rootChains
            ? $this->continueRootChains()
            : array_map(fn (array $methods) => new QueryChain(
                modelClassName: $this->modelClassName,
                methods: $methods,
                isRawDatabaseQuery: $this->isRawDatabaseQuery,
                shortCircuitsToNull: $this->shortCircuitsToNull,
            ), $this->methodVariants);

        return array_values(array_filter(
            $chains,
            fn (QueryChain $chain) => ($chain->modelClassName !== null || $chain->isRawDatabaseQuery)
                && $this->methodsAreAnalyzable($chain),
        ));
    }


    /**
     * @return list<QueryChain>
     */
    private function continueRootChains(): array
    {
        $chains = [];

        foreach ($this->rootChains as $rootChain) {
            foreach ($this->methodVariants as $methods) {
                $chain = $rootChain;

                foreach ($methods as $method) {
                    $chain = $chain->withMethod($method);
                }

                $chains[] = $this->shortCircuitsToNull ? $chain->withShortCircuitToNull() : $chain;
            }
        }

        return $chains;
    }


    /**
     * @param array<int, true> $visitedPositions
     */
    private function walkChain(Node\Expr $expr, array $visitedPositions = []): void
    {
        if ($expr instanceof MethodCall || $expr instanceof NullsafeMethodCall) {
            $this->walkChain($expr->var, $visitedPositions);

            if ($expr instanceof NullsafeMethodCall && $this->receiverCanBeNull($expr->var)) {
                $this->shortCircuitsToNull = true;
            }

            if ($this->rootChainInRelation($expr)) {
                return;
            }

            $this->recordMethod($expr);

        } else if ($expr instanceof StaticCall) {
            if ($expr->class instanceof Node\Expr) {
                if (! $this->rootChainInClass($this->resolveModelClassNameInExpression($expr->class))) {
                    $this->walkChain($expr->class, $visitedPositions);
                }

            } else if (! $this->rootChainInClass($this->scope->getResolvedClassName($expr->class))) {
                return;
            }

            $this->recordMethod($expr);

        } else if ($expr instanceof Node\Expr\Variable) {
            $this->walkVariable($expr, $visitedPositions);
        }
    }


    private function rootChainInClass(?string $className): bool
    {
        if (! $className) {
            return false;
        }

        if (is_a($className, DB::class, true)) {
            $this->isRawDatabaseQuery = true;

            return true;
        }

        if (! is_subclass_of($className, Model::class)) {
            return false;
        }

        $this->modelClassName = $className;

        return true;
    }


    private function rootChainInRelation(MethodCall|NullsafeMethodCall $expr): bool
    {
        if ($this->isRawDatabaseQuery) {
            return false;
        }

        $methodName = $this->scope->getRawValueFromNode($expr->name);

        if (! is_string($methodName)) {
            return false;
        }

        $modelClassName = $this->modelClassName ?? $this->resolveReceiverModelClassName($expr->var);

        if (! $modelClassName) {
            return false;
        }

        $modelPhpClass = $this->scope->getPhpClassInDeeperScope($modelClassName);

        if (! $modelPhpClass->getReflection()->hasMethod($methodName)) {
            return false;
        }

        $relatedModelClassName = (new Relation(
            modelPhpClass: $modelPhpClass,
            name: $methodName,
        ))->getRelatedModelClassName();

        if (! $relatedModelClassName) {
            return false;
        }

        $this->modelClassName = $relatedModelClassName;
        $this->methodVariants = [[]];

        return true;
    }


    /**
     * @param array<int, true> $visitedPositions
     */
    private function walkVariable(Node\Expr\Variable $expr, array $visitedPositions): void
    {
        $rootChains = BuilderType::chainsIn($this->resolveReceiverType($expr));

        if ($rootChains) {
            $this->rootChains = $rootChains;

            return;
        }

        $position = $expr->getAttribute('startFilePos');

        if (is_int($position)) {
            if (isset($visitedPositions[$position])) {
                return;
            }

            $visitedPositions[$position] = true;
        }

        if (! is_string($expr->name)) {
            return;
        }

        foreach ($this->scope->variables->events->getDirectAssignmentTypes($expr->name) as $type) {
            if ($type instanceof UnresolvedParserNodeType && $type->node instanceof Node\Expr) {
                $this->walkChain($type->node, $visitedPositions);

                return;
            }
        }
    }


    private function recordMethod(MethodCall|NullsafeMethodCall|StaticCall $expr): void
    {
        $method = new QueryChainMethod(
            name: (string) $this->scope->getRawValueFromNode($expr->name),
            args: ArgumentList::fromArgNodes($expr->args, $this->scope),
        );

        foreach ($this->methodVariants as $index => $methods) {
            $this->methodVariants[$index] = [...$methods, $method];
        }

        $this->recordCallbackMethods($expr, $method);
    }


    private function recordCallbackMethods(MethodCall|NullsafeMethodCall|StaticCall $expr, QueryChainMethod $method): void
    {
        $conditional = ConditionalCallback::read($method, $expr, $this->provisionalChain(), $this->scope);

        if ($conditional === null) {
            return;
        }

        $conditional = $conditional->withoutRowPreservingCalls($this->modelClassName);

        match ($conditional->callbackRuns) {
            true => $this->appendToEveryVariant($conditional->callbackMethods),
            false => $this->appendToEveryVariant($conditional->defaultMethods),
            null => $this->splitVariants($conditional->callbackMethods, $conditional->defaultMethods),
        };
    }


    private function provisionalChain(): QueryChain
    {
        return new QueryChain(
            modelClassName: $this->modelClassName,
            methods: [],
            isRawDatabaseQuery: $this->isRawDatabaseQuery,
        );
    }

    /**
     * @param list<QueryChainMethod> $methods
     */
    private function appendToEveryVariant(array $methods): void
    {
        if ($methods === []) {
            return;
        }

        foreach ($this->methodVariants as $index => $variant) {
            $this->methodVariants[$index] = [...$variant, ...$methods];
        }
    }


    /**
     * @param list<QueryChainMethod> $callbackMethods
     * @param list<QueryChainMethod> $defaultMethods
     */
    private function splitVariants(array $callbackMethods, array $defaultMethods): void
    {
        if ($callbackMethods === [] && $defaultMethods === []) {
            return;
        }

        if (count($this->methodVariants) * 2 > QueryChain::MAX_VARIANTS) {
            $this->appendToEveryVariant(array_map(
                fn (QueryChainMethod $method) => $method->asConditional(),
                [...$defaultMethods, ...$callbackMethods],
            ));

            return;
        }

        $split = [];

        foreach ($this->methodVariants as $variant) {
            $split[] = [...$variant, ...$defaultMethods];
            $split[] = [...$variant, ...$callbackMethods];
        }

        $this->methodVariants = $split;
    }



    private function receiverCanBeNull(Node\Expr $expr): bool
    {
        $receiverType = $this->resolveReceiverType($expr);

        return $receiverType !== null && $this->typeIncludesNull($receiverType);
    }


    /**
     * @return ?class-string<Model>
     */
    private function resolveReceiverModelClassName(Node\Expr $expr): ?string
    {
        $receiverType = $this->resolveReceiverType($expr);

        return $receiverType ? $this->resolveModelClassName($receiverType) : null;
    }


    /**
     * A static call can name its class through a variable holding either a
     * model instance or a model class-string.
     *
     * @return ?class-string<Model>
     */
    private function resolveModelClassNameInExpression(Node\Expr $expr): ?string
    {
        $expressionType = $this->resolveReceiverType($expr);

        if (! $expressionType) {
            return null;
        }

        $modelClassName = $this->resolveModelClassName($expressionType);

        if ($modelClassName) {
            return $modelClassName;
        }

        if (! ($expressionType instanceof StringType)) {
            return null;
        }

        $values = $expressionType->getPossibleValues() ?? [];

        return count($values) === 1 && is_subclass_of($values[0], Model::class, true) ? $values[0] : null;
    }


    private function resolveReceiverType(Node\Expr $expr): ?Type
    {
        if (! ($expr instanceof Node\Expr\Variable
            || $expr instanceof Node\Expr\PropertyFetch
            || $expr instanceof Node\Expr\NullsafePropertyFetch
            || $expr instanceof Node\Expr\ArrayDimFetch)
        ) {
            return null;
        }

        return $this->resolvedReceiverTypes[spl_object_id($expr)] ??= $this->scope->withoutSideEffects(
            fn () => $this->scope->resolveType($expr)->unwrapType($this->scope->config),
        );
    }


    private function methodsAreAnalyzable(QueryChain $chain): bool
    {
        $lastMethodIndex = count($chain->methods) - 1;

        for ($index = 0; $index < $lastMethodIndex; $index++) {
            if (BuilderMethodClassifier::terminatesBuilderChain($chain->methods[$index]->name)) {
                return false;
            }
        }

        if (! (config('autodoc.laravel.abandon_query_builder_parsing_on_unknown_methods') ?? false)) {
            return true;
        }

        foreach ($chain->methods as $method) {
            if (! BuilderMethodClassifier::isKnownBuilderMethod($method->name, $chain->modelClassName)) {
                return false;
            }
        }

        return true;
    }
}
