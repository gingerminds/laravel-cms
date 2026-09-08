<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Search;

use Gingerminds\LaravelCms\Models\Search\SearchIndex;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Repositories\AbstractRepository;
use Gingerminds\LaravelCore\Repositories\Filters\FilterHandlerRegistry;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * @extends AbstractRepository<SearchIndex>
 * @implements RepositoryInterface<SearchIndex>
 */
class SearchRepository extends AbstractRepository implements RepositoryInterface
{
    public function getModelClass(): string
    {
        return ResourceResolver::model('search');
    }

    public function update(
        ?FormRequestInterface $request,
        ResourceModelInterface $resourceModel
    ): ResourceModelInterface {
        throw new LogicException(SearchIndex::class . ' is read-only and cannot be updated through a repository.');
    }

    /**
     * @param  list<string>  $excludeKeys  filter keys to skip (e.g. ['type'])
     * @return Builder<SearchIndex>
     */
    public function buildFacetedQuery(array $excludeKeys = []): Builder
    {
        /** @var class-string<SearchIndex> $modelClass */
        $modelClass = $this->getModelClass();

        $query = $modelClass::query();

        $filters = (array) request()->query('filters', []);

        $this->applySearch($query, $filters);

        $filterConfig = $modelClass::getFilters();
        $registry     = app(FilterHandlerRegistry::class);

        foreach ($filters as $key => $value) {
            if (in_array($key, $excludeKeys, true) || ! array_key_exists($key, $filterConfig)) {
                continue;
            }

            $registry->get($filterConfig[$key]['type'])?->apply($query, $key, $value);
        }

        return $query;
    }

    /**
     * Counts search hits per type, applying every other active filter (and the
     * free-text search term) so the counts reflect the current result set.
     *
     * @return Collection<string, object{type: string, total: int}>
     */
    public function getTypeFacetCounts(): Collection
    {
        /** @var Collection<string, object{type: string, total: int}> $results */
        $results = $this->buildFacetedQuery(['type'])
            ->toBase()
            ->select('search_index.type', DB::raw('COUNT(*) as total'))
            ->groupBy('search_index.type')
            ->get()
            ->keyBy('type');

        return $results;
    }

    /**
     * Counts search hits per (type, category), applying every other active
     * filter (and the free-text search term). Unlike getTypeFacetCounts(),
     * this is an inner join on search_index_categories, so a (type, category)
     * pair with zero matching hits simply never appears in the result —
     * SearchFilterComputeService is what adds a currently-selected category
     * back in at total 0 when that happens.
     *
     * @return Collection<int, object{type: string, category_id: int, total: int}>
     */
    public function getCategoryFacetCounts(): Collection
    {
        /** @var Collection<int, object{type: string, category_id: int, total: int}> $results */
        $results = $this->buildFacetedQuery(['type'])
            ->toBase()
            ->join('search_index_categories', 'search_index_categories.search_index_id', '=', 'search_index.id')
            ->select(
                'search_index.type',
                'search_index_categories.category_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('search_index.type', 'search_index_categories.category_id')
            ->get();

        return $results;
    }
}
