<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Search;

use Illuminate\Support\Carbon;

final class SearchResultItem
{
    /**
     * @param array<string, mixed>|null $resource
     */
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $excerpt,
        public readonly ?Carbon $publishedAt,
        public readonly ?array $resource,
    ) {
    }
}
