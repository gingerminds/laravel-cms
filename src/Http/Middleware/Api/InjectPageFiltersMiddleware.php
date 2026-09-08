<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Middleware\Api;

use Gingerminds\LaravelCms\Services\Page\PageFilterStore;

class InjectPageFiltersMiddleware extends AbstractInjectFiltersMiddleware
{
    public function __construct(PageFilterStore $filterStore)
    {
        parent::__construct($filterStore);
    }
}
