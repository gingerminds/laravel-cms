<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\Menu;

use Gingerminds\LaravelCms\Models\Menu\MenuItem\MenuItem;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Repositories\AbstractRepository;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use InvalidArgumentException;

/**
 * @extends AbstractRepository<MenuItem>
 * @implements RepositoryInterface<MenuItem>
 */
class MenuItemRepository extends AbstractRepository implements RepositoryInterface
{
    public function getModelClass(): string
    {
        return ResourceResolver::model('menu_item');
    }

    public function update(
        ?FormRequestInterface $request,
        ResourceModelInterface $resourceModel
    ): ResourceModelInterface {
        if (!$resourceModel instanceof MenuItem) {
            throw new InvalidArgumentException(
                'ResourceModelInterface must be an instance of ' . MenuItem::class
            );
        }

        if (!$request instanceof FormRequestInterface) {
            return $resourceModel;
        }

        $resourceModel->fill($request->except(['translations', 'parent_id']));
        $resourceModel->parent_id = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        $resourceModel->site_id = app(SiteContext::class)->site()?->id;
        $resourceModel->save();

        $resourceModel->syncTranslations($request->input('translations', []));

        return $resourceModel;
    }
}
