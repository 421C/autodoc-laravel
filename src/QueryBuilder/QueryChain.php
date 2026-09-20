<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use Illuminate\Database\Eloquent\Model;

final class QueryChain
{
    public function __construct(
        /** @var ?class-string<Model> */
        public readonly ?string $modelClassName,

        /** @var list<QueryChainMethod> */
        public readonly array $methods,
        public readonly bool $isRawDatabaseQuery = false,
        public readonly bool $shortCircuitsToNull = false,
    ) {}


    public function isEloquentModelQuery(): bool
    {
        return $this->modelClassName !== null && ! $this->isRawDatabaseQuery;
    }


    public function withMethod(QueryChainMethod $method): self
    {
        return new self(
            modelClassName: $this->modelClassName,
            methods: [...$this->methods, $method],
            isRawDatabaseQuery: $this->isRawDatabaseQuery,
            shortCircuitsToNull: $this->shortCircuitsToNull,
        );
    }


    public function withShortCircuitToNull(): self
    {
        return new self(
            modelClassName: $this->modelClassName,
            methods: $this->methods,
            isRawDatabaseQuery: $this->isRawDatabaseQuery,
            shortCircuitsToNull: true,
        );
    }


    public function equals(self $other): bool
    {
        if ($this->modelClassName !== $other->modelClassName
            || $this->isRawDatabaseQuery !== $other->isRawDatabaseQuery
            || $this->shortCircuitsToNull !== $other->shortCircuitsToNull
            || count($this->methods) !== count($other->methods)
        ) {
            return false;
        }

        foreach ($this->methods as $index => $method) {
            if (! $method->isSameAs($other->methods[$index])) {
                return false;
            }
        }

        return true;
    }
}
