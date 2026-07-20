<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page\Transitions;

use Gingerminds\LaravelCms\State\Page\Status\Archived;
use Gingerminds\LaravelCms\State\Page\StatusState;

class PublishedToArchived extends AbstractPageStatusTransition
{
    protected function targetState(): StatusState
    {
        return new Archived($this->page);
    }

    protected function timestamps(): array
    {
        return [
            'archived_at' => now(),
            'published_at' => null,
        ];
    }
}
