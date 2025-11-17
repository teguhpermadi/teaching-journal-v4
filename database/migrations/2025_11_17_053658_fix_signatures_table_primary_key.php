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
        // Check if the table exists and if 'id' column exists
        if (Schema::hasTable('signatures')) {
            $columns = Schema::getColumnListing('signatures');
            
            // If 'id' column doesn't exist, we need to recreate the table
            if (!in_array('id', $columns)) {
                // Drop the table and recreate it with correct structure
                Schema::dropIfExists('signatures');
                
                Schema::create('signatures', function (Blueprint $table) {
                    $table->ulid('id')->primary();
                    $table->foreignUlid('journal_id')->constrained('journals')->cascadeOnDelete();
                    $table->foreignUlid('signer_id')->constrained('users')->cascadeOnDelete();
                    $table->string('signer_role');
                    $table->string('signature_path')->nullable();
                    $table->longText('signature_base64')->nullable();
                    $table->timestamp('signed_at')->nullable();
                    $table->timestamps();
                });
            } else {
                // If 'id' exists, just ensure signature fields exist
                if (!Schema::hasColumn('signatures', 'signature_path')) {
                    Schema::table('signatures', function (Blueprint $table) {
                        $table->string('signature_path')->nullable()->after('signer_role');
                        $table->longText('signature_base64')->nullable()->after('signature_path');
                        $table->timestamp('signed_at')->nullable()->after('signature_base64');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration fixes the structure, so we don't need to reverse it
    }
};
