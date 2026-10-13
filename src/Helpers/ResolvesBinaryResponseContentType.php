<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;

trait ResolvesBinaryResponseContentType
{
    /**
     * @param class-string $responseClassName
     */
    protected function binaryResponseTypeWithContentType(string $responseClassName, int $headersIndex, ArgumentList $argTypes, Scope $scope): ?Type
    {
        $headersArgIndex = $argTypes->indexForParameter('headers', $headersIndex);

        if ($headersArgIndex === null) {
            return null;
        }

        $headersType = $scope->withScalarTypeValuesShown(fn () => $argTypes->get($headersArgIndex)->unwrapType($scope->config));

        if (! ($headersType instanceof ArrayType)) {
            return null;
        }

        foreach ($headersType->shape as $headerName => $headerValueType) {
            if (strtolower((string) $headerName) !== 'content-type') {
                continue;
            }

            $headerValue = $headerValueType->unwrapType($scope->config);

            if (! ($headerValue instanceof StringType) || ! is_string($headerValue->value) || $headerValue->value === '') {
                return null;
            }

            $binaryType = new ObjectType(
                className: $responseClassName,
                typeToDisplay: new StringType(format: 'binary'),
            );

            $binaryType->contentType = $headerValue->value;

            return $binaryType;
        }

        return null;
    }
}
