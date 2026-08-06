<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request;

use Gingerminds\LaravelCms\Http\Request\Concerns\HandlesContentBlocksTrait;
use Gingerminds\LaravelCms\Http\Request\Concerns\HandlesTranslationFileFieldsTrait;
use Gingerminds\LaravelMultisite\Http\Requests\AbstractTranslatableResourceRequest;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractCmsTranslatableResourceRequest extends AbstractTranslatableResourceRequest
{
    use HandlesContentBlocksTrait;
    use HandlesTranslationFileFieldsTrait;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'translations' => $this->decodeTranslations($this->input('translations', [])),
        ]);
    }

    /**
     * @return list<int|string>
     */
    protected function submittedLanguageIds(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->input('translations', [])),
            array_keys($this->file('translations', []))
        )));
    }

    /**
     * @return array<string, mixed>
     */
    protected function translationFieldRules(int|string $langId): array
    {
        $rules = [];

        foreach ($this->fileFields() as $field) {
            $rules["translations.$langId.$field"]          = $this->fileRule("translations.$langId.$field");
            $rules["translations.$langId.{$field}_remove"] = ['nullable', 'boolean'];
        }

        $contentField = $this->contentFieldName();

        foreach ($this->input("translations.$langId", []) as $field => $value) {
            if ($this->isFileOrRemoveField((string) $field) || $field === $contentField) {
                continue;
            }

            $fieldRules = $this->requiredOrNullableRule($langId, (string) $field);

            if ($field === 'slug' && ($uniqueRule = $this->uniqueSlugRule($langId)) instanceof Unique) {
                $fieldRules[] = $uniqueRule;
            }

            $rules["translations.$langId.$field"] = $fieldRules;
        }

        return [...$rules, ...$this->contentBlockRules($langId)];
    }

    /**
     * @return array<string, string>
     */
    protected function baseAttributes(): array
    {
        $attributes = $this->translationAttributes($this->translationFieldLabels());

        foreach ($this->submittedLanguageIds() as $langId) {
            $attributes += $this->contentBlockAttributes($langId, $this->languageLabelFor($langId));
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fileFieldRules(): array
    {
        $rules = [];

        foreach ($this->fileFields() as $field) {
            $rules[$field]             = $this->fileRule($field);
            $rules[$field . '_remove'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    protected function allTranslationRules(): array
    {
        $rules = [];

        foreach ($this->submittedLanguageIds() as $langId) {
            $rules = [...$rules, ...$this->translationFieldRules($langId)];
        }

        return $rules;
    }
}
