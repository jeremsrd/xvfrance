<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_editions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions');
            $table->integer('year');
            $table->string('label', 100);
            $table->integer('france_ranking')->nullable();
            $table->timestamps();

            $table->index(['competition_id', 'year'], 'idx_editions_comp_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_editions');
    }
};
