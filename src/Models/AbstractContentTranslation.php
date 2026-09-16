<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models;

use Gingerminds\LaravelCore\Models\CacheCascadeInterface;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMultisite\Models\Trait\TranslationModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class AbstractContentTranslation extends Model implements
    CacheCascadeInterface,
    EagerLoadableModelInterface,
    ResourceModelInterface
{
    use TranslationModelTrait;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
    ];

    /**
     * @return array<int, string>
     */
    public static function getEagerLoads(): array
    {
        return ['mainVisual', 'thumbnail'];
    }

    /**
     * @return array<int, string>
     */
    public function getFillable(): array
    {
        return [
            'title',
            'slug',
            'hook',
            'content',
            'language_id',
            'main_visual_id',
            'thumbnail_id',
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

    /**
     * @return array<int, string>
     */
    public static function getCascadeCacheKeys(): array
    {
        return [static::cascadeCacheKey()];
    }

    abstract protected static function cascadeCacheKey(): string;
}
