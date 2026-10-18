<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('slug', 150)->nullable()->unique()->after('name');
        });

        // Adresse de la page du stade : nom, puis nom et ville en cas d'homonyme, puis identifiant
        $taken = [];
        foreach (DB::table('venues')->orderBy('id')->get(['id', 'name', 'city']) as $venue) {
            $slug = collect([Str::slug($venue->name), Str::slug($venue->name . ' ' . $venue->city), Str::slug($venue->name) . '-' . $venue->id])
                ->first(fn ($candidate) => !isset($taken[$candidate]));
            $taken[$slug] = true;
            DB::table('venues')->where('id', $venue->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
