<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Standalone "Média (seul)" block: a single exclusive file upload
 * (BlockFileFieldSync, docs/Blocks.md), no text field, no companion — think
 * of it as `TextImage` reduced to just its `image` field. Unlike that
 * field, this one isn't restricted to images (`mimes` explicitly set to
 * `null`, see `BlockFieldValidator::fileRules()`): any file type is
 * accepted, so it can carry a PDF, a video, etc.
 */
class Media extends AbstractBlock
{
    public function key(): string
    {
        return 'media';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.media.label');
    }

    public function icon(): string
    {
        return 'bi-file-earmark-richtext';
    }

    public function order(): int
    {
        return 150;
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'title',
                'type' => 'text',
                'label' => __('gingerminds-cms::translation.blocks.title_text.fields.title'),
                'required' => true,
                'size' => 'md',
            ],
            [
                'name' => 'file',
                'type' => 'file',
                'label' => __('gingerminds-cms::translation.blocks.media.fields.file'),
                'required' => true,
                'size' => 'xl',
                'mimes' => null,
            ],
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.media.preview';
    }
}
