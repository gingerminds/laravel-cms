<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Observers\Search;

use Gingerminds\LaravelCms\Services\Search\SearchIndexer;
use Illuminate\Database\Eloquent\Model;

class SearchIndexObserver
{
    public function __construct(private readonly SearchIndexer $indexer)
    {
    }

    public function saved(Model $model): void
    {
        $resourceKey = $this->indexer->resolveResourceKey($model);

        if (null !== $resourceKey) {
            $this->indexer->indexModel($resourceKey, $model);
        }
    }

    public function deleted(Model $model): void
    {
        $resourceKey = $this->indexer->resolveResourceKey($model);

        if (null !== $resourceKey) {
            $this->indexer->forget($resourceKey, $model);
        }
    }
}
