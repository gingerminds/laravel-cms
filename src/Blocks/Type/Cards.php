<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Title + text, followed by a `repeater` of cards (title, description,
 * image) — the first block in this package to use the `repeater` field
 * type (docs/Blocks.md): a card's `image` is an exclusive `file` field of
 * its own row (BlockFileFieldSync), just like `TextImage::image`, uploaded/
 * replaced/pruned independently per card.
 */
class Cards extends AbstractBlock
{
    public function key(): string
    {
        return 'cards';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.cards.label');
    }

    public function icon(): string
    {
        return 'bi-grid-3x2-gap';
    }

    public function order(): int
    {
        return 300;
    }

    public function fields(): array
    {
        return [
            $this->textField('title', __('gingerminds-cms::translation.blocks.cards.fields.title'), required: true),
            $this->wysiwygField('text', __('gingerminds-cms::translation.blocks.cards.fields.text'), rows: 6),
            $this->repeaterField(
                'cards',
                __('gingerminds-cms::translation.blocks.cards.fields.cards'),
                [
                    $this->textField('title', __('gingerminds-cms::translation.blocks.cards.fields.card_title')),
                    $this->textareaField(
                        'description',
                        __('gingerminds-cms::translation.blocks.cards.fields.card_description'),
                    ),
                    $this->fileField(
                        'image',
                        __('gingerminds-cms::translation.blocks.cards.fields.card_image'),
                        required: true,
                    ),
                ],
                __('gingerminds-cms::translation.blocks.cards.fields.add_card'),
                __('gingerminds-cms::translation.blocks.cards.fields.card_item_label'),
            ),
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.cards.preview';
    }
}
