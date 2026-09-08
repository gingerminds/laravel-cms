<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Search;

class SearchFilterStore
{
    /** @var array<string, array{type: string, multiple: bool, options: list<array{value: string, label: string, total: int, group: string|null}>}> */
    private array $filters = [];

    /**
     * @param array<string, array{type: string, multiple: bool, options: list<array{value: string, label: string, total: int, group: string|null}>}> $filters
     */
    public function set(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array<string, array{type: string, multiple: bool, options: list<array{value: string, label: string, total: int, group: string|null}>}>
     */
    public function get(): array
    {
        return $this->filters;
    }

    public function isEmpty(): bool
    {
        return $this->filters === [];
    }
}
