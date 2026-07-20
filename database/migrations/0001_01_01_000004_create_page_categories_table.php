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
        Schema::create('page_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('page_categories')->nullOnDelete();
            $table->string('code');
            $table->boolean('is_unique')->default(false);
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['code', 'site_id']);
        });

        Schema::create('page_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_category_id')->constrained('page_categories')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('prefix')->nullable();
            $table->timestamps();

            $table->unique(['page_category_id', 'language_id']);
            $table->unique(
                ['site_id', 'language_id', 'prefix'],
                'page_category_translations_site_language_prefix_unique'
            );
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('site_id')
                ->constrained('page_categories')->restrictOnDelete();
        });

        $this->backfillDefaultCategories();

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }

    /**
     * Categories are mandatory on `pages`, but existing pages have none. Give
     * every site with at least one uncategorized page a "default" category
     * whose prefix is blank (`''`) in every one of the site's languages, and
     * attach those pages to it — so their URL keeps resolving as `/{slug}`
     * (no visible prefix) exactly like before this feature existed.
     */
    private function backfillDefaultCategories(): void
    {
        $siteIdsNeedingDefault = DB::table('pages')
            ->whereNull('category_id')
            ->distinct()
            ->pluck('site_id');

        foreach ($siteIdsNeedingDefault as $siteId) {
            $categoryId = DB::table('page_categories')->insertGetId([
                'code' => 'default',
                'site_id' => $siteId,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $languageIds = DB::table('site_language')
                ->where('site_id', $siteId)
                ->pluck('language_id');

            foreach ($languageIds as $languageId) {
                DB::table('page_category_translations')->insert([
                    'page_category_id' => $categoryId,
                    'language_id' => $languageId,
                    'site_id' => $siteId,
                    'name' => 'Default',
                    'prefix' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('pages')
                ->where('site_id', $siteId)
                ->whereNull('category_id')
                ->update(['category_id' => $categoryId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('page_category_translations');
        Schema::dropIfExists('page_categories');
    }
};
