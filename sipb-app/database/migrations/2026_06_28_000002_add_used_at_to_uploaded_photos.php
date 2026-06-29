<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploaded_photos', function (Blueprint $table): void {
            $table->timestamp('used_at')->nullable()->after('photo_data');
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_photos', function (Blueprint $table): void {
            $table->dropColumn('used_at');
        });
    }
};
