<?php

namespace Gingerminds\LaravelCms\Models\Page;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\State\Page\StatusState;
use Gingerminds\LaravelCore\Models\FilterableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\SearchableModelInterface;
use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMultisite\Models\Site\SiteContextedModelTrait;
use Gingerminds\LaravelMultisite\Models\Trait\TranslatableModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Spatie\ModelStates\HasStates;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @property int<0, max>|null $site_id
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
#[ApiProperty(property: 'switch_lang', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
class Page extends Model implements ResourceModelInterface, FilterableModelInterface, SearchableModelInterface
{
    use HasStates;
    use SiteContextedModelTrait;
    use TranslatableModelTrait;

    protected string $translationModel = PageTranslation::class;

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
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->getMorphClass()::code();
    }

    public function getTitle(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->title;
    }

    public function getSlug(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->slug;
    }

    public function getHook(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->hook;
    }

    public function getContent(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->content;
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function mainVisual(): BelongsTo
    {
        return $this->belongsTo(File::class, 'main_visual_id');
    }

    public function getMainVisualFileAttribute(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        $fileId = null !== $translation?->main_visual_id
            ? $translation->mainVisual?->id
            : $this->getRelationValue('mainVisual')?->id;

        return null !== $fileId ? (string) $fileId : null;
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(File::class, 'thumbnail_id');
    }

    #[SerializedName('thumbnail')]
    public function getThumbnailFileAttribute(): ?string
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        $fileId = null !== $translation?->thumbnail_id
            ? $translation->thumbnail?->id
            : $this->getRelationValue('thumbnail')?->id;

        return null !== $fileId ? (string) $fileId : null;
    }

    /**
     * @return array<string, string|null>
     */
    public function getSwitchLangAttribute(): array
    {
        $slugs = [];

        /** @var Collection<int, PageTranslation> $translations */
        $translations = $this->translations;

        foreach ($translations as $translation) {
            $slugs[$translation->language->iso] = $translation->slug;
        }

        return $slugs;
    }

    public static function getFilters(): array
    {
        $statusChoices = [];

        foreach (StatusState::getStateMapping() as $state) {
            $statusChoices[$state::code()] = 'gingerminds-cms::translation.pages.statuses.' . $state::code();
        }

        return [
            'published_at' => [
                'type'  => 'date',
                'label' => 'gingerminds-cms::translation.form.published_at',
            ],
            'status' => [
                'type'     => 'select-state',
                'label'    => 'gingerminds-cms::translation.pages.form.status',
                'choices'  => $statusChoices,
                'multiple' => true,
            ],
        ];
    }

    public static function getSearchableFields(): array
    {
        return ['code', 'translations.title'];
    }
}
