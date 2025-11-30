<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpaceStation extends Model
{
    /**
     * @phpstan-ignore missingType.return, missingType.parameter
     */
    public function getIdAttribute($value)
    {
        if ($value < 100) {
            return '';
        }

        return $value;
    }

    public function getNameAttribute(string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return strtoupper($value);
    }

    /**
     * @phpstan-ignore missingType.iterableValue
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'x' => 1.23e5,
            'y' => -4.56e5,
            'z' => 7.89e5,
            'reference' => 'Galactic Center',
        ];
    }

    /**
     * @return HasMany<Rocket, $this>
     */
    public function rockets(): HasMany
    {
        return $this->hasMany(Rocket::class);
    }

    /**
     * @return BelongsTo<Planet, $this>
     */
    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }
}
