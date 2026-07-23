<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Repositories\PageCategory;

use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCms\Services\Page\PageUrlSyncer;
use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Repositories\AbstractRepository;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * @extends AbstractRepository<PageCategory>
 * @implements RepositoryInterface<PageCategory>
 */
class PageCategoryRepository extends AbstractRepository implements RepositoryInterface
{
    public function __construct(
        protected readonly PageUrlSyncer $urlSyncer,
    ) {
    }

    public function getModelClass(): string
    {
        return ResourceResolver::model('page_category');
    }

    public function update(
        ?FormRequestInterface $request,
        ResourceModelInterface $resourceModel
    ): ResourceModelInterface {
        if (!$resourceModel instanceof PageCategory) {
            throw new InvalidArgumentException(
                'ResourceModelInterface must be an instance of ' . PageCategory::class
            );
        }

        if (!$request instanceof FormRequestInterface) {
            return $resourceModel;
        }

        $resourceModel->fill($request->except(['translations', 'is_unique', 'parent_id']));
        $resourceModel->site_id   = app(SiteContext::class)->site()?->id;
        $resourceModel->parent_id = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        $resourceModel->is_unique = $request->boolean('is_unique');
        $resourceModel->save();

        $resourceModel->syncTranslations(
            $this->withSiteId($request->input('translations', []), $resourceModel->site_id)
        );

        $this->urlSyncer->syncCategorySubtree($resourceModel);

        return $resourceModel;
    }

    /**
     * @return Collection<int, PageCategory>
     */
    public function getRootItems(): Collection
    {
        /** @var class-string<PageCategory> $modelClass */
        $modelClass = $this->getModelClass();

        // Bypasses AbstractRepository::get() (tree fetch, not a paginated
        // listing), so getEagerLoads() (`parentChain`) is merged in by hand
        // here — `adminChildren` itself also nests `parentChain` at every
        // level (see PageCategory::adminChildren()), so the whole tree is
        // covered, not just these root rows.
        return $modelClass::query()
            ->whereNull('parent_id')
            ->orderBy('code')
            ->with(array_merge(['adminChildren'], $modelClass::getEagerLoads()))
            ->get();
    }

    /**
     * @return list<array{category: PageCategory, depth: int}>
     */
    public function getAllForSelect(): array
    {
        $rootItems = $this->getRootItems();

        $flattened = [];
        $this->flatten($rootItems, 0, $flattened);

        return $flattened;
    }

    /**
     * @param  iterable<PageCategory>  $items
     * @param  list<array{category: PageCategory, depth: int}>  $flattened
     */
    private function flatten(iterable $items, int $depth, array &$flattened): void
    {
        foreach ($items as $item) {
            $flattened[] = ['category' => $item, 'depth' => $depth];
            $this->flatten($item->adminChildren, $depth + 1, $flattened);
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $translations
     * @return array<int|string, array<string, mixed>>
     */
    private function withSiteId(array $translations, ?int $siteId): array
    {
        foreach ($translations as $languageId => $fields) {
            $fields['site_id'] = $siteId;

            if (array_key_exists('prefix', $fields) && '' === $fields['prefix']) {
                $fields['prefix'] = null;
            }

            $translations[$languageId] = $fields;
        }

        return $translations;
    }
}
