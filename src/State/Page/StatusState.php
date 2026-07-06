<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\State\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\State\Page\Status\Archived;
use Gingerminds\LaravelCms\State\Page\Status\Draft;
use Gingerminds\LaravelCms\State\Page\Status\Published;
use JsonSerializable;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Page>
 */
abstract class StatusState extends State implements JsonSerializable
{
    abstract public static function label(): string;

    public function jsonSerialize(): string
    {
        return static::label();
    }

    public static function config(): StateConfig
    {
        return parent::config()
            ->registerState(Draft::class)
            ->registerState(Published::class)
            ->registerState(Archived::class)
            ->default(Draft::class)
            ->allowTransition(Draft::class, Published::class)
            ->allowTransition(Published::class, Draft::class)
            ->allowTransition(Published::class, Archived::class)
            ->allowTransition(Archived::class, Draft::class)
        ;
    }
}
