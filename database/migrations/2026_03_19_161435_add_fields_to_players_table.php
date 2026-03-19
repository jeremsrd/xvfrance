<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('nickname', 100)->nullable()->after('last_name');
            $table->date('death_date')->nullable()->after('birth_city');
            $table->foreignId('birth_country_id')->nullable()->after('birth_city')
                ->constrained('countries');
            $table->integer('cap_number')->nullable()->after('is_active');
            $table->renameColumn('photo_url', 'photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['cap_number', 'death_date', 'nickname']);
            $table->dropConstrainedForeignId('birth_country_id');
            $table->renameColumn('photo_path', 'photo_url');
        });
    }
};
