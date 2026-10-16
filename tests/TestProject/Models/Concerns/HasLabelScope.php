<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasLabelScope
{
    /**
     * @param Builder<$this> $query
     */
    #[Scope]
    protected function labeled(Builder $query): void
    {
        $query->select('name', 'visited');
    }
}
