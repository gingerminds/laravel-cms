<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Search;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelCms\ApiProvider\Search\SearchProvider;
use Gingerminds\LaravelCms\ApiProvider\Search\SearchResultProvider;
use Gingerminds\LaravelCore\Models\FilterableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\SearchableModelInterface;
use Gingerminds\LaravelMultisite\Models\Site\SiteContextedModelTrait;
use Gingerminds\LaravelMultisite\Services\Context\LanguageContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property int $id
 * @property string $type
 * @property int $record_id
 * @property int<0, max>|null $site_id
 * @property int $language_id
 * @property string $title
 * @property string $url
 * @property string|null $excerpt
 * @property string $searchable_text
 * @property Carbon|null $published_at
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/search-indices',
            normalizationContext: ['groups' => [SearchIndex::GROUP_LIST]],
        ),
        new GetCollection(
            uriTemplate: '/search-results',
            provider: SearchResultProvider::class,
        ),
    ],
    provider: SearchProvider::class,
)]
#[ApiProperty(identifier: true, property: 'id', serialize: new Groups([SearchIndex::GROUP_LIST]))]
#[ApiProperty(property: 'type', serialize: new Groups([SearchIndex::GROUP_LIST]))]
#[ApiProperty(property: 'title', serialize: new Groups([SearchIndex::GROUP_LIST]))]
#[ApiProperty(property: 'url', serialize: new Groups([SearchIndex::GROUP_LIST]))]
#[ApiProperty(property: 'excerpt', serialize: new Groups([SearchIndex::GROUP_LIST]))]
#[ApiProperty(property: 'published_at', serialize: new Groups([SearchIndex::GROUP_LIST]))]
class SearchIndex extends Model implements
    ResourceModelInterface,
    FilterableModelInterface,
    SearchableModelInterface
{
    use SiteContextedModelTrait;

    public const string GROUP_LIST = 'search:list';

    protected $table = 'search_index';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('language', function (Builder $builder) {
            $context  = app(LanguageContext::class);
            $language = $context->current() ?? $context->fallback();

            if (null !== $language) {
                $builder->where($builder->getModel()->getTable() . '.language_id', $language->id);
            }
        });
    }

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'type',
            'record_id',
            'site_id',
            'language_id',
            'title',
            'url',
            'excerpt',
            'searchable_text',
            'published_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getSearchableFields(): array
    {
        return ['searchable_text'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getFilters(): array
    {
        return [
            'type' => ['type' => 'facet', 'multiple' => true],
        ];
    }
}
