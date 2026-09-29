<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('search_index_categories', function (Blueprint $table) {
            $table->foreignId('search_index_id')->constrained('search_index')->cascadeOnDelete();
            // No FK to a categories table: which table `category_id` points at
            // (NewsCategory, ProductRange, PageCategory, ...) depends on the
            // owning search_index row's own `type` — same reasoning as
            // search_index.type/record_id having no FK either.
            $table->unsignedBigInteger('category_id');

            $table->primary(['search_index_id', 'category_id']);
            $table->index(['category_id'], 'idx_search_index_categories_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_index_categories');
    }
};
