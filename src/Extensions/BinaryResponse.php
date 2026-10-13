<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\ClassExtension;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles file downloads and streamed responses.
 */
class BinaryResponse extends ClassExtension
{
    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if (is_a($phpClass->className, StreamedJsonResponse::class, true)) {
            return null;
        }

        if (is_a($phpClass->className, BinaryFileResponse::class, true)
            || is_a($phpClass->className, StreamedResponse::class, true)
        ) {
            $binaryType = new ObjectType(
                className: $phpClass->className,
                typeToDisplay: new StringType(format: 'binary'),
            );

            $binaryType->contentType = '*/*';

            return $binaryType;
        }

        return null;
    }
}
