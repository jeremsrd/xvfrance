<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remplacements des matches anciens (finalesrugby.fr) : la minute n'est pas toujours connue.
     */
    public function up(): void
    {
        Schema::table('match_substitutions', function (Blueprint $table) {
            $table->integer('minute')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('match_substitutions', function (Blueprint $table) {
            $table->integer('minute')->nullable(false)->change();
        });
    }
};
