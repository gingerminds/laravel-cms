<?php

namespace Gingerminds\LaravelCms\Models\PageCategory;

use ApiPlatform\Metadata\ApiResource;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCore\Models\CacheableResourceInterface;
use Gingerminds\LaravelCore\Models\CacheCascadeInterface;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\Trait\CacheableResourceTrait;
use Gingerminds\LaravelCore\Models\Trait\EagerLoadableModelTrait;
use Gingerminds\LaravelMultisite\Models\Site\SiteContextedModelTrait;
use Gingerminds\LaravelMultisite\Models\Trait\TranslatableModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 * @property int<0, max>|null $site_id
 * @property int|null $parent_id
 * @property bool $is_unique
 * @property-read PageCategoryTranslation|null $currentTranslation
 */
#[ApiResource(
    operations: [],
)]
class PageCategory extends Model implements
    ResourceModelInterface,
    EagerLoadableModelInterface,
    CacheableResourceInterface,
    CacheCascadeInterface
{
    use CacheableResourceTrait;
    use EagerLoadableModelTrait;
    use SiteContextedModelTrait;
    use TranslatableModelTrait;

    protected string $translationModel = PageCategoryTranslation::class;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_unique' => 'boolean',
    ];

    /**
     * `full_path`/`prefix` walk the parent chain to the root — without this,
     * every level up is a fresh lazy-loaded query (or a crash outright, with
     * `Model::preventLazyLoading()` on).
     *
     * @return array<int, string>
     */
    public static function getEagerLoads(): array
    {
        return ['parentChain'];
    }

    public static function getCacheKey(): string
    {
        return 'page_category';
    }

    /**
     * A category's own `code`/`name`/`prefix` feed `Page::getFullPath()` and
     * `getSwitchLangAttribute()`, both part of a cached Page's own
     * representation, so a category save must also invalidate `page`.
     *
     * @return array<int, string>
     */
    public static function getCascadeCacheKeys(): array
    {
        return ['page'];
    }

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'code',
            'parent_id',
            'site_id',
            'is_unique',
        ];
    }

    /**
     * @return BelongsTo<PageCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PageCategory::class, 'parent_id');
    }

    /**
     * Recursive eager-load for `getFullPathForLanguage()`'s upward walk —
     * same self-referential `->with()` trick as `adminChildren()`, just
     * walking `parent_id` instead of walking children. Terminates on its own
     * once a row with a null `parent_id` is reached (root), the same way
     * `adminChildren` terminates once a row has no children.
     *
     * @return BelongsTo<PageCategory, $this>
     */
    public function parentChain(): BelongsTo
    {
        return $this->belongsTo(PageCategory::class, 'parent_id')->with('parentChain');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'category_id');
    }

    /**
     * @return HasMany<PageCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(PageCategory::class, 'parent_id')->orderBy('code');
    }

    /**
     * Recursive eager-load for admin tree rendering (mirrors
     * `MenuItem::adminChildren()` / `MediaCategory::adminChildren()`).
     * Also nests `parentChain` at every level — `full_path`/`name`/`prefix`
     * can be read on any node while walking this tree (e.g. the category
     * select list), and each one needs its own upward walk, not just the
     * tree's root.
     *
     * @return HasMany<PageCategory, $this>
     */
    public function adminChildren(): HasMany
    {
        return $this
            ->hasMany(PageCategory::class, 'parent_id')
            ->orderBy('code')
            ->with(['adminChildren', 'parentChain']);
    }

    public function getNameAttribute(): ?string
    {
        return $this->currentTranslation?->name;
    }

    public function getPrefixAttribute(): ?string
    {
        return $this->currentTranslation?->prefix;
    }

    public function getFullPathAttribute(): string
    {
        return $this->getFullPathForLanguage();
    }

    public function getFullPathForLanguage(?int $languageId = null): string
    {
        $segments = [];
        $category = $this;

        while ($category instanceof self) {
            $prefix = $category->resolvePrefixForLanguage($languageId);

            if (null !== $prefix && '' !== $prefix) {
                $segments[] = $prefix;
            }

            // `parentChain`, not `parent`: pre-loaded recursively (see
            // Page::getEagerLoads()/PageCategory::getEagerLoads()) so walking
            // to the root never triggers a lazy load, however deep the tree.
            $category = $category->parentChain;
        }

        return implode('/', array_reverse($segments));
    }

    private function resolvePrefixForLanguage(?int $languageId): ?string
    {
        if (null === $languageId) {
            return $this->currentTranslation?->prefix;
        }

        /** @var PageCategoryTranslation|null $translationForLanguage */
        $translationForLanguage = $this->translation($languageId, fallback: false);
        $prefix                 = $translationForLanguage?->prefix;

        if (null !== $prefix && '' !== $prefix) {
            return $prefix;
        }

        return $this->currentTranslation?->prefix;
    }
}
