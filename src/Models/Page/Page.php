<?php

namespace Gingerminds\LaravelCms\Models\Page;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\State\Page\StatusState;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMultisite\Models\Site\SiteContextedModelTrait;
use Gingerminds\LaravelMultisite\Models\Trait\TranslatableModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @property int<0, max>|null $site_id
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
#[ApiProperty(property: 'status_label', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'main_visual', serialize: new Groups([
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'thumbnail', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
#[ApiProperty(property: 'switch_lang', serialize: new Groups([
    Page::GROUP_LIST,
    Page::GROUP_READ,
]))]
class Page extends Model implements ResourceModelInterface
{
    use SiteContextedModelTrait;
    use TranslatableModelTrait;

    protected string $translationModel = PageTranslation::class;

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
            'site_id',
        ];
    }

    /**
     * @return string[]
     */
    public function getCasts(): array
    {
        return [
            'status' => StatusState::class,
        ];
    }

    #[SerializedName('status')]
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label;
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

    #[SerializedName('main_visual')]
    public function getMainVisualAttribute(): ?File
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        if (null !== $translation?->main_visual_id) {
            return $translation->mainVisual;
        }

        return $this->mainVisual;
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(File::class, 'thumbnail_id');
    }

    #[SerializedName('thumbnail')]
    public function getThumbnailAttribute(): ?File
    {
        /** @var PageTranslation|null $translation */
        $translation = $this->currentTranslation;

        if (null !== $translation?->thumbnail_id) {
            return $translation->thumbnail;
        }

        return $this->thumbnail;
    }

    /**
     * @return string[]
     */
    public function getSwitchLangAttribute(): array
    {
        $slugs = [];

        foreach ($this->translations as $translation) {
            $slugs[] = $translation->slug;
        }

        return $slugs;
    }
}
