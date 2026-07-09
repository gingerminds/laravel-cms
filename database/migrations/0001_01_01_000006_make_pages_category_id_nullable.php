<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * A page no longer requires a category. Not every page needs to live
     * under a category's URL prefix (a bare `/{slug}` is a legitimate
     * page), and making it mandatory meant `category_id`'s `restrictOnDelete()`
     * blocked deleting a category outright the moment a single page used
     * it. Deleting a category now clears `category_id` on its pages instead
     * (they simply fall back to a prefix-less `/{slug}` URL) rather than
     * blocking the delete.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')->on('page_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')->on('page_categories')
                ->restrictOnDelete();
        });
    }
};
