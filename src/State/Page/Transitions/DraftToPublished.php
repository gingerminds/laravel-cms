<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page\Transitions;

use Gingerminds\LaravelCms\State\Page\Status\Published;
use Gingerminds\LaravelCms\State\Page\StatusState;

class DraftToPublished extends AbstractPageStatusTransition
{
    protected function targetState(): StatusState
    {
        return new Published($this->page);
    }

    protected function timestamps(): array
    {
        return ['published_at' => now()];
    }
}
