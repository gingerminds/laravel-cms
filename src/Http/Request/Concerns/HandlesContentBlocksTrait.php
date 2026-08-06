<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Request\Concerns;

use Gingerminds\LaravelCms\Blocks\ContentFieldSupport;

trait HandlesContentBlocksTrait
{
    protected function contentFieldName(): ?string
    {
        return 'content';
    }

    /**
     * @return array<string, mixed>
     */
    protected function contentBlockRules(int|string $langId): array
    {
        $field = $this->contentFieldName();

        if ($field === null) {
            return [];
        }

        return ContentFieldSupport::rulesFor(
            "translations.$langId.$field",
            $this->input("translations.$langId.$field", [])
        );
    }

    /**
     * @return array<string, string>
     */
    protected function contentBlockAttributes(int|string $langId, string $languageLabel): array
    {
        $field = $this->contentFieldName();

        if ($field === null) {
            return [];
        }

        return ContentFieldSupport::attributesFor(
            "translations.$langId.$field",
            $this->input("translations.$langId.$field", []),
            $languageLabel
        );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $translations
     * @return array<int|string, array<string, mixed>>
     */
    protected function decodeTranslations(array $translations): array
    {
        $field = $this->contentFieldName();

        if ($field === null) {
            return $translations;
        }

        return ContentFieldSupport::decodeAndPrune($translations, $field);
    }
}
