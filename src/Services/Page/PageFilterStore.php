<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Services\Page;

class PageFilterStore
{
    /** @var array<string, array{type: string, options: list<array{value: int|string, label: string, total: int}>}> */
    private array $filters = [];

    /**
     * @param array<string, array{type: string, options: list<array{value: int|string, label: string, total: int}>}> $filters
     */
    public function set(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array<string, array{type: string, options: list<array{value: int|string, label: string, total: int}>}>
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
