<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players');
            $table->integer('jersey_number');
            $table->boolean('is_starter');
            $table->string('position_played');
            $table->boolean('is_captain')->default(false);
            $table->string('team_side');
            $table->timestamps();

            $table->unique(['match_id', 'team_side', 'jersey_number']);
            $table->unique(['match_id', 'player_id']);
            $table->index('player_id', 'idx_lineups_player');
            $table->index(['match_id', 'team_side'], 'idx_lineups_match_team');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_lineups');
    }
};
