<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un club au sens de Wikipédia : un club renommé reste le même, une fusion qui crée un club en fait un nouveau
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->string('city', 100)->nullable();
            $table->foreignId('country_id')->constrained();
            $table->timestamps();
        });

        // Clubs successifs d'un joueur, dans l'ordre, avec le nom du club à l'époque (« Racing Club de France »)
        Schema::create('player_clubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('name', 150);
            $table->string('source', 50);
            $table->timestamps();

            $table->unique(['player_id', 'position']);
            $table->index('club_id', 'idx_player_clubs_club');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_clubs');
        Schema::dropIfExists('clubs');
    }
};
