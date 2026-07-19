<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\TestProject\Models\Planet;

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
}
