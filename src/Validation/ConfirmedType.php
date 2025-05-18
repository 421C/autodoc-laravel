<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Validation;

use AutoDoc\Config;
use AutoDoc\DataTypes\Type;


class ConfirmedType extends Type
{
    public function __construct(
        public ?string $confirmationKey,
        public Type $type,
    ) {}


    public function toSchema(?Config $config = null): array
    {
        return $this->type->toSchema($config);
    }
}
