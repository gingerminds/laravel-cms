<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Policies\Search;

use Gingerminds\LaravelCore\Models\User\User;
use Gingerminds\LaravelCore\Policies\AbstractResourcePolicy;

class SearchIndexPolicy extends AbstractResourcePolicy
{
    protected function resourceName(): string
    {
        return 'search_index';
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user): bool
    {
        return true;
    }
}
