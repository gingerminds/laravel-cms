<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Page;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest implements FormRequestInterface
{
    private const array FILE_FIELDS = ['main_visual', 'thumbnail'];

    private const array OPTIONAL_TEXT_FIELDS = ['hook', 'content'];

    /** @return  array<string, array<string>> */
    public function rules(): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:255'],
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

                $rules["translations.$langId.$field"] = (
                    (string) $langId === (string) $defaultLanguageId
                    && !in_array($field, self::OPTIONAL_TEXT_FIELDS, true)
                )
                    ? ['required', 'string']
                    : ['nullable', 'string'];
            }
        }

        return $rules;
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
