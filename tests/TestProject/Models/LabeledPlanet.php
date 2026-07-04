<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;

class LabeledPlanet extends Model
{
    protected $table = 'planets';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited' => 'boolean',
        ];
    }

    public function toArray(): array
    {
        return array_merge($this->attributesToArray(), [
            'label' => 'L',
        ]);
    }
}
