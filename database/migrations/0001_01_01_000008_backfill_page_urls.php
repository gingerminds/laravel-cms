<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Backfills `page_urls` for every page that already existed before
     * this index was introduced.
     *
     * This deliberately uses raw DB queries instead of the `Page`/
     * `PageCategory` Eloquent models (unlike `PageUrlSyncer`, which handles
     * every *future* change): both models are scoped by
     * `SiteContextedModelTrait`'s "current site" global scope, which
     * resolves to nothing in a CLI migration — no bound site, no HTTP
     * request to fall back on — so every lazy-loaded `->parent`/`->category`
     * relation would silently come back empty, truncating the category
     * chain for every page. Raw queries sidestep that scope entirely and
     * process every site's pages in one pass.
     */
    public function up(): void
    {
        // Keyed/grouped via a closure rather than a plain column-name
        // string: `keyBy('id')`/`groupBy(...)` can't tell PHPStan the key
        // is an `int` (the column name alone doesn't carry a PHP type),
        // which then can't satisfy `fullPathForLanguage()`'s
        // `Collection<int, ...>` parameter types below.
        /** @var Collection<int, stdClass> $categories */
        $categories = DB::table('page_categories')
            ->select('id', 'parent_id')
            ->get()
            ->keyBy(fn (stdClass $category): int => (int) $category->id);

        /** @var Collection<int, Collection<int, stdClass>> $categoryTranslations */
        $categoryTranslations = DB::table('page_category_translations')
            ->select('page_category_id', 'language_id', 'prefix')
            ->get()
            ->groupBy(fn (stdClass $translation): int => (int) $translation->page_category_id);

        $pages = DB::table('pages')->select('id', 'site_id', 'category_id')->get();

        /** @var Collection<int, Collection<int, stdClass>> $pageTranslations */
        $pageTranslations = DB::table('page_translations')
            ->select('page_id', 'language_id', 'slug', 'title')
            ->get()
            ->groupBy(fn (stdClass $translation): int => (int) $translation->page_id);

        $now  = now();
        $rows = [];

        foreach ($pages as $page) {
            /** @var Collection<int, stdClass> $translations */
            $translations = $pageTranslations->get($page->id) ?? collect();

            foreach ($translations as $translation) {
                // Same rule as `PageUrlSyncer::isActuallyTranslated()`: the
                // form submits every site language regardless of whether
                // anyone filled it in, so a blank title means this language
                // was never really translated — a blank *slug* on its own
                // isn't enough to tell that apart from a deliberate
                // category "home page" (blank slug, but a real title).
                $title = $translation->title ?? '';

                if ('' === $title) {
                    continue;
                }

                $categoryPath = $this->fullPathForLanguage(
                    $page->category_id,
                    (int) $translation->language_id,
                    $categories,
                    $categoryTranslations
                );

                $slug = $translation->slug ?? '';
                $path = match (true) {
                    '' === $categoryPath => $slug,
                    '' === $slug => $categoryPath,
                    default => $categoryPath . '/' . $slug,
                };

                $rows[] = [
                    'page_id' => $page->id,
                    'language_id' => $translation->language_id,
                    'site_id' => $page->site_id,
                    'path' => $path,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // insertOrIgnore rather than insert: pre-existing data can already
        // have two pages whose computed path collides for a given language
        // (e.g. a page whose own slug *and* its category's prefix were
        // never filled in for that language, silently resolving to the same
        // blank "home" path as an actual home page) — a conflict this
        // index is specifically meant to now prevent going forward, but
        // that can't retroactively decide *which* existing page was
        // "right" for a language nobody finished translating. Skipping the
        // losing row here means one of the two pages ends up temporarily
        // unresolvable by path for that language, surfaced as a normal
        // 404 rather than crashing the whole deploy — fix by revisiting
        // that page's (or its category's) translation for that language.
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('page_urls')->insertOrIgnore($chunk);
        }
    }

    /**
     * Same logic as `PageCategory::getFullPathForLanguage()`, re-expressed
     * over plain collections keyed/grouped ahead of time — see the class
     * doc comment for why this can't just call that method directly.
     *
     * @param  Collection<int, stdClass>  $categories  keyed by id
     * @param  Collection<int, Collection<int, stdClass>>  $categoryTranslations  grouped by page_category_id
     */
    private function fullPathForLanguage(
        ?int $categoryId,
        int $languageId,
        Collection $categories,
        Collection $categoryTranslations
    ): string {
        $segments = [];

        while (null !== $categoryId) {
            $category = $categories->get($categoryId);

            if (null === $category) {
                break;
            }

            $translation = ($categoryTranslations->get($categoryId) ?? collect())
                ->firstWhere('language_id', $languageId);

            $prefix = $translation?->prefix;

            if (null !== $prefix && '' !== $prefix) {
                $segments[] = $prefix;
            }

            $categoryId = $category->parent_id;
        }

        return implode('/', array_reverse($segments));
    }

    public function down(): void
    {
        DB::table('page_urls')->truncate();
    }
};
