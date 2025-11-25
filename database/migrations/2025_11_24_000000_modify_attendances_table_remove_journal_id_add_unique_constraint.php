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
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();

            // Check if foreign key exists before dropping
            $foreignKeyExists = $connection->select(
                "SELECT CONSTRAINT_NAME 
                 FROM information_schema.TABLE_CONSTRAINTS 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = 'attendances' 
                 AND CONSTRAINT_NAME = 'attendances_journal_id_foreign'
                 AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                [$databaseName]
            );

            if (!empty($foreignKeyExists)) {
                $table->dropForeign(['journal_id']);
            }

            // Check if unique index exists before dropping
            $uniqueIndexExists = $connection->select(
                "SELECT INDEX_NAME 
                 FROM information_schema.STATISTICS 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = 'attendances' 
                 AND INDEX_NAME = 'unique_journal_student_date'",
                [$databaseName]
            );

            if (!empty($uniqueIndexExists)) {
                $table->dropUnique('unique_journal_student_date');
            }

            // Drop column if it exists
            if (Schema::hasColumn('attendances', 'journal_id')) {
                $table->dropColumn('journal_id');
            }

            // Check if unique constraint already exists
            $uniqueStudentDateExists = $connection->select(
                "SELECT INDEX_NAME 
                 FROM information_schema.STATISTICS 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = 'attendances' 
                 AND INDEX_NAME = 'unique_student_date'",
                [$databaseName]
            );

            // Add unique constraint if it doesn't exist
            if (empty($uniqueStudentDateExists)) {
                $table->unique(['student_id', 'date'], 'unique_student_date');
            }
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
