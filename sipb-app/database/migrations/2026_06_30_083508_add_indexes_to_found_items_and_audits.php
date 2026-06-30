<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('found_items', function (Blueprint $table): void {
            $table->index('status');
            $table->index('published_at');
            $table->index('claimed_at');
            $table->index(['status', 'published_at']);
        });

        Schema::table('status_audits', function (Blueprint $table): void {
            $table->index('found_item_id');
            $table->index('user_id');
            $table->index('to_status');
        });

        Schema::table('uploaded_photos', function (Blueprint $table): void {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('found_items', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['published_at']);
            $table->dropIndex(['claimed_at']);
            $table->dropIndex(['status', 'published_at']);
        });

        Schema::table('status_audits', function (Blueprint $table): void {
            $table->dropIndex(['found_item_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['to_status']);
        });

        Schema::table('uploaded_photos', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });
    }
};
