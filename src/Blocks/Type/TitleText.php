<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Reference block for the block contract (docs/ContentBlocks.md step 2):
 * a title + a rich text field, both schema-driven (no custom form Blade).
 */
class TitleText extends AbstractBlock
{
    public function key(): string
    {
        return 'title_text';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.title_text.label');
    }

    public function icon(): string
    {
        return 'bi-type';
    }

    public function order(): int
    {
        return 0;
    }

    public function fields(): array
    {
        return [
            $this->textField(
                'title',
                __('gingerminds-cms::translation.blocks.title_text.fields.title'),
                required: true,
            ),
            $this->wysiwygField('text', __('gingerminds-cms::translation.blocks.title_text.fields.text')),
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.title-text.preview';
    }
}
