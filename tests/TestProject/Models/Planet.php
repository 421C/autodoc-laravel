<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

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

    public function setSlugAttribute(string $value): void
    {
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * @return BelongsToMany<SpaceStation, $this>
     */
    public function spaceStations(): BelongsToMany
    {
        return $this->belongsToMany(SpaceStation::class);
    }

    /**
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeVisited(Builder $query): Builder
    {
        return $query->where('visited', true);
    }

    /**
     * @phpstan-ignore missingType.generics
     */
    public function beacons(): MorphMany
    {
        return $this->morphMany(Beacon::class, 'beaconable');
    }

    /**
     * @return HasMany<Rocket, $this>
     */
    public function rockets(): HasMany
    {
        return $this->hasMany(Rocket::class);
    }
}
