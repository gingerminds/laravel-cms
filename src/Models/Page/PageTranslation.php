<?php

namespace Gingerminds\LaravelCms\Models\Page;

use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMultisite\Models\Trait\TranslationModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    use TranslationModelTrait;

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