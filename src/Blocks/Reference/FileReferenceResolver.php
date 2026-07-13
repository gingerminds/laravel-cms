<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Reference;

use Gingerminds\LaravelCms\Blocks\ReferenceFieldResolver;
use Gingerminds\LaravelMediaManager\Models\File\File;

/**
 * Built-in resolver for `file` type block fields (registered by default
 * under `gingerminds-cms.reference_resolvers.file`, see
 * `ContentReferenceResolver`) — an exclusive upload (`BlockFileFieldSync`),
 * so a plain `File` lookup is enough, no shared-library indirection like
 * `MediaReferenceResolver`.
 */
class FileReferenceResolver implements ReferenceFieldResolver
{
    public function loadMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var array<string, File> */
        return File::query()->whereIn('id', $ids)->get()->keyBy(
            static fn (File $file): string => (string) $file->id
        )->all();
    }

    public function resolveOne(mixed $loaded): ?array
    {
        if (!$loaded instanceof File) {
            return null;
        }

        return [
            'id'            => (string) $loaded->id,
            'url'           => "/api/files/{$loaded->id}",
            'thumbnail_url' => $loaded->isImage() ? "/api/files/{$loaded->id}/thumbnail" : null,
            'mime_type'     => $loaded->mime_type,
            'original_name' => $loaded->original_name,
            'size'          => $loaded->size,
            'is_image'      => $loaded->isImage(),
        ];
    }
}
