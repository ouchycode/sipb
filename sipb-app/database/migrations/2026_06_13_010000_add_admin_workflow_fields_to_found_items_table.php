<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('found_items', function (Blueprint $table) {
            $table->dateTime('rejected_at')->nullable()->after('claimed_at');
            $table->dateTime('expired_at')->nullable()->after('rejected_at');
            $table->string('claimant_name')->nullable()->after('finder_nim');
            $table->string('claimant_nim')->nullable()->after('claimant_name');
            $table->text('validation_notes')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('found_items', function (Blueprint $table) {
            $table->dropColumn([
                'rejected_at',
                'expired_at',
                'claimant_name',
                'claimant_nim',
                'validation_notes',
            ]);
        });
    }
};
