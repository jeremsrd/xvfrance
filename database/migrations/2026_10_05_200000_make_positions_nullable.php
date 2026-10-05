<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Poste inconnu pour les feuilles historiques : remplaçants sans poste, avants non numérotés avant 1920.
     * Sans poste connu, le poste principal reste vide au lieu de « centre » par défaut (xv:compute-primary-positions).
     */
    public function up(): void
    {
        Schema::table('match_lineups', function (Blueprint $table) {
            $table->string('position_played')->nullable()->change();
        });
        Schema::table('players', function (Blueprint $table) {
            $table->string('primary_position')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('match_lineups', function (Blueprint $table) {
            $table->string('position_played')->nullable(false)->change();
        });
        Schema::table('players', function (Blueprint $table) {
            $table->string('primary_position')->nullable(false)->default('centre')->change();
        });
    }
};
