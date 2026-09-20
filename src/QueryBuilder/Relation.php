<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

class Relation
{
    /**
     * @var array<string, class-string>
     */
    public const FACTORY_METHODS = [
        'hasOne' => HasOne::class,
        'hasMany' => HasMany::class,
        'belongsTo' => BelongsTo::class,
        'belongsToMany' => BelongsToMany::class,
        'hasOneThrough' => HasOneThrough::class,
        'hasManyThrough' => HasManyThrough::class,
        'morphOne' => MorphOne::class,
        'morphMany' => MorphMany::class,
        'morphTo' => MorphTo::class,
        'morphToMany' => MorphToMany::class,
        'morphedByMany' => MorphToMany::class,
    ];

    /** @var array<string, array{?class-string, ?class-string<Model>}> */
    private static array $parsedDefinitions = [];

    public function __construct(
        /** @var PhpClass<Model> */
        private PhpClass $modelPhpClass,
        public readonly string $name,

        /** @var string[] */
        public array $columns = [],

        /** @var array<string, Relation> */
        public array $relations = [],
    ) {
        $this->exportedName = $this->getExportedName();
    }

    public readonly string $exportedName;


    public function getExportedName(): string
    {
        $modelClassName = $this->modelPhpClass->className;

        return $modelClassName::$snakeAttributes ? Str::snake($this->name) : $this->name;
    }


    /**
     * @return ?class-string<Model>
     */
    public function getRelatedModelClassName(): ?string
    {
        return $this->getDefinition()[1];
    }


    /**
     * @return ?class-string
     */
    public function getRelationTypeClassName(): ?string
    {
        return $this->getDefinition()[0];
    }


    public function getKind(): ?RelationKind
    {
        return self::kindOf($this->getRelationTypeClassName());
    }


    public static function kindOf(?string $relationTypeClassName): ?RelationKind
    {
        if ($relationTypeClassName === null) {
            return null;
        }

        return match (true) {
            is_a($relationTypeClassName, MorphTo::class, true) => RelationKind::Polymorphic,

            is_a($relationTypeClassName, HasOne::class, true),
            is_a($relationTypeClassName, MorphOne::class, true),
            is_a($relationTypeClassName, HasOneThrough::class, true),
            is_a($relationTypeClassName, BelongsTo::class, true) => RelationKind::One,

            is_a($relationTypeClassName, HasMany::class, true),
            is_a($relationTypeClassName, MorphMany::class, true),
            is_a($relationTypeClassName, HasManyThrough::class, true),
            is_a($relationTypeClassName, BelongsToMany::class, true) => RelationKind::Many,

            default => null,
        };
    }


    public function getRelatedColumnType(string $column): ?Type
    {
        $relatedModelClassName = $this->getRelatedModelClassName();

        if (! $relatedModelClassName) {
            return null;
        }

        $relatedModelType = $this->modelPhpClass->scope->getPhpClassInDeeperScope($relatedModelClassName)->resolveType();
        $columnType = $relatedModelType->properties[$column] ?? $relatedModelType->hiddenProperties[$column] ?? null;

        return $columnType ? clone $columnType : null;
    }


    public function resolveType(): ?Type
    {
        return match ($this->getKind()) {
            RelationKind::One => new UnionType([$this->getRelatedModelObjectType(), new NullType]),

            RelationKind::Many => new ArrayType(
                itemType: $this->getRelatedModelObjectType(),
                className: Collection::class,
            ),

            RelationKind::Polymorphic => new UnionType([new ObjectType, new NullType]),

            null => null,
        };
    }


    /**
     * @return array{?class-string, ?class-string<Model>}
     */
    private function getDefinition(): array
    {
        $cacheKey = $this->modelPhpClass->className . '::' . $this->name;

        if (isset(self::$parsedDefinitions[$cacheKey])) {
            return self::$parsedDefinitions[$cacheKey];
        }

        if (! $this->modelPhpClass->getReflection()->hasMethod($this->name)) {
            return self::$parsedDefinitions[$cacheKey] = [null, null];
        }

        $definition = $this->parseGenericReturnTag() ?? $this->parseFactoryCall() ?? [null, null];

        return self::$parsedDefinitions[$cacheKey] = $definition;
    }


    /**
     * @return ?array{?class-string, ?class-string<Model>}
     */
    private function parseGenericReturnTag(): ?array
    {
        $phpDocReturnType = $this->modelPhpClass->getMethod($this->name)->getTypeFromPhpDocReturnTag();

        if (! $phpDocReturnType || ! ($phpDocReturnType->typeNode instanceof GenericTypeNode)) {
            return null;
        }

        $relationTypeClassName = $this->modelPhpClass->scope->getResolvedClassName($phpDocReturnType->typeNode->type->name);
        $firstGenericType = $phpDocReturnType->typeNode->genericTypes[0] ?? null;

        if (! ($firstGenericType instanceof IdentifierTypeNode) || ! self::kindOf($relationTypeClassName)) {
            return [$relationTypeClassName, null];
        }

        return [
            $relationTypeClassName,
            $this->resolveRelatedModelClassName($firstGenericType->name),
        ];
    }


    /**
     * @return ?array{?class-string, ?class-string<Model>}
     */
    private function parseFactoryCall(): ?array
    {
        $finder = new RelationFactoryCallFinder($this->name);

        $this->modelPhpClass->traverse($finder);

        if ($finder->factoryName === null) {
            return null;
        }

        $relatedClassArgument = $finder->relatedClassArgument;

        $relatedModelClassName = $relatedClassArgument instanceof Node\Expr\ClassConstFetch
            && $relatedClassArgument->class instanceof Node\Name
                ? $this->resolveRelatedModelClassName($relatedClassArgument->class->toString())
                : null;

        return [self::FACTORY_METHODS[$finder->factoryName], $relatedModelClassName];
    }


    /**
     * @return ?class-string<Model>
     */
    private function resolveRelatedModelClassName(string $name): ?string
    {
        $className = $this->modelPhpClass->scope->getResolvedClassName($name);

        if (! $className) {
            return null;
        }

        if (is_subclass_of($className, Model::class, true)) {
            return $className;
        }

        if ($this->modelPhpClass->scope->isDebugModeEnabled()) {
            throw new Exception('Relation "' . $this->name . '" of "' . $this->modelPhpClass->className . '" is not related to an Eloquent Model');
        }

        return null;
    }


    private function getRelatedModelObjectType(): ObjectType
    {
        $relatedModelClassName = $this->getRelatedModelClassName();

        if (! $relatedModelClassName) {
            return new ObjectType;
        }

        $objectType = clone $this->modelPhpClass->scope->getPhpClassInDeeperScope($relatedModelClassName)->resolveType();

        if ($this->columns) {
            $objectType->properties = array_filter($objectType->properties, fn ($propertyName) => in_array($propertyName, $this->columns), ARRAY_FILTER_USE_KEY);
        }

        foreach ($this->relations as $name => $relation) {
            $objectType->properties[$name] = $relation->resolveType() ?? new UnknownType;
        }

        return $objectType;
    }


    public static function clearCache(): void
    {
        self::$parsedDefinitions = [];
    }
}
