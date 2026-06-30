<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploaded_photos', function (Blueprint $table): void {
            $table->string('photo_path', 500)->nullable()->after('photo_data');
        });

        Schema::table('found_items', function (Blueprint $table): void {
            $table->string('photo_path', 500)->nullable()->after('photo_data');
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_photos', function (Blueprint $table): void {
            $table->dropColumn('photo_path');
        });

        Schema::table('found_items', function (Blueprint $table): void {
            $table->dropColumn('photo_path');
        });
    }
};
