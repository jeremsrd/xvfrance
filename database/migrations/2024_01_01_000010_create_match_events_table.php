<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players');
            $table->string('event_type');
            $table->integer('minute')->nullable();
            $table->string('team_side');
            $table->string('detail', 255)->nullable();
            $table->timestamps();

            $table->index('match_id', 'idx_events_match');
            $table->index('player_id', 'idx_events_player');
            $table->index('event_type', 'idx_events_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
