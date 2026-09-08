<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Filters\Handlers;

use Gingerminds\LaravelCore\Repositories\Filters\FilterHandlerInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Applies the `SearchIndex` `type` filter, whose values mix two granularities:
 * a bare resource type ("news") or a "{type}:{categoryId}" composite ("news:12")
 * scoping down to one of that type's categories. The front never builds these
 * values itself — it only ever replays what SearchFilterComputeService handed
 * back in `filters.type.options[].value`.
 *
 * Selected values are OR'd together regardless of their granularity, e.g.
 * ["event", "news:12"] matches every event OR a news item in category 12.
 */
class SearchFacetFilterHandler implements FilterHandlerInterface
{
    public function apply(Builder $query, string $property, mixed $value): void
    {
        $values = is_array($value) ? $value : [$value];

        $bareTypes         = [];
        $categoryIdsByType = [];

        foreach ($values as $item) {
            $item = (string) $item;

            if (!str_contains($item, ':')) {
                $bareTypes[] = $item;

                continue;
            }

            [$type, $categoryId] = explode(':', $item, 2);

            if (!is_numeric($categoryId)) {
                continue;
            }

            $categoryIdsByType[$type][] = (int) $categoryId;
        }

        if ([] === $bareTypes && [] === $categoryIdsByType) {
            return;
        }

        $table = $query->getModel()->getTable();

        $query->where(function (Builder $query) use ($bareTypes, $categoryIdsByType, $table) {
            if ([] !== $bareTypes) {
                $query->orWhereIn("$table.type", $bareTypes);
            }

            foreach ($categoryIdsByType as $type => $categoryIds) {
                // A nested `whereIn` subquery, not a `pluck()` fetched into
                // PHP first: a popular category can back thousands of
                // search_index rows — this keeps the id matching inside a
                // single SQL statement instead of round-tripping every id
                // through the application.
                $query->orWhere(function (Builder $query) use ($type, $categoryIds, $table) {
                    $query->where("$table.type", $type)
                        ->whereIn(
                            "$table.id",
                            function (QueryBuilder $subQuery) use ($categoryIds) {
                                $subQuery->select('search_index_id')
                                    ->from('search_index_categories')
                                    ->whereIn('category_id', $categoryIds);
                            }
                        );
                });
            }
        });
    }
}
