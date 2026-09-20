<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beacons', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedBigInteger('planet_id');
            $table->morphs('beaconable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beacons');
    }
};
