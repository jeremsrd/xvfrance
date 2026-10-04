<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Résumé vidéo du match : YouTube (lu dans la page) ou page d'un diffuseur
            $table->string('video_url', 255)->nullable()->after('weather');
            // Lecteur intégrable d'un diffuseur autre que YouTube (TF1+ : https://www.tf1.fr/player/<id>)
            $table->string('video_embed_url', 255)->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'video_embed_url']);
        });
    }
};
