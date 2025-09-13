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
        Schema::create('grade_student', function (Blueprint $table) {
            $table->foreignUlid('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained()->cascadeOnDelete();

            $table->unique(['grade_id', 'student_id'], 'grade_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_student');
    }
};
