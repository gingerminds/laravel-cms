<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Services\Url\AbstractUrlSyncer;
use Gingerminds\LaravelCms\State\Page\Status\Published;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends AbstractUrlSyncer<Page, PageTranslation>
 */
class PageUrlSyncer extends AbstractUrlSyncer
{
    /**
     * Recomputes every language's URL for one page.
     */
    public function syncPage(Page $page): void
    {
        $this->sync($page);
    }

    protected function urlModelClass(): string
    {
        return PageUrl::class;
    }

    protected function ownerForeignKey(): string
    {
        return 'page_id';
    }

    /**
     * @return list<string>
     */
    protected function eagerLoadRelations(): array
    {
        return ['category'];
    }

    protected function isPublishable(Model $owner): bool
    {
        /** @var Page $owner */
        return $owner->status instanceof Published;
    }

    protected function isEligible(Model $translation): bool
    {
        /** @var PageTranslation $translation */
        return null !== $translation->title && '' !== $translation->title;
    }

    protected function resolvePath(Model $owner, Model $translation): string
    {
        /** @var Page $owner */
        /** @var PageTranslation $translation */
        $categoryPath = $owner->category?->getFullPathForLanguage($translation->language_id) ?? '';

        return Page::composePath($categoryPath, $translation->slug ?? '');
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
