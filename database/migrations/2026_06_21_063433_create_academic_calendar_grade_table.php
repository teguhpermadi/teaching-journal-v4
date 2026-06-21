<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_calendar_grade', function (Blueprint $table) {
            $table->foreignUlid('academic_calendar_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('grade_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unique(['academic_calendar_id', 'grade_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_grade');
    }
};
