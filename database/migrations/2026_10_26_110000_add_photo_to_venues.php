<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photo libre du stade (Wikimedia Commons) avec son crédit, exigé par la licence
        Schema::table('venues', function (Blueprint $table) {
            $table->string('photo_path', 255)->nullable();
            $table->unsignedSmallInteger('photo_width')->nullable();
            $table->unsignedSmallInteger('photo_height')->nullable();
            $table->string('photo_author', 255)->nullable();
            $table->string('photo_license', 50)->nullable();
            $table->string('photo_license_url', 255)->nullable();
            $table->string('photo_source_url', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_width', 'photo_height', 'photo_author', 'photo_license',
                'photo_license_url', 'photo_source_url']);
        });
    }
};
