<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

/**
 * Optional base class: default `order()`, same spirit as
 * `AbstractApiProvider` in laravel-core (thin interface, common behaviour
 * lives here).
 */
abstract class AbstractBlock implements BlockInterface
{
    public function order(): int
    {
        return 100;
    }
}
