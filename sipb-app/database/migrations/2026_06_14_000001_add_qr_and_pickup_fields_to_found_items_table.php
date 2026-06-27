<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('found_items', function (Blueprint $table): void {
            $table->foreignId('submission_token_id')->nullable()->after('managed_by')->constrained('qr_access_tokens')->nullOnDelete();
            $table->json('pickup_checklist')->nullable()->after('validation_notes');
        });
    }

    public function down(): void
    {
        Schema::table('found_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submission_token_id');
            $table->dropColumn('pickup_checklist');
        });
    }
};
