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
        if (! Schema::hasTable('theme_assets')) {
            Schema::create('theme_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('theme_id')->constrained('themes')->onDelete('cascade');
                $table->string('name'); // Original filename
                $table->string('filename')->nullable();; // Stored filename
                $table->string('path')->nullable();; // Storage path
                $table->string('url')->nullable();; // Public URL
                $table->string('type')->default('image'); // Asset type
                $table->string('mime_type')->nullable(); // MIME type
                $table->integer('size')->nullable(); // File size in bytes
                $table->integer('width')->nullable(); // Image width
                $table->integer('height')->nullable(); // Image height
                $table->timestamps();

                $table->index(['theme_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_assets');
    }
};
