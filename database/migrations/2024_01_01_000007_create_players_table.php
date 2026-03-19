<?php

use App\Enums\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->date('birth_date')->nullable();
            $table->string('birth_city', 100)->nullable();
            $table->foreignId('country_id')->constrained('countries');
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->string('primary_position')->default(PlayerPosition::CENTRE->value);
            $table->string('photo_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('country_id', 'idx_players_country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
