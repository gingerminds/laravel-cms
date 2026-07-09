<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Page;

use Gingerminds\LaravelMultisite\Models\Language\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $page_id
 * @property int $language_id
 * @property int $site_id
 * @property string $path
 */
class PageUrl extends Model
{
    protected $fillable = [
        'page_id',
        'language_id',
        'site_id',
        'path',
    ];

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
