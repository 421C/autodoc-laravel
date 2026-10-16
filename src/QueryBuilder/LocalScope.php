<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\Scope;
use Illuminate\Database\Eloquent\Attributes\Scope as ScopeAttribute;
use PhpParser\Node;
use PhpParser\NodeVisitor\FirstFindingVisitor;
use ReflectionMethod;

final class LocalScope
{
    public static function method(string $scopeName, string $modelClassName): ?ReflectionMethod
    {
        if (method_exists($modelClassName, $scopeName)) {
            $method = new ReflectionMethod($modelClassName, $scopeName);

            if (! $method->isPrivate() && $method->getAttributes(ScopeAttribute::class) !== []) {
                return $method;
            }
        }

        $conventionalMethodName = 'scope' . ucfirst($scopeName);

        return method_exists($modelClassName, $conventionalMethodName)
            ? new ReflectionMethod($modelClassName, $conventionalMethodName)
            : null;
    }


    /**
     * @return non-empty-list<list<QueryChainMethod>>
     */
    public static function outcomes(ReflectionMethod $scopeMethod, QueryChain $chain, Scope $scope): array
    {
        if ($scope->depth > $scope->config->data['max_depth']) {
            return [[]];
        }

        $sourceClass = $scope->getPhpClassInDeeperScope(self::bodySourceClassName($scopeMethod));

        $methodFinder = new FirstFindingVisitor(
            fn (Node $node) => $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === $scopeMethod->name,
        );

        $sourceClass->traverse($methodFinder);
        $methodNode = $methodFinder->getFoundNode();

        if (! ($methodNode instanceof Node\Stmt\ClassMethod)) {
            return [[]];
        }

        return BuilderParameterReader::outcomesIn(
            function: $methodNode,
            chain: $chain,
            scope: $sourceClass->getMethod($scopeMethod->name)->scope,
        );
    }


    /**
     * @return class-string
     */
    private static function bodySourceClassName(ReflectionMethod $method): string
    {
        $candidates = [$method->getDeclaringClass()];

        while ($candidate = array_shift($candidates)) {
            if ($candidate->getFileName() === $method->getFileName()) {
                return $candidate->getName();
            }

            array_push($candidates, ...array_values($candidate->getTraits()));
        }

        return $method->getDeclaringClass()->getName();
    }
}
