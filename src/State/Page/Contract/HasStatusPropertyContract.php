<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page\Contract;

use Gingerminds\LaravelCms\State\Page\StatusState;
use Illuminate\Database\Eloquent\Model;

/**
 * PHPStan-only marker for `AbstractPageStatusTransition::handle()`'s local
 * `@var` cast of `$page` — never actually extended or instantiated at
 * runtime. `Gingerminds\LaravelCms\Models\Page\Page` and `App\Models\Event\Event`
 * (in the yanmar-extranet project) both already declare this exact shape via
 * their own `@property StatusState $status` class docblock (needed for the
 * `$casts['status'] => StatusState::class` cast); this class just gives the
 * transition base class — which only knows `$page` as a bare
 * `Illuminate\Database\Eloquent\Model`, since it's shared by any status-cast
 * model — something concrete to cast to.
 *
 * Deliberately a `Model` subclass, not a plain interface: Larastan's own
 * `ModelPropertyExtension` (which resolves `@property` docblock tags for
 * Eloquent models) only looks at an actual `Model`-descended class
 * reflection — a plain interface intersected with `Model` isn't picked up,
 * so casting to `Model&SomeInterface` left the property "not found" even
 * though the interface declared it.
 *
 * @property StatusState $status
 */
abstract class HasStatusPropertyContract extends Model
{
}
