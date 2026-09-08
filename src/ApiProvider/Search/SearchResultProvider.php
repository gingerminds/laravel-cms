<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\ApiProvider\Search;

use ApiPlatform\Laravel\Eloquent\Paginator as ApiPlatformPaginator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\Models\Search\SearchIndex;
use Gingerminds\LaravelCms\Models\Search\SearchResultItem;
use Gingerminds\LaravelCms\Repositories\Search\SearchRepository;
use Gingerminds\LaravelCms\Services\Search\SearchFilterComputeService;
use Gingerminds\LaravelCms\Services\Search\SearchFilterStore;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @implements ProviderInterface<object>
 */
class SearchResultProvider implements ProviderInterface
{
    public function __construct(
        private readonly SearchRepository $repository,
        private readonly SearchFilterStore $filterStore,
        private readonly SearchFilterComputeService $filterComputeService,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = request();

        $normalizer = app(NormalizerInterface::class);

        /** @var LengthAwarePaginator<int, SearchIndex> $hits */
        $hits = $this->repository->get($request);

        /** @var Collection<int, SearchIndex> $hitsCollection */
        $hitsCollection = collect($hits->items());
        $resourcesByHit = $this->fetchResourcesByHit($hitsCollection);

        $items = $hitsCollection->map(
            fn (SearchIndex $hit): SearchResultItem => $this->buildItem($hit, $resourcesByHit, $normalizer)
        )->values()->all();

        $this->filterStore->set($this->filterComputeService->computeFilters());

        return $this->newApiPlatformPaginator(
            $items,
            $hits->total(),
            $hits->perPage(),
            $hits->currentPage(),
            $request->url(),
            $request->query(),
        );
    }

    /**
     * @param array<int, object> $items
     * @param array<string, mixed> $query
     */
    private function newApiPlatformPaginator(
        array $items,
        int $total,
        int $perPage,
        int $currentPage,
        string $path,
        array $query,
    ): ApiPlatformPaginator {
        return new ApiPlatformPaginator(
            new LengthAwarePaginator($items, $total, $perPage, $currentPage, ['path' => $path, 'query' => $query])
        );
    }

    /**
     * @param Collection<string, Model> $resourcesByHit
     */
    private function buildItem(
        SearchIndex $hit,
        Collection $resourcesByHit,
        NormalizerInterface $normalizer,
    ): SearchResultItem {
        $definition = config("gingerminds-cms.search_resources.{$hit->type}");
        $group      = is_array($definition) ? ($definition['group'] ?? null) : null;
        $model      = $resourcesByHit->get($hit->type . ':' . $hit->record_id);

        $resource = null;

        if (null !== $model && is_string($group)) {
            $normalized = $normalizer->normalize($model, 'jsonld', ['groups' => [$group]]);
            $resource   = is_array($normalized) ? $normalized : null;
        }

        return new SearchResultItem(
            id: $hit->id,
            type: $hit->type,
            title: $hit->title,
            url: $hit->url,
            excerpt: $hit->excerpt,
            publishedAt: $hit->published_at,
            resource: $resource,
        );
    }

    /**
     * @param Collection<int, SearchIndex> $hits
     * @return Collection<string, Model> keyed by "{type}:{record_id}"
     */
    private function fetchResourcesByHit(Collection $hits): Collection
    {
        /** @var Collection<string, Model> $resources */
        $resources = collect();

        foreach ($hits->groupBy('type') as $type => $typeHits) {
            $definition = config("gingerminds-cms.search_resources.{$type}");
            $modelClass = is_array($definition) ? ($definition['model'] ?? null) : null;

            if (!is_string($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            $with = is_subclass_of($modelClass, EagerLoadableModelInterface::class)
                ? $modelClass::getEagerLoads()
                : [];

            /** @var Collection<int, SearchIndex> $typeHits */
            $ids = $typeHits->pluck('record_id')->all();

            foreach ($modelClass::query()->with($with)->whereIn('id', $ids)->get() as $model) {
                $resources->put($type . ':' . $model->getKey(), $model);
            }
        }

        return $resources;
    }
}
