<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Finds the `$this->hasMany(Rocket::class, ...)` call defining a relation
 * method, for models whose relation methods carry no generic PHPDoc tag.
 */
final class RelationFactoryCallFinder extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $methodName,
    ) {}

    public ?string $factoryName = null;

    public ?Node\Expr $relatedClassArgument = null;


    #[Override]
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassMethod && $node->name->toString() !== $this->methodName) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($this->factoryName !== null) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($this->isRelationFactoryCall($node)) {
            $this->factoryName = $node->name->toString();
            $firstArgument = $node->args[0] ?? null;

            $this->relatedClassArgument = $firstArgument instanceof Node\Arg ? $firstArgument->value : null;
        }

        return null;
    }


    /**
     * @phpstan-assert-if-true Node\Expr\MethodCall&object{name: Node\Identifier} $node
     */
    private function isRelationFactoryCall(Node $node): bool
    {
        return $node instanceof Node\Expr\MethodCall
            && $node->var instanceof Node\Expr\Variable
            && $node->var->name === 'this'
            && $node->name instanceof Node\Identifier
            && isset(Relation::FACTORY_METHODS[$node->name->toString()]);
    }
}
