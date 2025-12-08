<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\ClassExtension;

/**
 * Handles `Illuminate\View\View` responses.
 */
class ViewResponse extends ClassExtension
{
    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if ($phpClass->className === \Illuminate\View\View::class
            || $phpClass->className === \Illuminate\Contracts\View\View::class
            || is_a($phpClass->className, \Illuminate\Contracts\Support\Renderable::class, true)
        ) {
            $viewType = new ObjectType(
                className: $phpClass->className,
                typeToDisplay: new StringType,
            );

            $viewType->contentType = 'text/html';

            return $viewType;
        }

        return null;
    }
}
