<?php

namespace Gingerminds\LaravelCms\Models\Menu\MenuItem;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Gingerminds\LaravelCms\Models\Menu\Menu;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\SortableModelInterface;
use Gingerminds\LaravelMultisite\Models\Site\SiteContextedModelTrait;
use Gingerminds\LaravelMultisite\Models\Trait\TranslatableModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property int<0, max>|null $site_id
 * @property int|null $parent_id
 * @property MenuItemTranslation|null $currentTranslation
 */
#[ApiResource(
    operations: [],
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
#[ApiProperty(property: 'name', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'url', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'description', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'children', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'is_active', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'is_target_blank', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'is_no_referrer', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'is_no_opener', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'is_no_follow', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
#[ApiProperty(property: 'position', serialize: new Groups([
    Menu::GROUP_LIST,
    Menu::GROUP_READ,
]))]
class MenuItem extends Model implements ResourceModelInterface, SortableModelInterface
{
    use TranslatableModelTrait;
    use SiteContextedModelTrait;

    protected string $translationModel = MenuItemTranslation::class;

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'code',
            'menu_id',
            'site_id',
            'parent_id',
            'is_active',
            'is_target_blank',
            'is_no_referrer',
            'is_no_opener',
            'is_no_follow',
            'position',
        ];
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this
            ->hasMany(MenuItem::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('position')
            ->with('children');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function adminChildren(): HasMany
    {
        return $this
            ->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('position')
            ->with('adminChildren');
    }

    public function getNameAttribute(): string
    {
        return $this->currentTranslation?->name ?? ''; // @phpstan-ignore nullsafe.neverNull
    }

    public function getUrlAttribute(): string
    {
        return $this->currentTranslation?->url ?? ''; // @phpstan-ignore nullsafe.neverNull
    }

    public function getDescriptionAttribute(): string
    {
        return $this->currentTranslation?->description ?? ''; // @phpstan-ignore nullsafe.neverNull
    }
}
