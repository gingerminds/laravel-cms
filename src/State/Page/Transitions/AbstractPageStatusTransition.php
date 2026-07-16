<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page\Transitions;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\State\Page\StatusState;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\Transition;

abstract class AbstractPageStatusTransition extends Transition
{
    public function __construct(
        protected readonly Page $page,
    ) {
    }

    public function handle(): Page
    {
        $this->page->status = $this->targetState();

        foreach ($this->timestamps() as $attribute => $value) {
            $this->page->{$attribute} = $value;
        }

        $this->page->save();

        return $this->page;
    }

    abstract protected function targetState(): StatusState;

    /**
     * @return array<string, Carbon|null>
     */
    protected function timestamps(): array
    {
        return [];
    }
}
