<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Illuminate\Validation\Validator;

/**
 * Cross-field check run from `PageRequest::withValidator()`'s after-hook: a
 * page's *composed* public path (category prefix + slug) must stay unique
 * per (site, language) even though `slug` itself is only checked against
 * `page_translations.slug` in isolation. Two pages in different categories
 * can perfectly well share a bare slug as long as their full paths differ,
 * but never once the category prefix is folded in — see docs/Pages.md.
 */
class PageUrlCollisionValidator
{
    /**
     * @param array<int|string, array<string, mixed>> $translations
     */
    public function validate(
        Validator $validator,
        array $translations,
        ?Page $page,
        ?int $siteId,
        ?PageCategory $category
    ): void {
        $pageId = $page?->id;

        foreach ($translations as $langId => $fields) {
            if (!array_key_exists('slug', $fields)) {
                continue;
            }

            $title = $fields['title'] ?? '';

            if ('' === $title) {
                continue;
            }

            $slug         = $fields['slug']                                   ?? '';
            $categoryPath = $category?->getFullPathForLanguage((int) $langId) ?? '';
            $path         = Page::composePath($categoryPath, $slug);

            $collision = PageUrl::query()
                ->where('site_id', $siteId)
                ->where('language_id', $langId)
                ->where('path', $path)
                ->when($pageId, fn ($query) => $query->where('page_id', '!=', $pageId))
                ->exists();

            if ($collision) {
                $validator->errors()->add(
                    "translations.$langId.slug",
                    __('gingerminds-cms::translation.pages.message.url_taken')
                );
            }
        }
    }
}
