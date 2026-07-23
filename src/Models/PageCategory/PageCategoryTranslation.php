<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\PageCategory;

use Gingerminds\LaravelCore\Models\CacheCascadeInterface;
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
class PageCategoryTranslation extends Model implements CacheCascadeInterface
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

    /**
     * `name`/`prefix` feed both the owning category's own cached
     * representation and, transitively, every Page whose path/switch_lang is
     * built from it.
     *
     * @return array<int, string>
     */
    public static function getCascadeCacheKeys(): array
    {
        return ['page_category', 'page'];
    }
}
