<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * A precomputed index of every page's public URL, per language,
     * instead of walking the category tree and comparing candidates on
     * every `GET /pages/by-slug/{path}` request. Kept in sync by
     * `PageUrlSyncer`, called whenever a page is saved, a category is
     * saved (its prefix/parent can change every descendant page's URL),
     * or a category is deleted (its children re-parent to root, losing
     * every ancestor prefix above them).
     *
     * `path` is NOT NULL (stored as `''` for a "home page", never
     * `NULL`) so the `(site_id, language_id, path)` unique index actually
     * enforces "at most one page can resolve to this exact URL" — including
     * the blank/home case, unlike the NULL-based exemptions used elsewhere
     * in this package (category prefixes, page slugs) where more than one
     * blank value is intentionally allowed.
     */
    public function up(): void
    {
        Schema::create('page_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();

            $table->unique(['page_id', 'language_id']);
            $table->unique(['site_id', 'language_id', 'path'], 'page_urls_site_language_path_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_urls');
    }
};
