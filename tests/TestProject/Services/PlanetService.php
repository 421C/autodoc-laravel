<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Services;

class PlanetService
{
    /**
     * @return array{name: string, visited: bool}
     */
    public function getSummary(): array
    {
        return [
            'name' => 'Mars',
            'visited' => false,
        ];
    }
}
