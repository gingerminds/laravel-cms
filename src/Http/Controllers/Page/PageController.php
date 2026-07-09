<?php

namespace Gingerminds\LaravelCms\Http\Controllers\Page;

use Gingerminds\LaravelCms\Http\Request\Page\PageRequest;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCms\Repositories\PageCategory\PageCategoryRepository;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Http\Controllers\AbstractController;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends AbstractController
{
    public const string LABEL_S = 'gingerminds-cms::translation.pages.name_s';

    public function __construct(
        protected readonly PageRepository $repository,
        protected readonly PageCategoryRepository $categoryRepository,
    ) {
    }

    public function index(Request $request): Factory|View
    {
        $this->authorize('viewAny', ResourceResolver::model('page'));

        $items = $this->repository->get($request);

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.pages.index';

        return view($view, [
            'resource'     => ResourceResolver::model('page'),
            'items'        => $items,
            'categoryTree' => $this->categoryRepository->getRootItems(),
        ]);
    }

    public function create(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $categories = $this->categoryRepository->getAllForSelect();

        /** @var PageCategory|null $category */
        $category = $categoryId
            ? collect($categories)->firstWhere('category.id', (int) $categoryId)['category'] ?? null
            : null;

        $page     = new Page();
        $statuses = $page->status->transitionableStates();
        $site     = app(SiteContext::class)->site();

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.pages.create';

        return view($view, [
            'statuses'        => $statuses,
            'category'        => $category,
            'categories'      => $categories,
            'defaultLanguage' => $site?->defaultLanguage()->first(),
            'languages'       => $site?->languages,
        ]);
    }

    public function edit(Page $page): View
    {
        $site = app(SiteContext::class)->site();

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.pages.edit';

        return view($view, [
            'page'            => $page,
            'category'        => $page->category,
            'categories'      => $this->categoryRepository->getAllForSelect(),
            'statuses'        => $page->status->transitionableStates(),
            'defaultLanguage' => $site?->defaultLanguage()->first(),
            'languages'       => $site?->languages,
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $this->authorize('create', ResourceResolver::model('page'));

        /** @var Page $page */
        $page = $this->repository->update($request, new Page());

        /** @var PageTranslation|null $translation */
        $translation = $page->currentTranslation;

        return redirect()->route('gingerminds-cms.pages.index')
            ->with('success', __('gingerminds-core::translation.successfully_created', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($translation->title ?? $page->id),
            ]));
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $this->repository->update($request, $page);

        /** @var PageTranslation|null $translation */
        $translation = $page->currentTranslation;

        return redirect()->route('gingerminds-cms.pages.edit', $page->id)
            ->with('success', __('gingerminds-core::translation.successfully_updated', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($translation->title ?? $page->id),
            ]));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);
        $page->delete();

        /** @var PageTranslation|null $translation */
        $translation = $page->currentTranslation;

        return redirect()->route('gingerminds-cms.pages.index')
            ->with('success', __('gingerminds-core::translation.successfully_deleted', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($translation->title ?? $page->id),
            ]));
    }
}
