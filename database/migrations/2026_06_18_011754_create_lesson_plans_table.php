<?php

use App\TeachingStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('target_id')->nullable()->constrained()->nullOnDelete();
            $table->string('topic');
            $table->text('learning_objectives');
            $table->text('activities');
            $table->text('materials')->nullable();
            $table->text('assessment')->nullable();
            $table->date('planned_date');
            $table->string('status')->default(TeachingStatusEnum::PEMBELAJARAN->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
