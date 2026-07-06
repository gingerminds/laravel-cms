<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Page;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest implements FormRequestInterface
{
    /** @return  string[] */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255',
        ];
    }
}
