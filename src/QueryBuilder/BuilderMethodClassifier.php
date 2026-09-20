<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use Illuminate\Database\Eloquent\Model;

class BuilderMethodClassifier
{
    public static function supportsResultInference(string $methodName): bool
    {
        return match (strtolower($methodName)) {
            'get',
            'create',
            'first',
            'firstwhere',
            'firstorfail',
            'find',
            'findorfail',
            'firstornew',
            'firstorcreate',
            'updateorcreate',
            'sole',
            'firstor',
            'findor',
            'findmany',
            'pluck',
            'cursor',
            'lazy',
            'lazybyid',
            'lazybyiddesc',
            'paginate',
            'simplepaginate',
            'cursorpaginate',
            'count',
            'exists',
            'doesntexist',
            'sum',
            'avg',
            'average',
            'value',
            'min',
            'max' => true,
            default => false,
        };
    }


    public static function supportsModelStaticCall(string $methodName): bool
    {
        if (self::supportsResultInference($methodName)) {
            return true;
        }

        return match (strtolower($methodName)) {
            'insert',
            'insertorignore',
            'insertorthrow',
            'insertusing',
            'insertgetid',
            'insertusinggetid',
            'all' => true,
            default => false,
        };
    }


    /**
     * Write finishers whose return type is fully described by the builder's
     * own signature.
     */
    public static function resolvesFromBuilderSignature(string $methodName): bool
    {
        return str_starts_with(strtolower($methodName), 'insert');
    }


    /**
     * @param class-string<Model> $modelClassName
     */
    public static function startsBuilderChain(string $methodName, string $modelClassName): bool
    {
        if ($methodName === 'query' || $methodName === 'on' || $methodName === 'onWriteConnection') {
            return true;
        }

        if (self::terminatesBuilderChain($methodName)) {
            return false;
        }

        if (method_exists($modelClassName, 'scope' . ucfirst($methodName))) {
            return true;
        }

        return ! method_exists($modelClassName, $methodName);
    }


    /**
     * Finishers that throw `ModelNotFoundException` (rendered as HTTP 404) when
     * the Eloquent query yields no matching row.
     */
    public static function throwsModelNotFound(string $methodName): bool
    {
        return match (strtolower($methodName)) {
            'findorfail',
            'firstorfail',
            'sole' => true,
            default => false,
        };
    }


    public static function finisherBehindCallbackFallback(string $methodName): ?string
    {
        return match (strtolower($methodName)) {
            'firstor' => 'first',
            'findor' => 'find',
            default => null,
        };
    }


    public static function streamsRowsLazily(string $methodName): bool
    {
        return match (strtolower($methodName)) {
            'cursor',
            'lazy',
            'lazybyid',
            'lazybyiddesc' => true,
            default => false,
        };
    }


    public static function terminatesBuilderChain(string $methodName): bool
    {
        if (self::supportsResultInference($methodName)) {
            return true;
        }

        return match (strtolower($methodName)) {
            'all',
            'chunk',
            'chunkmap',
            'chunkbyid',
            'chunkbyiddesc',
            'orderedchunkbyid',
            'each',
            'eachbyid',
            'aggregate',
            'numericaggregate',
            'existsor',
            'doesntexistor',
            'insert',
            'insertorignore',
            'insertgetid',
            'insertusing',
            'insertorignoreusing',
            'update',
            'updatefrom',
            'updateorinsert',
            'upsert',
            'delete',
            'forcedelete',
            'increment',
            'incrementeach',
            'decrement',
            'decrementeach',
            'rawvalue',
            'solevalue',
            'tosql',
            'torawsql',
            'implode',
            'createquietly',
            'forcecreate',
            'forcecreatequietly',
            'touch' => true,
            default => false,
        };
    }
}
