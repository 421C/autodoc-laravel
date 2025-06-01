<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;

class Rocket extends Model
{
    public function toArray(): array
    {
        $id = rand(1, 100);

        return [
            'id' => $id,
            'name' => 'Rocket ' . $id,
        ];
    }
}
