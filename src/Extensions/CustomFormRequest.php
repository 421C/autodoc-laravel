<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\ClassExtension;
use AutoDoc\Laravel\Validation\ValidationRulesParser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * Handles parsing `Illuminate\Foundation\Http\FormRequest` subclasses.
 */
class CustomFormRequest extends ClassExtension
{
    use ValidationRulesParser;

    public function getRequestType(PhpClass $phpClass): ?Type
    {
        if (! is_subclass_of($phpClass->className, FormRequest::class)) {
            return null;
        }

        if (self::$cache[$phpClass->className] ?? false) {
            return self::$cache[$phpClass->className];
        }

        $validationRulesMethod = $phpClass->getMethod('rules');

        if (! $validationRulesMethod->exists()) {
            return null;
        }

        $validationArray = $validationRulesMethod->getReturnType(usePhpDocIfAvailable: false);

        if (! isset($validationArray->shape)) {
            return null;
        }

        $validatedStructure = Arr::undot($validationArray->shape);

        $requestType = $this->parseValidatedStructure($validatedStructure);

        self::$cache[$phpClass->className] = $requestType;

        return $requestType;
    }


    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if (! is_subclass_of($phpClass->className, FormRequest::class)) {
            return null;
        }

        $phpClass->typeToDisplay = $this->getRequestType($phpClass);

        return $phpClass->resolveType(useExtensions: false);
    }

    /**
     * @var array<class-string, Type>
     */
    private static array $cache = [];
}
