<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Repositories\Concerns\SyncsTranslatableResourceTrait;
use Gingerminds\LaravelCms\Repositories\Concerns\UpdatesPublishableCmsResourceTrait;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCms\Services\Page\PageUrlSyncer;
use Gingerminds\LaravelCms\State\Page\Status\Published;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Repositories\AbstractRepository;
use Gingerminds\LaravelCore\Repositories\Filters\FilterHandlerRegistry;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Gingerminds\LaravelMediaManager\Services\File\FileUploadService;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * @extends AbstractRepository<Page>
 * @implements RepositoryInterface<Page>
 */
class PageRepository extends AbstractRepository implements RepositoryInterface
{
    use SyncsTranslatableResourceTrait;
    use UpdatesPublishableCmsResourceTrait;

    private const array FILE_FIELDS = ['main_visual', 'thumbnail'];

    public function __construct(
        protected readonly FileUploadService $uploadService,
        protected readonly PageUrlSyncer $urlSyncer,
    ) {
    }

    public function getModelClass(): string
    {
        return ResourceResolver::model('page');
    }

    public function findPublishedByPath(string $path): ?Page
    {
        $segments = array_values(array_filter(
            explode('/', trim($path, '/')),
            static fn (string $segment): bool => '' !== $segment
        ));

        $normalizedPath = implode('/', $segments);
        $siteId         = app(SiteContext::class)->site()?->id;
        $languageIds    = $this->resolveLanguagePreference();

        /** @var class-string<Page> $modelClass */
        $modelClass = $this->getModelClass();

        foreach ([] !== $languageIds ? $languageIds : [null] as $languageId) {
            /** @var PageUrl|null $pageUrl */
            $pageUrl = PageUrl::query()
                ->where('site_id', $siteId)
                ->where('path', $normalizedPath)
                ->when($languageId, fn (Builder $query) => $query->where('language_id', $languageId))
                ->whereHas('page', fn (Builder $query) => $query->where('status', Published::class))
                ->first();

            if (null !== $pageUrl) {
                // @phpstan-ignore function.alreadyNarrowedType
                $with = is_subclass_of($modelClass, EagerLoadableModelInterface::class)
                    ? $modelClass::getEagerLoads()
                    : [];

                /** @var Page|null $page */
                $page = $modelClass::query()->with($with)->find($pageUrl->page_id);

                return $page;
            }
        }

        return null;
    }

    public function findPublishedByCode(string $code): ?Page
    {
        /** @var class-string<Page> $modelClass */
        $modelClass = $this->getModelClass();

        /** @var Page|null $page */
        $page = $modelClass::query()
            ->where('code', $code)
            ->where('status', Published::class)
            ->first();

        return $page;
    }

    public function update(
        ?FormRequestInterface $request,
        ResourceModelInterface $resourceModel
    ): ResourceModelInterface {
        if (!$resourceModel instanceof Page) {
            throw new InvalidArgumentException(
                'ResourceModelInterface must be an instance of ' . Page::class
            );
        }

        if (!$request instanceof FormRequestInterface) {
            return $resourceModel;
        }

        $this->updatePublishableResource(
            $request,
            $resourceModel,
            self::FILE_FIELDS,
            function () use ($request, $resourceModel) {
                $resourceModel->category_id = $request->filled('category_id')
                    ? max(0, (int) $request->input('category_id'))
                    : null;
                $resourceModel->save();
            },
        );

        $this->urlSyncer->syncPage($resourceModel);

        return $resourceModel;
    }

    protected function uploadFolder(): string
    {
        return 'pages';
    }

    /**
     * @return list<string>
     */
    protected function resourceFileFields(): array
    {
        return self::FILE_FIELDS;
    }

    /**
     * @param  list<string>  $excludeKeys  filter keys to skip (e.g. ['status'])
     * @return Builder<Page>
     */
    public function buildFacetedQuery(array $excludeKeys = []): Builder
    {
        /** @var class-string<Page> $modelClass */
        $modelClass = $this->getModelClass();

        $query = $modelClass::query();

        $filters      = (array) request()->query('filters', []);
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
     * Counts pages per status (raw state class stored in DB), applying all other active filters.
     *
     * @return Collection<string, object{status: string, total: int}>
     */
    public function getStatusFacetCounts(): Collection
    {
        /** @var Collection<string, object{status: string, total: int}> $results */
        $results = $this->buildFacetedQuery(['status'])
            ->toBase()
            ->select('pages.status', DB::raw('COUNT(*) as total'))
            ->groupBy('pages.status')
            ->get()
            ->keyBy('status');

        return $results;
    }

    /**
     * @return Collection<int, object{category_id: int, total: int}>
     */
    public function getCategoryFacetCounts(): Collection
    {
        /** @var Collection<int, object{category_id: int, total: int}> $results */
        $results = $this->buildFacetedQuery(['category_id'])
            ->toBase()
            ->whereNotNull('pages.category_id')
            ->select('pages.category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('pages.category_id')
            ->get()
            ->keyBy('category_id');

        return $results;
    }

    /**
     * @param Builder<Page> $query
     * @param array<mixed> $filters
     */
    protected function applySearch(Builder $query, array $filters): void
    {
        if (!array_key_exists('search', $filters)) {
            return;
        }

        $search = $filters['search'];

        $query->where(function (Builder $query) use ($search) {
            foreach (Page::getSearchableFields() as $field) {
                if (str_contains($field, '.')) {
                    [$relation, $column] = explode('.', $field, 2);

                    $query->orWhereHas($relation, function (Builder $relationQuery) use ($column, $search) {
                        $relationQuery->where($column, 'like', '%' . $search . '%');
                    });

                    continue;
                }

                $query->orWhere($field, 'like', '%' . $search . '%');
            }
        });
    }
}
