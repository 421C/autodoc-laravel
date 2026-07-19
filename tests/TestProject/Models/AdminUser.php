<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_super' => 'boolean',
        ];
    }
}
