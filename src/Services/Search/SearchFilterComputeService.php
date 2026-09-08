<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Search;

use Gingerminds\LaravelCms\Models\Search\SearchIndex;
use Gingerminds\LaravelCms\Repositories\Search\SearchRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Computes available filter options for the /search-results collection endpoint.
 *
 * A single `type` filter mixes two granularities in one flat, selectable list:
 * a bare resource type ("news") and a "{type}:{categoryId}" composite scoping
 * down to one of that type's categories ("news:12") — see the maquette this
 * implements: type and category live in the same control, not two separate
 * filters. A type with no `category_relation` (e.g. Event) simply never gets
 * composite entries under it.
 *
 * Counts are always computed against a base query that applies every currently
 * active filter EXCEPT the one being computed (plus the free-text search term),
 * same convention as PageFilterComputeService.
 */
class SearchFilterComputeService
{
    public function __construct(
        private readonly SearchRepository $repository,
    ) {
    }

    /**
     * @return array<string, array{type: string, multiple: bool, options: list<array{value: string, label: string, total: int, group: string|null}>}>
     */
    public function computeFilters(): array
    {
        $filterConfig = SearchIndex::getFilters();

        return [
            'type' => [
                'type' => $filterConfig['type']['type']         ?? 'facet',
                'multiple' => $filterConfig['type']['multiple'] ?? true,
                'options' => $this->computeTypeFilter(),
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string, total: int, group: string|null}>
     */
    public function computeTypeFilter(): array
    {
        [$selectedTypes, $selectedCategoryIdsByType] = $this->parseSelected();

        $typeTotals     = $this->repository->getTypeFacetCounts();
        $categoryTotals = $this->groupCategoryTotals($this->repository->getCategoryFacetCounts());

        // A currently-selected category can be missing from $categoryTotals
        // entirely (inner join → 0 matching hits): force it in at total 0 so
        // the applied filter never disappears from its own response.
        foreach ($selectedCategoryIdsByType as $type => $categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $categoryTotals[$type][$categoryId] ??= 0;
            }
        }

        /** @var array<string, array<string, mixed>> $searchResources */
        $searchResources = (array) config('gingerminds-cms.search_resources', []);

        $result = [];

        foreach ($searchResources as $type => $definition) {
            $total      = isset($typeTotals[$type]) ? (int) $typeTotals[$type]->total : 0;
            $isSelected = in_array($type, $selectedTypes, true);

            if ($total > 0 || $isSelected) {
                $result[] = [
                    'value' => $type,
                    'label' => (string) ($definition['label'] ?? $type),
                    'total' => $total,
                    'group' => null,
                ];
            }

            $result = [...$result, ...$this->computeCategoryOptions(
                $type,
                $definition,
                $categoryTotals[$type]            ?? [],
                $selectedCategoryIdsByType[$type] ?? []
            )];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<int, int> $categoryTotalsForType category_id => total
     * @param list<int> $selectedCategoryIdsForType
     * @return list<array{value: string, label: string, total: int, group: string|null}>
     */
    private function computeCategoryOptions(
        string $type,
        array $definition,
        array $categoryTotalsForType,
        array $selectedCategoryIdsForType,
    ): array {
        if ([] === $categoryTotalsForType) {
            return [];
        }

        $labels = $this->resolveCategoryLabels($definition, array_keys($categoryTotalsForType));

        $options = [];

        foreach ($categoryTotalsForType as $categoryId => $total) {
            $isSelected = in_array($categoryId, $selectedCategoryIdsForType, true);

            if (0 === $total && !$isSelected) {
                continue;
            }

            $options[] = [
                'value' => $type . ':' . $categoryId,
                'label' => $labels[$categoryId] ?? (string) $categoryId,
                'total' => $total,
                'group' => $type,
            ];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $definition
     * @param list<int> $categoryIds
     * @return array<int, string> category_id => translated label
     */
    private function resolveCategoryLabels(array $definition, array $categoryIds): array
    {
        $categoryModel = $definition['category_model'] ?? null;

        if (!is_string($categoryModel) || !is_subclass_of($categoryModel, Model::class)) {
            return [];
        }

        /** @var class-string<Model> $categoryModel */
        return $categoryModel::query()
            ->whereIn('id', $categoryIds)
            ->get()
            ->mapWithKeys(static fn (Model $category): array => [
                (int) $category->getKey() => (string) ($category->currentTranslation->name ?? $category->getKey()),
            ])
            ->all();
    }

    /**
     * Splits the active `type` filter selection into bare type strings and
     * "{type}:{categoryId}" composites, grouped by type.
     *
     * @return array{0: list<string>, 1: array<string, list<int>>}
     */
    private function parseSelected(): array
    {
        $activeFilters = (array) request()->query('filters', []);
        $selectedRaw   = $activeFilters['type'] ?? null;
        $selected      = match (true) {
            is_array($selectedRaw) => array_map('strval', $selectedRaw),
            $selectedRaw !== null => [(string) $selectedRaw],
            default => [],
        };

        $bareTypes         = [];
        $categoryIdsByType = [];

        foreach ($selected as $item) {
            if (!str_contains($item, ':')) {
                $bareTypes[] = $item;

                continue;
            }

            [$type, $categoryId] = explode(':', $item, 2);

            if (is_numeric($categoryId)) {
                $categoryIdsByType[$type][] = (int) $categoryId;
            }
        }

        return [$bareTypes, $categoryIdsByType];
    }

    /**
     * @param Collection<int, object{type: string, category_id: int, total: int}> $rows
     * @return array<string, array<int, int>> type => (category_id => total)
     */
    private function groupCategoryTotals(Collection $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row->type][(int) $row->category_id] = (int) $row->total;
        }

        return $grouped;
    }
}
