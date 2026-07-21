<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Trait;

use Gingerminds\LaravelCms\Blocks\ContentReferenceResolver;
use Gingerminds\LaravelCms\Models\Contract\HasContentFieldContract;

/**
 * Resolves `file`/`media` type block fields in a translation's raw stored
 * `content` json into richer objects (url, mime type, thumbnail...) before
 * being served by the API — sparing the headless frontend an extra
 * round-trip per referenced file.
 *
 * Used by `Gingerminds\LaravelCms\Models\Page\Page` itself. Only fit for
 * models whose translation model exposes a `content` property resolved
 * as-is (no special-casing needed) — a project-level override with bespoke
 * per-block-type handling (e.g. a `media_list` block, see
 * `App\Models\Page\Page` in the yanmar-extranet project) should keep its own
 * `getContentAttribute()` instead of using this trait.
 */
trait HasResolvedContentTrait
{
    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getContentAttribute(): ?array
    {
        /** @var HasContentFieldContract|null $translation */
        $translation = $this->currentTranslation;

        return ContentReferenceResolver::resolve($translation?->content);
    }
}
