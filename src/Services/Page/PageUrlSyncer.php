<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\State\Page\Status\Published;

class PageUrlSyncer
{
    /**
     * Recomputes every language's URL for one page.
     */
    public function syncPage(Page $page): void
    {
        $page->load(['translations', 'category']);

        if (!$page->status instanceof Published) {
            PageUrl::query()->where('page_id', $page->id)->delete();

            return;
        }

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

    private function isActuallyTranslated(PageTranslation $translation): bool
    {
        return null !== $translation->title && '' !== $translation->title;
    }

    public function syncCategorySubtree(PageCategory $category): void
    {
        $categoryIds = $this->collectSubtreeIds($category);

        Page::query()
            ->whereIn('category_id', $categoryIds)
            ->get()
            ->each(fn (Page $page) => $this->syncPage($page));
    }

    /**
     * @return list<int>
     */
    public function collectAffectedPageIds(PageCategory $category): array
    {
        $categoryIds = $this->collectSubtreeIds($category);

        /** @var list<int> */
        return Page::query()->whereIn('category_id', $categoryIds)->pluck('id')->all();
    }

    /**
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
