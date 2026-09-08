<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Contract;

interface ExcludableFromSearchInterface
{
    public function isExcludedFromSearch(): bool;
}
