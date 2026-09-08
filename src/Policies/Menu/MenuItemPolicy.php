<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Policies\Menu;

use Gingerminds\LaravelCore\Models\User\User;
use Gingerminds\LaravelCore\Policies\AbstractResourcePolicy;

class MenuItemPolicy extends AbstractResourcePolicy
{
    protected function resourceName(): string
    {
        return 'menu_items';
    }

    /**
     * Unlike `AbstractResourcePolicy`'s default, viewing menu items is left
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
