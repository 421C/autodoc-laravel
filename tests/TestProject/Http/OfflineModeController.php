<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\TestProject\Models\OfflineAttributedRecord;
use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use Illuminate\Support\Facades\DB;

/**
 * Endpoints exercised only under offline mode, where model attribute types come
 * from casts/appends/accessors/PHPDoc rather than database schema introspection.
 */
class OfflineModeController
{
    public function showPlanet(): Planet
    {
        return Planet::firstOrFail();
    }


    public function showAttributedRecord(): OfflineAttributedRecord
    {
        return OfflineAttributedRecord::firstOrFail();
    }

    public function rawTableQuery(): mixed
    {
        return DB::table('planets')->get();
    }


    public function joinedModelQuery(): mixed
    {
        return Planet::query()->join('rockets', 'rockets.target_planet_id', '=', 'planets.id')->get();
    }
}
