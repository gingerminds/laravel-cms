<?php

namespace Gingerminds\LaravelCms\Http\Controllers\PageCategory;

use Gingerminds\LaravelCms\Http\Request\PageCategory\PageCategoryRequest;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Repositories\PageCategory\PageCategoryRepository;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCms\Services\Page\PageUrlSyncer;
use Gingerminds\LaravelCore\Http\Controllers\AbstractController;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageCategoryController extends AbstractController
{
    public const string LABEL_S = 'gingerminds-cms::translation.page_categories.name_s';

    public function __construct(
        protected readonly PageCategoryRepository $repository,
        protected readonly PageUrlSyncer $urlSyncer,
    ) {
    }

    public function index(): Factory|View
    {
        $this->authorize('viewAny', ResourceResolver::model('page_category'));

        $rootItems = $this->repository->getRootItems();

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.page_categories.index';

        return view($view, [
            'resource' => ResourceResolver::model('page_category'),
            'rootItems' => $rootItems,
        ]);
    }

    public function create(Request $request): View
    {
        $site = app(SiteContext::class)->site();

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.page_categories.create';

        return view($view, [
            'categories' => $this->repository->getAllForSelect(),
            'defaultLanguage' => $site?->defaultLanguage()->first(),
            'languages' => $site?->languages,
            'parentId' => $request->query('parent_id'),
        ]);
    }

    public function edit(PageCategory $pageCategory): View
    {
        $site = app(SiteContext::class)->site();

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.page_categories.edit';

        return view($view, [
            'pageCategory' => $pageCategory,
            'categories' => $this->repository->getAllForSelect(),
            'defaultLanguage' => $site?->defaultLanguage()->first(),
            'languages' => $site?->languages,
        ]);
    }

    public function store(PageCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ResourceResolver::model('page_category'));

        /** @var PageCategory $pageCategory */
        $pageCategory = $this->repository->update($request, new PageCategory());

        return redirect()->route('gingerminds-cms.page-categories.index')
            ->with('success', __('gingerminds-core::translation.successfully_created', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($pageCategory->name ?? $pageCategory->id),
            ]));
    }

    public function update(PageCategoryRequest $request, PageCategory $pageCategory): RedirectResponse
    {
        $this->authorize('update', $pageCategory);

        $this->repository->update($request, $pageCategory);

        return redirect()->route('gingerminds-cms.page-categories.edit', $pageCategory)
            ->with('success', __('gingerminds-core::translation.successfully_updated', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($pageCategory->name ?? $pageCategory->id),
            ]));
    }

    public function destroy(PageCategory $pageCategory): RedirectResponse
    {
        $this->authorize('delete', $pageCategory);

        $affectedPageIds = $this->urlSyncer->collectAffectedPageIds($pageCategory);

        $pageCategory->delete();

        $this->urlSyncer->syncPagesByIds($affectedPageIds);

        return redirect()->route('gingerminds-cms.page-categories.index')
            ->with('success', __('gingerminds-core::translation.successfully_deleted', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($pageCategory->name ?? $pageCategory->id),
            ]));
    }
}
