<?php

namespace Gingerminds\LaravelCms\Models\PageCategory;

use ApiPlatform\Metadata\ApiResource;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
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
class PageCategory extends Model implements ResourceModelInterface
{
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
     *
     * @return HasMany<PageCategory, $this>
     */
    public function adminChildren(): HasMany
    {
        return $this
            ->hasMany(PageCategory::class, 'parent_id')
            ->orderBy('code')
            ->with('adminChildren');
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

            $category = $category->parent;
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
