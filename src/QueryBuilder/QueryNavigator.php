<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\PhpFunctionArgument;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\DataTypes\UnresolvedParserNodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Throwable;

class QueryNavigator
{
    public function __construct(
        private Scope $scope,
    ) {}

    /** @var ?class-string<Model> */
    private ?string $modelClassName = null;

    private ?Type $modelType = null;

    private ?string $modelTableName = null;

    /**
     * @var array<int, array{
     *     name: string,
     *     args: PhpFunctionArgument[],
     * }>
     */
    private array $builderMethods = [];

    private bool $allMethodsAreBuilderMethods = true;

    private ?Type $collectionKeyType = null;


    public function getCollectionKeyType(): Type
    {
        return $this->collectionKeyType ?? new IntegerType;
    }


    public function getResultType(MethodCall|StaticCall $methodCall, string $methodName): ?Type
    {
        $rowType = $this->scope->withoutScalarTypeValueMerging(function () use ($methodCall) {
            return $this->getRowType($methodCall);
        });

        if (! $rowType) {
            return null;
        }

        if ($methodName === 'get' || $methodName === 'pluck') {
            return new ArrayType(itemType: $rowType, className: Collection::class);
        }

        if (in_array($methodName, ['firstOrFail', 'findOrFail', 'firstOrNew', 'firstOrCreate'])) {
            return $rowType;
        }

        if ($methodName === 'paginate') {
            return (new Paginator(
                paginatorPhpClass: $this->scope->getPhpClassInDeeperScope(LengthAwarePaginator::class),
                entryClass: $this->modelClassName,
                entryType: $rowType,
            ))->resolveType();
        }

        if (in_array($methodName, ['first', 'latest', 'oldest'])) {
            return new UnionType([$rowType, new NullType]);
        }

        if ($methodName === 'count') {
            return new IntegerType(minimum: 0);
        }

        $methodArgs = PhpFunctionArgument::list($methodCall->args, scope: $this->scope);

        try {
            $phpClassMethod = $this->scope->getPhpClassInDeeperScope(\Illuminate\Database\Eloquent\Builder::class)->getMethod(
                name: $methodName,
                args: $methodArgs,
            );

            return $phpClassMethod->getReturnType()->unwrapType($this->scope->config);

        } catch (Throwable $exception) {
            $phpClassMethod = $this->scope->getPhpClassInDeeperScope(\Illuminate\Database\Query\Builder::class)->getMethod(
                name: $methodName,
                args: $methodArgs,
            );

            return $phpClassMethod->getReturnType()->unwrapType($this->scope->config);
        }
    }


    public function getRowType(Node\Expr $queryNode): ?Type
    {
        $this->extractBuilderMethodsAndModel($queryNode);

        if (! $this->modelClassName) {
            return null;
        }

        $this->checkBuilderMethods();

        if (! $this->modelClassName) {
            return null;
        }

        if (! $this->allMethodsAreBuilderMethods) {
            return null;
        }

        /** @var Type[][] */
        $columnSetVariants = [];

        $modelPhpClass = $this->scope->getPhpClassInDeeperScope($this->modelClassName);

        $this->modelType = clone $modelPhpClass->resolveType();

        if (! ($this->modelType instanceof ObjectType)) {
            return null;
        }

        $this->modelTableName = app()->make($this->modelClassName)->getTable();

        foreach ($this->builderMethods as $builderMethod) {
            if ($builderMethod['name'] === 'select') {
                $columnSetVariants = [
                    $this->getColumnsFromArguments($builderMethod['args']),
                ];
            }

            if ($builderMethod['name'] === 'addSelect') {
                $columns = $this->getColumnsFromArguments($builderMethod['args']);

                if ($columnSetVariants) {
                    foreach ($columnSetVariants as $index => $columnSet) {
                        $columnSetVariants[$index] = array_merge($columnSet, $columns);
                    }

                } else {
                    $columnSetVariants = [$columns];
                }
            }

            if ($builderMethod['name'] === 'pluck') {
                $columnArg = $builderMethod['args'][0] ?? null;
                $keyArg = $builderMethod['args'][1] ?? null;

                if (! $columnArg) {
                    return null;
                }

                $keyArgType = $keyArg?->getType()?->unwrapType($keyArg?->scope->config);

                if ($keyArgType instanceof StringType) {
                    $keyArgStrings = $keyArgType->getPossibleValues() ?? [];

                    if (count($keyArgStrings) === 1) {
                        [$propertyName, $propertyType] = $this->getColumnType($keyArgStrings[0]);

                        $this->collectionKeyType = $propertyType;

                    } else {
                        $this->collectionKeyType = new UnionType(
                            array_filter(array_map(
                                fn ($keyArgString) => $this->getColumnType($keyArgString)[1],
                                $keyArgStrings,
                            ))
                        );
                    }
                }

                $columnArgType = $columnArg->getType()?->unwrapType($columnArg->scope->config);

                if ($columnArgType instanceof StringType) {
                    $columnArgStrings = $columnArgType->getPossibleValues() ?? [];

                    if (count($columnArgStrings) === 1) {
                        return $this->getColumnType($columnArgStrings[0])[1] ?? new UnknownType;
                    }

                    return new UnionType(
                        array_filter(array_map(
                            fn ($columnArgString) => $this->getColumnType($columnArgString)[1],
                            $columnArgStrings,
                        ))
                    );
                }
            }

            if (in_array($builderMethod['name'], ['get', 'all'])) {
                if (! empty($builderMethod['args'])) {
                    $columnSetVariants = [
                        $this->getColumnsFromArguments($builderMethod['args']),
                    ];
                }
            }
        }

        if (! $columnSetVariants) {
            return $this->modelType;
        }

        $rowType = new UnionType;

        foreach ($columnSetVariants as $columnSet) {
            $columns = [];

            foreach ($columnSet as $column) {
                if ($column instanceof StringType) {
                    $columnStrings = $column->getPossibleValues() ?? [];

                    foreach ($columnStrings as $columnString) {
                        [$propertyName, $propertyType] = $this->getColumnType($columnString);

                        $columns[$propertyName] = $propertyType ?? new UnknownType;
                    }
                }
            }

            $objectType = clone $this->modelType;
            $objectType->properties = $columns;

            $rowType->types[] = $objectType;
        }

        return $rowType;
    }


    private function extractBuilderMethodsAndModel(Node\Expr $expr): void
    {
        if ($expr instanceof MethodCall || $expr instanceof StaticCall) {
            if ($expr instanceof MethodCall) {
                $this->extractBuilderMethodsAndModel($expr->var);

            } else {
                if ($expr->class instanceof Node\Expr) {
                    $this->extractBuilderMethodsAndModel($expr->class);

                } else {
                    $className = $this->scope->getResolvedClassName($expr->class);

                    if (! $className) {
                        return;
                    }

                    if (! is_subclass_of($className, Model::class)) {
                        return;
                    }

                    $this->modelClassName = $className;
                }
            }

            $methodName = (string) $this->scope->getRawValueFromNode($expr->name);

            $this->builderMethods[] = [
                'name' => $methodName,
                'args' => PhpFunctionArgument::list($expr->args, $this->scope),
            ];

        } else if ($expr instanceof Node\Expr\Variable) {
            $unresolvedVarType = $this->scope->getVariableType($expr);

            if ($unresolvedVarType instanceof UnresolvedParserNodeType
                && $unresolvedVarType->node instanceof Node\Expr
            ) {
                $this->extractBuilderMethodsAndModel($unresolvedVarType->node);
            }
        }
    }


    private function checkBuilderMethods(): void
    {
        $finisherMethods = array_fill_keys([
            'all', 'get', 'find', 'findor', 'findorfail', 'first', 'firstorfail', 'firstornew', 'firstorcreate',
            'latest', 'oldest', 'pluck', 'paginate', 'simplepaginate', 'cursorpaginate', 'cursor',
            'lazy', 'lazybyid', 'lazybyiddesc',
            'chunk', 'chunkmap', 'chunkbyid', 'chunkbyiddesc', 'orderedchunkbyid', 'each', 'eachbyid',
            'sum', 'avg', 'min', 'max', 'average', 'aggregate', 'numericaggregate',
            'count', 'exists', 'doesntexist', 'existsor', 'doesntexistor',
            'insert', 'insertorignore', 'insertgetid', 'insertusing', 'insertorignoreusing',
            'update', 'updatefrom', 'updateorinsert', 'upsert', 'delete', 'forcedelete',
            'increment', 'incrementeach', 'decrement', 'decrementeach',
            'value', 'rawvalue', 'solevalue', 'sole', 'tosql', 'torawsql', 'implode',
            'create', 'createquietly', 'forcecreate', 'forcecreatequietly', 'touch',
        ], true);

        $methodCount = count($this->builderMethods);

        for ($i = 0; $i < $methodCount - 1; $i++) {
            $methodName = strtolower($this->builderMethods[$i]['name']);

            if (isset($finisherMethods[$methodName])) {
                $this->allMethodsAreBuilderMethods = false;

                return;
            }
        }

        if (config('autodoc.laravel.abandon_query_builder_parsing_on_unknown_methods') ?? false) {
            static $builderClassMethods = null;

            if ($builderClassMethods === null) {
                $builderClassMethods = array_fill_keys(array_merge(
                    array_map(strtolower(...), get_class_methods(\Illuminate\Database\Eloquent\Builder::class)),
                    array_map(strtolower(...), get_class_methods(\Illuminate\Database\Query\Builder::class)),
                ), true);
            }

            foreach ($this->builderMethods as $method) {
                $methodName = strtolower($method['name']);

                if (! isset($builderClassMethods[$methodName])) {
                    $this->allMethodsAreBuilderMethods = false;

                    return;
                }
            }
        }
    }


    /**
     * @param PhpFunctionArgument[] $args
     * @return Type[]
     */
    private function getColumnsFromArguments(array $args): array
    {
        $columns = [];
        $firstArgIsArray = false;

        if (isset($args[0])) {
            $firstArgType = $args[0]->getType()?->unwrapType($args[0]->scope->config);

            if ($firstArgType instanceof ArrayType) {
                /**
                 * Example: ->select([
                 *     'id',
                 *     'name',
                 * ])
                 */
                $firstArgIsArray = true;
                $arrayItemType = $firstArgType->convertShapeToTypePair()->itemType;

                if ($arrayItemType instanceof UnionType) {
                    // array<ColumnA | ColumnB> -> [ColumnA, ColumnB]
                    $columns = $arrayItemType->types;

                } else if ($arrayItemType) {
                    // array<ColumnA> -> [ColumnA]
                    $columns = [$arrayItemType];
                }
            }
        }

        if (! $firstArgIsArray) {
            /**
             * Example: ->select('id', 'name')
             */
            foreach ($args as $index => $arg) {
                if ($index === 0 && isset($firstArgType)) {
                    $argType = $firstArgType;

                } else {
                    $argType = $arg->getType()?->unwrapType($arg->scope->config);
                }

                if ($argType) {
                    $columns[] = $argType;
                }
            }
        }

        return $columns;
    }


    /**
     * @return array{string|null, Type|null}
     */
    private function getColumnType(string $column): array
    {
        $prefix = null;
        $alias = null;

        if (str_contains($column, '.')) {
            [$prefix, $column] = explode('.', $column);
        }

        if (str_contains($column, ' as ')) {
            [$column, $alias] = explode(' as ', $column);
        }

        $propertyName = trim($alias ?? $column);
        $propertyType = new UnknownType;

        if (! $propertyName) {
            return [null, null];
        }

        if (! $prefix || $prefix === $this->modelTableName) {
            if (isset($this->modelType->properties[$column])) {
                $propertyType = clone $this->modelType->properties[$column];
            }
        }

        return [$propertyName, $propertyType];
    }
}
