<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Contract;

use Gingerminds\LaravelMediaManager\Models\File\File;
use Illuminate\Database\Eloquent\Model;

/**
 * PHPStan-only marker for the shape `HasMainVisualAndThumbnailTrait` expects
 * a translation model to have. Never actually extended or instantiated at
 * runtime — `PageTranslation`/`App\Models\Event\EventTranslation` already
 * declare this exact shape via their own `@property`/`@property-read` tags,
 * so this class only exists to give the trait something concrete to `@var`-
 * cast `currentTranslation` to (which `TranslatableModelTrait::currentTranslation()`
 * types as a bare `HasOne`/`Model`, losing the concrete translation class).
 *
 * Deliberately a Model subclass, not a plain interface: Larastan's own
 * `ModelPropertyExtension` only resolves `@property` docblock tags against
 * an actual `Model`-descended class reflection, not against an interface
 * intersected with `Model` — see `Gingerminds\LaravelCms\State\Page\Contract\HasStatusPropertyContract`
 * for the same reasoning applied to `status`.
 *
 * @property-read int|null $main_visual_id
 * @property-read File|null $mainVisual
 * @property-read int|null $thumbnail_id
 * @property-read File|null $thumbnail
 */
abstract class HasFileFieldsContract extends Model
{
}
