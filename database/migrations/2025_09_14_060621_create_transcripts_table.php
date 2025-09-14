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
        Schema::create('transcripts', function (Blueprint $table) {
            $table->ulid()->primary();
            $table->foreignUlid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->integer('score');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'journal_id', 'academic_year_id'], 'transcript_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcripts');
    }
};
