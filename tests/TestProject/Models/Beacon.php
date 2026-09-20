<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Beacon extends Model
{
    /**
     * @phpstan-ignore missingType.generics
     */
    public function beaconable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @phpstan-ignore missingType.generics
     */
    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }
}
