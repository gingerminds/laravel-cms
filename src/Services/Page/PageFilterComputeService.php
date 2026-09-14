<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCms\State\Page\StatusState;
use Gingerminds\LaravelMultisite\Services\Context\LanguageContext;
use Illuminate\Support\Collection;

/**
 * Computes available filter options for the page collection API endpoint.
 *
 * `status` (a Spatie model-state) and `category_id` expose faceted counts;
 * other filters declared on Page::getFilters() (e.g. published_at) stay
 * plain input filters with no options list.
 *
 * Counts are always computed against a base query that applies every currently
 * active filter EXCEPT the one being computed.
 */
class PageFilterComputeService
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly LanguageContext $languageContext,
    ) {
    }

    /**
     * @return array<string, array{type: string, multiple: bool, options: list<array{value: int|string, label: string, total: int}>}>
     */
    public function computeFilters(): array
    {
        $filterConfig = Page::getFilters();

        return [
            'status' => [
                'type' => $filterConfig['status']['type']         ?? 'select-state',
                'multiple' => $filterConfig['status']['multiple'] ?? true,
                'options' => $this->computeStatusFilter(),
            ],
            'category_id' => [
                'type' => $filterConfig['category_id']['type']         ?? 'select-model',
                'multiple' => $filterConfig['category_id']['multiple'] ?? false,
                'options' => $this->computeCategoryFilter(),
            ],
        ];
    }

    /**
     * Counts pages per status, respecting all other active filters.
     * Selected statuses are always kept even if their count is 0, so the
     * currently applied filter never disappears from the response.
     *
     * @return list<array{value: string, label: string, total: int}>
     */
    public function computeStatusFilter(): array
    {
        $activeFilters = (array) request()->query('filters', []);
        $selectedRaw   = $activeFilters['status'] ?? null;
        $selectedCodes = match (true) {
            is_array($selectedRaw) => array_map('strval', $selectedRaw),
            $selectedRaw !== null => [(string) $selectedRaw],
            default => [],
        };

        $rows = $this->pageRepository->getStatusFacetCounts();

        $result = [];

        foreach (StatusState::getStateMapping() as $stateClass) {
            $code       = $stateClass::code();
            $total      = isset($rows[$stateClass]) ? (int) $rows[$stateClass]->total : 0;
            $isSelected = in_array($code, $selectedCodes, true);

            if ($total === 0 && ! $isSelected) {
                continue;
            }

            $result[] = [
                'value' => $code,
                'label' => __('gingerminds-cms::translation.pages.statuses.' . $code, [], $this->resolveLocale()),
                'total' => $total,
            ];
        }

        return $result;
    }

    /**
     * Counts pages per category, respecting all other active filters. The
     * currently selected category is always kept even at 0, so the applied
     * filter never disappears from the response.
     *
     * @return list<array{value: int, label: string, total: int}>
     */
    public function computeCategoryFilter(): array
    {
        $activeFilters = (array) request()->query('filters', []);
        $selectedRaw   = $activeFilters['category_id'] ?? null;
        $selectedId    = null !== $selectedRaw && '' !== $selectedRaw ? (int) $selectedRaw : null;

        $counts = $this->pageRepository->getCategoryFacetCounts();

        $categoryIds = array_values(array_unique(array_filter(
            [...$counts->keys()->all(), $selectedId],
            static fn (?int $id): bool => null !== $id,
        )));

        if ([] === $categoryIds) {
            return [];
        }

        /** @var Collection<int, PageCategory> $categories */
        $categories = PageCategory::query()->whereIn('id', $categoryIds)->get()->keyBy('id');

        $result = [];

        foreach ($categories as $id => $category) {
            $total      = isset($counts[$id]) ? (int) $counts[$id]->total : 0;
            $isSelected = $id === $selectedId;

            if (0 === $total && ! $isSelected) {
                continue;
            }

            $result[] = [
                'value' => $id,
                'label' => $category->name ?? $category->code,
                'total' => $total,
            ];
        }

        return $result;
    }

    private function resolveLocale(): ?string
    {
        $language = $this->languageContext->current() ?? $this->languageContext->fallback();

        return $language?->iso;
    }
}
