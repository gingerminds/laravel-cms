<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Filters;

/**
 * A per-request store holding a resource's computed `filters` (see
 * `PageFilterComputeService`/`SearchFilterComputeService`), written by that
 * resource's own ApiProvider and read back by its
 * `AbstractInjectFiltersMiddleware` subclass to merge into the response body.
 */
interface FilterStoreInterface
{
    /**
     * @return array<string, array{type: string, options: list<array<string, mixed>>}>
     */
    public function get(): array;

    public function isEmpty(): bool;
}
