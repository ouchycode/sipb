<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('found_items', function (Blueprint $table): void {
            $table->string('report_type')->default('found')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('found_items', function (Blueprint $table): void {
            $table->dropColumn('report_type');
        });
    }
};
