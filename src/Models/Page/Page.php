<?php

namespace Gingerminds\LaravelCms\Models\Page;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Models\Trait\HasMainVisualAndThumbnailTrait;
use Gingerminds\LaravelCms\Models\Trait\HasResolvedContentTrait;
use Gingerminds\LaravelCms\Models\Trait\HasStatusLabelTrait;
use Gingerminds\LaravelCms\State\Page\StatusState;
use Gingerminds\LaravelCore\Models\CacheableResourceInterface;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelCore\Models\FilterableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\SearchableModelInterface;
use Gingerminds\LaravelCore\Models\Trait\CacheableResourceTrait;
use Gingerminds\LaravelCore\Models\Trait\EagerLoadableModelTrait;
use Gingerminds\LaravelMultisite\Models\Site\SiteContextedModelTrait;
use Gingerminds\LaravelMultisite\Models\Trait\TranslatableModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Spatie\ModelStates\HasStates;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @property int<0, max>|null $site_id
 * @property int<0, max>|null $category_id
 * @property StatusState $status
 */
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => [Page::GROUP_LIST]],
        ),
        new Get(
            normalizationContext: ['groups' => [Page::GROUP_READ]],
        ),
        new Get(
            uriTemplate: '/pages/by-slug/{slug}',
            uriVariables: ['slug' => new Link(fromClass: Page::class, identifiers: ['fullPath'])],
            requirements: ['slug' => '.+'],
            normalizationContext: ['groups' => [Page::GROUP_READ]],
            provider: PageProvider::class,
        ),
        new Get(
            uriTemplate: '/pages/by-code/{code}',
            uriVariables: ['code' => new Link(fromClass: Page::class, identifiers: ['code'])],
            normalizationContext: ['groups' => [Page::GROUP_READ]],
            provider: PageProvider::class,
        ),
    ],
    provider: PageProvider::class,
)]
#[ApiProperty(
    identifier: true,
    property: 'id',
    serialize: new Groups([
        Page::GROUP_LIST,
        Page::GROUP_READ,
    ])
)]
#[ApiProperty(property: 'code', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'title', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'hook', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'content', serialize: new Groups([
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'slug', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'status_label', serialize: [
    new Groups([
        Page::GROUP_LIST,
        Page::GROUP_READ,
    ]),
    new SerializedName('status'),
])]
#[ApiProperty(property: 'main_visual_file', serialize: [
    new Groups([
        Page::GROUP_READ,
    ]),
    new SerializedName('main_visual'),
])]
#[ApiProperty(property: 'thumbnail_file', serialize: [
    new Groups([
        Page::GROUP_LIST,
        Page::GROUP_READ,
    ]),
    new SerializedName('thumbnail'),
])]
#[ApiProperty(
    property: 'switch_lang',
    serialize: new Groups([
        Page::GROUP_LIST,
        Page::GROUP_READ,
    ]),
    nativeType: new CollectionType(
        new GenericType(
            new BuiltinType(TypeIdentifier::ARRAY),
            new BuiltinType(TypeIdentifier::STRING),
            new BuiltinType(TypeIdentifier::STRING),
        ),
        false,
    ),
)]
class Page extends Model implements
    ResourceModelInterface,
    FilterableModelInterface,
    SearchableModelInterface,
    EagerLoadableModelInterface,
    CacheableResourceInterface
{
    use CacheableResourceTrait;
    use EagerLoadableModelTrait;
    use HasMainVisualAndThumbnailTrait;
    use HasResolvedContentTrait;
    use HasStates;
    use HasStatusLabelTrait;
    use SiteContextedModelTrait;
    use TranslatableModelTrait;

    protected string $translationModel = PageTranslation::class;

    /**
     * `main_visual_file`/`thumbnail_file` (HasMainVisualAndThumbnailTrait) and
     * `category` (walked all the way to the root for `full_path`/`switch_lang`
     * via `PageCategory::parentChain()`) all lazy-load per row otherwise.
     *
     * @return array<int, string>
     */
    public static function getEagerLoads(): array
    {
        return ['mainVisual', 'thumbnail', 'category.parentChain'];
    }

    public static function getCacheKey(): string
    {
        return 'page';
    }

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => StatusState::class,
    ];

    public const string GROUP_LIST = 'pages:list';
    public const string GROUP_READ = 'pages:read';

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'code',
            'status',
            'main_visual_id',
            'thumbnail_id',
            'published_at',
            'archived_at',
            'site_id',
            'category_id',
        ];
    }

    /**
     * @return BelongsTo<PageCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PageCategory::class, 'category_id');
    }

    public function getTitleAttribute(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->title;
    }

    public function getSlugAttribute(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->slug;
    }

    public function getFullPath(): string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        $categoryPath = $this->category?->getFullPathForLanguage($translation?->language_id) ?? '';
        $slug         = $translation?->slug                                                  ?? ''; // @phpstan-ignore nullsafe.neverNull

        return self::composePath($categoryPath, $slug);
    }

    public static function composePath(string $categoryPath, string $slug): string
    {
        return match (true) {
            '' === $categoryPath => $slug,
            '' === $slug => $categoryPath,
            default => $categoryPath . '/' . $slug,
        };
    }

    public function getHookAttribute(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->hook;
    }

    /**
     * @return array<string, string>
     */
    public function getSwitchLangAttribute(): array
    {
        $paths = [];

        /** @var Collection<int, PageTranslation> $translations */
        $translations = $this->translations;

        foreach ($translations as $translation) {
            if (null === $translation->title || '' === $translation->title) {
                continue;
            }

            $categoryPath = $this->category?->getFullPathForLanguage($translation->language_id) ?? '';
            $path         = self::composePath($categoryPath, $translation->slug ?? '');

            $paths[$translation->language->iso] = $path;
        }

        return $paths;
    }

    public static function getFilters(): array
    {
        $statusChoices = [];

        foreach (StatusState::getStateMapping() as $state) {
            $statusChoices[$state::code()] = 'gingerminds-cms::translation.pages.statuses.' . $state::code();
        }

        return [
            'published_at' => [
                'type' => 'date',
                'label' => 'gingerminds-cms::translation.form.published_at',
            ],
            'status' => [
                'type' => 'select-state',
                'label' => 'gingerminds-cms::translation.pages.form.status',
                'choices' => $statusChoices,
                'multiple' => true,
            ],
            'category_id' => [
                'type' => 'select-model',
                'label' => 'gingerminds-cms::translation.pages.form.category',
                'model' => PageCategory::class,
            ],
        ];
    }

    public static function getSearchableFields(): array
    {
        return ['code', 'translations.title'];
    }
}
