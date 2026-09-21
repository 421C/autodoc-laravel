<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use Illuminate\Database\Eloquent\Model;

final class QueryChain
{
    public const MAX_VARIANTS = 8;

    public function __construct(
        /** @var ?class-string<Model> */
        public readonly ?string $modelClassName,

        /** @var list<QueryChainMethod> */
        public readonly array $methods,
        public readonly bool $isRawDatabaseQuery = false,
        public readonly bool $shortCircuitsToNull = false,
        public readonly bool $nextMethodIsConditional = false,
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
            nextMethodIsConditional: false,
        );
    }


    public function withShortCircuitToNull(): self
    {
        return new self(
            modelClassName: $this->modelClassName,
            methods: $this->methods,
            isRawDatabaseQuery: $this->isRawDatabaseQuery,
            shortCircuitsToNull: true,
            nextMethodIsConditional: $this->nextMethodIsConditional,
        );
    }


    /**
     * `when($condition)` with no callback returns a `HigherOrderWhenProxy`,
     * which forwards one call to the builder and then steps out of the way.
     */
    public function withNextMethodConditional(bool $nextMethodIsConditional): self
    {
        return new self(
            modelClassName: $this->modelClassName,
            methods: $this->methods,
            isRawDatabaseQuery: $this->isRawDatabaseQuery,
            shortCircuitsToNull: $this->shortCircuitsToNull,
            nextMethodIsConditional: $nextMethodIsConditional,
        );
    }


    /**
     * @param list<self> $chains
     */
    public static function mergeVariants(array $chains): self
    {
        $first = $chains[0];
        $sharedLength = count($first->methods);

        foreach ($chains as $chain) {
            $sharedLength = min($sharedLength, self::sharedPrefixLength($first->methods, $chain->methods));
        }

        $methods = array_slice($first->methods, 0, $sharedLength);

        foreach ($chains as $chain) {
            foreach (array_slice($chain->methods, $sharedLength) as $method) {
                $conditionalMethod = $method->asConditional();

                if (! array_any($methods, fn (QueryChainMethod $kept) => $kept->isSameAs($conditionalMethod))) {
                    $methods[] = $conditionalMethod;
                }
            }
        }

        return new self(
            modelClassName: $first->modelClassName,
            methods: $methods,
            isRawDatabaseQuery: $first->isRawDatabaseQuery,
            shortCircuitsToNull: array_any($chains, fn (self $chain) => $chain->shortCircuitsToNull),
            nextMethodIsConditional: array_any($chains, fn (self $chain) => $chain->nextMethodIsConditional),
        );
    }


    /**
     * @param list<QueryChainMethod> $methods
     * @param list<QueryChainMethod> $otherMethods
     */
    private static function sharedPrefixLength(array $methods, array $otherMethods): int
    {
        $length = 0;

        while (isset($methods[$length], $otherMethods[$length])
            && $methods[$length]->isSameAs($otherMethods[$length])
        ) {
            $length++;
        }

        return $length;
    }


    public function equals(self $other): bool
    {
        return $this->modelClassName === $other->modelClassName
            && $this->isRawDatabaseQuery === $other->isRawDatabaseQuery
            && $this->shortCircuitsToNull === $other->shortCircuitsToNull
            && $this->nextMethodIsConditional === $other->nextMethodIsConditional
            && QueryChainMethod::listsAreSame($this->methods, $other->methods);
    }
}
