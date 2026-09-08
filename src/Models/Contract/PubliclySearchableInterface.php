<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Models\Contract;

interface PubliclySearchableInterface
{
    /**
     * @return iterable<array{
     *     language_id: int,
     *     title: string,
     *     url: string,
     *     excerpt: string|null,
     *     searchable_text: string,
     * }>
     */
    public function getPublicSearchTranslations(): iterable;

    public function isPubliclyVisible(): bool;
}
