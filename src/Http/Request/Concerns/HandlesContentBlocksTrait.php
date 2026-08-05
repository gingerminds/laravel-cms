<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Concerns;

trait HandlesContentBlocksTrait
{
    protected function contentFieldName(): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function contentBlockRules(int|string $langId): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function contentBlockAttributes(int|string $langId, string $languageLabel): array
    {
        return [];
    }

    /**
     * Hook to decode/normalize the submitted translations before
     * validation runs (e.g. the JSON-encoded content blocks payload).
     *
     * @param  array<int|string, array<string, mixed>>  $translations
     * @return array<int|string, array<string, mixed>>
     */
    protected function decodeTranslations(array $translations): array
    {
        return $translations;
    }
}
