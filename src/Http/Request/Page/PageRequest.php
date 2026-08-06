<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Page;

use Closure;
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

    private const array OPTIONAL_TEXT_FIELDS = ['hook', 'slug'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        return [
            'code' => $this->codeRules($page),
            'category_id' => $this->categoryIdRules($page),
            ...$this->fileFieldRules(),
            ...$this->allTranslationRules(),
        ];
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
