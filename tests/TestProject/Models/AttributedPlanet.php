<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $phpdoc_visible
 * @property float $phpdoc_secret
 * @property bool $phpdoc_omitted
 */
#[Table(
    name: 'planets',
    key: 'id',
    keyType: 'string',
    incrementing: true,
    timestamps: false,
)]
#[Hidden('diameter', 'phpdoc_secret')]
#[Visible(['id', 'name', 'display_name', 'phpdoc_visible', 'phpdoc_secret'])]
#[Appends('display_name')]
class AttributedPlanet extends Model
{
    public function getDisplayNameAttribute(mixed $value): string
    {
        return 'Planet';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'kind' => 'planet',
        ]);
    }
}
