<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Config;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

final class BuilderState
{
    private function __construct(
        /** @var non-empty-list<BuilderType> */
        private readonly array $builderTypes,
    ) {}


    public static function in(?Type $type): ?self
    {
        $builderTypes = BuilderType::in($type);

        if ($builderTypes === []) {
            return null;
        }

        return new self(count($builderTypes) > QueryChain::MAX_VARIANTS
            ? [self::mergedInto($builderTypes)]
            : $builderTypes);
    }


    public static function fromChain(QueryChain $chain): self
    {
        return new self([BuilderType::forChain($chain)]);
    }


    public function chain(): QueryChain
    {
        return $this->builderTypes[0]->chain;
    }


    /**
     * @return list<QueryChain>
     */
    public function chains(): array
    {
        return array_map(fn (BuilderType $builderType) => $builderType->chain, $this->builderTypes);
    }


    /**
     * @return ?class-string<Model>
     */
    public function modelClassName(): ?string
    {
        return $this->chain()->modelClassName;
    }


    public function withMethod(QueryChainMethod $method): self
    {
        return $this->withMethods([$method]);
    }


    public function applying(ConditionalCallback $conditional): self
    {
        if ($conditional->isEmpty()) {
            return $this;
        }

        return $this->splitInto($conditional->outcomes());
    }


    public function toType(Config $config): Type
    {
        return count($this->builderTypes) === 1
            ? $this->builderTypes[0]
            : new UnionType($this->builderTypes)->unwrapType($config);
    }


    /**
     * @param non-empty-list<list<QueryChainMethod>> $outcomes
     */
    private function splitInto(array $outcomes): self
    {
        if (count($this->builderTypes) * count($outcomes) > QueryChain::MAX_VARIANTS) {
            return $this->withMethods(array_map(
                fn (QueryChainMethod $method) => $method->asConditional(),
                array_merge(...$outcomes),
            ));
        }

        $split = [];

        foreach ($this->builderTypes as $builderType) {
            foreach ($outcomes as $methods) {
                $split[] = self::continued($builderType, $methods);
            }
        }

        return new self($split);
    }


    /**
     * @param list<QueryChainMethod> $methods
     */
    private function withMethods(array $methods): self
    {
        return new self(array_map(
            fn (BuilderType $builderType) => self::continued($builderType, $methods),
            $this->builderTypes,
        ));
    }


    /**
     * @param non-empty-list<BuilderType> $builderTypes
     */
    private static function mergedInto(array $builderTypes): BuilderType
    {
        return new BuilderType(
            chain: QueryChain::mergeVariants(array_map(
                fn (BuilderType $builderType) => $builderType->chain,
                $builderTypes,
            )),
            builderClassName: $builderTypes[0]->className ?? EloquentBuilder::class,
        );
    }


    /**
     * @param list<QueryChainMethod> $methods
     */
    private static function continued(BuilderType $builderType, array $methods): BuilderType
    {
        $chain = $builderType->chain;

        foreach ($methods as $method) {
            $chain = $chain->withMethod($method);
        }

        return new BuilderType(
            chain: $chain,
            builderClassName: $builderType->className ?? EloquentBuilder::class,
        );
    }
}
