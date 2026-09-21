<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Config;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

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


    public function chain(): QueryChain
    {
        return $this->builderTypes[0]->chain;
    }


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

        if ($conditional->callbackRuns !== null) {
            return $this->withMethods($conditional->callbackRuns
                ? $conditional->callbackMethods
                : $conditional->defaultMethods);
        }

        return $this->splitInto($conditional->callbackMethods, $conditional->defaultMethods);
    }


    public function toType(Config $config): Type
    {
        return count($this->builderTypes) === 1
            ? $this->builderTypes[0]
            : new UnionType($this->builderTypes)->unwrapType($config);
    }


    /**
     * @param list<QueryChainMethod> $callbackMethods
     * @param list<QueryChainMethod> $defaultMethods
     */
    private function splitInto(array $callbackMethods, array $defaultMethods): self
    {
        if (count($this->builderTypes) * 2 > QueryChain::MAX_VARIANTS) {
            return $this->withMethods(array_map(
                fn (QueryChainMethod $method) => $method->asConditional(),
                [...$defaultMethods, ...$callbackMethods],
            ));
        }

        $split = [];

        foreach ($this->builderTypes as $builderType) {
            $split[] = self::continued($builderType, $defaultMethods);
            $split[] = self::continued($builderType, $callbackMethods);
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
