<?php

namespace Gingerminds\LaravelCms\Models\Menu;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelCms\ApiProvider\Menu\MenuProvider;
use Gingerminds\LaravelCms\Models\Menu\MenuItem\MenuItem;
use Gingerminds\LaravelCore\Models\CacheableResourceInterface;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\Trait\CacheableResourceTrait;
use Gingerminds\LaravelCore\Models\Trait\EagerLoadableModelTrait;
use Gingerminds\LaravelMultisite\Models\Site\SiteContextedModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property int<0, max>|null $site_id
 */
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => [Menu::GROUP_LIST]],
        ),
        new Get(
            normalizationContext: ['groups' => [Menu::GROUP_READ]],
        ),
    ],
    provider: MenuProvider::class,
)]
#[ApiProperty(
    identifier: true,
    property: 'id',
    serialize: new Groups([
        Menu::GROUP_LIST,
        Menu::GROUP_READ,
    ])
)]
#[ApiProperty(property: 'code', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'active_items', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
class Menu extends Model implements
    ResourceModelInterface,
    EagerLoadableModelInterface,
    CacheableResourceInterface
{
    use CacheableResourceTrait;
    use EagerLoadableModelTrait;
    use SiteContextedModelTrait;

    public const string GROUP_LIST = 'menus:list';
    public const string GROUP_READ = 'menus:read';

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'code',
            'site_id',
        ];
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function activeItems(): HasMany
    {
        return $this
            ->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('position')
            ->with('children');
    }

    /**
     * `active_items` is serialized in both GROUP_LIST and GROUP_READ but was
     * never eager-loaded: every Menu row in a listing triggered its own
     * activeItems query, and each MenuItem inside triggered its own
     * recursive children query on top. Declaring it here fixes that N+1
     * whether or not caching is even active.
     *
     * @return array<int, string>
     */
    public static function getEagerLoads(): array
    {
        return ['activeItems', 'activeItems.children'];
    }

    public static function getCacheKey(): string
    {
        return 'menu';
    }

    /**
     * Barely changes — 24h instead of the default 1h
     * (config('cache.resource_ttl_seconds')).
     */
    public static function getCacheTtlSeconds(): ?int
    {
        return 86400;
    }
}
