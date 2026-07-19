<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Model;

class ClassifiedPlanet extends Model
{
    protected $table = 'planets';

    protected $visible = ['name', 'nickname'];

    protected $hidden = ['secret_token'];
}
