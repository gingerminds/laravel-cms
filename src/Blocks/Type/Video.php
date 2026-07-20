<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Reference block for the block contract (docs/ContentBlocks.md step 2):
 * a title + video field, both schema-driven (no custom form Blade).
 */
class Video extends AbstractBlock
{
    public function key(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.video.label');
    }

    public function icon(): string
    {
        return 'bi-play-circle';
    }

    public function order(): int
    {
        return 200;
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'title',
                'type' => 'text',
                'label' => __('gingerminds-cms::translation.blocks.title_text.fields.title'),
                'required' => false,
                'size' => 'md',
            ],
            [
                'name' => 'embed_code',
                'type' => 'text',
                'label' => __('gingerminds-cms::translation.blocks.video.fields.embed_code'),
                'required' => true,
                'size' => 'md',
            ],
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.video.preview';
    }
}
