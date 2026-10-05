<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remplaçant resté sur le banc : il figure sur la feuille de match mais ne compte pas comme sélection.
     */
    public function up(): void
    {
        Schema::table('match_lineups', function (Blueprint $table) {
            $table->boolean('played')->default(true)->after('is_captain');
        });
    }

    public function down(): void
    {
        Schema::table('match_lineups', function (Blueprint $table) {
            $table->dropColumn('played');
        });
    }
};
