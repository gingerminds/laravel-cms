<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Middleware\Api;

use Gingerminds\LaravelCms\Services\Search\SearchFilterStore;

class InjectSearchFiltersMiddleware extends AbstractInjectFiltersMiddleware
{
    public function __construct(SearchFilterStore $filterStore)
    {
        parent::__construct($filterStore);
    }
}
