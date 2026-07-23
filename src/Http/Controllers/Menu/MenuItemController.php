<?php

namespace Gingerminds\LaravelCms\Http\Controllers\Menu;

use Gingerminds\LaravelCms\Http\Request\Menu\MenuItemReorderRequest;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuItemRequest;
use Gingerminds\LaravelCms\Models\Menu\Menu;
use Gingerminds\LaravelCms\Models\Menu\MenuItem\MenuItem;
use Gingerminds\LaravelCms\Models\Menu\MenuItem\MenuItemTranslation;
use Gingerminds\LaravelCms\Repositories\Menu\MenuItemRepository;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Http\Controllers\AbstractController;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuItemController extends AbstractController
{
    protected int $perPage = 100;

    public const string LABEL_S = 'gingerminds-cms::translation.menu_items.name_s';

    public function __construct(
        protected readonly MenuItemRepository $repository,
        protected readonly MenuItemRepository $menuItemRepository
    ) {
    }

    public function index(Request $request, Menu $menu): Factory|View
    {
        $this->authorize('viewAny', ResourceResolver::model('menu_item'));

        $rootItems = $menu->items()
            ->whereNull('parent_id')
            ->orderBy('position')
            ->with('adminChildren')
            ->get();

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.menu_items.index';

        return view($view, [
            'resource' => ResourceResolver::model('menu_item'),
            'rootItems' => $rootItems,
            'menu' => $menu,
            'filters' => $request->query('filters', []),
        ]);
    }

    public function create(Request $request, Menu $menu): View
    {
        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.menu_items.create';

        $site = app(SiteContext::class)->site();

        return view($view, [
            'menu' => $menu,
            'defaultLanguage' => $site?->defaultLanguage()->first(),
            'languages' => $site?->languages,
            'menuItems' => $this->menuItemRepository->get($request),
        ]);
    }

    public function edit(Request $request, Menu $menu, MenuItem $menuItem): View
    {
        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.menu_items.edit';

        $site = app(SiteContext::class)->site();

        return view($view, [
            'menuItem' => $menuItem,
            'defaultLanguage' => $site?->defaultLanguage()->first(),
            'languages' => $site?->languages,
            'menuItems' => $this->menuItemRepository->get($request),
        ]);
    }

    public function store(MenuItemRequest $request, Menu $menu): RedirectResponse
    {
        $this->authorize('create', ResourceResolver::model('menu_item'));

        /** @var MenuItem $menuItem */
        $menuItem = $this->repository->update($request, new MenuItem());

        /** @var MenuItemTranslation|null $translation */
        $translation = $menuItem->currentTranslation;

        return redirect()->route('gingerminds-cms.menu_items.index', [
            'menu' => $menu->id,
        ])
            ->with('success', __('gingerminds-core::translation.successfully_created', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($translation->name ?? $menuItem->id),
            ]));
    }

    public function update(MenuItemRequest $request, Menu $menu, MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('update', $menuItem);

        $this->repository->update($request, $menuItem);

        /** @var MenuItemTranslation|null $translation */
        $translation = $menuItem->currentTranslation;

        return redirect()->route('gingerminds-cms.menu_items.edit', [
            'menu' => $menu->id,
            'menuItem' => $menuItem->id,
        ])
            ->with('success', __('gingerminds-core::translation.successfully_updated', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($translation->name ?? $menuItem->id),
            ]));
    }

    public function reorder(MenuItemReorderRequest $request, Menu $menu): JsonResponse
    {
        $this->authorize('update', ResourceResolver::model('menu_item'));

        foreach ($request->input('ids') as $position => $id) {
            $menu->items()->where('id', $id)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Menu $menu, MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('delete', $menuItem);
        $menuItem->delete();

        /** @var MenuItemTranslation|null $translation */
        $translation = $menuItem->currentTranslation;

        return redirect()->route('gingerminds-cms.menu_items.index', [
            'menu' => $menu->id,
        ])
            ->with('success', __('gingerminds-core::translation.successfully_deleted', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($translation->name ?? $menuItem->id),
            ]));
    }
}
