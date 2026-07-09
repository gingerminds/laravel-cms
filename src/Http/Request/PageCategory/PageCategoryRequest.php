<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\PageCategory;

use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategoryTranslation;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Http\Requests\Concerns\BuildsTranslationAttributesTrait;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageCategoryRequest extends FormRequest implements FormRequestInterface
{
    use BuildsTranslationAttributesTrait;

    private const array OPTIONAL_TEXT_FIELDS = ['prefix'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var PageCategory|null $category */
        $category = $this->route('page_category');
        $siteId   = app(SiteContext::class)->site()?->id;

        $rules = [
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('page_categories', 'code')
                    ->where(fn ($query) => $query->where('site_id', $siteId))
                    ->ignore($category),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('page_categories', 'id')->where(fn ($query) => $query->where('site_id', $siteId)),
                Rule::notIn($category ? $this->descendantAndSelfIds($category) : []),
            ],
            'is_unique' => ['nullable', 'boolean'],
        ];

        $defaultLanguageId = app(SiteContext::class)->site()?->defaultLanguage()->first()?->id;

        $languageIds = array_keys($this->input('translations', []));

        foreach ($languageIds as $langId) {
            foreach ($this->input("translations.$langId", []) as $field => $value) {
                $fieldRules = (
                    (string) $langId === (string) $defaultLanguageId
                    && !in_array($field, self::OPTIONAL_TEXT_FIELDS, true)
                )
                    ? ['required', 'string']
                    : ['nullable', 'string'];

                if ($field === 'prefix') {
                    // `prefix` uniqueness is scoped to *sibling* categories
                    // (same parent, same site) rather than checked globally:
                    // two categories in different branches of the tree can
                    // legitimately share a prefix (or both leave it blank),
                    // since their final full path still differs thanks to
                    // their distinct ancestors — only same-level siblings
                    // would actually collide.
                    $parentId   = $this->filled('parent_id') ? (int) $this->input('parent_id') : null;
                    $categoryId = $category?->id;

                    $siblingCategoryIds = PageCategory::query()
                        ->where('site_id', $siteId)
                        ->where(fn ($query) => $parentId
                            ? $query->where('parent_id', $parentId)
                            : $query->whereNull('parent_id'))
                        ->when($categoryId, fn ($query) => $query->where('id', '!=', $categoryId))
                        ->pluck('id');

                    /** @var PageCategoryTranslation|null $existingTranslation */
                    $existingTranslation   = $category?->translations->firstWhere('language_id', (int) $langId);
                    $existingTranslationId = $existingTranslation?->id;

                    $fieldRules[] = Rule::unique('page_category_translations', 'prefix')
                        ->where(fn ($query) => $query
                            ->where('language_id', $langId)
                            ->whereIn('page_category_id', $siblingCategoryIds))
                        ->ignore($existingTranslationId);
                }

                $rules["translations.$langId.$field"] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * A category can't be re-parented under itself or one of its own
     * descendants — that would create a cycle when walking the tree to
     * build a page's full URL path.
     *
     * @return array<int>
     */
    private function descendantAndSelfIds(PageCategory $category): array
    {
        $ids = [$category->id];

        /** @var PageCategory $child */
        foreach ($category->children()->get() as $child) {
            $ids = array_merge($ids, $this->descendantAndSelfIds($child));
        }

        return $ids;
    }

    public function attributes(): array
    {
        $attributes = [
            'is_unique' => __('gingerminds-cms::translation.page_categories.form.is_unique'),
        ];

        return $attributes + $this->translationAttributes([
            'name'   => __('gingerminds-cms::translation.page_categories.form.name'),
            'prefix' => __('gingerminds-cms::translation.page_categories.form.prefix'),
        ]);
    }
}
