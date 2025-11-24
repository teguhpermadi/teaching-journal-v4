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
        Schema::table('attendances', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['journal_id']);

            // Drop old unique index explicitly
            $table->dropUnique('unique_journal_student_date');

            // Drop column
            $table->dropColumn('journal_id');

            // Add unique constraint
            $table->unique(['student_id', 'date'], 'unique_student_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignUlid('journal_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dropUnique('unique_student_date');
        });
    }
};
