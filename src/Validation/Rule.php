<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Validation;

use AutoDoc\Analyzer\ArgumentList;


class Rule
{
    public function __construct(
        /**
         * @var class-string
         */
        public string $className,
        public ?ArgumentList $args = null,
    ) {}
}
