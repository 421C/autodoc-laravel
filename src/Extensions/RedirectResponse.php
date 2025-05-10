<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\ClassExtension;

/**
 * Handles `Illuminate\Http\RedirectResponse` responses.
 */
class RedirectResponse extends ClassExtension
{
    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if ($phpClass->className === \Illuminate\Http\RedirectResponse::class) {
            $phpClass->scope->route?->addResponse(302, 'text/html', new StringType);

            return new UnknownType;
        }

        return null;
    }
}
