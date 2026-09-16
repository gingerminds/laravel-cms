<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\ApiProvider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use Gingerminds\LaravelCore\ApiProvider\AbstractApiProvider;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Gingerminds\LaravelCore\Services\Filters\FilterStoreInterface;

abstract class AbstractPageLikeProvider extends AbstractApiProvider
{
    /**
     * @param RepositoryInterface<*> $repository
     */
    public function __construct(
        RepositoryInterface $repository,
        private readonly FilterStoreInterface $filterStore,
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
        if (isset($uriVariables['slug'])) {
            return $this->provideByPath((string) $uriVariables['slug']);
        }

        $result = parent::provide($operation, $uriVariables, $context);

        if ($operation instanceof CollectionOperationInterface) {
            $this->filterStore->set($this->computeFilters());
        }

        return $result;
    }

    abstract protected function provideByPath(string $path): object;

    /** @return array<string, mixed> */
    abstract protected function computeFilters(): array;
}
