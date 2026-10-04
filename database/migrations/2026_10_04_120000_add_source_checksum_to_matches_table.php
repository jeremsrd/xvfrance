<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Empreinte du JSON de la feuille de match au dernier import (xv:import-match-data --changed)
            $table->string('source_checksum', 64)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('source_checksum');
        });
    }
};
