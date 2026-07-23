<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Menu\MenuItem;

use Gingerminds\LaravelCore\Models\CacheCascadeInterface;
use Gingerminds\LaravelMultisite\Models\Trait\TranslationModelTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $url
 * @property string $description
 */
class MenuItemTranslation extends Model implements CacheCascadeInterface
{
    use TranslationModelTrait;

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'name',
            'url',
            'description',
            'language_id',
        ];
    }

    /**
     * A translated name/url/description change must also invalidate Menu's
     * cache, since active_items (embedded in Menu's cached representation)
     * renders these fields.
     *
     * @return array<int, string>
     */
    public static function getCascadeCacheKeys(): array
    {
        return ['menu'];
    }
}
