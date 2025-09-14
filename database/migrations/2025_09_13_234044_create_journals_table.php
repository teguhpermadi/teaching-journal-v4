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
        Schema::create('journals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('target');
            $table->text('chapter');
            $table->text('activity');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
