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
        Schema::table('journals', function (Blueprint $table) {
            // change target to target_id
            $table->foreignUlid('target_id')->constrained('targets')->cascadeOnDelete();
            $table->dropColumn('target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            // remove target_id
            $table->dropForeign(['target_id']);
            $table->dropColumn('target_id');
            $table->text('target');
        });
    }
};
