<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Menu;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Illuminate\Foundation\Http\FormRequest;

class MenuItemReorderRequest extends FormRequest implements FormRequestInterface
{
    /** @return array<string, list<string>|string> */
    public function rules(): array
    {
        return [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:menu_items,id',
            'parent_id' => 'nullable|integer|exists:menu_items,id',
        ];
    }
}
