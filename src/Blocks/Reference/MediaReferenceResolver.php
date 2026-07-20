<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Reference;

use Gingerminds\LaravelCms\Blocks\ReferenceFieldResolver;
use Gingerminds\LaravelMediaManager\Resolver\ResourceResolver as MediaResourceResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * Built-in resolver for `media` type block fields (registered by default
 * under `gingerminds-cms.reference_resolvers.media`, see
 * `ContentReferenceResolver`) — picks from the shared library, resolved
 * through the project's own `Media` model (`ResourceResolver::model()`,
 * same override mechanism as everywhere else this field type is used).
 *
 * Reuses `Media::GROUP_LIST`'s own field names (id, name, file_reference,
 * file_size, thumbnail_reference, thumbnail_size) rather than inventing a
 * dedicated shape, per docs/ContentBlocks.md's explicit intent: a media
 * embedded in a block should look exactly like the media list endpoint.
 */
class MediaReferenceResolver implements ReferenceFieldResolver
{
    public function loadMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $mediaModelClass = MediaResourceResolver::model('media');

        /** @var array<int|string, Model> */
        return $mediaModelClass::query()->whereIn('id', $ids)->get()->keyBy('id')->all();
    }

    public function resolveOne(mixed $loaded): ?array
    {
        if (!$loaded instanceof Model) {
            return null;
        }

        return [
            'id' => $loaded->getAttribute('id'),
            'name' => $loaded->getAttribute('name'),
            'file_reference' => $loaded->getAttribute('file_reference'),
            'file_size' => $loaded->getAttribute('file_size'),
            'thumbnail_reference' => $loaded->getAttribute('thumbnail_reference'),
            'thumbnail_size' => $loaded->getAttribute('thumbnail_size'),
        ];
    }
}
