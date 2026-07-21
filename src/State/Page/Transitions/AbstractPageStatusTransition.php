<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page\Transitions;

use Gingerminds\LaravelCms\State\Page\Contract\HasStatusPropertyContract;
use Gingerminds\LaravelCms\State\Page\StatusState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\Transition;

/**
 * Named after `Page` for historical reasons, but generic on purpose:
 * `StatusState` (and this whole transition set) is shared by any model
 * that casts a `status` column to it — `Gingerminds\LaravelCms\Models\Page\Page`
 * and `App\Models\Event\Event` (in the yanmar-extranet project) both do.
 * Nothing below needs more than plain Eloquent attribute get/set + `save()`,
 * so a bare `Model` is both correct and enough.
 */
abstract class AbstractPageStatusTransition extends Transition
{
    public function __construct(
        protected readonly Model $page,
    ) {
    }

    public function handle(): Model
    {
        /** @var HasStatusPropertyContract $page */
        $page = $this->page;

        $page->status = $this->targetState();

        foreach ($this->timestamps() as $attribute => $value) {
            $page->{$attribute} = $value;
        }

        $page->save();

        return $page;
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
