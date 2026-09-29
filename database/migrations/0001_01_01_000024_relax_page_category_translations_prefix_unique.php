<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * A blank `prefix` is a legitimate, deliberate choice — it means "no
     * URL segment" — and more than one category may need one, e.g. two
     * categories in different branches of the tree, since their final full
     * path still differs thanks to their distinct ancestors. The global
     * `(site_id, language_id, prefix)` unique index added by the previous
     * migration was too strict: it allowed only ONE blank-prefix category
     * per site+language across the *entire* tree, so the "default"
     * category backfilled for pre-existing pages permanently blocked every
     * other category from ever leaving its own prefix blank — crashing
     * with a raw SQL duplicate-key error instead of a validation message.
     *
     * Uniqueness is now enforced in `PageCategoryRequest` instead, scoped
     * to sibling categories (same parent) and skipped entirely for blank
     * prefixes — logic a plain DB index can't express, since it needs the
     * category tree.
     */
    public function up(): void
    {
        try {
            Schema::table('page_category_translations', function ($table) {
                $table->dropUnique('page_category_translations_site_language_prefix_unique');
            });
        } catch (Throwable) {
            // Already dropped, or never existed on this connection — no-op,
            // consistent with the idempotency guards used elsewhere in this
            // package's migrations.
        }

        // Rows created by the previous migration's backfill stored a blank
        // prefix as '' rather than NULL. Normalize them so blank prefixes
        // are consistently NULL going forward (matching how `slug` was
        // handled), which also means they naturally never collide with one
        // another under any future DB-level index.
        DB::table('page_category_translations')
            ->where('prefix', '')
            ->update(['prefix' => null]);
    }

    public function down(): void
    {
        Schema::table('page_category_translations', function ($table) {
            $table->unique(
                ['site_id', 'language_id', 'prefix'],
                'page_category_translations_site_language_prefix_unique'
            );
        });
    }
};
