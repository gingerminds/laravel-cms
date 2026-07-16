<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCms\State\Page\StatusState;

/**
 * Computes available filter options for the page collection API endpoint.
 *
 * Only `status` (a Spatie model-state) exposes faceted counts today; other
 * filters declared on Page::getFilters() (e.g. published_at) stay plain
 * input filters with no options list.
 *
 * Counts are always computed against a base query that applies every currently
 * active filter EXCEPT the one being computed.
 */
class PageFilterComputeService
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {
    }

    /**
     * @return array<string, array{type: string, multiple: bool, options: list<array{value: string, label: string, total: int}>}>
     */
    public function computeFilters(): array
    {
        $filterConfig = Page::getFilters();

        return [
            'status' => [
                'type'     => $filterConfig['status']['type']     ?? 'select-state',
                'multiple' => $filterConfig['status']['multiple'] ?? true,
                'options'  => $this->computeStatusFilter(),
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
            $selectedRaw !== null  => [(string) $selectedRaw],
            default                => [],
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
                'label' => 'gingerminds-cms::translation.pages.statuses.' . $code,
                'total' => $total,
            ];
        }

        return $result;
    }
}
