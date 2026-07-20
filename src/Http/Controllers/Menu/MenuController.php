<?php

namespace Gingerminds\LaravelCms\Http\Controllers\Menu;

use Gingerminds\LaravelCms\Http\Request\Menu\MenuRequest;
use Gingerminds\LaravelCms\Models\Menu\Menu;
use Gingerminds\LaravelCms\Repositories\Menu\MenuRepository;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Http\Controllers\AbstractController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuController extends AbstractController
{
    public const string LABEL_S = 'gingerminds-cms::translation.menus.name_s';

    public function __construct(
        protected readonly MenuRepository $repository
    ) {
    }

    public function index(Request $request): Factory|View
    {
        $this->authorize('viewAny', ResourceResolver::model('menu'));

        $items = $this->repository->get($request);

        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.menus.index';

        return view($view, [
            'resource' => ResourceResolver::model('menu'),
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.menus.create';

        return view($view);
    }

    public function edit(Menu $menu): View
    {
        /** @var view-string $view */
        $view = 'gingerminds-cms::pages.menus.edit';

        return view($view, [
            'menu' => $menu,
        ]);
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        $this->authorize('create', ResourceResolver::model('menu'));

        /** @var Menu $menu */
        $menu = $this->repository->update($request, new Menu());

        return redirect()->route('gingerminds-cms.menus.index')
            ->with('success', __('gingerminds-core::translation.successfully_created', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($menu->code ?? $menu->id),
            ]));
    }

    public function update(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $this->authorize('update', $menu);

        $this->repository->update($request, $menu);

        return redirect()->route('gingerminds-cms.menus.edit', $menu->id)
            ->with('success', __('gingerminds-core::translation.successfully_updated', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($menu->code ?? $menu->id),
            ]));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $this->authorize('delete', $menu);
        $menu->delete();

        return redirect()->route('gingerminds-cms.menus.index')
            ->with('success', __('gingerminds-core::translation.successfully_deleted', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($menu->code ?? $menu->id),
            ]));
    }
}
