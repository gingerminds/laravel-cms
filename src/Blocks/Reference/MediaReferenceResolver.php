<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Reference;

use Gingerminds\LaravelCms\Blocks\ReferenceFieldResolver;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelMediaManager\Resolver\ResourceResolver as MediaResourceResolver;
use Illuminate\Database\Eloquent\Model;

class MediaReferenceResolver implements ReferenceFieldResolver
{
    public function loadMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $mediaModelClass = MediaResourceResolver::model('media');

        $with = is_subclass_of($mediaModelClass, EagerLoadableModelInterface::class)
            ? $mediaModelClass::getEagerLoads()
            : [];

        /** @var array<int|string, Model> */
        return $mediaModelClass::query()->with($with)->whereIn('id', $ids)->get()->keyBy('id')->all();
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
            'file_type' => $loaded->getAttribute('file_type'),
            'thumbnail_reference' => $loaded->getAttribute('thumbnail_reference'),
            'thumbnail_size' => $loaded->getAttribute('thumbnail_size'),
        ];
    }
}
