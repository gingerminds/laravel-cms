<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;

/**
 * Keeps the `page_urls` index (see its migration for why it exists) in
 * sync with whatever actually determines a page's public URL: its own
 * slug, and every ancestor category's prefix. Called from three places —
 * see each call site for why: `PageRepository::update()` (the page's own
 * slug/category changed), `PageCategoryRepository::update()` (a category's
 * prefix or parent changed, affecting every page under it *and its
 * descendants*), and `PageCategoryController::destroy()` (deleting a
 * category re-parents its children to root, and nulls `category_id` on
 * its own direct pages).
 */
class PageUrlSyncer
{
    /**
     * Recomputes every language's URL for one page.
     */
    public function syncPage(Page $page): void
    {
        // `load()`, not `loadMissing()`: `Page` has a global scope
        // (`TranslatableModelTrait::bootTranslatableModelTrait()`) that
        // eager-loads `translations` the moment the model is first
        // fetched — well before `PageRepository::update()` runs. That
        // trait's own `syncTranslations()` persists changes via a fresh
        // `$this->translations()->updateOrCreate(...)` query, which never
        // touches the model's already-cached `translations` relation. If
        // we used `loadMissing()` here, a translation added/changed in
        // the very save that triggered this sync would still look
        // "already loaded" (with pre-save data, or missing entirely for a
        // brand new language) and never make it into `page_urls`.
        $page->load(['translations', 'category']);

        $translatedLanguageIds = [];

        foreach ($page->translations as $translation) {
            /** @var PageTranslation $translation */
            if (!$this->isActuallyTranslated($translation)) {
                continue;
            }

            $categoryPath = $page->category?->getFullPathForLanguage($translation->language_id) ?? '';
            $path         = Page::composePath($categoryPath, $translation->slug ?? '');

            PageUrl::query()->updateOrCreate(
                ['page_id' => $page->id, 'language_id' => $translation->language_id],
                ['site_id' => $page->site_id, 'path' => $path]
            );

            $translatedLanguageIds[] = $translation->language_id;
        }

        // Authoritative, not just additive: a language that no longer
        // qualifies (e.g. its title was cleared out after having one)
        // must lose its stale row too, not just skip getting a new one.
        PageUrl::query()
            ->where('page_id', $page->id)
            ->whereNotIn('language_id', $translatedLanguageIds)
            ->delete();
    }

    /**
     * The page-creation form always submits every one of the site's
     * languages, so *every* page ends up with a `PageTranslation` row per
     * language regardless of whether anyone has actually translated it —
     * a blank `slug` on its own can't tell "deliberately the home page in
     * this language" apart from "nobody's gotten to this language yet".
     * A blank `title` is treated as the signal for the latter: `title` is
     * only strictly `required` on the site's default language, but in
     * practice nobody publishes a language without giving it one, so a
     * page whose title *is* filled in for this language is genuinely
     * translated, deliberate blank slug or not.
     */
    private function isActuallyTranslated(PageTranslation $translation): bool
    {
        return null !== $translation->title && '' !== $translation->title;
    }

    /**
     * Recomputes every page attached to this category *or any of its
     * descendants* — a single category's prefix or parent change cascades
     * to the URL of every page anywhere under it in the tree.
     */
    public function syncCategorySubtree(PageCategory $category): void
    {
        $categoryIds = $this->collectSubtreeIds($category);

        Page::query()
            ->whereIn('category_id', $categoryIds)
            ->get()
            ->each(fn (Page $page) => $this->syncPage($page));
    }

    /**
     * The pages that would need resyncing if this category (and hence its
     * whole subtree) were deleted right now — must be called *before* the
     * actual delete, since deleting re-parents children to root and nulls
     * `category_id` on direct pages, at which point this category's
     * subtree can no longer be walked to find them.
     *
     * @return list<int>
     */
    public function collectAffectedPageIds(PageCategory $category): array
    {
        $categoryIds = $this->collectSubtreeIds($category);

        /** @var list<int> */
        return Page::query()->whereIn('category_id', $categoryIds)->pluck('id')->all();
    }

    /**
     * Resyncs a fixed list of pages by id — for use after a category
     * delete, once `category_id`/`parent_id` have already been nulled by
     * the DB and the ids gathered beforehand via `collectAffectedPageIds()`.
     *
     * @param list<int> $pageIds
     */
    public function syncPagesByIds(array $pageIds): void
    {
        Page::query()
            ->whereIn('id', $pageIds)
            ->get()
            ->each(fn (Page $page) => $this->syncPage($page));
    }

    /**
     * @return list<int>
     */
    private function collectSubtreeIds(PageCategory $category): array
    {
        $ids = [$category->id];

        /** @var PageCategory $child */
        foreach ($category->children()->get() as $child) {
            $ids = array_merge($ids, $this->collectSubtreeIds($child));
        }

        return $ids;
    }
}
