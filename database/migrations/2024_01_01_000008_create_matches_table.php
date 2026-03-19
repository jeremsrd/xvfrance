<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->date('match_date');
            $table->time('kickoff_time')->nullable();
            $table->foreignId('venue_id')->nullable()->constrained('venues');
            $table->foreignId('opponent_id')->constrained('countries');
            $table->foreignId('edition_id')->nullable()->constrained('competition_editions');
            $table->integer('france_score');
            $table->integer('opponent_score');
            $table->boolean('is_home');
            $table->boolean('is_neutral')->default(false);
            $table->string('stage')->nullable();
            $table->integer('match_number')->nullable();
            $table->integer('attendance')->nullable();
            $table->string('referee', 150)->nullable();
            $table->foreignId('referee_country_id')->nullable()->constrained('countries');
            $table->string('weather', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('match_date', 'idx_matches_date');
            $table->index('opponent_id', 'idx_matches_opponent');
            $table->index('edition_id', 'idx_matches_edition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
