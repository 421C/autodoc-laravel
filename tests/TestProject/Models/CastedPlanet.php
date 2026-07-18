<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use AutoDoc\Laravel\Tests\TestProject\Casts\AsSignal;
use Illuminate\Database\Eloquent\Model;

class CastedPlanet extends Model
{
    protected $table = 'planets';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited' => AsSignal::class,
        ];
    }
}
