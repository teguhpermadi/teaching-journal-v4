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
        Schema::table('signatures', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('signer_role');
            $table->longText('signature_base64')->nullable()->after('signature_path');
            $table->timestamp('signed_at')->nullable()->after('signature_base64');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'signature_base64', 'signed_at']);
        });
    }
};
