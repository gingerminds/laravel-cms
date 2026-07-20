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
use Illuminate\Validation\Rules\Unique;

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
            'code' => $this->codeRules($siteId, $category),
            'parent_id' => $this->parentIdRules($siteId, $category),
            'is_unique' => ['nullable', 'boolean'],
        ];

        $defaultLanguageId = app(SiteContext::class)->site()?->defaultLanguage()->first()?->id;

        foreach (array_keys($this->input('translations', [])) as $langId) {
            $rules = [...$rules, ...$this->translationFieldRules($langId, $defaultLanguageId, $siteId, $category)];
        }

        return $rules;
    }

    /** @return array<int, mixed> */
    private function codeRules(?int $siteId, ?PageCategory $category): array
    {
        return [
            'required', 'string', 'max:255',
            Rule::unique('page_categories', 'code')
                ->where(fn ($query) => $query->where('site_id', $siteId))
                ->ignore($category),
        ];
    }

    /** @return array<int, mixed> */
    private function parentIdRules(?int $siteId, ?PageCategory $category): array
    {
        return [
            'nullable',
            Rule::exists('page_categories', 'id')->where(fn ($query) => $query->where('site_id', $siteId)),
            Rule::notIn($category instanceof PageCategory ? $this->descendantAndSelfIds($category) : []),
        ];
    }

    /**
     * Every rule for one submitted language: required/nullable for every
     * field, plus `prefix`'s extra sibling-scoped uniqueness check.
     *
     * @return array<string, mixed>
     */
    private function translationFieldRules(
        int|string $langId,
        ?int $defaultLanguageId,
        ?int $siteId,
        ?PageCategory $category
    ): array {
        $rules = [];

        foreach ($this->input("translations.$langId", []) as $field => $value) {
            $fieldRules = $this->requiredOrNullableRule($langId, $defaultLanguageId, (string) $field);

            if ($field === 'prefix') {
                $fieldRules[] = $this->uniquePrefixRule($langId, $siteId, $category);
            }

            $rules["translations.$langId.$field"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string>
     */
    private function requiredOrNullableRule(int|string $langId, ?int $defaultLanguageId, string $field): array
    {
        $isDefaultLanguage = (string) $langId === (string) $defaultLanguageId;

        if ($isDefaultLanguage && !in_array($field, self::OPTIONAL_TEXT_FIELDS, true)) {
            return ['required', 'string'];
        }

        return ['nullable', 'string'];
    }

    /**
     * Scoped to *sibling* categories (same parent, same site) rather than
     * checked globally: two categories in different branches of the tree
     * can legitimately share a prefix (or both leave it blank), since
     * their final full path still differs thanks to their distinct
     * ancestors — only same-level siblings would actually collide.
     */
    private function uniquePrefixRule(int|string $langId, ?int $siteId, ?PageCategory $category): Unique
    {
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

        return Rule::unique('page_category_translations', 'prefix')
            ->where(fn ($query) => $query
                ->where('language_id', $langId)
                ->whereIn('page_category_id', $siblingCategoryIds))
            ->ignore($existingTranslationId);
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
            'name' => __('gingerminds-cms::translation.page_categories.form.name'),
            'prefix' => __('gingerminds-cms::translation.page_categories.form.prefix'),
        ]);
    }
}
