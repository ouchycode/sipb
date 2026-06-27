<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_audits', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('status_audits', function (Blueprint $table): void {
            $table->dropColumn('ip_address');
        });
    }
};
