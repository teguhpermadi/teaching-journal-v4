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
        Schema::create('signatures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignUlid('signer_id')->constrained('users')->cascadeOnDelete();
            $table->string('signer_role');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
