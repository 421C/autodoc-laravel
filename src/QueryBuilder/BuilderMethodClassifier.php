<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

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
            'pluck',
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


    public static function terminatesBuilderChain(string $methodName): bool
    {
        if (self::supportsResultInference($methodName)) {
            return true;
        }

        return match (strtolower($methodName)) {
            'all',
            'findor',
            'cursor',
            'lazy',
            'lazybyid',
            'lazybyiddesc',
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
