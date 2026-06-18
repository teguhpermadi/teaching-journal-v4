<?php

use App\AcademicStatusCalendarEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('status')->default(AcademicStatusCalendarEnum::EFFECTIVE->value);
            $table->string('google_calendar_event_id')->nullable()->unique();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendars');
    }
};
