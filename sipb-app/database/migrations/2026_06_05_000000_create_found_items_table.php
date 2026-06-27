<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('found_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->string('location');
            $table->dateTime('found_at');
            $table->string('photo_url');
            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->dateTime('claimed_at')->nullable();
            $table->string('finder_name')->nullable();
            $table->string('finder_nim')->nullable();
            $table->string('storage_location')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('managed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('found_items');
    }
};
