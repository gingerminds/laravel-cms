<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageUrl;
use Gingerminds\LaravelCms\Repositories\Concerns\SyncsTranslatableResourceTrait;
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
                // Not `$pageUrl->page`: that relation is declared on
                // `PageUrl` itself as `belongsTo(Page::class)`, hardcoded to
                // this package's own `Page` class — it can't know about a
                // project's resolved override (`ResourceResolver::model('page')`,
                // same mechanism `getModelClass()` already uses everywhere
                // else in this repository). Re-fetching explicitly through
                // the resolved class is what actually respects a project's
                // `App\Models\Page\Page`-style override (e.g. a customized
                // `getContentAttribute()`).
                // Bypasses AbstractRepository::get() (single lookup by
                // resolved URL, not a paginated listing), so getEagerLoads()
                // is merged in by hand here — otherwise every relation it
                // declares (mainVisual, thumbnail, category.parentChain)
                // lazy-loads instead.
                // The `@var class-string<Page>` above is a local assertion for
                // this method's own type-checking, not a runtime guarantee:
                // `$modelClass` actually comes from `ResourceResolver::model()`,
                // which just reads a config string — a project could point
                // `gingerminds-cms.resources.page.model` at a class that
                // doesn't extend this package's `Page` (and so doesn't pick up
                // `EagerLoadableModelInterface` from it). Real `Page` subtypes
                // will always pass this check, hence PHPStan flags it as
                // always-true, but it's what keeps a misconfigured override
                // from fataling here instead of just skipping eager loads.
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

        $resourceModel->fill($request->except(['status', 'category_id']));
        $resourceModel->site_id = app(SiteContext::class)->site()?->id;
        // `max(0, ...)`, not a plain `(int)` cast: `Page::$category_id` is
        // typed `int<0, max>|null` (an unsigned FK column), but a bare
        // `(int)` cast is a full-range `int` as far as PHPStan is
        // concerned — `max()` with a literal `0` lower bound is enough for
        // it to narrow the result back down to a non-negative int.
        $resourceModel->category_id = $request->filled('category_id')
            ? max(0, (int) $request->input('category_id'))
            : null;

        foreach (self::FILE_FIELDS as $field) {
            $this->syncResourceFile($request, $resourceModel, $field);
        }

        $this->syncStatus($request, $resourceModel);

        $resourceModel->syncTranslations(
            $this->prepareTranslations($request, $resourceModel)
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
