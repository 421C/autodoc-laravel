<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Validation;

use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;


class ConfirmedType extends Type
{
    public function __construct(
        public ?string $confirmationKey,
        public Type $type,
    ) {}


    public function toSchema(): array
    {
        return $this->type->toSchema();
    }
}
