<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Nom court en français pour la compétition créée en 2026
return new class extends Migration
{
    public function up(): void
    {
        DB::table('competitions')->where('short_name', 'Nations Championship')->update(['short_name' => 'Championnat des Nations']);
    }

    public function down(): void
    {
        DB::table('competitions')->where('short_name', 'Championnat des Nations')->update(['short_name' => 'Nations Championship']);
    }
};
