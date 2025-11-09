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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "business", "Business", "bussinnesses"
            $table->string('normalized_name'); // "business" (lowercase, singular)
            $table->foreignId('source_id')->constrained('article_sources');
            $table->timestamps();

            $table->unique(['name', 'source_id']); // Each source has unique category names
            $table->index('normalized_name'); // For fast search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
