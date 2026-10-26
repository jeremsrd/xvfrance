<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photos d'époque d'un match : une équipe (team_side) ou les deux / une scène de jeu (team_side NULL)
        Schema::create('match_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('team_side', 20)->nullable();
            $table->string('path', 255);
            $table->string('caption', 255)->nullable();
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->index('match_id', 'idx_match_photos_match');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_photos');
    }
};
