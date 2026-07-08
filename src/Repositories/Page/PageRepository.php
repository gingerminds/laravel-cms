<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCms\State\Page\StatusState;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Repositories\AbstractRepository;
use Gingerminds\LaravelCore\Repositories\Filters\FilterHandlerRegistry;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMediaManager\Services\File\FileUploadService;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * @extends AbstractRepository<Page>
 * @implements RepositoryInterface<Page>
 */
class PageRepository extends AbstractRepository implements RepositoryInterface
{
    private const array FILE_FIELDS = ['main_visual', 'thumbnail'];

    public function __construct(
        protected readonly FileUploadService $uploadService,
    ) {
    }

    public function getModelClass(): string
    {
        return ResourceResolver::model('page');
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

        $resourceModel->fill($request->except('status'));
        $resourceModel->site_id = app(SiteContext::class)->site()?->id;

        foreach (self::FILE_FIELDS as $field) {
            $this->syncPageFile($request, $resourceModel, $field);
        }

        $this->syncStatus($request, $resourceModel);

        $resourceModel->syncTranslations(
            $this->prepareTranslations($request, $resourceModel)
        );

        return $resourceModel;
    }

    /**
     * Status changes go through Spatie's transition mechanism (rather than a
     * plain attribute assignment via fill()) so that allowed-transition
     * validation runs and the `StateChanged` event fires — that event is what
     * `SetPublishedAtOnPublish` listens to in order to stamp `published_at`.
     */
    private function syncStatus(FormRequestInterface $request, Page $page): void
    {
        /** @var class-string<StatusState>|null $requestedStatus */
        $requestedStatus = $request->input('status');

        if (null === $requestedStatus || $requestedStatus === get_class($page->status)) {
            $page->save();

            return;
        }

        $page->status->transitionTo($requestedStatus);
    }

    private function syncPageFile(
        FormRequestInterface $request,
        Page $page,
        string $field
    ): void {
        /** @var BelongsTo<File, Page> $relation */
        $relation = $page->{$this->relationName($field)}();

        $uploaded = $request->file($field);

        if ($uploaded !== null) {
            /** @var File|null $old */
            $old = $relation->getResults();

            $file = $this->uploadService->replace($uploaded, $old, 'pages');
            $relation->associate($file);

            return;
        }

        if ($request->boolean($field . '_remove')) {
            /** @var File|null $old */
            $old = $relation->getResults();

            if ($old !== null) {
                $this->uploadService->delete($old);
                $relation->dissociate();
            }
        }
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function prepareTranslations(
        FormRequestInterface $request,
        Page $page
    ): array {
        /** @var array<int|string, array<string, mixed>> $translations */
        $translations = $request->input('translations', []);

        $existingTranslations = $page->translations->keyBy('language_id');

        foreach ($translations as $languageId => $fields) {
            /** @var PageTranslation|null $translation */
            $translation = $existingTranslations->get($languageId);

            foreach (self::FILE_FIELDS as $field) {
                $fields = $this->syncTranslationFile(
                    $request,
                    $translation,
                    $languageId,
                    $field,
                    $fields
                );
            }

            $translations[$languageId] = $fields;
        }

        return $translations;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function syncTranslationFile(
        FormRequestInterface $request,
        ?PageTranslation $translation,
        int|string $languageId,
        string $field,
        array $fields
    ): array {
        $idKey        = $field . '_id';
        $relationName = $this->relationName($field);

        /** @var File|null $old */
        $old = $translation?->{$relationName};

        $uploaded = $request->file("translations.$languageId.$field");

        unset($fields[$field], $fields[$field . '_remove']);

        if ($uploaded !== null) {
            $file           = $this->uploadService->replace($uploaded, $old, 'pages');
            $fields[$idKey] = $file->id;

            return $fields;
        }

        if ($request->boolean("translations.$languageId.{$field}_remove") && $old !== null) {
            $this->uploadService->delete($old);
            $fields[$idKey] = null;
        }

        return $fields;
    }

    private function relationName(string $field): string
    {
        return $field === 'main_visual' ? 'mainVisual' : 'thumbnail';
    }

    /**
     * Builds a base Page query with all active request filters applied,
     * except those in $excludeKeys (faceted/drill-down search).
     *
     * Delegates to the same FilterHandlerRegistry as AbstractRepository::applyFilters(),
     * so the faceted query stays in sync with whatever a normal request does —
     * including for filter types registered by other packages.
     *
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
