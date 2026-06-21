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
        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('title');
            $table->date('end_date')->nullable()->after('start_date');
        });

        \Illuminate\Support\Facades\DB::statement('UPDATE academic_calendars SET start_date = date, end_date = date');

        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->dropColumn('date');
        });
    }

    public function down(): void
    {
        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->date('date')->nullable()->after('title');
        });

        \Illuminate\Support\Facades\DB::statement('UPDATE academic_calendars SET date = start_date');

        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
