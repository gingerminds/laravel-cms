<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guarded so a re-run after a failed attempt (e.g. the unique index
        // below rejected by pre-existing duplicates) doesn't blow up on
        // "column already exists" — MySQL DDL isn't transactional, so a
        // failure partway through leaves this step already applied.
        if (!Schema::hasColumn('page_translations', 'site_id')) {
            Schema::table('page_translations', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('page_id')->constrained('sites')->cascadeOnDelete();
            });

            // Denormalize the parent page's site_id onto each translation so slug
            // uniqueness can be scoped per site without joining through `pages`
            // (DB-level unique indexes can't span a join).
            DB::table('page_translations')->update([
                'site_id' => DB::raw(
                    '(select site_id from pages where pages.id = page_translations.page_id)'
                ),
            ]);
        }

        // An empty string is a real, colliding value for a unique index —
        // unlike NULL, which MySQL/Postgres/SQLite all exempt. Existing rows
        // saved with slug = '' (rather than NULL) for the same site+language
        // would otherwise violate the index below; normalize them first.
        DB::table('page_translations')->where('slug', '')->update(['slug' => null]);

        Schema::table('page_translations', function (Blueprint $table) {
            $table->unique(['site_id', 'language_id', 'slug'], 'page_translations_site_language_slug_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropUnique('page_translations_site_language_slug_unique');
            $table->dropConstrainedForeignId('site_id');
        });
    }
};
