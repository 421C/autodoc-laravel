<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

enum RelationKind
{
    case One;
    case Many;
    case Polymorphic;
}
