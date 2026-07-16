<?php

namespace Gingerminds\LaravelCms\Models\Page;

use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Models\Trait\TranslationModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $site_id
 * @property int $language_id
 * @property string|null $title
 * @property string|null $slug
 * @property string|null $hook
 * @property array<int, array<string, mixed>>|null $content
 * @property int|null $main_visual_id
 * @property int|null $thumbnail_id
 * @property-read Language $language
 * @property-read File|null $mainVisual
 * @property-read File|null $thumbnail
 */
class PageTranslation extends Model
{
    use TranslationModelTrait;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
    ];

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'title',
            'slug',
            'hook',
            'content',
            'main_visual_id',
            'thumbnail_id',
            'language_id',
            'site_id',
        ];
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function mainVisual(): BelongsTo
    {
        return $this->belongsTo(File::class, 'main_visual_id');
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(File::class, 'thumbnail_id');
    }
}
