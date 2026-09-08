<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Jobs\Search;

use Gingerminds\LaravelCms\Services\Search\SearchIndexer;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReindexCategoryItemsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $resourceKey,
        private readonly string $itemModelClass,
        private readonly string $categoryRelationName,
        private readonly int|string $categoryId,
    ) {
    }

    public function handle(SearchIndexer $indexer): void
    {
        $itemsQuery = $this->buildItemsQuery();

        $itemsQuery?->chunkById(100, function ($items) use ($indexer) {
            foreach ($items as $item) {
                $indexer->indexModel($this->resourceKey, $item);
            }
        });
    }

    /**
     * @return Builder<*>|null
     */
    private function buildItemsQuery(): ?Builder
    {
        $modelClass = $this->itemModelClass;
        /** @var Model $probe */
        $probe    = new $modelClass();
        $relation = $probe->{$this->categoryRelationName}();

        $with = is_subclass_of($modelClass, EagerLoadableModelInterface::class)
            ? $modelClass::getEagerLoads()
            : [];
        $with[] = $this->categoryRelationName;
        $with   = array_values(array_unique($with));

        if ($relation instanceof BelongsTo) {
            return $modelClass::query()->with($with)->where($relation->getForeignKeyName(), $this->categoryId);
        }

        if ($relation instanceof BelongsToMany) {
            $categoryId = $this->categoryId;

            return $modelClass::query()->with($with)->whereHas(
                $this->categoryRelationName,
                fn (Builder $q) => $q->whereKey($categoryId)
            );
        }

        return null;
    }
}
