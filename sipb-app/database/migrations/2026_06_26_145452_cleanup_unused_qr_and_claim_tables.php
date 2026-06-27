<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('item_claims');
        
        Schema::table('found_items', function (Blueprint $table): void {
            if (Schema::hasColumn('found_items', 'submission_token_id')) {
                $table->dropConstrainedForeignId('submission_token_id');
            }
            if (Schema::hasColumn('found_items', 'report_type')) {
                $table->dropColumn('report_type');
            }
        });

        Schema::dropIfExists('qr_access_tokens');
    }

    public function down(): void
    {
        // No down migration as these tables/columns are being permanently removed
    }
};
