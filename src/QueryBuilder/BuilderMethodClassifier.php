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
            'pluck',
            'paginate',
            'count',
            'exists',
            'doesntexist',
            'sum',
            'avg',
            'average' => true,
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


    public static function terminatesBuilderChain(string $methodName): bool
    {
        if (self::supportsResultInference($methodName)) {
            return true;
        }

        return match (strtolower($methodName)) {
            'all',
            'findor',
            'simplepaginate',
            'cursorpaginate',
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
            'min',
            'max',
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
            'value',
            'rawvalue',
            'solevalue',
            'sole',
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
