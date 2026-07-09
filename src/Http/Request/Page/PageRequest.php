<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Page;

use Closure;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PageRequest extends FormRequest implements FormRequestInterface
{
    private const array FILE_FIELDS = ['main_visual', 'thumbnail'];

    private const array OPTIONAL_TEXT_FIELDS = ['hook', 'content', 'slug'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page   = $this->route('page');
        $siteId = app(SiteContext::class)->site()?->id;

        $rules = [
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('pages', 'code')
                    ->where(fn ($query) => $query->where('site_id', $siteId))
                    ->ignore($page),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('page_categories', 'id')->where(fn ($query) => $query->where('site_id', $siteId)),
                function (string $attribute, mixed $value, Closure $fail) use ($page): void {
                    if (!$value) {
                        return;
                    }

                    /** @var PageCategory|null $category */
                    $category = PageCategory::find($value);

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
                },
            ],
        ];

        foreach (self::FILE_FIELDS as $field) {
            $rules[$field]             = $this->fileRule($field);
            $rules[$field . '_remove'] = ['nullable', 'boolean'];
        }

        $defaultLanguageId = app(SiteContext::class)->site()?->defaultLanguage()->first()?->id;

        $languageIds = array_unique(array_merge(
            array_keys($this->input('translations', [])),
            array_keys($this->file('translations', []))
        ));

        foreach ($languageIds as $langId) {
            foreach (self::FILE_FIELDS as $field) {
                $rules["translations.$langId.$field"]          = $this->fileRule("translations.$langId.$field");
                $rules["translations.$langId.{$field}_remove"] = ['nullable', 'boolean'];
            }

            foreach ($this->input("translations.$langId", []) as $field => $value) {
                if (
                    in_array($field, self::FILE_FIELDS, true)
                    || str_ends_with((string) $field, '_remove')
                ) {
                    continue;
                }

                $fieldRules = (
                    (string) $langId === (string) $defaultLanguageId
                    && !in_array($field, self::OPTIONAL_TEXT_FIELDS, true)
                )
                    ? ['required', 'string']
                    : ['nullable', 'string'];

                if ($field === 'slug') {
                    /** @var PageTranslation|null $existingTranslation */
                    $existingTranslation   = $page?->translations->firstWhere('language_id', (int) $langId);
                    $existingTranslationId = $existingTranslation?->id;

                    $fieldRules[] = Rule::unique('page_translations', 'slug')
                        ->where(fn ($query) => $query
                            ->where('site_id', $siteId)
                            ->where('language_id', $langId))
                        ->ignore($existingTranslationId);
                }

                $rules["translations.$langId.$field"] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * `page_urls` enforces "at most one page per (site, language, path)"
     * strictly — unlike category prefixes or slugs alone, a blank
     * *computed path* is never exempt, since it's the actual public URL
     * (see that table's migration). `Rule::unique` can't express this: it
     * only ever checks the raw `slug` column, not the full path (own slug
     * + every ancestor category's prefix). This recomputes that same path
     * per submitted language and checks it against `page_urls` by hand,
     * so a collision surfaces as a normal validation error instead of the
     * raw SQL exception `PageUrlSyncer` would otherwise hit on save.
     */
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

                // The form always submits every site language, so a blank
                // title here means this language simply hasn't been
                // translated yet — not "deliberately blank slug" — and
                // must not be checked as if it resolves to a real path
                // (see PageUrlSyncer::isActuallyTranslated() for the same
                // rule applied on save).
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
        $attributes = [];

        $labels = [
            'title'       => __('gingerminds-cms::translation.form.title'),
            'slug'        => __('gingerminds-cms::translation.form.slug'),
            'hook'        => __('gingerminds-cms::translation.form.hook'),
            'main_visual' => __('gingerminds-cms::translation.form.main_visual'),
            'thumbnail'   => __('gingerminds-media-manager::translation.form.thumbnail'),
        ];

        $attributes['category_id'] = __('gingerminds-cms::translation.pages.form.category');

        $languages = app(SiteContext::class)->site()->languages ?? collect();

        foreach ($this->input('translations', []) as $langId => $fields) {
            $language      = $languages->firstWhere('id', $langId);
            $languageLabel = $language->iso ?? $langId;

            foreach ($fields as $field => $value) {
                $fieldLabel                                = $labels[$field] ?? $field;
                $attributes["translations.$langId.$field"] = "$fieldLabel ($languageLabel)";
            }
        }

        return $attributes;
    }
}
