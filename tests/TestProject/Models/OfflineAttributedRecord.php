<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Table(
    name: 'offline_attributed_records',
    key: 'uuid',
    keyType: 'string',
)]
#[WithoutIncrementing]
#[WithoutTimestamps]
class OfflineAttributedRecord extends Model
{
}
