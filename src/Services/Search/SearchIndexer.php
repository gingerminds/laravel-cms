<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Search;

use Gingerminds\LaravelCms\Models\Contract\ExcludableFromSearchInterface;
use Gingerminds\LaravelCms\Models\Contract\PubliclySearchableInterface;
use Gingerminds\LaravelCms\Models\Search\SearchIndex;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchIndexer
{
    public function indexModel(string $resourceKey, Model $model): void
    {
        if (!$model instanceof PubliclySearchableInterface) {
            return;
        }

        if (!$this->isVisible($model)) {
            $this->forget($resourceKey, $model);

            return;
        }

        $indexedLanguageIds = [];
        $searchIndexIds     = [];

        foreach ($model->getPublicSearchTranslations() as $translation) {
            $indexedLanguageIds[] = $translation['language_id'];

            /** @var SearchIndex $searchIndex */
            $searchIndex = SearchIndex::withoutGlobalScopes()->updateOrCreate(
                [
                    'type' => $resourceKey,
                    'record_id' => $model->getKey(),
                    'language_id' => $translation['language_id'],
                ],
                [
                    'site_id' => $model->getAttribute('site_id'),
                    'title' => $translation['title'],
                    'url' => $translation['url'],
                    'excerpt' => $translation['excerpt'],
                    'searchable_text' => $translation['searchable_text'],
                    'published_at' => $model->getAttribute('published_at'),
                ]
            );

            $searchIndexIds[] = $searchIndex->id;
        }

        SearchIndex::withoutGlobalScopes()
            ->where('type', $resourceKey)
            ->where('record_id', $model->getKey())
            ->whereNotIn('language_id', $indexedLanguageIds)
            ->delete();

        $this->syncCategories($resourceKey, $model, $searchIndexIds);
    }

    /**
     * @param list<int> $searchIndexIds one per indexed translation — every
     *   language row for a record shares the same categories
     */
    private function syncCategories(string $resourceKey, Model $model, array $searchIndexIds): void
    {
        $categoryRelation = config("gingerminds-cms.search_resources.{$resourceKey}.category_relation");

        if (!is_string($categoryRelation) || !method_exists($model, $categoryRelation)) {
            return;
        }

        $categoryIds = $this->resolveCategoryIds($model, $categoryRelation);

        foreach ($searchIndexIds as $searchIndexId) {
            DB::table('search_index_categories')->where('search_index_id', $searchIndexId)->delete();

            if ([] !== $categoryIds) {
                DB::table('search_index_categories')->insert(array_map(
                    static fn (int $categoryId): array => [
                        'search_index_id' => $searchIndexId,
                        'category_id' => $categoryId,
                    ],
                    $categoryIds
                ));
            }
        }
    }

    /**
     * @return list<int>
     */
    private function resolveCategoryIds(Model $model, string $categoryRelation): array
    {
        $related = $model->{$categoryRelation};

        if ($related instanceof Collection) {
            return array_values($related->pluck('id')->map(static fn ($id): int => (int) $id)->unique()->all());
        }

        if ($related instanceof Model) {
            return [(int) $related->getKey()];
        }

        return [];
    }

    public function forget(string $resourceKey, Model $model): void
    {
        SearchIndex::withoutGlobalScopes()
            ->where('type', $resourceKey)
            ->where('record_id', $model->getKey())
            ->delete();
    }

    public function reindexType(string $resourceKey): void
    {
        $definition = config("gingerminds-cms.search_resources.{$resourceKey}");
        $modelClass = is_array($definition) ? ($definition['model'] ?? null) : null;

        if (!is_string($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return;
        }

        $with = is_subclass_of($modelClass, EagerLoadableModelInterface::class)
            ? $modelClass::getEagerLoads()
            : [];

        $categoryRelation = $definition['category_relation'] ?? null;

        if (is_string($categoryRelation)) {
            $with[] = $categoryRelation;
        }

        $modelClass::query()->with(array_values(array_unique($with)))->chunkById(
            100,
            function ($models) use ($resourceKey) {
                foreach ($models as $model) {
                    $this->indexModel($resourceKey, $model);
                }
            }
        );
    }

    public function resolveResourceKey(Model $model): ?string
    {
        foreach ((array) config('gingerminds-cms.search_resources', []) as $key => $definition) {
            $modelClass = is_array($definition) ? ($definition['model'] ?? null) : null;

            if (is_string($modelClass) && $model instanceof $modelClass) {
                return (string) $key;
            }
        }

        return null;
    }

    private function isVisible(Model $model): bool
    {
        if (!$model instanceof PubliclySearchableInterface || !$model->isPubliclyVisible()) {
            return false;
        }
        return !$model instanceof ExcludableFromSearchInterface || !$model->isExcludedFromSearch();
    }
}
