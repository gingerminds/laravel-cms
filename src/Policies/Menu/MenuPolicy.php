<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Policies\Menu;

use Gingerminds\LaravelCore\Policies\AbstractResourcePolicy;

class MenuPolicy extends AbstractResourcePolicy
{
    protected function resourceName(): string
    {
        return 'menus';
    }
}
