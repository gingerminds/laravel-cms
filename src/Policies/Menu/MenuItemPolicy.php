<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Policies\Menu;

use Gingerminds\LaravelCore\Policies\AbstractResourcePolicy;

class MenuItemPolicy extends AbstractResourcePolicy
{
    protected function resourceName(): string
    {
        return 'menu_items';
    }
}
