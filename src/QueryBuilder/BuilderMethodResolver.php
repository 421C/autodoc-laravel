<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\Type;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class BuilderMethodResolver
{
    public function __construct(
        private Scope $scope,
    ) {}


    public function getReturnType(string $methodName, ArgumentList $arguments): Type
    {
        $method = $this->scope->getPhpClassInDeeperScope(EloquentBuilder::class)->getMethod(
            name: $methodName,
            args: $arguments,
        );

        if (! $method->exists()) {
            $method = $this->scope->getPhpClassInDeeperScope(QueryBuilder::class)->getMethod(
                name: $methodName,
                args: $arguments,
            );
        }

        return $method->getReturnType()->unwrapType($this->scope->config);
    }
}
