<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;

class AppendablePlanet extends Model
{
    protected $table = 'planets';

    /**
     * @var list<string>
     */
    protected $appends = ['summary'];

    /**
     * @var list<string>
     */
    protected $hidden = ['diameter'];

    public function getSummaryAttribute(): string
    {
        return 'S';
    }

    public function getBadgeAttribute(): string
    {
        return 'B';
    }
}
