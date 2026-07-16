<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Policies\PageCategory;

use Gingerminds\LaravelCore\Models\User\User;
use Gingerminds\LaravelCore\Policies\AbstractResourcePolicy;

class PageCategoryPolicy extends AbstractResourcePolicy
{
    protected function resourceName(): string
    {
        return 'page_categories';
    }

    /**
     * Unlike `AbstractResourcePolicy`'s default, viewing categories is left
     * open to everyone — only creating/editing/deleting is permission-gated.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user): bool
    {
        return true;
    }
}
