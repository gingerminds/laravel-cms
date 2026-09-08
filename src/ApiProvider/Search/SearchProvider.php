<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\ApiProvider\Search;

use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\Models\Search\SearchIndex;
use Gingerminds\LaravelCms\Repositories\Search\SearchRepository;
use Gingerminds\LaravelCore\ApiProvider\AbstractApiProvider;
use Gingerminds\LaravelCore\ApiProvider\ApiProviderInterface;

/**
 * @implements ProviderInterface<SearchIndex>
 */
class SearchProvider extends AbstractApiProvider implements ProviderInterface, ApiProviderInterface
{
    public function __construct(SearchRepository $repository)
    {
        parent::__construct($repository);
    }
}
