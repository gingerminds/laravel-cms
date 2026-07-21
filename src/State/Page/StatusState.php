<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page;

use Gingerminds\LaravelCms\State\Page\Status\Archived;
use Gingerminds\LaravelCms\State\Page\Status\Draft;
use Gingerminds\LaravelCms\State\Page\Status\Published;
use Gingerminds\LaravelCms\State\Page\Transitions\ArchivedToDraft;
use Gingerminds\LaravelCms\State\Page\Transitions\DraftToPublished;
use Gingerminds\LaravelCms\State\Page\Transitions\PublishedToArchived;
use Gingerminds\LaravelCms\State\Page\Transitions\PublishedToDraft;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Reused as-is by any model that casts its `status` column to it — not just
 * `Page` (see `Gingerminds\LaravelCms\State\Page\Transitions\AbstractPageStatusTransition`'s
 * docblock for why that's safe: the whole transition set only ever needs
 * plain Eloquent Model behavior).
 *
 * @extends State<Model>
 */
abstract class StatusState extends State implements JsonSerializable
{
    abstract public static function code(): string;

    public function jsonSerialize(): string
    {
        return static::code();
    }

    public static function config(): StateConfig
    {
        return parent::config()
            ->registerState(Draft::class)
            ->registerState(Published::class)
            ->registerState(Archived::class)
            ->default(Draft::class)
            ->allowTransition(Draft::class, Published::class, DraftToPublished::class)
            ->allowTransition(Published::class, Draft::class, PublishedToDraft::class)
            ->allowTransition(Published::class, Archived::class, PublishedToArchived::class)
            ->allowTransition(Archived::class, Draft::class, ArchivedToDraft::class)
        ;
    }
}
