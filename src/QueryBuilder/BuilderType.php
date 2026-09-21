<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
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


    public static function forChain(QueryChain $chain): self
    {
        return new self(
            chain: $chain,
            builderClassName: $chain->isRawDatabaseQuery ? QueryBuilder::class : EloquentBuilder::class,
        );
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
        return array_map(fn (self $builderType) => $builderType->chain, self::in($type));
    }


    /**
     * @return list<self>
     */
    public static function in(?Type $type): array
    {
        if ($type instanceof self) {
            return [$type];
        }

        if (! ($type instanceof UnionType)) {
            return [];
        }

        $builderTypes = [];

        foreach ($type->types as $variant) {
            $builderTypes = [...$builderTypes, ...self::in($variant)];
        }

        return $builderTypes;
    }
}
