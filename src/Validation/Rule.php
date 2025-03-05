<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Validation;

use AutoDoc\Analyzer\PhpFunctionArgument;


class Rule
{
    public function __construct(
        /**
         * @var class-string
         */
        public string $className,

        /**
         * @var PhpFunctionArgument[]
         */
        public array $args,
    ) {}
}
