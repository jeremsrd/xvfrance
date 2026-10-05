<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Autres noms d'un stade : anciens noms (avec date de fin, affichés pour les matches antérieurs)
        // et variantes (nom de sponsor, autre graphie : reconnues à l'import, jamais affichées)
        Schema::create('venue_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->date('used_until')->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_names');
    }
};
