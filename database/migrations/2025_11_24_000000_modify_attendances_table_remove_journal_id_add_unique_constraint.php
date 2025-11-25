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
            // Check if foreign key exists before dropping
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('attendances');

            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getName() === 'attendances_journal_id_foreign') {
                    $table->dropForeign(['journal_id']);
                    break;
                }
            }

            // Check if unique index exists before dropping
            $indexes = $sm->listTableIndexes('attendances');
            if (isset($indexes['unique_journal_student_date'])) {
                $table->dropUnique('unique_journal_student_date');
            }

            // Drop column if it exists
            if (Schema::hasColumn('attendances', 'journal_id')) {
                $table->dropColumn('journal_id');
            }

            // Add unique constraint if it doesn't exist
            if (!isset($indexes['unique_student_date'])) {
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
