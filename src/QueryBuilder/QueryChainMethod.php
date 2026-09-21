<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;

final class QueryChainMethod
{
    public function __construct(
        public readonly string $name,
        public readonly ArgumentList $args,
        public readonly bool $runsConditionally = false,
    ) {}


    public function asConditional(): self
    {
        return new self($this->name, $this->args, runsConditionally: true);
    }


    public function isSameAs(self $other): bool
    {
        return $this->name === $other->name
            && $this->args === $other->args
            && $this->runsConditionally === $other->runsConditionally;
    }
}
