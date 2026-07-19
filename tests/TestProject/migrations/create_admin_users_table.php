<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('handle');
            $table->boolean('is_super')->default(false);
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
