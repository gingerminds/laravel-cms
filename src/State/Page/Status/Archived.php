<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page\Status;

use Gingerminds\LaravelCms\State\Page\StatusState;

class Archived extends StatusState
{
    public static function code(): string
    {
        return 'archived';
    }
}
