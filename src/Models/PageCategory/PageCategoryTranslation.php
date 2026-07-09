<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\PageCategory;

use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Models\Trait\TranslationModelTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $language_id
 * @property int|null $site_id
 * @property string|null $name
 * @property string|null $prefix
 * @property-read Language $language
 */
class PageCategoryTranslation extends Model
{
    use TranslationModelTrait;

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'name',
            'prefix',
            'language_id',
            'site_id',
        ];
    }
}
