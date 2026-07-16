<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

use Closure;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;

/**
 * `is_unique` categories (see `PageCategory`) may only ever have one page
 * attached — this is that constraint's actual enforcement point, used as
 * an inline validation rule closure in `PageRequest::categoryIdRules()`.
 */
class PageCategoryUniquenessValidator
{
    public function ensureNotAlreadyUsed(mixed $categoryId, Closure $fail, ?Page $page): void
    {
        if (!$categoryId) {
            return;
        }

        /** @var PageCategory|null $category */
        $category = PageCategory::find($categoryId);

        if (!$category?->is_unique) {
            return;
        }

        $pageId = $page?->id;

        $alreadyUsed = $category->pages()
            ->when($pageId, fn ($query) => $query->where('id', '!=', $pageId))
            ->exists();

        if ($alreadyUsed) {
            $fail(__('gingerminds-cms::translation.pages.message.is_unique_taken'));
        }
    }
}
