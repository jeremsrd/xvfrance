<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_off_id')->constrained('players');
            $table->foreignId('player_on_id')->constrained('players');
            $table->integer('minute');
            $table->boolean('is_tactical')->default(true);
            $table->string('team_side');
            $table->timestamps();

            $table->index('match_id', 'idx_subs_match');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_substitutions');
    }
};
