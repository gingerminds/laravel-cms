<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Contract;

use Illuminate\Database\Eloquent\Model;

/**
 * PHPStan-only marker for the shape `HasResolvedContentTrait` expects a
 * translation model to have — see `HasFileFieldsContract`'s docblock for why
 * this is a Model subclass rather than a plain interface, and why a concrete
 * `@var` cast to `PageTranslation` wouldn't work here (this trait's other
 * consumer is `App\Models\Event\EventTranslation`).
 *
 * @property-read array<int, array<string, mixed>>|null $content
 */
abstract class HasContentFieldContract extends Model
{
}
