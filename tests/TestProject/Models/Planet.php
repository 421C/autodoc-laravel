<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planet extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<SpaceStation, $this>
     */
    public function spaceStations(): BelongsToMany
    {
        return $this->belongsToMany(SpaceStation::class);
    }

    /**
     * @return HasMany<Rocket, $this>
     */
    public function rockets(): HasMany
    {
        return $this->hasMany(Rocket::class);
    }
}
