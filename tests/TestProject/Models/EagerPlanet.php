<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EagerPlanet extends Model
{
    protected $table = 'planets';

    protected $with = ['rockets'];

    protected $withCount = ['rockets'];

    /**
     * @return HasMany<Rocket, $this>
     */
    public function rockets(): HasMany
    {
        return $this->hasMany(Rocket::class, 'target_planet_id');
    }
}
