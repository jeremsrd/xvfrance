<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('logo_path', 255)->nullable()->after('type');
        });

        // Logos versionnés dans public/images/competitions (variantes pour fond sombre)
        $logos = [
            'Tournoi des 5/6 Nations' => 'images/competitions/six-nations.png',
            'Coupe du Monde de Rugby' => 'images/competitions/coupe-du-monde.svg',
            'Championnat des Nations' => 'images/competitions/championnat-des-nations.svg',
            'Jeux Olympiques' => 'images/competitions/jeux-olympiques.svg',
        ];

        foreach ($logos as $name => $path) {
            DB::table('competitions')->where('name', $name)->update(['logo_path' => $path]);
        }
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
