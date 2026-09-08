<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Trait;

use Gingerminds\LaravelCms\Models\Contract\ExcludableFromSearchInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait ExcludableFromSearchTrait
{
    public function isExcludedFromSearch(): bool
    {
        if ((bool) $this->getAttribute('is_hidden_from_search')) {
            return true;
        }

        $relationName = $this->searchExclusionParentRelation();

        if (null === $relationName || !method_exists($this, $relationName)) {
            return false;
        }

        return $this->isSearchExclusionParentExcluded(
            $this->resolveSearchExclusionParent($relationName)
        );
    }

    protected function searchExclusionParentRelation(): ?string
    {
        return null;
    }

    private function resolveSearchExclusionParent(string $relationName): mixed
    {
        return $this->relationLoaded($relationName)
            ? $this->getRelation($relationName)
            : $this->{$relationName}()->getResults();
    }

    private function isSearchExclusionParentExcluded(mixed $related): bool
    {
        if ($related instanceof EloquentCollection) {
            return $related->isNotEmpty() && $related->every(
                fn ($item) => $item instanceof ExcludableFromSearchInterface && $item->isExcludedFromSearch()
            );
        }

        return $related instanceof ExcludableFromSearchInterface && $related->isExcludedFromSearch();
    }
}
