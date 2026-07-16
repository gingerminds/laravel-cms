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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        if (isset($uriVariables['slug'])) {
            return $this->provideByPath((string) $uriVariables['slug']);
        }

        if (isset($uriVariables['code'])) {
            return $this->provideByCode((string) $uriVariables['code']);
        }

        $result = parent::provide($operation, $uriVariables, $context);

        if ($operation instanceof CollectionOperationInterface) {
            $this->filterStore->set($this->filterComputeService->computeFilters());
        }

        return $result;
    }

    private function provideByPath(string $path): Page
    {
        /** @var PageRepository $repository */
        $repository = $this->repository;
        $page       = $repository->findPublishedByPath($path);

        if (!$page instanceof Page) {
            throw new NotFoundHttpException();
        }

        return $page;
    }

    private function provideByCode(string $code): Page
    {
        /** @var PageRepository $repository */
        $repository = $this->repository;
        $page       = $repository->findPublishedByCode($code);

        if (!$page instanceof Page) {
            throw new NotFoundHttpException();
        }

        return $page;
    }
}
