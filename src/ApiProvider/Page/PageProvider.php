<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\ApiProvider\Page;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCms\Services\Page\PageFilterComputeService;
use Gingerminds\LaravelCms\Services\Page\PageFilterStore;
use Gingerminds\LaravelCore\ApiProvider\AbstractApiProvider;
use Gingerminds\LaravelCore\ApiProvider\ApiProviderInterface;

/**
 * @implements ProviderInterface<Page>
 */
class PageProvider extends AbstractApiProvider implements ProviderInterface, ApiProviderInterface
{
    public function __construct(
        PageRepository $repository,
        private readonly PageFilterStore $filterStore,
        private readonly PageFilterComputeService $filterComputeService,
    ) {
        parent::__construct($repository);
    }

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return object|array<mixed>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $result = parent::provide($operation, $uriVariables, $context);

        if ($operation instanceof CollectionOperationInterface) {
            $this->filterStore->set($this->filterComputeService->computeFilters());
        }

        return $result;
    }
}
