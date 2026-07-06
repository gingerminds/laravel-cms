<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Page;

use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Repositories\AbstractRepository;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use InvalidArgumentException;

/**
 * @extends AbstractRepository<Page>
 * @implements RepositoryInterface<Page>
 */
class PageRepository extends AbstractRepository implements RepositoryInterface
{
    public function getModelClass(): string
    {
        return ResourceResolver::model('page');
    }

    public function update(
        ?FormRequestInterface $request,
        ResourceModelInterface $resourceModel
    ): ResourceModelInterface {
        if (!$resourceModel instanceof Page) {
            throw new InvalidArgumentException(
                'ResourceModelInterface must be an instance of ' . Page::class
            );
        }

        if (!$request instanceof FormRequestInterface) {
            return $resourceModel;
        }

        $resourceModel->fill($request->all());
        $resourceModel->site_id = app(SiteContext::class)->site()?->id;
        $resourceModel->save();

        $resourceModel->syncTranslations($request->input('translations', []));

        return $resourceModel;
    }
}
