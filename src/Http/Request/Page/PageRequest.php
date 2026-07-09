<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Page;

use Closure;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Http\Requests\Concerns\BuildsTranslationAttributesTrait;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

class PageRequest extends FormRequest implements FormRequestInterface
{
    use BuildsTranslationAttributesTrait;

    private const array FILE_FIELDS = ['main_visual', 'thumbnail'];

    private const array OPTIONAL_TEXT_FIELDS = ['hook', 'content', 'slug'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page   = $this->route('page');
        $siteId = app(SiteContext::class)->site()?->id;

        $rules = [
            'code'        => $this->codeRules($siteId, $page),
            'category_id' => $this->categoryIdRules($siteId, $page),
        ];

        foreach (self::FILE_FIELDS as $field) {
            $rules[$field]             = $this->fileRule($field);
            $rules[$field . '_remove'] = ['nullable', 'boolean'];
        }

        $defaultLanguageId = app(SiteContext::class)->site()?->defaultLanguage()->first()?->id;

        foreach ($this->submittedLanguageIds() as $langId) {
            $rules = [...$rules, ...$this->translationFieldRules($langId, $defaultLanguageId, $siteId, $page)];
        }

        return $rules;
    }

    /**
     * Every language id that has *something* submitted for it, whether a
     * text field or a file upload — either input alone would miss
     * languages that only have the other kind of field set.
     *
     * @return list<int|string>
     */
    private function submittedLanguageIds(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->input('translations', [])),
            array_keys($this->file('translations', []))
        )));
    }

    /** @return array<int, mixed> */
    private function codeRules(?int $siteId, ?Page $page): array
    {
        return [
            'required', 'string', 'max:255',
            Rule::unique('pages', 'code')
                ->where(fn ($query) => $query->where('site_id', $siteId))
                ->ignore($page),
        ];
    }

    /** @return array<int, mixed> */
    private function categoryIdRules(?int $siteId, ?Page $page): array
    {
        return [
            'nullable',
            Rule::exists('page_categories', 'id')->where(fn ($query) => $query->where('site_id', $siteId)),
            function (string $attribute, mixed $value, Closure $fail) use ($page): void {
                $this->failIfCategoryAlreadyUniquelyUsed($value, $fail, $page);
            },
        ];
    }

    /**
     * `is_unique` categories (see `PageCategory`) may only ever have one
     * page attached — this is that constraint's actual enforcement point.
     */
    private function failIfCategoryAlreadyUniquelyUsed(mixed $categoryId, Closure $fail, ?Page $page): void
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

    /**
     * Every rule for one submitted language: its file fields, plus every
     * other translation field (required/nullable, and `slug`'s extra
     * per-site uniqueness check).
     *
     * @return array<string, mixed>
     */
    private function translationFieldRules(
        int|string $langId,
        ?int $defaultLanguageId,
        ?int $siteId,
        ?Page $page
    ): array {
        $rules = [];

        foreach (self::FILE_FIELDS as $field) {
            $rules["translations.$langId.$field"]          = $this->fileRule("translations.$langId.$field");
            $rules["translations.$langId.{$field}_remove"] = ['nullable', 'boolean'];
        }

        foreach ($this->input("translations.$langId", []) as $field => $value) {
            if ($this->isFileOrRemoveField((string) $field)) {
                continue;
            }

            $fieldRules = $this->requiredOrNullableRule($langId, $defaultLanguageId, (string) $field);

            if ($field === 'slug') {
                $fieldRules[] = $this->uniqueSlugRule($langId, $siteId, $page);
            }

            $rules["translations.$langId.$field"] = $fieldRules;
        }

        return $rules;
    }

    private function isFileOrRemoveField(string $field): bool
    {
        return in_array($field, self::FILE_FIELDS, true) || str_ends_with($field, '_remove');
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
     * Scoped to `(site_id, language_id)`, not globally — two pages in
     * different categories can never share a slug even though their full
     * public paths (category prefix + slug) differ; see `docs/Pages.md`.
     */
    private function uniqueSlugRule(int|string $langId, ?int $siteId, ?Page $page): Unique
    {
        /** @var PageTranslation|null $existingTranslation */
        $existingTranslation   = $page?->translations->firstWhere('language_id', (int) $langId);
        $existingTranslationId = $existingTranslation?->id;

        return Rule::unique('page_translations', 'slug')
            ->where(fn ($query) => $query
                ->where('site_id', $siteId)
                ->where('language_id', $langId))
            ->ignore($existingTranslationId);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Page|null $page */
            $page   = $this->route('page');
            $siteId = app(SiteContext::class)->site()?->id;

            $categoryId = $this->filled('category_id') ? (int) $this->input('category_id') : null;
            /** @var PageCategory|null $category */
            $category = $categoryId ? PageCategory::find($categoryId) : null;
            $pageId   = $page?->id;

            foreach ($this->input('translations', []) as $langId => $fields) {
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
        });
    }

    /**
     * @return string[]
     */
    private function fileRule(string $key): array
    {
        return $this->hasFile($key)
            ? ['file', 'image', 'max:5120']
            : ['nullable'];
    }

    public function attributes(): array
    {
        $attributes = [
            'category_id' => __('gingerminds-cms::translation.pages.form.category'),
        ];

        return $attributes + $this->translationAttributes([
            'title'       => __('gingerminds-cms::translation.form.title'),
            'slug'        => __('gingerminds-cms::translation.form.slug'),
            'hook'        => __('gingerminds-cms::translation.form.hook'),
            'main_visual' => __('gingerminds-cms::translation.form.main_visual'),
            'thumbnail'   => __('gingerminds-media-manager::translation.form.thumbnail'),
        ]);
    }
}
