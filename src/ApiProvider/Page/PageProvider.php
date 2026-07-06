<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\ApiProvider\Page;

use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCore\ApiProvider\AbstractApiProvider;
use Gingerminds\LaravelCore\ApiProvider\ApiProviderInterface;

/**
 * @implements ProviderInterface<Page>
 */
class PageProvider extends AbstractApiProvider implements ProviderInterface, ApiProviderInterface
{
    public function __construct(PageRepository $repository)
    {
        parent::__construct($repository);
    }
}
