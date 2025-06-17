<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;

class Paginator
{
    public function __construct(
        /** @var PhpClass<object> */
        private PhpClass $paginatorPhpClass,

        /** @var ?class-string */
        private ?string $entryClass,
    ) {}

    public function resolveType(): Type
    {
        $paginatorType = $this->paginatorPhpClass
            ->resolveType(useExtensions: false)
            ->unwrapType($this->paginatorPhpClass->scope->config);

        if ($paginatorType instanceof ArrayType && $paginatorType->shape) {
            $entryType = $this->entryClass
                ? $this->paginatorPhpClass->scope->getPhpClassInDeeperScope($this->entryClass)->resolveType()
                : new ObjectType;

            $paginatorType->shape['data'] = new ArrayType(itemType: $entryType);

            $paginatorType->shape['links'] = new ArrayType(
                itemType: new ArrayType(
                    shape: [
                        'url' => new UnionType([new StringType, new NullType]),
                        'label' => new StringType,
                        'active' => new BoolType,
                    ]
                )
            );
        }

        return $paginatorType;
    }
}
