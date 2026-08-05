<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Page;

use Closure;
use Gingerminds\LaravelCms\Blocks\ContentFieldSupport;
use Gingerminds\LaravelCms\Http\Request\AbstractCmsTranslatableResourceRequest;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Services\Page\PageCategoryUniquenessValidator;
use Gingerminds\LaravelCms\Services\Page\PageUrlCollisionValidator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PageRequest extends AbstractCmsTranslatableResourceRequest
{
    private const array FILE_FIELDS = ['main_visual', 'thumbnail'];

    // `content` isn't in here: it gets its own array/block-schema rules
    // from contentBlockRules(), not the generic required/nullable string
    // rule the other optional fields get.
    private const array OPTIONAL_TEXT_FIELDS = ['hook', 'slug'];

    private const string CONTENT_FIELD = 'content';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        $rules = [
            'code' => $this->codeRules($page),
            'category_id' => $this->categoryIdRules($page),
        ];

        foreach ($this->fileFields() as $field) {
            $rules[$field]             = $this->fileRule($field);
            $rules[$field . '_remove'] = ['nullable', 'boolean'];
        }

        foreach ($this->submittedLanguageIds() as $langId) {
            $rules = [...$rules, ...$this->translationFieldRules($langId)];
        }

        return $rules;
    }

    /** @return array<int, mixed> */
    private function codeRules(?Page $page): array
    {
        return $this->uniqueCodeRule('pages', $page?->id);
    }

    /** @return array<int, mixed> */
    private function categoryIdRules(?Page $page): array
    {
        return [
            'nullable',
            Rule::exists('page_categories', 'id')->where(fn ($query) => $query->where('site_id', $this->siteId())),
            function (string $attribute, mixed $value, Closure $fail) use ($page): void {
                app(PageCategoryUniquenessValidator::class)->ensureNotAlreadyUsed($value, $fail, $page);
            },
        ];
    }

    protected function fileFields(): array
    {
        return self::FILE_FIELDS;
    }

    protected function optionalTextFields(): array
    {
        return self::OPTIONAL_TEXT_FIELDS;
    }

    protected function slugTranslationsTable(): ?string
    {
        return 'page_translations';
    }

    /**
     * Scoped to `(site_id, language_id)`, not globally — two pages in
     * different categories can never share a slug even though their full
     * public paths (category prefix + slug) differ; see `docs/Pages.md`.
     */
    protected function existingTranslationId(int|string $langId): ?int
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        /** @var PageTranslation|null $existingTranslation */
        $existingTranslation = $page?->translations->firstWhere('language_id', (int) $langId);

        return $existingTranslation?->id;
    }

    protected function contentFieldName(): ?string
    {
        return self::CONTENT_FIELD;
    }

    protected function contentBlockRules(int|string $langId): array
    {
        return ContentFieldSupport::rulesFor(
            "translations.$langId." . self::CONTENT_FIELD,
            $this->input("translations.$langId." . self::CONTENT_FIELD, [])
        );
    }

    protected function contentBlockAttributes(int|string $langId, string $languageLabel): array
    {
        return ContentFieldSupport::attributesFor(
            "translations.$langId." . self::CONTENT_FIELD,
            $this->input("translations.$langId." . self::CONTENT_FIELD, []),
            $languageLabel
        );
    }

    /**
     * The hidden `content` input submits a JSON string (see
     * `<x-gingerminds-cms::form.inputs.canvas>`); decode it into a PHP
     * array up front so the cast on `PageTranslation::content` ('array')
     * doesn't double-encode it later, and so `content.*` rules below can
     * validate it as a real array. Pruning of stale block fields also
     * happens here — see `ContentFieldSupport::decodeAndPrune()`.
     *
     * @param  array<int|string, array<string, mixed>>  $translations
     * @return array<int|string, array<string, mixed>>
     */
    protected function decodeTranslations(array $translations): array
    {
        return ContentFieldSupport::decodeAndPrune($translations, self::CONTENT_FIELD);
    }

    protected function translationFieldLabels(): array
    {
        return [
            'title' => __('gingerminds-cms::translation.form.title'),
            'slug' => __('gingerminds-cms::translation.form.slug'),
            'hook' => __('gingerminds-cms::translation.form.hook'),
            'main_visual' => __('gingerminds-cms::translation.form.main_visual'),
            'thumbnail' => __('gingerminds-media-manager::translation.form.thumbnail'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Page|null $page */
            $page = $this->route('page');

            $categoryId = $this->filled('category_id') ? (int) $this->input('category_id') : null;
            /** @var PageCategory|null $category */
            $category = $categoryId ? PageCategory::find($categoryId) : null;

            app(PageUrlCollisionValidator::class)->validate(
                $validator,
                $this->input('translations', []),
                $page,
                $this->siteId(),
                $category
            );
        });
    }

    public function attributes(): array
    {
        return [
            'category_id' => __('gingerminds-cms::translation.pages.form.category'),
        ] + $this->baseAttributes();
    }
}
