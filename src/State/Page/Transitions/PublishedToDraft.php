<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page\Transitions;

use Gingerminds\LaravelCms\State\Page\Status\Draft;
use Gingerminds\LaravelCms\State\Page\StatusState;

class PublishedToDraft extends AbstractPageStatusTransition
{
    protected function targetState(): StatusState
    {
        return new Draft($this->page);
    }

    protected function timestamps(): array
    {
        return ['published_at' => null];
    }
}
