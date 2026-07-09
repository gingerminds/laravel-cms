<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Menu;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Http\Requests\Concerns\BuildsTranslationAttributesTrait;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Foundation\Http\FormRequest;

class MenuItemRequest extends FormRequest implements FormRequestInterface
{
    use BuildsTranslationAttributesTrait;

    /** @return array<string, list<string>|string> */
    public function rules(): array
    {
        $rules = [
            'code'            => 'required|string|max:255',
            'parent_id'       => 'nullable|integer|exists:menu_items,id',
            'is_active'       => 'required|boolean',
            'position'        => 'required|integer|min:0',
            'is_target_blank' => 'required|boolean',
            'is_no_referrer'  => 'required|boolean',
            'is_no_opener'    => 'required|boolean',
            'translations'    => 'required|array',
        ];

        $defaultLanguageId = app(SiteContext::class)->site()?->defaultLanguage()->first()?->id;

        foreach ($this->input('translations', []) as $langId => $fields) {
            foreach ($fields as $field => $value) {
                if ($langId === $defaultLanguageId && $field !== 'description') {
                    $rules["translations.$langId.$field"] = ['required', 'string'];
                } else {
                    $rules["translations.$langId.$field"] = ['nullable', 'string'];
                }
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return $this->translationAttributes([
            'name'        => __('gingerminds-core::translation.form.name'),
            'url'         => __('gingerminds-cms::translation.form.url'),
            'description' => __('gingerminds-core::translation.form.description'),
        ]);
    }
}
