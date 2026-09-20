<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;

final class QueryChainMethod
{
    public function __construct(
        public readonly string $name,
        public readonly ArgumentList $args,
    ) {}


    public function isSameAs(self $other): bool
    {
        return $this->name === $other->name && $this->args === $other->args;
    }
}
