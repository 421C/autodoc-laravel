<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use Override;

final class BuilderType extends ObjectType
{
    /**
     * @param class-string $builderClassName
     */
    public function __construct(
        public readonly QueryChain $chain,
        string $builderClassName,
    ) {
        parent::__construct(className: $builderClassName);
    }


    #[Override]
    public function canMergeWith(ObjectType $other): bool
    {
        return $other instanceof self && $other->chain->equals($this->chain);
    }


    /**
     * @return list<QueryChain>
     */
    public static function chainsIn(?Type $type): array
    {
        if ($type instanceof self) {
            return [$type->chain];
        }

        if (! ($type instanceof UnionType)) {
            return [];
        }

        $chains = [];

        foreach ($type->types as $variant) {
            $chains = [...$chains, ...self::chainsIn($variant)];
        }

        return $chains;
    }
}
