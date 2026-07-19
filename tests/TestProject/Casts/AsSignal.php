<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<string, string>
 */
class AsSignal implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value ? 'on' : 'off';
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): bool
    {
        return $value === 'on';
    }
}
