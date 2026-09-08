<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Observers\Search;

use Gingerminds\LaravelCms\Jobs\Search\ReindexCategoryItemsJob;
use Illuminate\Database\Eloquent\Model;

class SearchExclusionCascadeObserver
{
    public function saved(Model $category): void
    {
        if (!$category->wasChanged('is_hidden_from_search')) {
            return;
        }

        foreach ((array) config('gingerminds-cms.search_resources', []) as $resourceKey => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $itemModelClass       = $definition['model']             ?? null;
            $categoryRelationName = $definition['category_relation'] ?? null;

            if (
                !is_string($itemModelClass) || !class_exists($itemModelClass)
                                            || !is_string($categoryRelationName)
            ) {
                continue;
            }

            /** @var Model $itemModel */
            $itemModel = new $itemModelClass();

            if (
                !method_exists($itemModel, $categoryRelationName)
                || $itemModel->{$categoryRelationName}()->getRelated()::class !== $category::class
            ) {
                continue;
            }

            ReindexCategoryItemsJob::dispatch(
                (string) $resourceKey,
                $itemModelClass,
                $categoryRelationName,
                $category->getKey()
            );
        }
    }
}
