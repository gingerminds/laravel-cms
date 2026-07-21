<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Trait;

use Gingerminds\LaravelCms\Models\Contract\HasFileFieldsContract;
use Gingerminds\LaravelMediaManager\Models\File\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared `main_visual`/`thumbnail` relations + API accessors for models
 * whose translation row may itself override the owner's file (falling back
 * to the owner's own relation otherwise). Used by
 * `Gingerminds\LaravelCms\Models\Page\Page` itself, and available to
 * consuming projects for their own Page-like models that don't extend
 * `Page` (e.g. an `Event` model).
 *
 * Requires the using model to expose `currentTranslation` (see
 * `Gingerminds\LaravelMultisite\Models\Trait\TranslatableModelTrait`) whose
 * translation model has its own `mainVisual`/`thumbnail` relations and
 * `main_visual_id`/`thumbnail_id` columns.
 */
trait HasMainVisualAndThumbnailTrait
{
    /**
     * @return BelongsTo<File, $this>
     */
    public function mainVisual(): BelongsTo
    {
        return $this->belongsTo(File::class, 'main_visual_id');
    }

    public function getMainVisualFileAttribute(): ?string
    {
        /** @var HasFileFieldsContract|null $translation */
        $translation = $this->currentTranslation;

        $fileId = $translation?->main_visual_id !== null
            ? $translation->mainVisual?->id
            : $this->getRelationValue('mainVisual')?->id;

        return $fileId !== null ? (string) $fileId : null;
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(File::class, 'thumbnail_id');
    }

    public function getThumbnailFileAttribute(): ?string
    {
        /** @var HasFileFieldsContract|null $translation */
        $translation = $this->currentTranslation;

        $fileId = $translation?->thumbnail_id !== null
            ? $translation->thumbnail?->id
            : $this->getRelationValue('thumbnail')?->id;

        return $fileId !== null ? (string) $fileId : null;
    }
}
