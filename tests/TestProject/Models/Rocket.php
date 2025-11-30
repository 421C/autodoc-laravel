<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rocket extends Model
{
    public function toArray(): array
    {
        $id = rand(1, 100);

        return [
            'id' => $id,
            'name' => 'Rocket ' . $id,
        ];
    }

    /**
     * @return HasOne<Planet, $this>
     */
    public function targetPlanet(): HasOne
    {
        return $this->hasOne(Planet::class, 'id', 'target_planet_id');
    }
}
