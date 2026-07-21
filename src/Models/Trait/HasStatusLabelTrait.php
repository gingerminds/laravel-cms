<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Trait;

/**
 * Shared by any model that casts its `status` column to a
 * `Spatie\ModelStates\State` (typically via `Gingerminds\LaravelCms\State\Page\StatusState`)
 * and needs to expose the current state's short code. Used by
 * `Gingerminds\LaravelCms\Models\Page\Page` itself, and available to
 * consuming projects for their own Page-like models that don't extend
 * `Page` (e.g. an `Event` model with its own table/translation model).
 */
trait HasStatusLabelTrait
{
    public function getStatusLabelAttribute(): string
    {
        return $this->status->getMorphClass()::code();
    }
}
