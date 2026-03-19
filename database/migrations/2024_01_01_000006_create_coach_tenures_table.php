<?php

use App\Enums\CoachRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_tenures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('coaches');
            $table->string('role')->default(CoachRole::SELECTIONNEUR->value);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index('coach_id', 'idx_tenures_coach');
            $table->index(['start_date', 'end_date'], 'idx_tenures_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_tenures');
    }
};
