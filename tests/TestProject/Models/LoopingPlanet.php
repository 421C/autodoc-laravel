<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoopingPlanet extends Model
{
    protected $table = 'planets';

    protected $with = ['neighbour'];

    /**
     * @return BelongsTo<LoopingPlanet, $this>
     */
    public function neighbour(): BelongsTo
    {
        return $this->belongsTo(LoopingPlanet::class, 'id');
    }
}
